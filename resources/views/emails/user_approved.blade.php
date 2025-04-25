<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Approved</title>
</head>
<body style="font-family: sans-serif; line-height: 1.4; margin: 0; padding: 0;">
{{-- Logo centered --}}
<div style="text-align: center; padding: 20px 0;">
    <img
        src="{{ asset('images/saray_logo.png') }}"
        alt="{{ config('app.name') }} Logo"
        style="max-width: 200px; height: auto; display: inline-block;"
    >
</div>

<div style="padding: 0 20px;">
    <h1 style="text-align: center; margin-bottom: 0.5em;">Congratulations, {{ $user->name }}!</h1>

    <p>
        We’re pleased to let you know that your account has been <strong>approved</strong> and is now active.
    </p>

    <p>
        Click the link below to log in to the portal and get started:
    </p>

    <p style="text-align: center; margin: 30px 0;">
        <a
            href="{{ config('app.front_end_url') }}"
            style="
                    display: inline-block;
                    padding: 12px 20px;
                    text-decoration: none;
                    background: dodgerblue;
                    color: #ffffff;
                    border-radius: 4px;
                    font-weight: bold;
                "
        >
            Log in to your account
        </a>
    </p>

    <p>Thanks for joining us,<br>{{ config('app.name') }} Team</p>
</div>
</body>
</html>
