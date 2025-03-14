<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales and Purchase Agreement</title>
    <style>
        /* Basic PDF styling */
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
            font-size: 14px;
            color: #333;
        }
        h1, h2, h3 {
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 20px;
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
</head>
<body>

<h1>Sales and Purchase Agreement (SPA)</h1>

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
        <!-- Add more fields as needed (floor, total_area, etc.) -->
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
        <p><span class="label">Booking Percentage:</span><span class="value">{{ $paymentPlan->booking_percentage }}%</span></p>
        <p><span class="label">DLD Fee Percentage:</span><span class="value">{{ $paymentPlan->dld_fee_percentage }}%</span></p>
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

</body>
</html>
