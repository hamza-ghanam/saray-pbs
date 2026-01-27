<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
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
            color: #999999;
            text-align: center;
        }

        .highlight {
            color: #2c3e50;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            text-decoration: none;
            background: dodgerblue;
            color: #ffffff;
            border-radius: 4px;
            font-weight: bold;
        }

        blockquote {
            margin: 12px 0;
            padding: 12px 16px;
            background: #fff;
            border-left: 4px solid #2c3e50;
            word-break: break-word;
        }

        .note {
            font-size: 14px;
            color: #666;
            margin-top: 16px;
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
        <h1>Signature Required</h1>
    </div>

    <div class="content">
        <p>
            Dear {{ $recipientName ?? 'Customer' }},
        </p>

        <p>
            Please review and sign the following document:
            <span class="highlight">{{ $documentTitle ?? 'Document' }}</span>.
        </p>

        <p class="note">
            This is a one-time link and will expire as soon as you signed the document and submit the request.</span>.
        </p>

        <p style="text-align:center; margin: 22px 0;">
            <a class="btn" href="{{ $signingUrl }}">
                Review &amp; Sign
            </a>
        </p>

        <p style="text-align:center; margin: 12px 0;">
            <a class="btn" href="{{ $downloadUrl }}">
                Download PDF
            </a>
        </p>

        <p>If the buttons above don't work, copy and paste the following links into your browser:</p>

        Review &amp; Sign:
        <blockquote cite="{{ $signingUrl }}">
            {{ $signingUrl }}
        </blockquote>

        Download PDF:
        <blockquote cite="{{ $signingUrl }}">
            {{ $signingUrl }}
        </blockquote>

        <p class="note">
            If you did not expect this email, you can safely ignore it.
        </p>

        <p>
            Best regards,<br>
            <strong>{{ $companyTeamName ?? 'Sales Team' }}</strong>
        </p>
    </div>

    <div class="footer">
        &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
    </div>
</div>
</body>
</html>