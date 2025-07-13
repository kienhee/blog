<!DOCTYPE html>
<html lang="en" style="background: #181818;">
<head>
    <meta charset="UTF-8">
    <title>New Contact Message</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Be Vietnam Pro', 'Montserrat', 'Roboto', Arial, sans-serif;
            background: #181818;
            color: #fff5e6;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 520px;
            margin: 32px auto;
            background: #232323;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 32px 28px 24px 28px;
        }
        .email-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .email-header h2 {
            color: #ff8800;
            font-size: 1.7rem;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: 1px;
        }
        .email-header p {
            color: #fff5e6;
            font-size: 1rem;
            margin: 0;
        }
        .email-content {
            background: #181818;
            border-radius: 8px;
            padding: 20px 18px;
            margin-bottom: 18px;
        }
        .email-content strong {
            color: #ff8800;
        }
        .email-content p {
            margin: 8px 0;
            font-size: 1.05rem;
            color: #fff5e6;
        }
        .email-content .msg-box {
            background: #232323;
            border-radius: 6px;
            padding: 12px 14px;
            margin: 8px 0 0 0;
            color: #fff5e6;
            border: 1px solid #ff8800;
        }
        .email-footer {
            text-align: center;
            color: #ff8800;
            font-size: 0.95rem;
            margin-top: 18px;
        }
        .email-btn {
            display: inline-block;
            background: #ff8800;
            color: #181818 !important;
            padding: 10px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 18px;
            font-size: 1.08rem;
        }
        @media (max-width: 600px) {
            .email-container {
                padding: 12px 2vw 10px 2vw;
                margin: 10px auto;
            }
            .email-header h2 {
                font-size: 1.2rem;
            }
            .email-content {
                padding: 12px 6px;
            }
            .email-content p, .email-content .msg-box {
                font-size: 0.98rem;
            }
        }
        /* Light mode for email clients that support prefers-color-scheme */
        @media (prefers-color-scheme: light) {
            body {
                background: #f8f9fa !important;
                color: #232323 !important;
            }
            .email-container {
                background: #fff !important;
                color: #232323 !important;
            }
            .email-header h2 {
                color: #ff8800 !important;
            }
            .email-header p {
                color: #232323 !important;
            }
            .email-content {
                background: #f5f7fa !important;
            }
            .email-content strong {
                color: #ff8800 !important;
            }
            .email-content p, .email-content .msg-box {
                color: #232323 !important;
            }
            .email-content .msg-box {
                background: #fff !important;
                border: 1px solid #ff8800 !important;
            }
            .email-footer {
                color: #ff8800 !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2>ZenBlog - New Contact Message</h2>
            <p>You have received a new contact message from your website.</p>
        </div>
        <div class="email-content">
            <p><strong>Name:</strong> {{ $contact->name }}</p>
            <p><strong>Email:</strong> {{ $contact->email }}</p>
            <p><strong>Subject:</strong> {{ $contact->subject }}</p>
            <p><strong>Message:</strong></p>
            <div class="msg-box">{{ $contact->message }}</div>
        </div>
        <div class="email-footer">
            <p>This email was sent automatically from <a href="{{ url('/') }}" style="color:#ff8800;text-decoration:underline;">ZenBlog</a>.</p>
        </div>
    </div>
</body>
</html>
