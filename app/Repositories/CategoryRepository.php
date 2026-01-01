<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function gridData()
    {
        $query = $this->model::query();

        // Đếm số bài viết TRỰC TIẾP của category (không tính category con)
        $query->select([
            'categories.*',
            DB::raw('(
                SELECT COUNT(p.id)
                FROM posts p
                WHERE p.deleted_at IS NULL
                  AND p.category_id = categories.id
            ) as post_count'),
        ]);

        return $query;
    }

    public function filterData($grid)
    {
        $request = request();
        $createdAt = $request->input('created_at');
        if ($createdAt) {
            // Convert from d/m/Y to Y-m-d
            $date = \DateTime::createFromFormat('d/m/Y', $createdAt);
            $formattedDate = $date ? $date->format('Y-m-d') : null;
            if ($formattedDate) {
                $grid->whereDate('created_at', $formattedDate);
            }
        }

        return $grid;
    }

    public function renderDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('checkbox_html', function ($row) {
                // Không hiển thị checkbox cho uncategorized category
                if ($this->isUncategorizedCategory($row->id)) {
                    return '';
                }

                return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$row->id.'" />';
            })
            ->addColumn('name_html', function ($row) {
                $thumbnail = $row->thumbnail ? thumb_path($row->thumbnail) : asset('resources/admin/assets/img/no-img.png');
                $name = $row->name;
                $isUncategorized = $this->isUncategorizedCategory($row->id);
                $editUrl = $isUncategorized ? 'javascript:void(0)' : route('admin.categories.edit', $row->id);

                return '
                    <div class="d-flex justify-content-start align-items-center product-name">
                        <div class="avatar-wrapper">
                            <div class="avatar avatar-xl me-2 rounded-2 bg-label-secondary">
                                <a href="'.$thumbnail.'" data-lightbox="post-thumbnails" data-title="'.$name.'" class="d-block h-100">
                                    <img src="'.$thumbnail.'" alt="'.$name.'" class="rounded-2 img-fluid object-fit-cover">
                                </a>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <a href="'.$editUrl.'" class="text-body fw-bold text-nowrap mb-0" title="'.$name.'">'.Str::limit($name, 50).'</a>
                        </div>
                    </div>
                ';
            })
            ->addColumn('post_count_html', function ($row) {
                $postCount = isset($row->post_count) ? (int) $row->post_count : 0;

                return '<span class="badge bg-label-primary">'.number_format($postCount).'</span>';
            })
            ->addColumn('created_at_html', function ($row) {
                $createdAt = $row->created_at;

                return '<span class="text-muted">'.$createdAt->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('action_html', function ($row) {
                $editUrl = route('admin.categories.edit', $row->id);
                $deleteUrl = route('admin.categories.destroy', $row->id);
                $title = $row->name;

                // Không cho phép xóa và sửa danh mục "Chưa phân loại" (ID 9999)
                $isUncategorized = $this->isUncategorizedCategory($row->id);

                if ($isUncategorized) {
                    return '<span class="text-muted">—</span>';
                }

                $canEdit = auth()->user()->can('category.update');
                $canDelete = auth()->user()->can('category.delete');

                $html = '<div class="d-inline-block text-nowrap">';
                
                if ($canEdit) {
                    $html .= '<a href="'.$editUrl.'" class="btn btn-sm btn-icon text-warning" title="Chỉnh sửa">
                        <i class="bx bx-edit"></i>
                    </a>';
                }
                
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon text-danger btn-delete" title="Xóa"
                        data-url="'.$deleteUrl.'" data-title="'.htmlspecialchars($title).'">
                        <i class="bx bx-trash"></i>
                    </button>';
                }
                
                $html .= '</div>';

                return $html ?: '<span class="text-muted">—</span>';
            })
            ->rawColumns(['checkbox_html', 'name_html', 'post_count_html', 'created_at_html', 'action_html'])
            ->make(true);
    }

    public function buildTree(array $categories, $parent_id = null): array
    {
        $branch = [];
        foreach ($categories as $key => $category) {
            if ($category['parent_id'] == $parent_id) { // nếu là null thì  tức là cha
                unset($categories[$key]); // bỏ qua lần sau không duyệt nữa
                $children = $this->buildTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $branch[] = $category;
            }
        }

        // Sắp xếp theo order
        usort($branch, function ($a, $b) {
            $orderA = $a['order'] ?? 0;
            $orderB = $b['order'] ?? 0;
            if ($orderA == $orderB) {
                return 0;
            }

            return ($orderA < $orderB) ? -1 : 1;
        });

        return $branch;
    }

    public function formatForJsTree(array $tree): array
    {
        $result = [];
        foreach ($tree as $node) {
            $item = [
                'id' => $node['id'],
                'text' => $node['name'], // JSTree cần 'text'
                'icon' => 'bx bx-folder',
                'state' => ['opened' => true],
            ];

            if (! empty($node['children'])) {
                $item['children'] = $this->formatForJsTree($node['children']);
            } else {
                $item['children'] = false;
            }

            $result[] = $item;
        }

        return $result;
    }

    public function getCategoryByType($type = null)
    {
        $grid = $this->gridData();

        return $grid
            ->orderBy('parent_id', 'asc')
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'asc')
            ->where('id', '!=', 9999) // không lấy danh mục "Chưa phân loại"
            ->get();
    }

    public function getCategoryById($id)
    {
        $grid = $this->gridData();

        return $grid->where('id', $id)->first();
    }

    public function getCategoryBySlug($slug)
    {
        $grid = $this->gridData();

        return $grid->where('slug', $slug)->whereNull('deleted_at')->first();
    }

    public function checkHasChildrenById($id)
    {
        $grid = $this->gridData();

        return $grid->where('parent_id', $id)->exists();
    }

    /**
     * Kiểm tra xem $targetId có phải là con (descendant) của $ancestorId không
     *
     * @param  int  $ancestorId
     * @param  int  $targetId
     * @return bool
     */
    public function isDescendant($ancestorId, $targetId)
    {
        // Lấy tất cả các con của ancestorId
        $children = $this->gridData()->where('parent_id', $ancestorId)->get();

        foreach ($children as $child) {
            // Nếu targetId là con trực tiếp
            if ($child->id == $targetId) {
                return true;
            }

            // Kiểm tra đệ quy: targetId có phải là con của child không
            if ($this->isDescendant($child->id, $targetId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lấy tất cả các danh mục con (recursive) của một danh mục
     *
     * @param  int  $categoryId
     * @return array
     */
    public function getAllChildrenIds($categoryId)
    {
        $childrenIds = [];
        $directChildren = $this->gridData()->where('parent_id', $categoryId)->get();

        foreach ($directChildren as $child) {
            $childrenIds[] = $child->id;
            // Lấy đệ quy các con của child
            $grandChildren = $this->getAllChildrenIds($child->id);
            $childrenIds = array_merge($childrenIds, $grandChildren);
        }

        return $childrenIds;
    }

    /**
     * Lấy thông tin chi tiết để xóa danh mục (tree children và số lượng post/product trực tiếp)
     *
     * @param  int  $categoryId
     * @return array|null
     */
    public function getDeleteInfo($categoryId)
    {
        $category = $this->findById($categoryId);
        if (! $category) {
            return null;
        }

        // Lấy tất cả categories để build tree
        $allCategories = $this->gridData()->get()->toArray();

        // Build tree structure từ category này
        $tree = $this->buildCategoryTree($allCategories, $categoryId);

        // Đếm tổng số children (tất cả levels)
        $allChildrenIds = $this->getAllChildrenIds($categoryId);
        $totalChildrenCount = count($allChildrenIds);

        // Đếm số lượng post TRỰC TIẾP của category này (không tính children)
        $directPostCount = Post::where('category_id', $categoryId)->count();

        // Lấy tất cả category IDs (bao gồm chính nó và tất cả children) để di chuyển post/product khi xóa
        $allCategoryIds = array_merge([$categoryId], $allChildrenIds);

        // Lấy children trực tiếp để cập nhật parent_id khi xóa
        $directChildren = $this->gridData()->where('parent_id', $categoryId)->get();
        $directChildrenIds = $directChildren->pluck('id')->toArray();

        return [
            'category' => $category,
            'tree' => $tree, // Tree structure để hiển thị
            'total_children_count' => $totalChildrenCount, // Tổng số children (tất cả levels)
            'direct_post_count' => $directPostCount, // Số bài viết trực tiếp (không tính children)
            'all_category_ids' => $allCategoryIds, // Tất cả IDs để di chuyển post/product
            'direct_children_ids' => $directChildrenIds, // IDs của children trực tiếp để cập nhật parent_id
        ];
    }

    /**
     * Build tree structure từ một category cụ thể
     *
     * @param  int  $rootId
     * @return array
     */
    private function buildCategoryTree(array $categories, $rootId)
    {
        $tree = [];

        // Tìm root category
        $rootCategory = null;
        foreach ($categories as $key => $category) {
            if ($category['id'] == $rootId) {
                $rootCategory = $category;
                unset($categories[$key]);
                break;
            }
        }

        if (! $rootCategory) {
            return $tree;
        }

        // Build tree từ root
        $rootNode = [
            'id' => $rootCategory['id'],
            'name' => $rootCategory['name'],
            'children' => $this->buildTreeRecursive($categories, $rootId),
        ];

        return [$rootNode];
    }

    /**
     * Build tree recursive helper
     *
     * @param  int|null  $parentId
     * @return array
     */
    private function buildTreeRecursive(array $categories, $parentId = null)
    {
        $branch = [];

        foreach ($categories as $key => $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTreeRecursive($categories, $category['id']);
                $node = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                ];

                if (! empty($children)) {
                    $node['children'] = $children;
                }

                $branch[] = $node;
            }
        }

        // Sắp xếp theo order
        usort($branch, function ($a, $b) use ($categories) {
            // Tìm order của từng node trong categories
            $orderA = 0;
            $orderB = 0;
            foreach ($categories as $cat) {
                if ($cat['id'] == $a['id']) {
                    $orderA = $cat['order'] ?? 0;
                }
                if ($cat['id'] == $b['id']) {
                    $orderB = $cat['order'] ?? 0;
                }
            }
            if ($orderA == $orderB) {
                return 0;
            }

            return ($orderA < $orderB) ? -1 : 1;
        });

        return $branch;
    }

    /**
     * Cập nhật order và parent_id của category khi drag drop
     *
     * @param  int  $categoryId
     * @param  int|null  $newParentId
     * @param  int|null  $position
     * @return void
     */
    public function updateOrder($categoryId, $newParentId, $position = null)
    {
        $category = $this->findById($categoryId);
        if (! $category) {
            throw new \Exception('Danh mục không tồn tại');
        }

        $oldParentId = $category->parent_id;

        // 1. Nếu category được di chuyển từ parent cũ, lấy siblings cũ TRƯỚC KHI cập nhật
        $oldSiblingIds = [];
        if ($oldParentId != $newParentId && $oldParentId !== null) {
            $oldSiblings = $this->gridData()
                ->where('parent_id', $oldParentId)
                ->where('id', '!=', $categoryId)
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();
            $oldSiblingIds = $oldSiblings->pluck('id')->toArray();
        }

        // 2. Lấy tất cả siblings của parent mới (CHƯA bao gồm category đang di chuyển)
        $newSiblings = $this->gridData()
            ->where('parent_id', $newParentId)
            ->where('id', '!=', $categoryId)
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // 3. Tạo mảng IDs của siblings mới
        $siblingIds = $newSiblings->pluck('id')->toArray();

        // 4. Chèn category vào vị trí mới dựa trên position
        if ($position !== null && $position >= 0 && $position <= count($siblingIds)) {
            // Chèn category vào vị trí position
            array_splice($siblingIds, $position, 0, $categoryId);
        } else {
            // Nếu không có position hoặc position không hợp lệ, đặt category ở cuối
            $siblingIds[] = $categoryId;
        }

        // 5. Sử dụng DB transaction để cập nhật parent_id và order
        DB::transaction(function () use ($categoryId, $newParentId, $siblingIds, $oldSiblingIds) {
            // 5.1. Cập nhật parent_id của category
            $this->model::where('id', $categoryId)->update(['parent_id' => $newParentId]);

            // 5.2. Cập nhật order của tất cả siblings mới (bao gồm category vừa di chuyển) dựa trên thứ tự mới (0, 1, 2, ...)
            foreach ($siblingIds as $index => $siblingId) {
                $this->model::where('id', $siblingId)->update(['order' => $index]);
            }

            // 5.3. Nếu category được di chuyển từ parent cũ, sắp xếp lại order của siblings cũ (0, 1, 2, ...)
            if (! empty($oldSiblingIds)) {
                foreach ($oldSiblingIds as $index => $siblingId) {
                    $this->model::where('id', $siblingId)->update(['order' => $index]);
                }
            }
        });
    }

    /**
     * Lấy hoặc tạo danh mục "Chưa phân loại" (blog mặc định)
     *
     * @return \App\Models\Category
     */
    public function getOrCreateUncategorizedCategory()
    {
        $fixedId = 9999;
        $name = 'Chưa phân loại';
        $slug = 'chua-phan-loai';
        $description = 'Danh mục mặc định cho các bài viết chưa được phân loại';

        // Tìm category với ID cố định (bao gồm cả trashed)
        $uncategorized = $this->model::withTrashed()->find($fixedId);

        if ($uncategorized) {
            // Nếu đã bị xóa, restore lại
            if ($uncategorized->trashed()) {
                $uncategorized->restore();
            }
            // Cập nhật thông tin để đảm bảo đúng name, slug, description, parent_id, order
            $updateData = [
                'name' => $name,
                'description' => $description,
                'parent_id' => null,
                'order' => $fixedId,
            ];

            // Chỉ update slug nếu slug hiện tại không đúng
            if ($uncategorized->slug !== $slug) {
                // Kiểm tra xem slug mới có tồn tại không (trừ chính nó)
                $slugExists = $this->model::where('slug', $slug)
                    ->where('id', '!=', $fixedId)
                    ->exists();

                if (! $slugExists) {
                    $updateData['slug'] = $slug;
                }
                // Nếu slug đã tồn tại, không update slug (giữ nguyên)
            }

            $uncategorized->update($updateData);

            return $uncategorized;
        }

        // Nếu không tồn tại, tạo mới với ID cố định
        // Kiểm tra xem slug đã tồn tại chưa (trừ ID cố định)
        $existingSlug = $this->model::where('slug', $slug)
            ->where('id', '!=', $fixedId)
            ->exists();

        if ($existingSlug) {
            // Nếu slug đã tồn tại, thêm timestamp
            $slug = $slug.'-'.time();
        }

        // Sử dụng DB::table để insert với ID cố định
        try {
            DB::table('categories')->insert([
                'id' => $fixedId,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'parent_id' => null,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            return $this->model::find($fixedId);
        } catch (\Exception $e) {
            // Nếu không thể insert với ID cố định (có thể do ID đã tồn tại trong bảng khác hoặc conflict),
            // tạo mới với ID tự động và log warning
            Log::warning("Không thể tạo danh mục '{$name}' với ID {$fixedId}. Tạo với ID tự động.", [
                'fixed_id' => $fixedId,
                'name' => $name,
            ]);

            return $this->model::create([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'parent_id' => null,
                'order' => 0,
            ]);
        }
    }

    /**
     * Tính toán order mới cho category
     *
     * @param  int|null  $parentId
     * @return int
     */
    public function calculateNewOrder($parentId)
    {
        $maxOrder = $this->gridData()
            ->where('parent_id', $parentId)
            ->max('order');

        return ($maxOrder !== null) ? $maxOrder + 1 : 0;
    }

    /**
     * Tạo category mới với order tự động
     *
     * @return Category
     */
    public function createWithOrder(array $data)
    {
        $parentId = $data['parent_id'] ?? null;
        $data['order'] = $this->calculateNewOrder($parentId);

        return $this->create($data);
    }

    /**
     * Lấy categories với thông tin disabled IDs
     *
     * @param  int|null  $excludeId
     * @return array
     */
    public function getCategoriesWithDisabledIds($excludeId = null)
    {
        $categories = $this->getCategoryByType();

        if (! $excludeId) {
            return [
                'categories' => $categories,
                'disabled_ids' => [],
            ];
        }

        $disabledIds = [(int) $excludeId];

        // Thêm tất cả con của excludeId vào danh sách disabled
        $allCategories = $categories->toArray();
        foreach ($allCategories as $category) {
            if ($this->isDescendant($excludeId, $category['id'])) {
                $disabledIds[] = (int) $category['id'];
            }
        }

        return [
            'categories' => $categories,
            'disabled_ids' => array_unique($disabledIds),
        ];
    }

    /**
     * Kiểm tra xem category có phải là uncategorized category không
     *
     * @param  int  $id
     * @return bool
     */
    public function isUncategorizedCategory($id)
    {
        return $id == 9999;
    }

    /**
     * Xóa category với toàn bộ logic xử lý
     *
     * @param  int  $id
     * @return array
     *
     * @throws \Exception
     */
    public function deleteCategory($id)
    {
        // Không cho phép xóa uncategorized categories
        if ($this->isUncategorizedCategory($id)) {
            throw new \Exception('Không thể xóa danh mục "Chưa phân loại". Đây là danh mục hệ thống.');
        }

        $category = $this->findById($id);
        if (! $category) {
            throw new \Exception('Danh mục không tồn tại.');
        }

        return DB::transaction(function () use ($category) {
            $hasChildren = $this->checkHasChildrenById($category->id);

            if ($hasChildren) {
                // Xóa danh mục cha: cần xử lý children
                $deleteInfo = $this->getDeleteInfo($category->id);
                $directChildrenIds = $deleteInfo['direct_children_ids'];

                // 1. Cập nhật parent_id của children trực tiếp về parent_id của category bị xóa
                // (các danh mục con gần nhất sẽ trở thành danh mục cha)
                $newParentId = $category->parent_id;

                if (! empty($directChildrenIds)) {
                    Category::whereIn('id', $directChildrenIds)
                        ->update(['parent_id' => $newParentId]);
                }
            }

            // 2. Xóa category (soft delete)
            $this->delete($category->id);

            return [
                'status' => true,
                'message' => 'Xóa danh mục thành công',
            ];
        });
    }

    /**
     * Get trashed data for DataTables
     */
    public function gridTrashedData()
    {
        $query = $this->model::onlyTrashed();
        $query->select([
            'categories.*',
            DB::raw('(
                SELECT COUNT(p.id)
                FROM posts p
                WHERE p.deleted_at IS NULL
                  AND p.category_id = categories.id
            ) as post_count'),
        ]);

        return $query;
    }

    /**
     * Render DataTables for trashed categories
     */
    public function renderTrashedDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name_html', function ($row) {
                $name = $row->name;

                return '<span class="text-body fw-bold">'.Str::limit($name, 50).'</span>';
            })
            ->addColumn('deleted_at_html', function ($row) {
                $deletedAt = $row->deleted_at;

                return '<span class="text-muted">'.$deletedAt->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('post_count_html', function ($row) {
                $postCount = isset($row->post_count) ? (int) $row->post_count : 0;

                return '<span class="badge bg-label-primary">'.number_format($postCount).'</span>';
            })
            ->addColumn('checkbox_html', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$row->id.'" />';
            })
            ->addColumn('action_html', function ($row) {
                $restoreUrl = route('admin.categories.restore', $row->id);
                $forceDeleteUrl = route('admin.categories.forceDelete', $row->id);
                $title = $row->name;

                $canUpdate = auth()->user()->can('category.update');
                $canDelete = auth()->user()->can('category.delete');

                $html = '<div class="d-inline-block text-nowrap">';
                
                if ($canUpdate) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon btn-success btn-restore" title="Khôi phục"
                        data-url="'.$restoreUrl.'" data-title="'.htmlspecialchars($title).'">
                        <i class="bx bx-undo"></i>
                    </button>';
                }
                
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon text-danger btn-force-delete" title="Xóa vĩnh viễn"
                        data-url="'.$forceDeleteUrl.'" data-title="'.htmlspecialchars($title).'">
                        <i class="bx bx-trash"></i>
                    </button>';
                }
                
                $html .= '</div>';

                return $html ?: '<span class="text-muted">—</span>';
            })
            ->rawColumns(['checkbox_html', 'name_html', 'post_count_html', 'deleted_at_html', 'action_html'])
            ->make(true);
    }

    /**
     * Restore a trashed category
     */
    public function restore($id)
    {
        $category = $this->model::withTrashed()->find($id);
        if ($category && $category->trashed()) {
            return $category->restore();
        }

        return false;
    }

    /**
     * Force delete a category
     * Khi xóa vĩnh viễn: chỉ di chuyển bài viết của danh mục bị xóa vào danh mục "Chưa phân loại"
     * Khi xóa danh mục cha, các danh mục con gần nhất sẽ trở thành danh mục cha
     */
    public function forceDelete($id)
    {
        // Không cho phép xóa vĩnh viễn uncategorized categories
        if ($this->isUncategorizedCategory($id)) {
            throw new \Exception('Không thể xóa vĩnh viễn danh mục "Chưa phân loại". Đây là danh mục hệ thống.');
        }

        $category = $this->model::withTrashed()->find($id);
        if (! $category || ! $category->trashed()) {
            throw new \Exception('Danh mục không tồn tại trong thùng rác.');
        }

        return DB::transaction(function () use ($category) {
            // 1. Xử lý children nếu có: cập nhật parent_id của children trực tiếp về parent_id của category bị xóa
            // (các danh mục con gần nhất sẽ trở thành danh mục cha)
            $hasChildren = $this->model::withTrashed()
                ->where('parent_id', $category->id)
                ->exists();

            if ($hasChildren) {
                $directChildren = $this->model::withTrashed()
                    ->where('parent_id', $category->id)
                    ->get();
                $directChildrenIds = $directChildren->pluck('id')->toArray();
                $newParentId = $category->parent_id;

                if (! empty($directChildrenIds)) {
                    Category::whereIn('id', $directChildrenIds)
                        ->update(['parent_id' => $newParentId]);
                }
            }

            // 2. Di chuyển posts CỦA DANH MỤC BỊ XÓA về danh mục "Chưa phân loại"
            // (chỉ di chuyển posts của category bị xóa, không di chuyển posts của children)
            $uncategorizedCategory = $this->getOrCreateUncategorizedCategory();

            $postCount = Post::where('category_id', $category->id)->count();
            if ($postCount > 0) {
                Post::where('category_id', $category->id)
                    ->update(['category_id' => $uncategorizedCategory->id]);
            }

            // 3. Xóa vĩnh viễn category
            return $category->forceDelete();
        });
    }

    /**
     * Bulk delete categories (soft delete)
     */
    public function bulkDelete(array $ids)
    {
        return $this->model::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->delete();
    }

    /**
     * Bulk restore categories
     */
    public function bulkRestore(array $ids)
    {
        return $this->model::withTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->restore();
    }

    /**
     * Bulk force delete categories
     * Khi xóa vĩnh viễn: chỉ di chuyển bài viết của các danh mục bị xóa vào danh mục "Chưa phân loại"
     * Khi xóa danh mục cha, các danh mục con gần nhất sẽ trở thành danh mục cha
     */
    public function bulkForceDelete(array $ids)
    {
        // Lọc bỏ uncategorized categories
        $ids = array_filter($ids, function ($id) {
            return ! $this->isUncategorizedCategory($id);
        });

        if (empty($ids)) {
            throw new \Exception('Không thể xóa vĩnh viễn danh mục "Chưa phân loại". Đây là danh mục hệ thống.');
        }

        return DB::transaction(function () use ($ids) {
            // 1. Xử lý children của các categories bị xóa: cập nhật parent_id của children trực tiếp
            // (các danh mục con gần nhất sẽ trở thành danh mục cha)
            $categoriesToDelete = $this->model::withTrashed()
                ->whereIn('id', $ids)
                ->whereNotNull('deleted_at')
                ->get();

            foreach ($categoriesToDelete as $category) {
                $directChildren = $this->model::withTrashed()
                    ->where('parent_id', $category->id)
                    ->get();
                $directChildrenIds = $directChildren->pluck('id')->toArray();
                $newParentId = $category->parent_id;

                if (! empty($directChildrenIds)) {
                    Category::whereIn('id', $directChildrenIds)
                        ->update(['parent_id' => $newParentId]);
                }
            }

            // 2. Di chuyển posts CỦA CÁC DANH MỤC BỊ XÓA về danh mục "Chưa phân loại"
            // (chỉ di chuyển posts của các categories bị xóa, không di chuyển posts của children)
            $uncategorizedCategory = $this->getOrCreateUncategorizedCategory();

            $postCount = Post::whereIn('category_id', $ids)->count();
            if ($postCount > 0) {
                Post::whereIn('category_id', $ids)
                    ->update(['category_id' => $uncategorizedCategory->id]);
            }

            // 3. Xóa vĩnh viễn các categories
            return $this->model::withTrashed()
                ->whereIn('id', $ids)
                ->whereNotNull('deleted_at')
                ->forceDelete();
        });
    }
}
