<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreRequest;
use App\Http\Requests\Admin\Category\UpdateOrderRequest;
use App\Http\Requests\Admin\Category\UpdateRequest;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryRepository;

    public function __construct(CategoryRepository $category)
    {
        $this->categoryRepository = $category;
    }

    public function list()
    {
        return view('admin.modules.category.list');
    }

    public function ajaxGetTreeView($type)
    {
        $categories = $this->categoryRepository->getCategoryByType()->toArray();
        $tree = $this->categoryRepository->buildTree($categories);
        $formatted = $this->categoryRepository->formatForJsTree($tree);

        return response()->json($formatted);
    }

    public function ajaxGetData()
    {
        $grid = $this->categoryRepository->gridData();
        $data = $this->categoryRepository->filterData($grid);

        return $this->categoryRepository->renderDataTables($data);
    }

    public function ajaxGetTrashedData()
    {
        $grid = $this->categoryRepository->gridTrashedData();
        $data = $this->categoryRepository->filterData($grid);

        return $this->categoryRepository->renderTrashedDataTables($data);
    }

    public function ajaxGetCategoryByType(Request $request)
    {
        $excludeId = $request->get('exclude_id'); // Category cần disable (chính nó và các con)

        $result = $this->categoryRepository->getCategoriesWithDisabledIds($excludeId);

        // Nếu có exclude_id, trả về với thông tin disabled
        if ($excludeId) {
            return response()->json($result);
        }

        return $result['categories'];
    }

    public function create()
    {
        return view('admin.modules.category.create');
    }

    public function store(StoreRequest $request)
    {
        try {
            $this->categoryRepository->createWithOrder([
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description'),
                'parent_id' => $request->input('parent_id'),
                'thumbnail' => $request->input('thumbnail'),
            ]);

            return back()->with('success', 'Thêm mới thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    /**
     * Quick create category from post module (via AJAX)
     * Returns JSON response for quick creation
     */
    public function quickStore(\App\Http\Requests\Admin\Category\QuickStoreRequest $request)
    {

        try {
            $category = $this->categoryRepository->createWithOrder([
                'name' => $request->input('name'),
                'slug' => \Illuminate\Support\Str::slug($request->input('name')),
                'description' => null,
                'parent_id' => null,
                'thumbnail' => null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Thêm danh mục thành công',
                'data' => $category
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra'
            ], 500);
        }
    }

    public function edit($id)
    {
        // Không cho phép edit danh mục "Chưa phân loại"
        if ($this->categoryRepository->isUncategorizedCategory($id)) {
            return redirect()->route('admin.categories.list')->with('error', 'Không thể chỉnh sửa danh mục "Chưa phân loại". Đây là danh mục hệ thống.');
        }

        $data = $this->categoryRepository->getCategoryById($id);

        return view('admin.modules.category.edit', data: compact('data'));
    }

    public function update(UpdateRequest $request, $id)
    {
        // Không cho phép update danh mục "Chưa phân loại"
        if ($this->categoryRepository->isUncategorizedCategory($id)) {
            return back()->with('error', 'Không thể cập nhật danh mục "Chưa phân loại". Đây là danh mục hệ thống.');
        }

        try {
            $this->categoryRepository->update($id, [
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description'),
                'parent_id' => $request->input('parent_id'),
                'thumbnail' => $request->input('thumbnail'),
            ]);

            return back()->with('success', 'Cập nhật thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    public function updateOrder(UpdateOrderRequest $request)
    {
        try {
            $this->categoryRepository->updateOrder(
                $request->input('id'),
                $request->input('parent_id'),
                $request->input('position')
            );

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra',
            ], 500);
        }
    }

    public function getDeleteInfo($id)
    {
        // Không cho phép xóa 2 danh mục "Chưa phân loại" (ID 9999 và 1000)
        if ($this->categoryRepository->isUncategorizedCategory($id)) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa danh mục "Chưa phân loại". Đây là danh mục hệ thống.',
            ], 403);
        }

        $deleteInfo = $this->categoryRepository->getDeleteInfo($id);

        if (! $deleteInfo) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'category' => [
                    'id' => $deleteInfo['category']->id,
                    'name' => $deleteInfo['category']->name,
                ],
                'tree' => $deleteInfo['tree'], // Tree structure
                'total_children_count' => $deleteInfo['total_children_count'],
                'direct_post_count' => $deleteInfo['direct_post_count'],
            ],
        ]);
    }

    public function destroy($id)
    {
        try {
            $result = $this->categoryRepository->deleteCategory($id);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra',
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $category = Category::withTrashed()->find($id);
            if (!$category || !$category->trashed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Danh mục không tồn tại trong thùng rác',
                ], 404);
            }

            $this->categoryRepository->restore($id);

            return response()->json([
                'status' => true,
                'message' => 'Khôi phục danh mục thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục danh mục',
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $category = Category::withTrashed()->find($id);
            if (!$category || !$category->trashed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Danh mục không tồn tại trong thùng rác',
                ], 404);
            }

            $this->categoryRepository->forceDelete($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn danh mục thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn danh mục',
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một danh mục',
                ], 400);
            }

            $count = $this->categoryRepository->bulkDelete($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã xóa {$count} danh mục thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa',
            ], 500);
        }
    }

    public function bulkRestore(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một danh mục',
                ], 400);
            }

            $count = $this->categoryRepository->bulkRestore($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã khôi phục {$count} danh mục thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục',
            ], 500);
        }
    }

    public function bulkForceDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một danh mục',
                ], 400);
            }

            $count = $this->categoryRepository->bulkForceDelete($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã xóa vĩnh viễn {$count} danh mục thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn',
            ], 500);
        }
    }
}
