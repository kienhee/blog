<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Jobs\SendContactNotificationEmail;
use App\Models\Contact;
use App\Repositories\ContactRepository;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function contact()
    {
        return view('client.pages.contact');
    }

    /**
     * Xử lý form liên hệ
     */
    public function submitContact(Request $request)
    {
        try {
            $validated = $request->validate([
                'fullname' => 'required|string|min:2|max:255',
                'email' => 'required|email|max:255',
                'phone' => [
                    'required',
                    'string',
                    'size:10',
                    'regex:/^(032|033|034|035|036|037|038|039|086|096|097|098|081|082|083|084|085|088|091|094|056|058|092|070|076|077|078|079|089|090|093|099|059)[0-9]{7}$/',
                ],
                'subject' => 'required|string|min:3|max:255',
                'message' => 'required|string|min:10|max:2000',
            ], [
                'fullname.required' => 'Vui lòng nhập họ và tên.',
                'fullname.min' => 'Họ và tên phải có ít nhất 2 ký tự.',
                'fullname.max' => 'Họ và tên không được vượt quá 255 ký tự.',
                'email.required' => 'Vui lòng nhập email.',
                'email.email' => 'Email không đúng định dạng.',
                'email.max' => 'Email không được vượt quá 255 ký tự.',
                'phone.required' => 'Vui lòng nhập số điện thoại.',
                'phone.size' => 'Số điện thoại phải có đúng 10 số.',
                'phone.regex' => 'Số điện thoại không hợp lệ.',
                'subject.required' => 'Vui lòng nhập tiêu đề.',
                'subject.min' => 'Tiêu đề phải có ít nhất 3 ký tự.',
                'subject.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
                'message.required' => 'Vui lòng nhập tin nhắn.',
                'message.min' => 'Tin nhắn phải có ít nhất 10 ký tự.',
                'message.max' => 'Tin nhắn không được vượt quá 2000 ký tự.',
            ]);

            // Tạo contact mới với status mặc định là PENDING (0)
            $contact = Contact::create([
                'full_name' => $validated['fullname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'status' => ContactRepository::STATUS_PENDING,
            ]);

            // Gửi email thông báo đến admin qua queue
            SendContactNotificationEmail::dispatch($contact);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Gửi tin nhắn thành công! Chúng tôi sẽ phản hồi sớm nhất có thể.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'message' => 'Vui lòng kiểm tra lại thông tin đã nhập.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.' . $e->getMessage(),
            ], 500);
        }
    }
}
