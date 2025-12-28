<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\NewsletterRepository;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    protected $newsletterRepository;

    public function __construct(NewsletterRepository $newsletterRepository)
    {
        $this->newsletterRepository = $newsletterRepository;
    }

    public function list()
    {
        $statusLabels = $this->newsletterRepository->getStatusLabel();

        return view('admin.modules.newsletter.list', compact('statusLabels'));
    }

    public function ajaxGetData(Request $request)
    {
        $grid = $this->newsletterRepository->gridData();
        $data = $this->newsletterRepository->filterData($grid);

        return $this->newsletterRepository->renderDataTables($data);
    }

    public function destroy($id)
    {
        try {
            $newsletter = $this->newsletterRepository->findById($id);

            if (! $newsletter) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email đăng ký không tồn tại.',
                ], 404);
            }

            $this->newsletterRepository->delete($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa email đăng ký thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa email đăng ký.',
            ], 500);
        }
    }
}
