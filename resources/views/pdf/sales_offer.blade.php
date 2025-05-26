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

        .table-compact td h4,
        .table-compact td p {
            margin: 0;
            padding: 0;
        }
        /* optionally collapse cell padding too */
        .table-compact {
            border-collapse: collapse;
        }
        .table-compact td {
            padding: 4px 8px; /* tweak to whatever you like */
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
        <table class="table-compact">
            <tr>
                <td>
                    <h4>Unit No:{{ $unit->unit_no }}</h4>
                </td>
                <td>
                    <p><strong>Unit Type:</strong> {{ $unit->unit_type }}</p>
                </td>
                <td>
                    <p><strong>Floor:</strong> {{ $unit->floor }}</p>
                </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td colspan="3"><h4>Square Ft<sup>2</sup></h4></td>
            </tr>
            <tr>
                <td>
                    <p><strong>Internal:</strong> {{ $unit->internal_square_ft }}</p>
                </td>
                <td>
                    <p><strong>External:</strong> {{ $unit->external_square_ft }}</p>
                </td>
                <td>
                    <p><strong>Total:</strong> {{ $unit->total_square_ft }}</p>
                </td>
            </tr>
        </table>
        <p><strong>Price:</strong> AED {{ number_format($unit->price, 2) }}</p>
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
            <h3>Plan: {{ $plan->name }}</h3>
            <p><strong>Selling Price:</strong> AED {{ number_format($unit->price, 2) }}</p>
            @if($salesOffer->discount > 0)
                <p><strong>Discount:</strong> {{ $salesOffer->discount }}%</p>
                <p><strong>Effective Price:</strong> AED {{ number_format($salesOffer->offer_price, 2) }}</p>
            @endif
            <p><strong>Admin Fee:</strong> AED {{ number_format($plan->admin_fee, 2) }}</p>
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
