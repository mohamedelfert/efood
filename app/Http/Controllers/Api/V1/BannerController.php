<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Model\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Get banners for a specific branch or global banners
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getBanners(Request $request): JsonResponse
    {
        $branchId = $request->header('branch-id') ?? $request->branch_id;
        
        $banners = Banner::active()
            ->with(['product', 'category', 'products', 'branches'])
            ->when($branchId, function ($query) use ($branchId) {
                // Get banners that are either global or assigned to this branch
                $query->where(function($q) use ($branchId) {
                    $q->where('is_global', true)
                      ->orWhereHas('branches', function($query) use ($branchId) {
                          $query->where('branch_id', $branchId);
                      });
                });
            }, function ($query) {
                // If no branch specified, only return global banners
                $query->where('is_global', true);
            })
            ->whereHas('product', function($q) {
                $q->where('status', 1);
            }, '>=', 0)
            ->get();

        // Filter active offers
        $activeBanners = $banners->filter(function ($banner) {
            return $banner->isOfferActive();
        })->map(function ($banner) use ($branchId) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'image' => $banner->imageFullPath,
                'banner_type' => $banner->banner_type,
                'is_global' => $banner->is_global,
                'branches' => $banner->is_global ? [] : $banner->branches->map(function($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name
                    ];
                }),
                'product' => $banner->banner_type === 'single_product' && $banner->product ? [
                    'id' => $banner->product->id,
                    'name' => $banner->product->name,
                    'image' => $banner->product->imageFullPath ?? [],
                    'price' => (float) $banner->product->price,
                ] : null,
                'products_count' => $banner->banner_type === 'multiple_products' ? $banner->products->count() : 0,
                'category' => $banner->banner_type === 'category' && $banner->category ? [
                    'id' => $banner->category->id,
                    'name' => $banner->category->name,
                    'image' => $banner->category->imageFullPath,
                ] : null,
                'pricing' => $banner->banner_type !== 'category' ? [
                    'original_price' => (float) $banner->calculateOriginalPrice(),
                    'final_price' => (float) $banner->calculateFinalPrice(),
                    'discount_amount' => (float) $banner->getDiscountAmount(),
                    'discount_percentage' => (float) $banner->getDiscountPercentage(),
                    'discount_type' => $banner->discount_type,
                ] : null,
                'dates' => [
                    'start_date' => $banner->start_date ? $banner->start_date->format('Y-m-d') : null,
                    'end_date' => $banner->end_date ? $banner->end_date->format('Y-m-d') : null,
                    'is_active' => $banner->isOfferActive(),
                ],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $activeBanners
        ]);
    }

    /**
     * Get banner details by ID
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getBannerDetails(Request $request, $id): JsonResponse
    {
        $branchId = $request->header('branch-id') ?? $request->branch_id;
        
        $banner = Banner::active()
            ->with(['product', 'category', 'products', 'branches'])
            ->find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        // Check if banner is available for this branch
        if ($branchId && !$banner->isAvailableForBranch($branchId)) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not available for this branch'
            ], 403);
        }

        // Check if offer is active
        if (!$banner->isOfferActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Offer has expired or not yet started'
            ], 410);
        }

        $data = [
            'id' => $banner->id,
            'title' => $banner->title,
            'image' => $banner->imageFullPath,
            'banner_type' => $banner->banner_type,
            'is_global' => $banner->is_global,
            'branches' => $banner->is_global ? [] : $banner->branches->map(function($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address,
                ];
            }),
            'product' => $banner->banner_type === 'single_product' && $banner->product ? [
                'id' => $banner->product->id,
                'name' => $banner->product->name,
                'description' => $banner->product->description,
                'image' => $banner->product->imageFullPath ?? [],
                'price' => (float) $banner->product->price,
                'discount' => (float) $banner->product->discount,
                'rating' => (float) $banner->product->rating,
            ] : null,
            'category' => $banner->banner_type === 'category' && $banner->category ? [
                'id' => $banner->category->id,
                'name' => $banner->category->name,
                'image' => $banner->category->imageFullPath,
            ] : null,
            'pricing' => $banner->banner_type !== 'category' ? [
                'original_price' => (float) $banner->calculateOriginalPrice(),
                'final_price' => (float) $banner->calculateFinalPrice(),
                'discount_amount' => (float) $banner->getDiscountAmount(),
                'discount_percentage' => (float) $banner->getDiscountPercentage(),
                'discount_type' => $banner->discount_type,
                'savings' => (float) $banner->getDiscountAmount(),
            ] : null,
            'dates' => [
                'start_date' => $banner->start_date ? $banner->start_date->format('Y-m-d H:i:s') : null,
                'end_date' => $banner->end_date ? $banner->end_date->format('Y-m-d H:i:s') : null,
                'is_active' => $banner->isOfferActive(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get products for a banner (for multiple products banner type)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getBannerProducts(Request $request, $id): JsonResponse
    {
        $branchId = $request->header('branch-id') ?? $request->branch_id;
        
        $banner = Banner::active()
            ->with('products')
            ->find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        // Check if banner is available for this branch
        if ($branchId && !$banner->isAvailableForBranch($branchId)) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not available for this branch'
            ], 403);
        }

        if ($banner->banner_type !== 'multiple_products') {
            return response()->json([
                'success' => false,
                'message' => 'This banner is not a multiple products offer'
            ], 400);
        }

        $products = $banner->products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'image' => $product->imageFullPath ?? [],
                'price' => (float) $product->price,
                'discount' => (float) $product->discount,
                'rating' => (float) $product->rating,
                'available_time_starts' => $product->available_time_starts,
                'available_time_ends' => $product->available_time_ends,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'banner' => [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'pricing' => [
                        'original_price' => (float) $banner->calculateOriginalPrice(),
                        'final_price' => (float) $banner->calculateFinalPrice(),
                        'discount_amount' => (float) $banner->getDiscountAmount(),
                        'discount_percentage' => (float) $banner->getDiscountPercentage(),
                    ],
                ],
                'products' => $products,
                'total_products' => $products->count(),
            ]
        ]);
    }

    /**
     * Get active offers (banners with valid date ranges)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getActiveOffers(Request $request): JsonResponse
    {
        $branchId = $request->header('branch-id') ?? $request->branch_id;
        
        $banners = Banner::active()
            ->with(['product', 'products', 'category', 'branches'])
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function($q) use ($branchId) {
                    $q->where('is_global', true)
                      ->orWhereHas('branches', function($query) use ($branchId) {
                          $query->where('branch_id', $branchId);
                      });
                });
            }, function ($query) {
                $query->where('is_global', true);
            })
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();

        // Filter for active date ranges only
        $activeOffers = $banners->filter(function ($banner) {
            return $banner->isOfferActive();
        })->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'image' => $banner->imageFullPath,
                'banner_type' => $banner->banner_type,
                'is_global' => $banner->is_global,
                'pricing' => $banner->banner_type !== 'category' ? [
                    'discount_percentage' => (float) $banner->getDiscountPercentage(),
                    'savings' => (float) $banner->getDiscountAmount(),
                ] : null,
                'valid_until' => $banner->end_date ? $banner->end_date->format('Y-m-d H:i:s') : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $activeOffers,
            'total' => $activeOffers->count()
        ]);
    }
}