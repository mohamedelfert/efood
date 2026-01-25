<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\Category;
use App\Model\Translation;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private Category $category,
        private Translation $translation,
        private Branch $branch
    ) {
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    public function index(Request $request)
    {
        $queryParam = [];
        $search = $request['search'];

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $categories = $this->category->where('position', 0)->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
            $queryParam = ['search' => $request['search']];
        } else {
            $categories = $this->category->where('position', 0);
        }

        $categories = $categories->latest()->paginate(Helpers::getPagination())->appends($queryParam);
        $branches = $this->branch->active()->get();

        return view('admin-views.category.index', compact('categories', 'search', 'branches'));
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    function subIndex(Request $request): Renderable
    {
        $search = $request['search'];
        $queryParam = ['search' => $search];

        $categories = $this->category->with(['parent'])
            ->when($request['search'], function ($query) use ($search) {
                $query->orWhere('name', 'like', "%{$search}%");
            })
            ->where(['position' => 1])
            ->latest()
            ->paginate(Helpers::getPagination())
            ->appends($queryParam);

        // Fetch branches for the form
        $branches = $this->branch->active()->get();

        return view('admin-views.category.sub-index', compact('categories', 'search', 'branches'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'branch_ids' => 'required|array',
            'branch_ids.*' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value != '0' && !\App\Model\Branch::where('id', $value)->exists()) {
                        $fail(translate('Branch ID does not exist'));
                    }
                }
            ],
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        $messages = [
            'name.required' => translate('Name is required'),
            'name.array' => translate('Name must be an array'),
            'name.*.required' => translate('Category name is required in all languages'),
            'name.*.string' => translate('Name must be a string'),
            'name.*.max' => translate('Name must not exceed 255 characters'),
            'branch_ids.required' => translate('Please select at least one branch or all branches'),
            'branch_ids.array' => translate('Branch IDs must be an array'),
            'branch_ids.*.exists' => translate('Branch ID does not exist'),
            'image.required' => translate('Image is required'),
            'image.image' => translate('Image must be an image'),
            'image.mimes' => translate('Image must be of type: jpeg, png, jpg, gif'),
            'image.max' => translate('Image size must not exceed 2048 KB'),
        ];

        $request->validate($rules, $messages);

        // Check duplicate
        if (!in_array('0', $request->branch_ids)) {
            foreach ($request->name as $index => $name) {
                if (
                    $this->category->where('name', $name)
                        ->where('parent_id', $request->parent_id ?? 0)
                        ->whereJsonContains('branch_ids', $request->branch_ids[0])
                        ->exists()
                ) {
                    Toastr::error(translate('Category already exists in selected branch!'));
                    return back();
                }
            }
        }

        $imageName = Helpers::upload('category/', 'png', $request->file('image'));
        $bannerImageName = $request->hasFile('banner_image')
            ? Helpers::upload('category/banner/', 'png', $request->file('banner_image'))
            : 'def.png';

        $category = new $this->category();
        $category->name = $request->name[array_search('en', $request->lang ?? ['en'])];
        $category->image = $imageName;
        $category->banner_image = $bannerImageName;
        $category->parent_id = $request->parent_id ?? 0;
        $category->position = $request->position ?? 0;
        $category->priority = $request->priority ?? 0;
        $category->all_branches = in_array('0', $request->branch_ids) ? 1 : 0;
        $category->branch_ids = in_array('0', $request->branch_ids) ? json_encode([]) : json_encode($request->branch_ids);
        $category->save();

        // Translations
        $data = [];
        foreach ($request->lang ?? ['en'] as $index => $key) {
            if ($request->name[$index] && $key != 'en') {
                $data[] = [
                    'translationable_type' => 'App\Model\Category',
                    'translationable_id' => $category->id,
                    'locale' => $key,
                    'key' => 'name',
                    'value' => $request->name[$index],
                ];
            }
        }
        if ($data) {
            $this->translation->insert($data);
        }

        Toastr::success(translate('Category added successfully!'));
        return back();
    }


    /**
     * @param $id
     * @return Renderable
     */
    public function edit($id)
    {
        $category = $this->category->withoutGlobalScopes()->with('translations')->findOrFail($id);
        $branches = $this->branch->active()->get();
        return view('admin-views.category.edit', compact('category', 'branches'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $category = $this->category->find($request->id);
        $category->status = $request->status;
        $category->save();

        Toastr::success($category->parent_id == 0 ? translate('Category status updated!') : translate('Sub Category status updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'branch_ids' => 'required|array',
            'branch_ids.*' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value != '0' && !\App\Model\Branch::where('id', $value)->exists()) {
                        $fail(translate('Branch ID does not exist'));
                    }
                }
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];

        $messages = [
            'name.required' => translate('Name is required'),
            'name.array' => translate('Name must be an array'),
            'name.*.required' => translate('Category name is required'),
            'name.*.string' => translate('Name must be a string'),
            'name.*.max' => translate('Name must not exceed 255 characters'),
            'branch_ids.required' => translate('Please select at least one branch or all branches'),
            'branch_ids.array' => translate('Branch IDs must be an array'),
            'branch_ids.*.exists' => translate('Branch ID does not exist'),
            'image.nullable' => '',
            'image.image' => translate('Image must be an image'),
            'image.mimes' => translate('Image must be of type: jpeg, png, jpg, gif, webp'),
            'image.max' => translate('Image size must not exceed 2048 KB'),
            'banner_image.nullable' => '',
            'banner_image.image' => translate('Banner image must be an image'),
            'banner_image.mimes' => translate('Banner image must be of type: jpeg, png, jpg, gif, webp'),
            'banner_image.max' => translate('Banner image size must not exceed 2048 KB'),
        ];

        $request->validate($rules, $messages);

        $category = $this->category->findOrFail($id);

        // English name (fallback)
        $enIndex = array_search('en', $request->lang ?? ['en']);
        $category->name = $request->name[$enIndex] ?? $request->name[$enIndex];

        // Update images only if new file uploaded
        if ($request->hasFile('image')) {
            $category->image = Helpers::update('category/', $category->image, 'png', $request->file('image'));
        }

        if ($request->hasFile('banner_image')) {
            $category->banner_image = Helpers::update('category/banner/', $category->banner_image, 'png', $request->file('banner_image'));
        }

        // Save selected branches as JSON
        $category->all_branches = in_array('0', $request->branch_ids) ? 1 : 0;
        $category->branch_ids = in_array('0', $request->branch_ids) ? json_encode([]) : json_encode($request->branch_ids);
        $category->save();

        // Handle translations (non-English)
        foreach ($request->lang ?? [] as $index => $langCode) {
            if ($langCode === 'en')
                continue;

            if (!empty($request->name[$index])) {
                $this->translation->updateOrInsert(
                    ([
                        'translationable_type' => 'App\Model\Category',
                        'translationable_id' => $category->id,
                        'locale' => $langCode,
                        'key' => 'name',
                    ]),
                    [
                        'value' => $request->name[$index]
                    ]
                );
            }
        }

        Toastr::success(translate('Category updated successfully!'));
        return back();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $category = $this->category->find($request->id);
        Helpers::delete('category/' . $category['image']);

        if ($category->childes->count() == 0) {
            $category->delete();
            Toastr::success($category->parent_id == 0 ? translate('Category removed!') : translate('Sub Category removed!'));
        } else {
            Toastr::warning($category->parent_id == 0 ? translate('Remove subcategories first!') : translate('Sub Remove subcategories first!'));
        }

        return back();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function priority(Request $request): RedirectResponse
    {
        $category = $this->category->find($request->id);
        $category->priority = $request->priority;
        $category->save();

        Toastr::success(translate('priority updated!'));
        return back();
    }
}