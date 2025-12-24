<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Branch;
use App\Models\BranchReview;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BranchReviewController extends Controller
{
    public function __construct(
        private BranchReview $branchReview,
        private Branch       $branch
    ) {}

    /**
     * Get all reviews for a specific branch
     *
     * @param Request $request
     * @param int $branchId
     * @return JsonResponse
     */
    public function getReviews(Request $request, int $branchId): JsonResponse
    {
        $reviews = $this->branchReview
            ->with(['customer', 'branch', 'order'])
            ->where('branch_id', $branchId)
            ->active()
            ->latest()
            ->paginate($request->input('limit', 10), ['*'], 'page', $request->input('offset', 1));

        foreach ($reviews as $review) {
            $review->attachment = $review->attachment ?? [];
        }

        // Total number of reviews
        $totalReview = $this->branchReview
            ->where('branch_id', $branchId)
            ->active()
            ->count();

        // Average rating
        $averageRating = $this->branchReview
            ->where('branch_id', $branchId)
            ->active()
            ->avg('rating');
        $averageRating = round($averageRating, 2);

        // Count of each rating type
        $ratingGroupCount = $this->branchReview
            ->where('branch_id', $branchId)
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
     * Get overall rating for a specific branch
     *
     * @param int $branchId
     * @return JsonResponse
     */
    public function getRating(int $branchId): JsonResponse
    {
        try {
            $totalReviews = $this->branchReview
                ->where('branch_id', $branchId)
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
     * Submit a review for a branch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitReview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'order_id' => 'nullable|exists:orders,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'attachment.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Check if branch exists and is active
        $branch = $this->branch->find($request->branch_id);
        if (!$branch || $branch->status != 1) {
            return response()->json(['errors' => [['code' => 'branch', 'message' => translate('Branch not found or inactive')]]], 404);
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

        // Check if user already reviewed this branch for this order
        $existingReview = $this->branchReview
            ->where('user_id', $request->user()->id)
            ->where('branch_id', $request->branch_id);

        if ($request->order_id) {
            $existingReview->where('order_id', $request->order_id);
        }

        $existingReview = $existingReview->first();

        if ($existingReview) {
            // Update existing review
            $existingReview->rating = $request->rating;
            $existingReview->comment = $request->comment;
            if (!empty($imageArray)) {
                $existingReview->attachment = $imageArray;
            }
            $existingReview->save();

            return response()->json(['message' => translate('Review updated successfully')], 200);
        }

        // Create new review
        $review = $this->branchReview->create([
            'user_id' => $request->user()->id,
            'branch_id' => $request->branch_id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'attachment' => $imageArray,
        ]);

        return response()->json(['message' => translate('Review submitted successfully'), 'review' => $review], 201);
    }

    /**
     * Get user's own reviews for branches
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyReviews(Request $request): JsonResponse
    {
        $reviews = $this->branchReview
            ->with(['branch', 'order'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->input('limit', 10), ['*'], 'page', $request->input('offset', 1));

        foreach ($reviews as $review) {
            $review->attachment = $review->attachment ?? [];
        }

        return response()->json([
            'total_size' => $reviews->total(),
            'limit' => $request->input('limit', 10),
            'offset' => $request->input('offset', 1),
            'reviews' => $reviews->items(),
        ], 200);
    }

    /**
     * Delete a branch review
     *
     * @param Request $request
     * @param int $reviewId
     * @return JsonResponse
     */
    public function deleteReview(Request $request, int $reviewId): JsonResponse
    {
        $review = $this->branchReview
            ->where('id', $reviewId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$review) {
            return response()->json(['errors' => [['code' => 'review', 'message' => translate('Review not found')]]], 404);
        }

        $review->delete();

        return response()->json(['message' => translate('Review deleted successfully')], 200);
    }
}