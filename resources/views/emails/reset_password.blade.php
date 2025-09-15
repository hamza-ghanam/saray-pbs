<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fafafa;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header img {
            max-height: 80px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #2c3e50;
        }

        .content {
            font-size: 16px;
        }

        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #999;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 6px;
            text-decoration: none;
            background: #1E90FF;
            color: #fff;
            font-weight: bold;
        }

        .btn:visited {
            color: #fff;
        }

        blockquote {
            margin: 12px 0;
            padding: 12px 16px;
            background: #fff;
            border-left: 4px solid #2c3e50;
            word-break: break-word;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img
            src="{{ asset('images/saray_logo.png') }}"
            alt="{{ config('app.name') }} Logo"
            style="max-width: 200px; height: auto; display: inline-block; margin-bottom: 15px;"
        >
        <h1>Password Reset Request</h1>
    </div>

    <div class="content">
        <p>Hello {{ $user->name ?? 'User' }},</p>

        <p>We received a request to reset the password for your <strong>{{ config('app.name') }}</strong> account.</p>

        <p style="text-align:center; margin: 22px 0;">
            <a class="btn" href="{{ $resetUrl }}">Reset Password</a>
        </p>

        <p>If the button above doesn't work, copy and paste the following link into your browser:</p>
        <blockquote>{{ $resetUrl }}</blockquote>

        <p>If you did not request a password reset, please ignore this email.
            Your password will remain unchanged.</p>

        <p>Best regards,<br>{{ config('app.name') }} Team</p>
    </div>

    <div class="footer">
        &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
    </div>
</div>
</body>
</html>
