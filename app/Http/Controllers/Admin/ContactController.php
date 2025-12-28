<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Contact\ReplyRequest;
use App\Mail\ContactReplyMail;
use App\Models\Contact;
use App\Models\ContactReply;
use App\Repositories\ContactRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    protected $contactRepository;

    public function __construct(ContactRepository $contactRepository)
    {
        $this->contactRepository = $contactRepository;
    }

    public function list()
    {
        $statusLabels = $this->contactRepository->getStatusLabel();
        return view('admin.modules.contact.list', compact('statusLabels'));
    }

    public function ajaxGetData(Request $request)
    {
        $grid = $this->contactRepository->gridData();
        $data = $this->contactRepository->filterData($grid);
        return $this->contactRepository->renderDataTables($data);
    }

    public function countPending()
    {
        $count = $this->contactRepository->countPending();
        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    public function show($id)
    {
        $contact = $this->contactRepository->findById($id);

        if (! $contact) {
            return response()->json([
                'status' => false,
                'message' => 'Liên hệ không tồn tại.',
            ], 404);
        }

        // Load replies với user information
        $contact->load(['replies.user']);

        $replies = $contact->replies->map(function ($reply) {
            return [
                'id' => $reply->id,
                'subject' => $reply->subject,
                'message' => $reply->message,
                'user_name' => $reply->user ? $reply->user->full_name ?? $reply->user->email : 'Hệ thống',
                'created_at' => $reply->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $contact->id,
                'full_name' => $contact->full_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'subject' => $contact->subject,
                'message' => $contact->message,
                'status' => $contact->status,
                'created_at' => $contact->created_at->format('d/m/Y H:i'),
                'updated_at' => $contact->updated_at->format('d/m/Y H:i'),
                'replies' => $replies,
            ],
        ]);
    }

    public function reply(ReplyRequest $request, $id)
    {
        try {
            $contact = $this->contactRepository->findById($id);

            if (! $contact) {
                return response()->json([
                    'status' => false,
                    'message' => 'Liên hệ không tồn tại.',
                ], 404);
            }

            DB::beginTransaction();

            // Tạo reply record
            $reply = ContactReply::create([
                'contact_id' => $contact->id,
                'user_id' => Auth::id(),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
            ]);

            // Gửi email cho khách hàng
            try {
                Mail::to($contact->email)->send(new ContactReplyMail($contact, $reply));
            } catch (\Exception $e) {
                // Log error nhưng không rollback transaction
                Log::error('Failed to send contact reply email', [
                    'contact_id' => $contact->id,
                    'reply_id' => $reply->id,
                    'error' => 'Có lỗi xảy ra khi gửi email',
                ]);
            }

            // Cập nhật status của contact thành "Đã trả lời email" khi gửi email
            if ($contact->status === ContactRepository::STATUS_PENDING ||
                $contact->status === ContactRepository::STATUS_CONTACTED) {
                $contact->status = ContactRepository::STATUS_REPLIED;
                $contact->save();
            }

            DB::commit();

            // Load lại contact với replies để trả về
            $contact->load(['replies.user']);
            $replies = $contact->replies->map(function ($reply) {
                return [
                    'id' => $reply->id,
                    'subject' => $reply->subject,
                    'message' => $reply->message,
                    'user_name' => $reply->user ? $reply->user->full_name ?? $reply->user->email : 'Hệ thống',
                    'created_at' => $reply->created_at->format('d/m/Y H:i'),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Gửi trả lời thành công',
                'data' => [
                    'reply' => [
                        'id' => $reply->id,
                        'subject' => $reply->subject,
                        'message' => $reply->message,
                        'user_name' => Auth::user() ? (Auth::user()->full_name ?? Auth::user()->email) : 'Hệ thống',
                        'created_at' => $reply->created_at->format('d/m/Y H:i'),
                    ],
                    'replies' => $replies,
                    'contact_status' => $contact->status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra',
            ], 500);
        }
    }

    public function changeStatus($id, $status)
    {
        $contact = $this->contactRepository->findById($id);

        if (! $contact) {
            return response()->json([
                'status' => false,
                'message' => 'Liên hệ không tồn tại.',
            ], 404);
        }

        // Validate status
        $validStatuses = [
            ContactRepository::STATUS_PENDING,
            ContactRepository::STATUS_CONTACTED,
            ContactRepository::STATUS_REPLIED,
            ContactRepository::STATUS_SPAM,
        ];

        if (! in_array($status, $validStatuses)) {
            return response()->json([
                'status' => false,
                'message' => 'Trạng thái không hợp lệ.',
            ], 400);
        }

        $contact->status = $status;
        $contact->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái liên hệ thành công',
        ]);
    }
}
