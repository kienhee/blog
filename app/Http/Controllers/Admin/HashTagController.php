<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HashTag\StoreRequest;
use App\Http\Requests\Admin\HashTag\UpdateRequest;
use App\Models\HashTag;
use App\Repositories\HashTagRepository;
use App\Support\ClientCacheHelper;
use Illuminate\Http\Request;

class HashTagController extends Controller
{
    protected $hashTagRepository;

    public function __construct(HashTagRepository $hashTagRepository)
    {
        $this->hashTagRepository = $hashTagRepository;
    }

    public function list()
    {
        return view('admin.modules.hashtag.list');
    }

    public function ajaxGetData()
    {
        $grid = $this->hashTagRepository->gridData();
        $data = $this->hashTagRepository->filterData($grid);

        return $this->hashTagRepository->renderDataTables($data);
    }

    public function ajaxGetTrashedData()
    {
        $grid = $this->hashTagRepository->gridTrashedData();
        $data = $this->hashTagRepository->filterData($grid);

        return $this->hashTagRepository->renderTrashedDataTables($data);
    }

    public function create()
    {
        return view('admin.modules.hashtag.create');
    }

    public function store(StoreRequest $request)
    {
        try {
            $hashtag = $this->hashTagRepository->create([
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
            ]);

            // Clear client cache
            if ($hashtag) {
                ClientCacheHelper::clearHashtagCache($hashtag->id);
            }

            return back()->with('success', 'Thêm mới thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    /**
     * Quick create hashtag from post module (via AJAX)
     * Returns JSON response for quick creation
     */
    public function quickStore(\App\Http\Requests\Admin\HashTag\QuickStoreRequest $request)
    {

        try {
            $hashtag = $this->hashTagRepository->create([
                'name' => $request->input('name'),
                'slug' => \Illuminate\Support\Str::slug($request->input('name')),
            ]);

            // Clear client cache
            if ($hashtag) {
                ClientCacheHelper::clearHashtagCache($hashtag->id);
            }

            return response()->json([
                'status' => true,
                'message' => 'Thêm hashtag thành công',
                'data' => $hashtag,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra',
            ], 500);
        }
    }

    public function edit($id)
    {
        $data = $this->hashTagRepository->findById($id);
        if (! $data) {
            return redirect()->route('admin.hashtags.list')->with('error', 'Hashtag không tồn tại');
        }

        return view('admin.modules.hashtag.edit', compact('data'));
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $this->hashTagRepository->update($id, [
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
            ]);

            // Clear client cache
            ClientCacheHelper::clearHashtagCache($id);

            return back()->with('success', 'Cập nhật thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    public function destroy($id)
    {
        try {
            $hashtag = $this->hashTagRepository->findById($id);
            if (! $hashtag) {
                return response()->json([
                    'status' => false,
                    'message' => 'Hashtag không tồn tại',
                ], 404);
            }

            $this->hashTagRepository->delete($id);

            // Clear client cache
            ClientCacheHelper::clearHashtagCache($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa hashtag thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa hashtag',
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $hashtag = HashTag::withTrashed()->find($id);
            if (! $hashtag || ! $hashtag->trashed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Hashtag không tồn tại trong thùng rác',
                ], 404);
            }

            $this->hashTagRepository->restore($id);

            // Clear client cache
            ClientCacheHelper::clearHashtagCache($id);

            return response()->json([
                'status' => true,
                'message' => 'Khôi phục hashtag thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục hashtag',
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $hashtag = HashTag::withTrashed()->find($id);
            if (! $hashtag || ! $hashtag->trashed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Hashtag không tồn tại trong thùng rác',
                ], 404);
            }

            $this->hashTagRepository->forceDelete($id);

            // Clear client cache
            ClientCacheHelper::clearHashtagCache($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn hashtag thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn hashtag',
            ], 500);
        }
    }

    public function bulkRestore(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một hashtag',
                ], 400);
            }

            $count = $this->hashTagRepository->bulkRestore($ids);

            // Clear client cache
            foreach ($ids as $id) {
                ClientCacheHelper::clearHashtagCache($id);
            }

            return response()->json([
                'status' => true,
                'message' => "Đã khôi phục {$count} hashtag thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục',
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
                    'message' => 'Vui lòng chọn ít nhất một hashtag',
                ], 400);
            }

            $count = $this->hashTagRepository->bulkDelete($ids);

            // Clear client cache
            foreach ($ids as $id) {
                ClientCacheHelper::clearHashtagCache($id);
            }

            return response()->json([
                'status' => true,
                'message' => "Đã xóa {$count} hashtag thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa',
            ], 500);
        }
    }

    public function bulkForceDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một hashtag',
                ], 400);
            }

            $count = $this->hashTagRepository->bulkForceDelete($ids);

            // Clear client cache
            foreach ($ids as $id) {
                ClientCacheHelper::clearHashtagCache($id);
            }

            return response()->json([
                'status' => true,
                'message' => "Đã xóa vĩnh viễn {$count} hashtag thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn',
            ], 500);
        }
    }

    public function search(Request $request)
    {
        $query = HashTag::query();

        // Tìm kiếm theo tên nếu có
        if ($request->has('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }

        $hashtags = $query->paginate(20);

        return response()->json([
            'data' => $hashtags->items(),
            'total' => $hashtags->total(),
        ]);
    }
}
