<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Contact\StoreRequest;
use App\Jobs\SendContactNotificationEmail;
use App\Models\Setting;
use App\Repositories\ContactRepository;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class ContactController extends Controller
{
    protected $contactRepository;

    public function __construct(ContactRepository $contactRepository)
    {
        $this->contactRepository = $contactRepository;
    }

    public function contact()
    {
        $seoModel = new SEOData(
            title: 'Liên hệ',
            description: 'Liên hệ với chúng tôi để được hỗ trợ, đặt câu hỏi hoặc chia sẻ ý kiến của bạn. Chúng tôi luôn sẵn sàng lắng nghe!',
            url: route('client.contact', [], false),
            type: 'website',
            robots: 'noindex, follow',
        );

        // Get contact information from settings
        $contactInfo = [
            'email' => Setting::getValue('email', ''),
            'phone' => Setting::getValue('phone', ''),
            'address' => Setting::getValue('address', ''),
        ];

        return view('client.pages.contact', compact('seoModel', 'contactInfo'));
    }

    /**
     * Xử lý form liên hệ
     */
    public function submitContact(StoreRequest $request)
    {
        try {
            $validated = $request->validated();

            $contact = $this->contactRepository->create([
                'full_name' => $validated['fullname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'status' => ContactRepository::STATUS_PENDING,
            ]);

            SendContactNotificationEmail::dispatch($contact);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Gửi tin nhắn thành công! Chúng tôi sẽ phản hồi sớm nhất có thể.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
            ], 500);
        }
    }
}
