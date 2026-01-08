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
     * 
     * @param Request $request
     * @return JsonResponse
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
     * 
     * @param int $id
     * @return JsonResponse
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
     * 
     * @param Banner $banner
     * @return array
     */
    private function formatBannerData(Banner $banner): array
    {
        $data = [
            'id' => $banner->id,
            'title' => $banner->title,
            'image' => $banner->image_full_path,
            'banner_type' => $banner->banner_type,
            'start_date' => $banner->start_date?->format('Y-m-d'),
            'end_date' => $banner->end_date?->format('Y-m-d'),
            'is_offer_active' => $banner->isOfferActive(),
            'created_at' => $banner->created_at?->format('Y-m-d H:i:s'),
        ];

        // Handle different banner types
        switch ($banner->banner_type) {
            case 'single_product':
                $data['product'] = $banner->product 
                    ? Helpers::product_data_formatting($banner->product) 
                    : null;
                $data['original_price'] = $banner->product ? $banner->product->price : 0;
                $data['offer_price'] = $banner->offer_price;
                $data['discount_percentage'] = $banner->discount_percentage;
                
                // Calculate discount amount if percentage is provided
                if ($banner->discount_percentage && $banner->product) {
                    $data['discount_amount'] = ($banner->product->price * $banner->discount_percentage) / 100;
                    $data['final_price'] = $banner->product->price - $data['discount_amount'];
                } elseif ($banner->offer_price) {
                    $data['discount_amount'] = ($banner->product->price ?? 0) - $banner->offer_price;
                    $data['final_price'] = $banner->offer_price;
                } else {
                    $data['discount_amount'] = 0;
                    $data['final_price'] = $banner->product->price ?? 0;
                }
                break;

            case 'multiple_products':
                $products = $banner->products->map(function ($product) use ($banner) {
                    $formattedProduct = Helpers::product_data_formatting($product);
                    
                    // Add offer details from pivot if available
                    if ($product->pivot) {
                        $formattedProduct['offer_price'] = $product->pivot->offer_price;
                        $formattedProduct['discount_percentage'] = $product->pivot->discount_percentage;
                        
                        // Calculate discount for this product
                        if ($product->pivot->discount_percentage) {
                            $formattedProduct['discount_amount'] = ($product->price * $product->pivot->discount_percentage) / 100;
                            $formattedProduct['final_price'] = $product->price - $formattedProduct['discount_amount'];
                        } elseif ($product->pivot->offer_price) {
                            $formattedProduct['discount_amount'] = $product->price - $product->pivot->offer_price;
                            $formattedProduct['final_price'] = $product->pivot->offer_price;
                        } else {
                            $formattedProduct['discount_amount'] = 0;
                            $formattedProduct['final_price'] = $product->price;
                        }
                    }
                    
                    return $formattedProduct;
                });
                
                $data['products'] = $products;
                $data['products_count'] = $products->count();
                
                // Calculate totals
                $data['total_original_price'] = $banner->products->sum('price');
                $data['total_final_price'] = $products->sum('final_price');
                $data['total_discount'] = $data['total_original_price'] - $data['total_final_price'];
                break;

            case 'category':
                $data['category'] = $banner->category ? [
                    'id' => $banner->category->id,
                    'name' => $banner->category->name,
                    'image' => $banner->category->image_full_url ?? null,
                    'parent_id' => $banner->category->parent_id,
                ] : null;
                $data['discount_percentage'] = $banner->discount_percentage;
                break;
        }

        return $data;
    }

    /**
     * Get products from a banner (for multiple products or category banners)
     * 
     * @param Request $request
     * @param int $bannerId
     * @return JsonResponse
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

            if ($banner->banner_type === 'multiple_products') {
                $products = $banner->products->map(function ($product) {
                    $formattedProduct = Helpers::product_data_formatting($product);
                    
                    // Add offer details from pivot
                    if ($product->pivot) {
                        $formattedProduct['offer_price'] = $product->pivot->offer_price;
                        $formattedProduct['discount_percentage'] = $product->pivot->discount_percentage;
                        
                        if ($product->pivot->discount_percentage) {
                            $formattedProduct['discount_amount'] = ($product->price * $product->pivot->discount_percentage) / 100;
                            $formattedProduct['final_price'] = $product->price - $formattedProduct['discount_amount'];
                        } elseif ($product->pivot->offer_price) {
                            $formattedProduct['discount_amount'] = $product->price - $product->pivot->offer_price;
                            $formattedProduct['final_price'] = $product->pivot->offer_price;
                        }
                    }
                    
                    return $formattedProduct;
                });
            } elseif ($banner->banner_type === 'category' && $banner->category) {
                // Get products from category
                $categoryProducts = $banner->category->products()
                    ->active()
                    ->when($request->has('limit'), function($query) use ($request) {
                        return $query->limit($request->limit);
                    })
                    ->get();
                
                $products = $categoryProducts->map(function ($product) use ($banner) {
                    $formattedProduct = Helpers::product_data_formatting($product);
                    
                    // Apply banner discount to all category products
                    if ($banner->discount_percentage) {
                        $formattedProduct['discount_percentage'] = $banner->discount_percentage;
                        $formattedProduct['discount_amount'] = ($product->price * $banner->discount_percentage) / 100;
                        $formattedProduct['final_price'] = $product->price - $formattedProduct['discount_amount'];
                    }
                    
                    return $formattedProduct;
                });
            }

            return response()->json([
                'banner_id' => $banner->id,
                'banner_title' => $banner->title,
                'banner_type' => $banner->banner_type,
                'discount_percentage' => $banner->discount_percentage,
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
     * Get active banner offers (only banners with active date ranges)
     * 
     * @return JsonResponse
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