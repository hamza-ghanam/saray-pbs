<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 150px 45px 100px 45px;
            size: A4 portrait;
        }

        body {
            font-family: DejaVu Sans, sans-serif; font-size: 14px;
        }

        header {
            position: fixed;
            /* shift it up by exactly its own height so its bottom edge lands at the top of the page */
            top: -1in;
            left: -45px;
            width: calc(100% + 45px);
            /* make it 0.8in tall */
            height: 0.8in;
            line-height: 35px;
        }

        footer {
            position: fixed;
            bottom: -65px;
            left:   -45px;   /* pull into the left margin */
            right:  -45px;   /* pull into the right margin */
            height: 50px;
            text-align: center;
            line-height: 35px;
        }

        .footer-bar {
            background-color: #404040;
        }

        h1, h2, h3 {
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
        }
        .value {
            margin-left: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            text-align: left;
            padding: 6px;
            border: 1px solid #ccc;
        }
    </style>
    <title>SPA</title>
</head>
<body>
<!-- TODO: Customize the final PDF file -->
<header>
    <img src="{{ public_path('images/Saray_Header.png') }}" alt="Company Header" style="width: 50%;">
</header>

<footer>
    <img src="{{ public_path('images/tail_img.png') }}" alt="Company Footer" style="width: 95%;">
    <div class="footer-bar">&nbsp;</div>
</footer>

<main>

    <h1 style="text-align: center;">Sales and Purchase Agreement (SPA)</h1>

    <!-- Booking Section -->
    <div class="section">
        <h2>Booking Details</h2>
        @if($booking)
            <p><span class="label">Booking ID:</span><span class="value">{{ $booking->id }}</span></p>
            <p><span class="label">Booking Status:</span><span class="value">{{ $booking->status }}</span></p>
        @else
            <p>No booking data available.</p>
        @endif
    </div>

    <!-- Unit Section -->
    <div class="section">
        <h2>Unit Information</h2>
        @if($unit)
            <p><span class="label">Unit #:</span><span class="value">{{ $unit->unit_no }}</span></p>
            <p><span class="label">Unit Type:</span><span class="value">{{ $unit->unit_type }}</span></p>
            <p><span class="label">Status:</span><span class="value">{{ $unit->status }}</span></p>
            <p><span class="label">Price:</span><span class="value">{{ number_format($unit->price, 2) }}</span></p>
            <!-- Add more fields as needed (floor, total_square, etc.) -->
        @else
            <p>No unit data available.</p>
        @endif
    </div>

    <!-- Customer Info Section -->
    <div class="section">
        <h2>Customer Information</h2>
        @if($customerInfo)
            <p><span class="label">Name:</span><span class="value">{{ $customerInfo->name }}</span></p>
            <p><span class="label">Passport Number:</span><span class="value">{{ $customerInfo->passport_number }}</span></p>
            <p><span class="label">Birth Date:</span><span class="value">{{ $customerInfo->birth_date }}</span></p>
            <p><span class="label">Nationality:</span><span class="value">{{ $customerInfo->nationality }}</span></p>
            <!-- Add or remove fields as needed -->
        @else
            <p>No customer data available.</p>
        @endif
    </div>

    <!-- Payment Plan Section -->
    <div class="section">
        <h2>Payment Plan</h2>
        @if($paymentPlan)
            <p><span class="label">Plan Name:</span><span class="value">{{ $paymentPlan->name }}</span></p>
            <p><span class="label">Selling Price:</span><span class="value">{{ number_format($paymentPlan->selling_price, 2) }}</span></p>
            @if($booking->discount > 0)
                <p><span class="label">Discount:</span> {{ $booking->discount }}%</p>
                <p><span class="label">Effective Price:</span> AED {{ number_format($booking->price, 2) }}</p>
            @endif
            <p><strong>DLD Fee:</strong> {{ (int) $paymentPlan->dld_fee_percentage }}% | AED {{ number_format($paymentPlan->dld_fee, 2) }}</p>
            <!-- Add more payment plan details or installments if needed -->
        @else
            <p>No payment plan assigned.</p>
        @endif
    </div>

    <!-- Signatures Section -->
    <div class="section">
        <h2>Signatures</h2>
        <p>Seller Signature: __________________________</p>
        <p>Buyer Signature: __________________________</p>
    </div>

    <!-- Additional Terms / Conditions -->
    <div class="section">
        <h3>Terms &amp; Conditions</h3>
        <p>Here you can include any legal text or disclaimers regarding the sale and purchase agreement.</p>
    </div>
</main>
</body>
</html>
