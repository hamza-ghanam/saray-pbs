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
            max-width: 200px;
            height: auto;
            display: inline-block;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #2c3e50;
        }
        .content {
            font-size: 16px;
        }
        .detail-box {
            background-color: #f0f4f8;
            border-left: 4px solid #2c3e50;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .detail-box p {
            margin: 5px 0;
        }
        .highlight {
            color: #2c3e50;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #999999;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="{{ asset('images/app_logo.png') }}" alt="{{ config('app.name') }} Logo">
        <h1>Payment Reminder</h1>
    </div>

    <div class="content">
        <p>Dear <span class="highlight">{{ $customer->name_en ?? 'Customer' }}</span>,</p>

        <p>
            This is a friendly reminder that you have an upcoming installment payment due in
            <span class="highlight">7 days</span>.
        </p>

        <div class="detail-box">
            <p><strong>Project:</strong> {{ $installment->booking->unit->building->name }}</p>
            <p><strong>Unit No.:</strong> {{ $installment->booking->unit->unit_no }}</p>
            <p><strong>Installment:</strong> {{ $installment->description }}</p>
            <p><strong>Due Date:</strong> {{ $installment->date->format('d-M-Y') }}</p>
            <p><strong>Amount Due:</strong> AED {{ number_format($installment->remaining_amount, 2) }}</p>
        </div>

        <p>
            Please ensure your payment is processed before the due date to avoid any delays.
            If you have already made this payment, kindly disregard this reminder.
        </p>

        <p>
            For any questions or assistance, please don't hesitate to contact our sales team.
        </p>

        <p>
            Best regards,<br>
            <strong>Sales Team</strong>
        </p>
    </div>

    <div class="footer">
        &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
    </div>
</div>
</body>
</html>
