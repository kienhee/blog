<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\PostHashtag;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PostRepository extends BaseRepository
{
    public function __construct(Post $model)
    {
        parent::__construct($model);
    }

    /**
     * Lấy trạng thái bài viết
     *
     * @return string[]
     */
    public function getStatusLabel()
    {
        return [
            $this->model::STATUS_PUBLISHED => 'Công khai',
            $this->model::STATUS_SCHEDULED => 'Lên lịch',
            $this->model::STATUS_DRAFT => 'Bản nháp',
        ];
    }

    public function gridData()
    {
        $query = $this->model::query();
        $query->select([
            'posts.id',
            'posts.thumbnail as thumbnail',
            'posts.title as title',
            'posts.slug as slug',
            'posts.content as content',
            'posts.status as status',
            'posts.description as meta_description',
            'posts.category_id as category_id',
            'posts.allow_comment as allow_comment',
            'posts.created_at as created_at',
            'posts.scheduled_at as scheduled_at',
            'categories.name as category_name',
            'categories.slug as category_slug',
            'users.full_name',
            'users.avatar',
            'users.description',
            'users.twitter_url',
            'users.facebook_url',
            'users.instagram_url',
            'users.linkedin_url',
        ])
            ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
            ->leftJoin('users', 'users.id', '=', 'posts.user_id')
            // Lấy danh sách hashtag IDs
            ->selectSub(function ($q) {
                $q->from('post_hashtags as bh')
                    ->join('hash_tags as ht', 'bh.hashtag_id', '=', 'ht.id')
                    ->whereColumn('bh.post_id', 'posts.id')
                    ->selectRaw('GROUP_CONCAT(DISTINCT ht.id ORDER BY ht.id SEPARATOR ", ")');
            }, 'hashtag_ids')
            // Lấy danh sách hashtag names
            ->selectSub(function ($q) {
                $q->from('post_hashtags as bh')
                    ->join('hash_tags as ht', 'bh.hashtag_id', '=', 'ht.id')
                    ->whereColumn('bh.post_id', 'posts.id')
                    ->selectRaw('GROUP_CONCAT(DISTINCT ht.name ORDER BY ht.id SEPARATOR ", ")');
            }, 'hashtag_names')
            // Lấy danh sách hashtag slugs
            ->selectSub(function ($q) {
                $q->from('post_hashtags as bh')
                    ->join('hash_tags as ht', 'bh.hashtag_id', '=', 'ht.id')
                    ->whereColumn('bh.post_id', 'posts.id')
                    ->selectRaw('GROUP_CONCAT(DISTINCT ht.slug ORDER BY ht.id SEPARATOR ", ")');
            }, 'hashtag_slugs');

        // Tổng lượt xem cho từng bài viết
        $query->selectSub(function ($q) {
            $q->from('post_views as pv')
                ->whereColumn('pv.post_id', 'posts.id')
                ->selectRaw('COUNT(*)');
        }, 'view_count');

        return $query;
    }

    public function filterData($grid)
    {
        $request = request();
        $createdAt = $request->input('created_at');
        $categoryId = $request->input('category_id');
        $status = $request->input('status');
        $title = $request->input('title');

        if ($createdAt) {
            // Convert from d/m/Y to Y-m-d
            $date = \DateTime::createFromFormat('d/m/Y', $createdAt);
            $formattedDate = $date ? $date->format('Y-m-d') : null;
            if ($formattedDate) {
                $grid->whereDate('posts.created_at', $formattedDate);
            }
        }

        if ($categoryId) {
            $grid->where('posts.category_id', $categoryId);
        }

        if ($status) {
            $grid->where('posts.status', $status);
        }

        if ($title) {
            $grid->where('posts.title', 'like', '%'.$title.'%');
        }

        return $grid;
    }

    public function renderDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('checkbox_html', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$row->id.'" />';
            })
            ->addColumn('title_html', function ($row) {
                $thumbnail = thumb_path($row->thumbnail);
                $title = $row->title;
                $editUrl = route('admin.posts.edit', $row->id);
                $categoryName = $row->category_name;

                return '
                    <div class="d-flex justify-content-start align-items-center product-name">
                        <div class="avatar-wrapper">
                            <div class="avatar avatar-xl me-2 rounded-2 bg-label-secondary">
                                <a href="'.$thumbnail.'" data-lightbox="post-thumbnails" data-title="'.$title.'" class="d-block h-100">
                                    <img src="'.$thumbnail.'" alt="'.$title.'" class="rounded-2 img-fluid object-fit-cover">
                                </a>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <a href="'.$editUrl.'" class="text-body fw-bold text-nowrap mb-0" title="'.$title.'">'.Str::limit($title, 30).'</a>
                            <small class="text-muted text-truncate d-none d-sm-block">Danh mục: '.$categoryName.'</small>
                        </div>
                    </div>
                ';
            })

            ->addColumn('status_html', function ($row) {
                $status = $row->status;
                $scheduledAt = isset($row->scheduled_at) ? $row->scheduled_at : null;

                if ($status == 'published') {
                    return '<span class="badge bg-label-success">Xuất bản</span>';
                } elseif ($status == 'scheduled' && $scheduledAt && Carbon::parse($scheduledAt) > now()) {
                    $scheduledDate = Carbon::parse($scheduledAt)->format('d/m/Y H:i');

                    return '<span class="badge bg-label-warning">Lên lịch</span> <small class="text-muted d-block">'.$scheduledDate.'</small>';
                } else {
                    return '<span class="badge bg-label-danger">Bản nháp</span>';
                }
            })

            ->addColumn('allow_comment_html', function ($row) {
                $isComment = $row->allow_comment;

                return $isComment == 1 ? '<span class="badge bg-label-success">Bật</span>' : '<span class="badge bg-label-danger">Tắt</span>';
            })

            ->addColumn('view_count_html', function ($row) {
                $viewCount = (int) ($row->view_count ?? 0);

                return '<span class="badge bg-label-primary">'.number_format($viewCount).'</span>';
            })

            ->addColumn('created_at_html', function ($row) {
                $createdAt = $row->created_at;

                return '<span class="text-muted">'.$createdAt->format('d/m/Y H:i').'</span>';
            })

            ->addColumn('action_html', function ($row) {
                $editUrl = route('admin.posts.edit', $row->id);
                $deleteUrl = route('admin.posts.destroy', $row->id);
                $previewUrl = route('client.post', $row->slug ?? $row->id);
                $title = $row->title;

                return '
                        <div class="d-inline-block text-nowrap">
                            <a href="'.$previewUrl.'" target="_blank" class="btn btn-sm btn-icon" title="Xem trước">
                                <i class="bx bx-show"></i>
                            </a>
                            <a href="'.$editUrl.'" class="btn btn-sm btn-icon text-warning" title="Chỉnh sửa">
                                <i class="bx bx-edit"></i>
                            </a>

                            <button type="button" class="btn btn-sm btn-icon text-danger btn-delete" title="Xóa"
                                data-url="'.$deleteUrl.'" data-title="'.htmlspecialchars($title).'">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    ';
            })

            ->rawColumns(['checkbox_html', 'title_html', 'status_html', 'allow_comment_html', 'created_at_html', 'action_html', 'view_count_html'])
            ->make(true);
    }

    /**
     * Get trashed data for DataTables
     */
    public function gridTrashedData()
    {
        $query = $this->model::onlyTrashed();
        $query->select([
            'posts.id',
            'posts.thumbnail as thumbnail',
            'posts.title as title',
            'posts.slug as slug',
            'posts.status as status',
            'posts.category_id as category_id',
            'posts.allow_comment as allow_comment',
            'posts.created_at as created_at',
            'posts.deleted_at as deleted_at',
            'categories.name as category_name',
        ])
            ->leftJoin('categories', 'posts.category_id', '=', 'categories.id');

        // Tổng lượt xem cho từng bài viết
        $query->selectSub(function ($q) {
            $q->from('post_views as pv')
                ->whereColumn('pv.post_id', 'posts.id')
                ->selectRaw('COUNT(*)');
        }, 'view_count');

        return $query;
    }

    /**
     * Render DataTables for trashed posts
     */
    public function renderTrashedDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('title_html', function ($row) {
                $thumbnail = thumb_path($row->thumbnail);
                $title = $row->title;

                return '
                    <div class="d-flex justify-content-start align-items-center product-name">
                        <div class="avatar-wrapper">
                            <div class="avatar avatar-xl me-2 rounded-2 bg-label-secondary">
                                <a href="'.$thumbnail.'" data-lightbox="post-thumbnails" data-title="'.$title.'" class="d-block h-100">
                                    <img src="'.$thumbnail.'" alt="'.$title.'" class="rounded-2 img-fluid object-fit-cover">
                                </a>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-body fw-bold text-nowrap mb-0" title="'.$title.'">'.Str::limit($title, 30).'</span>
                            <small class="text-muted text-truncate d-none d-sm-block">Danh mục: '.$row->category_name.'</small>
                        </div>
                    </div>
                ';
            })
            ->addColumn('status_html', function ($row) {
                $status = $row->status;
                if ($status == 'published') {
                    return '<span class="badge bg-label-success">Xuất bản</span>';
                } elseif ($status == 'scheduled') {
                    return '<span class="badge bg-label-warning">Lên lịch</span>';
                } else {
                    return '<span class="badge bg-label-danger">Bản nháp</span>';
                }
            })
            ->addColumn('allow_comment_html', function ($row) {
                $isComment = $row->allow_comment;

                return $isComment == 1 ? '<span class="badge bg-label-success">Bật</span>' : '<span class="badge bg-label-danger">Tắt</span>';
            })
            ->addColumn('view_count_html', function ($row) {
                $viewCount = (int) ($row->view_count ?? 0);

                return '<span class="badge bg-label-primary">'.number_format($viewCount).'</span>';
            })
            ->addColumn('deleted_at_html', function ($row) {
                $deletedAt = $row->deleted_at;

                return '<span class="text-muted">'.$deletedAt->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('checkbox_html', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$row->id.'" />';
            })
            ->addColumn('action_html', function ($row) {
                $restoreUrl = route('admin.posts.restore', $row->id);
                $forceDeleteUrl = route('admin.posts.forceDelete', $row->id);
                $title = $row->title;

                return '
                    <div class="d-inline-block text-nowrap">
                        <button type="button" class="btn btn-sm btn-icon btn-success btn-restore" title="Khôi phục"
                            data-url="'.$restoreUrl.'" data-title="'.htmlspecialchars($title).'">
                            <i class="bx bx-undo"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-icon text-danger btn-force-delete" title="Xóa vĩnh viễn"
                            data-url="'.$forceDeleteUrl.'" data-title="'.htmlspecialchars($title).'">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['checkbox_html', 'title_html', 'status_html', 'allow_comment_html', 'deleted_at_html', 'action_html', 'view_count_html'])
            ->make(true);
    }

    /**
     * Restore a trashed post
     */
    public function restore($id)
    {
        $post = $this->model::withTrashed()->find($id);
        if ($post && $post->trashed()) {
            return $post->restore();
        }

        return false;
    }

    /**
     * Force delete a post
     */
    public function forceDelete($id)
    {
        $post = $this->model::withTrashed()->find($id);
        if ($post && $post->trashed()) {
            return $post->forceDelete();
        }

        return false;
    }

    /**
     * Bulk restore posts
     */
    public function bulkRestore(array $ids)
    {
        return $this->model::withTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->restore();
    }

    /**
     * Bulk delete posts (soft delete)
     */
    public function bulkDelete(array $ids)
    {
        return $this->model::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->delete();
    }

    /**
     * Bulk force delete posts
     */
    public function bulkForceDelete(array $ids)
    {
        return $this->model::withTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->forceDelete();
    }

    /**
     * Bulk move posts to a category (only active posts)
     */
    public function bulkMoveCategory(array $ids, int $categoryId)
    {
        return $this->model::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update(['category_id' => $categoryId]);
    }

    /**
     * Summary of getPostById
     *
     * @param  mixed  $id
     */
    public function getPostById($id)
    {
        return $this->gridData()->where('posts.id', $id)->first();
    }

    /**
     * Tạo post mới kèm hashtags
     *
     * @return Post
     */
    public function createWithHashtags(array $data, ?array $hashtagIds = null)
    {
        return DB::transaction(function () use ($data, $hashtagIds) {
            $post = $this->create($data);

            if ($hashtagIds && ! empty($hashtagIds)) {
                $this->attachHashtags($post->id, $hashtagIds);
            }

            // Gửi email thông báo nếu bài viết được publish
            if ($post->status === $this->model::STATUS_PUBLISHED) {
                // event(new \App\Events\PostPublished($post));
            }

            return $post;
        });
    }

    /**
     * Cập nhật post kèm hashtags
     *
     * @param  int  $id
     * @return Post|null
     */
    public function updateWithHashtags($id, array $data, ?array $hashtagIds = null)
    {
        return DB::transaction(function () use ($id, $data, $hashtagIds) {
            $existingPost = $this->findById($id);
            $wasPublished = $existingPost && $existingPost->status === $this->model::STATUS_PUBLISHED;

            $post = $this->update($id, $data);

            if ($post) {
                // Xóa tất cả hashtags cũ
                $this->detachAllHashtags($post->id);

                // Gắn hashtags mới nếu có
                if ($hashtagIds && ! empty($hashtagIds)) {
                    $this->attachHashtags($post->id, $hashtagIds);
                }

                // Gửi email thông báo nếu bài viết vừa được publish (từ draft/scheduled sang published)
                if ($post->status === $this->model::STATUS_PUBLISHED && ! $wasPublished) {
                    // event(new \App\Events\PostPublished($post));
                }
            }

            return $post;
        });
    }

    /**
     * Gắn hashtags vào post
     *
     * @param  int  $postId
     * @return void
     */
    public function attachHashtags($postId, array $hashtagIds)
    {
        PostHashtag::attachHashtagsToPost($hashtagIds, $postId);
    }

    /**
     * Xóa tất cả hashtags của post
     *
     * @param  int  $postId
     * @return bool|null
     */
    public function detachAllHashtags($postId)
    {
        return PostHashtag::deleteByPostId($postId);
    }
}
