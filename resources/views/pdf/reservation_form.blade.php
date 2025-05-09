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

        .striped-table {
            width: 100%;

            border-collapse: collapse;
        }
        .striped-table th,
        .striped-table td {
            padding: 8px;
            border-bottom: 1px solid #ccc;    /* gray line */
            text-align: left;
        }
        .striped-table tr:nth-child(even) {
            background-color: #f9f9f9;        /* subtle stripe */
        }
    </style>
    <title>Reservation Form</title>
</head>
<body>
<header>
    <img src="{{ public_path('images/Saray_Header.png') }}" alt="Company Header" style="width: 50%;">
</header>

<footer>
    <img src="{{ public_path('images/tail_img.png') }}" alt="Company Footer" style="width: 95%;">
    <div class="footer-bar">&nbsp;</div>
</footer>

<main>
    <h1 style="text-align: center;">Reservation Form</h1>
    {{-- Booking Information --}}
    <div class="section">
        <h2>Booking Information</h2>
        <p><span class="label">Booking ID:</span> {{ $booking->id }}</p>
        <p><span class="label">Booking Status:</span> {{ $booking->status }}</p>
    </div>

    {{-- Unit Information --}}
    <div class="section">
        <h2>Unit Information</h2>
        @if($unit)
            <p><span class="label">Unit #:</span> {{ $unit->unit_no }}</p>
            <p><span class="label">Unit Status:</span> {{ $unit->status }}</p>
            <p><span class="label">Price:</span> {{ number_format($unit->price, 2) }}</p>
        @else
            <p>No unit data available.</p>
        @endif
    </div>

    {{-- Customer Information --}}
    <div class="section">
        <h2>Customer Information</h2>
        @if($customerInfo)
            <p><span class="label">Name:</span> {{ $customerInfo->name }}</p>
            <p><span class="label">Passport #:</span> {{ $customerInfo->passport_number }}</p>
            <p><span class="label">Birth Date:</span> {{ $customerInfo->birth_date }}</p>
            <p><span class="label">Nationality:</span> {{ $customerInfo->nationality }}</p>
        @else
            <p>No customer data available.</p>
        @endif
    </div>

    {{-- Payment Plan --}}
    <div class="section">
        <h2>Payment Plan</h2>
        @if($paymentPlan)
            <p><span class="label">Plan Name:</span> {{ $paymentPlan->name }}</p>
            <p><span class="label">Selling Price:</span> AED {{ number_format($paymentPlan->selling_price, 2) }}</p>
            <p><strong>Admin Fee:</strong> AED {{ number_format($paymentPlan->admin_fee, 2) }}</p>
            <p><span class="label">DLD Fee:</span> {{ (int) $paymentPlan->dld_fee_percentage }}% | AED {{ $paymentPlan->dld_fee }}</p>
            <p><span class="label">Booking:</span> {{ $paymentPlan->booking_percentage }}%</p>
        @else
            <p>No payment plan assigned.</p>
        @endif

        @if($paymentPlan->installments->count())
            <table class="striped-table">
                <colgroup>
                    <col>
                    <col style="width:5%">
                    <col><
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>Description</th>
                    <th>Percentage</th>
                    <th>Date</th>
                    <th>Amount</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Expression of interest (EOI)</td>
                    <td>-</td>
                    <td>-</td>
                    <td>AED {{ number_format($paymentPlan->EOI, 2) }}</td>
                </tr>
                @foreach($paymentPlan->installments as $installment)
                    <tr>
                        <td>
                            {{ $installment->description }}
                            @if($loop->first)
                                <br/><small>({{ (int) $installment->percentage }}% + {{ (int) $plan->dld_fee_percentage }}% DLD fee + Admin fee - EOI)</small>
                            @endif
                        </td>
                        <td>{{ $installment->percentage }}%</td>
                        <td>{{ \Carbon\Carbon::parse($installment->date)->format('Y-m-d') }}</td>
                        <td>AED {{ number_format($installment->amount, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>



    {{-- Signatures --}}
    <div class="section">
        <h3>Signatures</h3>
        <p>Sales Signature: __________________________</p>
        <p>Customer Signature: ________________________</p>
    </div>

</main>
</body>
</html>

