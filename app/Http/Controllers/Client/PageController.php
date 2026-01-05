<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Newsletter\SubscribeRequest;
use App\Models\Setting;
use App\Repositories\NewsletterRepository;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class PageController extends Controller
{
    protected $newsletterRepository;

    public function __construct(NewsletterRepository $newsletterRepository)
    {
        $this->newsletterRepository = $newsletterRepository;
    }

    public function about()
    {
        $seoModel = new SEOData(
            title: 'Về chúng tôi',
            description: 'Tìm hiểu về chúng tôi và sứ mệnh chia sẻ kiến thức, kinh nghiệm về lập trình và công nghệ đến cộng đồng.',
            url: route('client.about', [], false),
            type: 'website',
        );

        // Get social media links from settings
        $socialLinks = [
            'github' => Setting::getValue('github', ''),
            'linkedin' => Setting::getValue('linkedin', ''),
            'facebook' => Setting::getValue('facebook', ''),
            'twitter' => Setting::getValue('twitter', ''),
            'email' => Setting::getValue('email', ''),
        ];

        return view('client.pages.about', compact('seoModel', 'socialLinks'));
    }

    /**
     * Xử lý đăng ký newsletter với bot detection
     */
    public function subscribeNewsletter(SubscribeRequest $request)
    {
        try {
            $validated = $request->validated();

            $result = $this->newsletterRepository->subscribe(
                $validated['email'],
                $validated['scroll_percentage'] ?? 0,
                $validated['time_on_page'] ?? 0
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
            ], 500);
        }
    }
}
