<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Offer</title>
    <style>
        /* Basic styling for the PDF */
        body { font-family: Arial, sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 8px; text-align: left; }
    </style>
</head>
<body>

<h1>Reservation Form</h1>

<!-- Booking Details -->
<div class="section">
    <h2>Booking Information</h2>
    <p><span class="label">Booking ID:</span><span class="value">{{ $booking->id }}</span></p>
    <p><span class="label">Booking Status:</span><span class="value">{{ $booking->status }}</span></p>
</div>

<!-- Unit Details -->
<div class="section">
    <h2>Unit Information</h2>
    @if($unit)
        <p><span class="label">Unit #:</span><span class="value">{{ $unit->unit_no }}</span></p>
        <p><span class="label">Unit Status:</span><span class="value">{{ $unit->status }}</span></p>
        <p><span class="label">Price:</span><span class="value">{{ number_format($unit->price, 2) }}</span></p>
        <!-- Add more unit fields as needed -->
    @else
        <p>No unit data available.</p>
    @endif
</div>

<!-- Customer Info -->
<div class="section">
    <h2>Customer Information</h2>
    @if($customerInfo)
        <p><span class="label">Name:</span><span class="value">{{ $customerInfo->name }}</span></p>
        <p><span class="label">Passport Number:</span><span class="value">{{ $customerInfo->passport_number }}</span></p>
        <p><span class="label">Birth Date:</span><span class="value">{{ $customerInfo->birth_date }}</span></p>
        <p><span class="label">Nationality:</span><span class="value">{{ $customerInfo->nationality }}</span></p>
        <!-- Add more customer fields as needed -->
    @else
        <p>No customer data available.</p>
    @endif
</div>

<!-- Payment Plan -->
<div class="section">
    <h2>Payment Plan</h2>
    @if($paymentPlan)
        <p><span class="label">Plan Name:</span><span class="value">{{ $paymentPlan->name }}</span></p>
        <p><span class="label">Selling Price:</span><span class="value">{{ number_format($paymentPlan->selling_price, 2) }}</span></p>
        <p><span class="label">DLD Fee Percentage:</span><span class="value">{{ $paymentPlan->dld_fee_percentage }}%</span></p>
        <p><span class="label">Booking Percentage:</span><span class="value">{{ $paymentPlan->booking_percentage }}%</span></p>
        <!-- Add more payment plan fields or installments as needed -->
    @else
        <p>No payment plan assigned.</p>
    @endif
</div>

<!-- Signature / Footer -->
<div class="section">
    <h3>Signatures</h3>
    <p>Sales Signature: __________________________</p>
    <p>Customer Signature: ________________________</p>
</div>

</body>
</html>
