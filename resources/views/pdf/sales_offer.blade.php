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
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
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
            left: -45px; /* pull into the left margin */
            right: -45px; /* pull into the right margin */
            height: 50px;
            text-align: center;
            line-height: 35px;
        }

        .footer-bar {
            background-color: #404040;
        }

        .page-break {
            /* force a page break before (or after) this element */
            page-break-before: always;
            /* optional newer syntax */
            break-before: page;
        }

        .striped-table {
            width: 100%;

            border-collapse: collapse;
        }

        .striped-table th,
        .striped-table td {
            padding: 8px;
            border-bottom: 1px solid #ccc; /* gray line */
            text-align: left;
        }

        .striped-table tr:nth-child(even) {
            background-color: #f9f9f9; /* subtle stripe */
        }

        .unit-info {
            font-size: 11pt;
            color: #000;
            line-height: 1.6;
            width: 100%;
            max-width: 600px;
        }

        .unit-info table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .unit-info td {
            padding-top: 4px;
            vertical-align: top;
        }

        .unit-info .section-title {
            font-weight: bold;
            padding-top: 10px;
        }

        .area-values {
            padding-left: 10px;
        }

        .plan-title {
            background-color: #E5E5E5;
        }
    </style>
    <title>Sales Offer</title>
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

    <div>
        <h1 style="text-align: center">Sales Offer</h1>
        <p style="text-align: center">Offer Date: {{ $salesOffer->offer_date->format('Y-m-d H:i') }}</p>
    </div>

    <div class="section">
        <h2>Unit Details</h2>
        <p><strong>Building:</strong> {{ $unit->building->name ?? 'N/A' }}</p>

        <div class="unit-info">
            <table>
                <tr>
                    <td><strong>Unit No:</strong> {{ $unit->unit_no }}</td>
                    <td><strong>Unit Type:</strong> {{ $unit->unit_type }}</td>
                    <td><strong>Floor:</strong> {{ $unit->floor }}</td>
                    <td><strong>Furnished:</strong>  {{ $unit->furnished == 0 ? 'No' : 'Yes' }}</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td><strong>Parking:</strong> {{ $unit->parking }}</td>
                    <td><strong>Amenity:</strong> {{ $unit->amenity }}</td>
                    <td><strong>&nbsp;</strong>&nbsp;</td>
                    <td><strong>&nbsp;</strong>&nbsp;</td>
                </tr>
            </table>

            <div class="section-title">Area (ft²)</div>
            <table>
                <tr>
                    <td class="area-values"><strong>Internal:</strong> {{ $unit->internal_square }}</td>
                    <td class="area-values"><strong>External:</strong> {{ $unit->external_square }}</td>
                    <td class="area-values"><strong>Total:</strong> {{ $unit->total_square }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($notes)
        <div class="section">
            <h2>Notes</h2>
            <p>{{ $notes }}</p>
        </div>
    @endif

    <div class="section">
        <h2>Payment Plans</h2>
        @foreach($paymentPlans as $plan)
            <h3 class="plan-title">Plan: {{ $plan->name }}</h3>
            <p><strong>Selling Price:</strong> AED {{ number_format($unit->price, 2) }}</p>
            @if($salesOffer->discount > 0)
                <p><strong>Discount:</strong> {{ $salesOffer->discount }}%</p>
                <p><strong>Effective Price:</strong> AED {{ number_format($salesOffer->offer_price, 2) }}</p>
            @endif
            <p><strong>Admin fee:</strong> AED {{ number_format($plan->admin_fee, 2) }}</p>
            <p><strong>DLD fee:</strong> {{ (int) $plan->dld_fee_percentage }}% |
                AED {{ number_format($plan->dld_fee, 2) }}</p>
            @if($plan->installments->count())
                <table class="striped-table">
                    <colgroup>
                        <col>
                        <col style="width:5%">
                        <col>
                        <
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
                        <td>AED {{ number_format($plan->EOI, 2) }}</td>
                    </tr>
                    @foreach($plan->installments as $installment)
                        <tr>
                            <td>
                                {{ $installment->description }}
                                @if($loop->first)
                                    <br/><small>({{ (int) $installment->percentage }}%
                                        + {{ (int) $plan->dld_fee_percentage }}% DLD fee + Admin fee - EOI)</small>
                                @endif
                            </td>
                            <td>
                                @if($loop->first)
                                    -
                                @else
                                    {{ $installment->percentage }}%
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($installment->date)->format('Y-m-d') }}</td>
                            <td>AED {{ number_format($installment->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
            <br/>
        @endforeach
    </div>

    <div class="section">
        <h2>Generated By</h2>
        <p>{{ $generated_by->name }} ({{ $generated_by->email }})</p>
    </div>

    <!-- insert this where you want a clean new page -->
    <div class="page-break"></div>

    <section>
        <h1 style="text-align: center;">{{ $unit->unit_type }}</h1>
        <h3 style="text-align: center;">UNIT NO: {{ $unit->unit_no }}</h3>

        @if($unit->floor_plan)
            <img
                src="file:///{{ str_replace('\\','/', storage_path('app/private/' . $unit->floor_plan)) }}"
                alt="Floor Plan"
                style="width:90%; height:auto; margin-bottom: 30px;"
            >
        @endif

        <br/>

        @if($unit->building->image_path)
            <img
                src="file:///{{ str_replace('\\','/', storage_path('app/private/' . $unit->building->image_path)) }}"
                alt="Building image"
                style="width:90%; height:auto;"
            >
        @endif
    </section>
</main>
</body>
</html>
