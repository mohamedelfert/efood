<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Banner;
use App\Model\Branch;
use App\Model\Category;
use App\Model\Product;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    public function __construct(
        private Banner   $banner,
        private Product  $product,
        private Category $category,
        private Branch   $branch
    )
    {}

    /**
     * @return Renderable
     */
    function index(): Renderable
    {
        $products = $this->product->orderBy('name')->get();
        $categories = $this->category->where(['parent_id' => 0])->orderBy('name')->get();
        $branches = $this->branch->active()->orderBy('name')->get();

        return view('admin-views.banner.index', compact('products', 'categories', 'branches'));
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    function list(Request $request): Renderable
    {
        $search = $request->search;
        $branchFilter = $request->branch_id;
        $queryParam = ['search' => $search, 'branch_id' => $branchFilter];

        $banners = $this->banner
            ->with(['products', 'category', 'product', 'branches'])
            ->when($search, function ($query) use ($search) {
                $keywords = explode(' ', $search);
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'LIKE', "%$keyword%")
                        ->orwhere('id', 'LIKE', "%$keyword%");
                }
            })
            ->when($branchFilter, function ($query) use ($branchFilter) {
                if ($branchFilter === 'global') {
                    $query->where('is_global', true);
                } else {
                    $query->where(function($q) use ($branchFilter) {
                        $q->where('is_global', true)
                          ->orWhereHas('branches', function($query) use ($branchFilter) {
                              $query->where('branch_id', $branchFilter);
                          });
                    });
                }
            })
            ->latest()
            ->paginate(Helpers::getPagination())
            ->appends($queryParam);

        $branches = $this->branch->active()->orderBy('name')->get();

        return view('admin-views.banner.list', compact('banners', 'search', 'branches', 'branchFilter'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Base validation rules
        $rules = [
            'title' => 'required|max:255',
            'image' => 'required',
            'banner_type' => 'required|in:single_product,multiple_products,category',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'discount_type' => 'required|in:fixed,percentage',
            'total_offer_price' => 'nullable|numeric|min:0',
            'total_discount_amount' => 'nullable|numeric|min:0',
            'total_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_global' => 'required|boolean',
            'branch_ids' => 'required_if:is_global,0|array',
            'branch_ids.*' => 'exists:branches,id',
        ];

        // Conditional validation based on banner type
        if ($request->banner_type == 'single_product') {
            $rules['product_id'] = 'required|exists:products,id';
        } elseif ($request->banner_type == 'multiple_products') {
            $rules['product_ids'] = 'required|array|min:1';
            $rules['product_ids.*'] = 'required|exists:products,id';
        } elseif ($request->banner_type == 'category') {
            $rules['category_id'] = 'required|exists:categories,id';
        }

        $messages = [
            'title.required' => translate('messages.title_required'),
            'image.required' => translate('messages.image_required'),
            'banner_type.required' => translate('messages.banner_type_required'),
            'product_id.required' => translate('messages.select_product'),
            'product_ids.required' => translate('messages.select_at_least_one_product'),
            'category_id.required' => translate('messages.select_category'),
            'end_date.after_or_equal' => translate('messages.end_date_after_start'),
            'branch_ids.required_if' => translate('messages.select_at_least_one_branch'),
        ];

        $request->validate($rules, $messages);

        $banner = new Banner();
        $banner->title = $request->title;
        $banner->banner_type = $request->banner_type;
        $banner->start_date = $request->start_date;
        $banner->end_date = $request->end_date;
        $banner->discount_type = $request->discount_type;
        $banner->total_offer_price = $request->total_offer_price;
        $banner->total_discount_amount = $request->total_discount_amount;
        $banner->total_discount_percentage = $request->total_discount_percentage;
        $banner->is_global = $request->is_global;

        // Handle single product
        if ($request->banner_type == 'single_product') {
            $banner->product_id = $request->product_id;
            $banner->category_id = null;
        }

        // Handle category
        if ($request->banner_type == 'category') {
            $banner->category_id = $request->category_id;
            $banner->product_id = null;
        }

        // Handle multiple products
        if ($request->banner_type == 'multiple_products') {
            $banner->product_id = null;
            $banner->category_id = null;
        }

        $banner->image = Helpers::upload('banner/', 'png', $request->file('image'));
        $banner->save();

        // Attach multiple products to pivot table
        if ($request->banner_type == 'multiple_products' && !empty($request->product_ids)) {
            $productIds = array_filter($request->product_ids, function($id) {
                return !empty($id);
            });
            
            if (!empty($productIds)) {
                $banner->products()->attach($productIds);
            }
        }

        // Attach branches if not global
        if (!$request->is_global && !empty($request->branch_ids)) {
            $branchIds = array_filter($request->branch_ids, function($id) {
                return !empty($id);
            });
            
            if (!empty($branchIds)) {
                $banner->branches()->attach($branchIds);
            }
        }

        Toastr::success(translate('messages.banner_added_successfully'));
        return redirect('admin/banner/list');
    }

    /**
     * @param $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $products = $this->product->orderBy('name')->get();
        $banner = $this->banner->with(['products', 'branches'])->find($id);
        $categories = $this->category->where(['parent_id' => 0])->orderBy('name')->get();
        $branches = $this->branch->active()->orderBy('name')->get();

        return view('admin-views.banner.edit', compact('banner', 'products', 'categories', 'branches'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        // Base validation rules
        $rules = [
            'title' => 'required|max:255',
            'banner_type' => 'required|in:single_product,multiple_products,category',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'discount_type' => 'required|in:fixed,percentage',
            'total_offer_price' => 'nullable|numeric|min:0',
            'total_discount_amount' => 'nullable|numeric|min:0',
            'total_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_global' => 'required|boolean',
            'branch_ids' => 'required_if:is_global,0|array',
            'branch_ids.*' => 'exists:branches,id',
        ];

        // Conditional validation
        if ($request->banner_type == 'single_product') {
            $rules['product_id'] = 'required|exists:products,id';
        } elseif ($request->banner_type == 'multiple_products') {
            $rules['product_ids'] = 'required|array|min:1';
            $rules['product_ids.*'] = 'required|exists:products,id';
        } elseif ($request->banner_type == 'category') {
            $rules['category_id'] = 'required|exists:categories,id';
        }

        $request->validate($rules);

        $banner = $this->banner->find($id);
        $banner->title = $request->title;
        $banner->banner_type = $request->banner_type;
        $banner->start_date = $request->start_date;
        $banner->end_date = $request->end_date;
        $banner->discount_type = $request->discount_type;
        $banner->total_offer_price = $request->total_offer_price;
        $banner->total_discount_amount = $request->total_discount_amount;
        $banner->total_discount_percentage = $request->total_discount_percentage;
        $banner->is_global = $request->is_global;

        // Reset relationships
        $banner->product_id = null;
        $banner->category_id = null;

        // Handle single product
        if ($request->banner_type == 'single_product') {
            $banner->product_id = $request->product_id;
            $banner->products()->detach();
        }

        // Handle category
        if ($request->banner_type == 'category') {
            $banner->category_id = $request->category_id;
            $banner->products()->detach();
        }

        // Handle multiple products
        if ($request->banner_type == 'multiple_products') {
            $banner->products()->detach();
            
            if (!empty($request->product_ids)) {
                $productIds = array_filter($request->product_ids, function($id) {
                    return !empty($id);
                });
                
                if (!empty($productIds)) {
                    $banner->products()->attach($productIds);
                }
            }
        }

        $banner->image = $request->has('image') 
            ? Helpers::update('banner/', $banner->image, 'png', $request->file('image')) 
            : $banner->image;
        
        $banner->save();

        // Update branches
        $banner->branches()->detach();
        if (!$request->is_global && !empty($request->branch_ids)) {
            $branchIds = array_filter($request->branch_ids, function($id) {
                return !empty($id);
            });
            
            if (!empty($branchIds)) {
                $banner->branches()->attach($branchIds);
            }
        }

        Toastr::success(translate('messages.banner_updated_successfully'));
        return redirect('admin/banner/list');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $banner = $this->banner->find($request->id);
        $banner->products()->detach();
        $banner->branches()->detach();
        Helpers::delete('banner/' . $banner['image']);
        $banner->delete();

        Toastr::success(translate('messages.banner_removed'));
        return back();
    }
}