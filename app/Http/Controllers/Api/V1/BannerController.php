<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(
        private Banner $banner
    )
    {
    }

    /**
     * Get all active banners with their associated data
     */
    public function getBanners(Request $request): JsonResponse
    {
        try {
            $banners = $this->banner
                ->with([
                    'product.rating',
                    'product.branch_product',
                    'products.rating',
                    'products.branch_product',
                    'category'
                ])
                ->active()
                ->when($request->has('banner_type'), function($query) use ($request) {
                    return $query->where('banner_type', $request->banner_type);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedBanners = $banners->map(function ($banner) {
                return $this->formatBannerData($banner);
            });

            return response()->json($formattedBanners, 200);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'banner', 'message' => 'Failed to fetch banners']
                ]
            ], 500);
        }
    }

    /**
     * Get banner details by ID
     */
    public function getBannerDetails(int $id): JsonResponse
    {
        try {
            $banner = $this->banner
                ->with([
                    'product.rating',
                    'product.branch_product',
                    'products.rating',
                    'products.branch_product',
                    'category'
                ])
                ->active()
                ->find($id);

            if (!$banner) {
                return response()->json([
                    'errors' => [
                        ['code' => 'banner', 'message' => 'Banner not found']
                    ]
                ], 404);
            }

            $formattedBanner = $this->formatBannerData($banner);

            return response()->json($formattedBanner, 200);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'banner', 'message' => 'Failed to fetch banner details']
                ]
            ], 500);
        }
    }

    /**
     * Format banner data based on banner type
     */
    private function formatBannerData(Banner $banner): array
    {
        $data = [
            'id' => $banner->id,
            'title' => $banner->title,
            'image' => $banner->image_full_path,
            'banner_type' => $banner->banner_type,
            'discount_type' => $banner->discount_type,
            'start_date' => $banner->start_date?->format('Y-m-d'),
            'end_date' => $banner->end_date?->format('Y-m-d'),
            'is_offer_active' => $banner->isOfferActive(),
            'created_at' => $banner->created_at?->format('Y-m-d H:i:s'),
        ];

        // Calculate pricing based on banner type
        $originalPrice = $banner->calculateOriginalPrice();
        $finalPrice = $banner->calculateFinalPrice();
        
        $data['original_price'] = $originalPrice;
        $data['final_price'] = $finalPrice;
        $data['discount_amount'] = $banner->getDiscountAmount();
        $data['discount_percentage'] = $banner->getDiscountPercentage();

        // Handle different banner types
        switch ($banner->banner_type) {
            case 'single_product':
                $data['product'] = $banner->product 
                    ? Helpers::product_data_formatting($banner->product) 
                    : null;
                break;

            case 'multiple_products':
                $products = $banner->products->map(function ($product) use ($banner, $originalPrice) {
                    $formattedProduct = Helpers::product_data_formatting($product);
                    
                    // Calculate proportional discount for each product
                    if ($originalPrice > 0 && $banner->getDiscountAmount() > 0) {
                        $productProportion = $product->price / $originalPrice;
                        $productDiscount = $banner->getDiscountAmount() * $productProportion;
                        
                        $formattedProduct['discount_amount'] = round($productDiscount, 2);
                        $formattedProduct['final_price'] = round($product->price - $productDiscount, 2);
                        $formattedProduct['discount_percentage'] = round(($productDiscount / $product->price) * 100, 2);
                    } else {
                        $formattedProduct['discount_amount'] = 0;
                        $formattedProduct['final_price'] = $product->price;
                        $formattedProduct['discount_percentage'] = 0;
                    }
                    
                    return $formattedProduct;
                });
                
                $data['products'] = $products;
                $data['products_count'] = $products->count();
                break;

            case 'category':
                $data['category'] = $banner->category ? [
                    'id' => $banner->category->id,
                    'name' => $banner->category->name,
                    'image' => $banner->category->image_full_url ?? null,
                    'parent_id' => $banner->category->parent_id,
                ] : null;
                break;
        }

        return $data;
    }

    /**
     * Get products from a banner
     */
    public function getBannerProducts(Request $request, int $bannerId): JsonResponse
    {
        try {
            $banner = $this->banner
                ->with(['products.rating', 'products.branch_product', 'category'])
                ->active()
                ->find($bannerId);

            if (!$banner) {
                return response()->json([
                    'errors' => [
                        ['code' => 'banner', 'message' => 'Banner not found']
                    ]
                ], 404);
            }

            $products = collect();
            $originalPrice = $banner->calculateOriginalPrice();

            if ($banner->banner_type === 'multiple_products') {
                $products = $banner->products->map(function ($product) use ($banner, $originalPrice) {
                    $formattedProduct = Helpers::product_data_formatting($product);
                    
                    // Calculate proportional discount
                    if ($originalPrice > 0 && $banner->getDiscountAmount() > 0) {
                        $productProportion = $product->price / $originalPrice;
                        $productDiscount = $banner->getDiscountAmount() * $productProportion;
                        
                        $formattedProduct['discount_amount'] = round($productDiscount, 2);
                        $formattedProduct['final_price'] = round($product->price - $productDiscount, 2);
                        $formattedProduct['discount_percentage'] = round(($productDiscount / $product->price) * 100, 2);
                    }
                    
                    return $formattedProduct;
                });
            } elseif ($banner->banner_type === 'category' && $banner->category) {
                $categoryProducts = $banner->category->products()
                    ->active()
                    ->when($request->has('limit'), function($query) use ($request) {
                        return $query->limit($request->limit);
                    })
                    ->get();
                
                $products = $categoryProducts->map(function ($product) use ($banner) {
                    $formattedProduct = Helpers::product_data_formatting($product);
                    
                    // Apply banner discount to category products
                    if ($banner->total_discount_percentage) {
                        $discount = ($product->price * $banner->total_discount_percentage) / 100;
                        $formattedProduct['discount_percentage'] = $banner->total_discount_percentage;
                        $formattedProduct['discount_amount'] = round($discount, 2);
                        $formattedProduct['final_price'] = round($product->price - $discount, 2);
                    }
                    
                    return $formattedProduct;
                });
            }

            return response()->json([
                'banner_id' => $banner->id,
                'banner_title' => $banner->title,
                'banner_type' => $banner->banner_type,
                'discount_percentage' => $banner->getDiscountPercentage(),
                'products' => $products,
                'total_products' => $products->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'banner', 'message' => 'Failed to fetch banner products']
                ]
            ], 500);
        }
    }

    /**
     * Get active banner offers
     */
    public function getActiveOffers(): JsonResponse
    {
        try {
            $banners = $this->banner
                ->with([
                    'product.rating',
                    'product.branch_product',
                    'products.rating',
                    'products.branch_product',
                    'category'
                ])
                ->active()
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedBanners = $banners->map(function ($banner) {
                return $this->formatBannerData($banner);
            });

            return response()->json($formattedBanners, 200);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'banner', 'message' => 'Failed to fetch active offers']
                ]
            ], 500);
        }
    }
}