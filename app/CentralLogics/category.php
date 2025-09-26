<?php

namespace App\CentralLogics;

use App\Model\Category;
use App\Model\Product;
use Illuminate\Support\Facades\Log;

class CategoryLogic
{
    public static function parents()
    {
        return Category::where('position', 0)->get();
    }

    public static function child($parent_id)
    {
        return Category::where(['parent_id' => $parent_id])->get();
    }

    public static function products($category_id, $type, $name, $limit, $offset)
    {
        try {
            $limit = is_null($limit) ? null : $limit;
            $offset = is_null($offset) ? 1 : $offset;
            $key = $name ? explode(' ', $name) : [];
            $productType = ($type == 'veg') ? 'veg' : ($type == 'non_veg' ? 'non_veg' : 'all');

            $productsQuery = Product::active()
                ->with(['branch_product', 'rating'])
                ->whereHas('branch_product.branch', function ($query) {
                    $query->where('status', 1);
                })
                ->branchProductAvailability()
                ->when($productType != 'all', function ($query) use ($productType) {
                    return $query->where('product_type', $productType);
                })
                ->when(!empty($key), function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                    $q->orWhereHas('tags', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('tag', 'like', "%{$value}%");
                            }
                        });
                    });
                })
                ->when($category_id, function ($q) use ($category_id) {
                    $q->whereJsonContains('category_ids', [['id' => (string)$category_id]]);
                })
                ->latest();

            if (is_null($limit)) {
                $categoryProducts = $productsQuery->get();
                $totalSize = $categoryProducts->count();
            } else {
                $categoryProducts = $productsQuery->paginate($limit, ['*'], 'page', $offset);
                $totalSize = $categoryProducts->total();
            }

            return [
                'total_size' => $totalSize,
                'limit' => $limit,
                'offset' => $offset,
                'products' => is_null($limit) ? $categoryProducts : $categoryProducts->items(),
            ];
        } catch (\Exception $e) {
            Log::error('CategoryLogic::products failed', [
                'category_id' => $category_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'total_size' => 0,
                'limit' => $limit,
                'offset' => $offset,
                'products' => []
            ];
        }
    }

    public static function all_products($id)
    {
        try {
            $cate_ids = [(int)$id];
            foreach (self::child($id) as $ch1) {
                $cate_ids[] = $ch1['id'];
                foreach (self::child($ch1['id']) as $ch2) {
                    $cate_ids[] = $ch2['id'];
                }
            }

            $products = Product::active()->branchProductAvailability()->get();
            $productIds = [];
            foreach ($products as $product) {
                $categoryIds = is_array($product['category_ids'])
                    ? $product['category_ids']
                    : (json_decode($product['category_ids'], true) ?? []);

                if (!is_array($categoryIds)) {
                    Log::warning('Invalid category_ids format', [
                        'product_id' => $product['id'],
                        'category_ids' => $product['category_ids']
                    ]);
                    continue;
                }

                foreach ($categoryIds as $category) {
                    $categoryId = is_array($category) ? (string)($category['id'] ?? '') : (string)$category;
                    if (in_array($categoryId, $cate_ids)) {
                        $productIds[] = $product['id'];
                    }
                }
            }

            return Product::with(['rating', 'branch_product'])->whereIn('id', $productIds)->get();
        } catch (\Exception $e) {
            Log::error('CategoryLogic::all_products failed', [
                'category_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]);
        }
    }
}