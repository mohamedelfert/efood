<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Models\ServiceReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceReviewController extends Controller
{
    public function __construct(
        private ServiceReview $serviceReview,
        private Order         $order
    ) {}

    /**
     * Get all reviews for a specific service type
     *
     * @param Request $request
     * @param string $serviceType
     * @return JsonResponse
     */
    public function getReviews(Request $request, string $serviceType): JsonResponse
    {
        $reviews = $this->serviceReview
            ->with(['customer', 'order'])
            ->where('service_type', $serviceType)
            ->active()
            ->latest()
            ->paginate($request->input('limit', 10), ['*'], 'page', $request->input('offset', 1));

        foreach ($reviews as $review) {
            $review->attachment = $review->attachment ?? [];
            $review->service_ratings = $review->service_ratings ?? [];
        }

        // Total number of reviews
        $totalReview = $this->serviceReview
            ->where('service_type', $serviceType)
            ->active()
            ->count();

        // Average rating
        $averageRating = $this->serviceReview
            ->where('service_type', $serviceType)
            ->active()
            ->avg('rating');
        $averageRating = round($averageRating, 2);

        // Count of each rating type
        $ratingGroupCount = $this->serviceReview
            ->where('service_type', $serviceType)
            ->active()
            ->select('rating', DB::raw('count(rating) as total'))
            ->groupBy('rating')
            ->get();

        $ratingInfo = [
            'total_review' => $totalReview,
            'average_rating' => $averageRating,
            'rating_group_count' => $ratingGroupCount,
        ];

        $data = [
            'total_size' => $reviews->total(),
            'limit' => $request->input('limit', 10),
            'offset' => $request->input('offset', 1),
            'reviews' => $reviews->items(),
        ];

        return response()->json(['rating_info' => $ratingInfo, 'reviews' => $data], 200);
    }

    /**
     * Get overall rating for a specific service type
     *
     * @param string $serviceType
     * @return JsonResponse
     */
    public function getRating(string $serviceType): JsonResponse
    {
        try {
            $totalReviews = $this->serviceReview
                ->where('service_type', $serviceType)
                ->active()
                ->get();

            $rating = 0;
            foreach ($totalReviews as $review) {
                $rating += $review->rating;
            }

            if ($rating == 0 || $totalReviews->count() == 0) {
                $overallRating = 0;
            } else {
                $overallRating = number_format($rating / $totalReviews->count(), 2);
            }

            return response()->json([
                'rating' => floatval($overallRating),
                'total_reviews' => $totalReviews->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['errors' => ['message' => $e->getMessage()]], 403);
        }
    }

    /**
     * Submit a service review
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitReview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|in:delivery,dine_in,takeaway',
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'service_ratings' => 'nullable|array',
            'service_ratings.*.aspect' => 'required_with:service_ratings|string',
            'service_ratings.*.rating' => 'required_with:service_ratings|numeric|min:1|max:5',
            'attachment.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Verify order belongs to user
        $order = $this->order
            ->where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json(['errors' => [['code' => 'order', 'message' => translate('Order not found or does not belong to you')]]], 404);
        }

        // Handle image uploads
        $imageArray = [];
        if ($request->hasFile('attachment')) {
            foreach ($request->file('attachment') as $image) {
                $imageName = Helpers::upload('review/', 'png', $image);
                if ($imageName) {
                    $imageArray[] = $imageName;
                }
            }
        }

        // Check if user already reviewed this service for this order
        $existingReview = $this->serviceReview
            ->where('user_id', $request->user()->id)
            ->where('order_id', $request->order_id)
            ->where('service_type', $request->service_type)
            ->first();

        if ($existingReview) {
            // Update existing review
            $existingReview->rating = $request->rating;
            $existingReview->comment = $request->comment;
            if (!empty($imageArray)) {
                $existingReview->attachment = $imageArray;
            }
            $existingReview->service_ratings = $request->service_ratings;
            $existingReview->save();

            return response()->json(['message' => translate('Service review updated successfully')], 200);
        }

        // Create new review
        $review = $this->serviceReview->create([
            'user_id' => $request->user()->id,
            'service_type' => $request->service_type,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'attachment' => $imageArray,
            'service_ratings' => $request->service_ratings,
        ]);

        return response()->json(['message' => translate('Service review submitted successfully'), 'review' => $review], 201);
    }

    /**
     * Get user's own service reviews
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyReviews(Request $request): JsonResponse
    {
        $reviews = $this->serviceReview
            ->with(['order'])
            ->where('user_id', $request->user()->id)
            ->when($request->service_type, function ($query) use ($request) {
                return $query->where('service_type', $request->service_type);
            })
            ->latest()
            ->paginate($request->input('limit', 10), ['*'], 'page', $request->input('offset', 1));

        foreach ($reviews as $review) {
            $review->attachment = $review->attachment ?? [];
            $review->service_ratings = $review->service_ratings ?? [];
        }

        return response()->json([
            'total_size' => $reviews->total(),
            'limit' => $request->input('limit', 10),
            'offset' => $request->input('offset', 1),
            'reviews' => $reviews->items(),
        ], 200);
    }

    /**
     * Delete a service review
     *
     * @param Request $request
     * @param int $reviewId
     * @return JsonResponse
     */
    public function deleteReview(Request $request, int $reviewId): JsonResponse
    {
        $review = $this->serviceReview
            ->where('id', $reviewId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$review) {
            return response()->json(['errors' => [['code' => 'review', 'message' => translate('Review not found')]]], 404);
        }

        $review->delete();

        return response()->json(['message' => translate('Service review deleted successfully')], 200);
    }

    /**
     * Get service statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getServiceStatistics(Request $request): JsonResponse
    {
        $serviceTypes = ['delivery', 'dine_in', 'takeaway'];
        $statistics = [];

        foreach ($serviceTypes as $serviceType) {
            $reviews = $this->serviceReview
                ->where('service_type', $serviceType)
                ->active()
                ->get();

            $totalReviews = $reviews->count();
            $averageRating = $reviews->avg('rating');

            $statistics[$serviceType] = [
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 2),
            ];
        }

        return response()->json(['statistics' => $statistics], 200);
    }
}