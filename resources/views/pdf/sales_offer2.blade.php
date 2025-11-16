<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        /* 1) Define your page size, margins, and hook up header/footer */
        @page {
            margin: 150px 45px 150px 45px;
        }

        @page {
            /* MUST match the htmlpageheader name="MyHeader" below */
            header: html_MyHeader;
        }

        @page {
            /* MUST match the htmlpageheader name="MyHeader" below */
            footer: html_MyFooter;
        }

        /* 2) Base body styles */
        body {
            font-family: 'rubic', sans-serif;
            font-size: 14px;
            direction: ltr;
            margin: 0;
            padding: 0;
        }

        /* 3) Optional: page-break helper */
        .page-break {
            page-break-before: always;
        }

        h2 {
            border-bottom: 1px solid #E5E5E5; /* Adjust color, width, and style as needed */
        }

        /* 6) RTL helper */
        .rtl-text {
            direction: rtl;
        }

        table.info-table, table.info-table td, table.info-table th {
            border: 1px solid #ddd; /* light gray, slim border */
            padding: 5px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            width: 100%;
            margin-bottom: 0.5em;
            margin-top: 1.5em;
        }

        .header-table tr td {
            height: 30px;
        }

        .header-table tr td:nth-child(1) {
            width: 34%;
        }

        .header-table tr td:nth-child(2) {
            width: 33%;
        }

        .header-table tr td:nth-child(3) {
            width: 33%;
        }

        .left-th {
            text-align: left;
            padding-left: 5px;
        }

        .right-th {
            text-align: right;
        }

        .justified {
            /* fully justify the lines */
            text-align: justify;
            text-justify: inter-word;

            /* allow words to break and hyphenate */
            -webkit-hyphens: auto;
            -moz-hyphens: auto;
            -ms-hyphens: auto;
            hyphens: auto;
            overflow-wrap: break-word;
        }

        .spaced-text {
            line-height: 1.5;
        }

        .justified[lang="en"] {
            /* To be added in Production */
        }

        .section {
            margin-bottom: 20px;
        }

        .plan-title {
            background-color: #E5E5E5;
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
    </style>
    <title>Sales Offer</title>
</head>
<body>

<htmlpageheader name="MyHeader">
    <div style="margin-left: -45px; height: 150px;">
        <div style="height: 50px">&nbsp;</div>
        <img
            src="{{ public_path('images/Saray_Header.png') }}"
            alt="Company Header"
            style="width:50%; max-width:200mm;"
        />
    </div>
</htmlpageheader>

<htmlpagefooter name="MyFooter">
    <div style="height: 30px; text-align: center;">
        <table style="border-collapse:collapse; border:none; width: 100%">
            <tr>
                <td style="text-align: center;">{PAGENO}</td>
            </tr>
        </table>
    </div>
    <div style="height: 50px; background-color: #404040; margin-left: -45px; margin-right: -45px">&nbsp;</div>
</htmlpagefooter>

<main>

    <div>
        <h1 style="text-align: center">Sales Offer</h1>
        <p style="text-align: center">Offer Date: {{ $salesOffer->offer_date->format('Y-m-d H:i') }}</p>
    </div>

    <div class="section">
        <h2>Unit Details</h2>

        <table class="header-table">
            <tr>
                <td colspan="3" class="left-th" style="font-size: 1.15rem; padding-bottom: 10px;">
                    <strong>Building:</strong> {{ $unit->building->name ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td class="left-th"><strong>Unit No:</strong> {{ $unit->unit_no }}</td>
                <td class="left-th"><strong>Unit Type:</strong> {{ $unit->unit_type }}</td>
                <td class="left-th"><strong>Floor:</strong> {{ $unit->floor }}</td>
            </tr>
            <tr>
                <td class="left-th"><strong>Furnished:</strong> {{ $unit->furnished == 0 ? 'No' : 'Yes' }}</td>
                <td class="left-th"><strong>Parking:</strong> {{ $unit->parking }}</td>
                <td class="left-th"><strong>Amenity:</strong> {{ $unit->amenity }}</td>
            </tr>
            <tr>
                <td class="left-th"><strong>Internal Area:</strong> {{ $unit->internal_square }} ft²</td>
                <td class="left-th"><strong>External Area:</strong> {{ $unit->external_square }} ft²</td>
                <td class="left-th"><strong>Total Area:</strong> {{ $unit->total_square }} ft²</td>
            </tr>
        </table>
    </div>

    @if($notes)
        <div class="section">
            <h2>Notes</h2>
            <p>{{ $notes }}</p>
        </div>
    @endif

    <div class="section">
        @php
            $dollar_rate = 3.65;
        @endphp
        <h2>Payment Plans</h2>

        @foreach($paymentPlans as $plan)
            <h3 class="plan-title">{{ $plan->name }}</h3>

            <table class="header-table">
                <tr>
                    <td class="left-th">
                        <strong>Selling Price:</strong>
                    </td>
                    <td class="left-th">
                        <img src="{{ public_path('images/aed_symbol.svg') }}" width="12"
                             alt="AED"/> {{ number_format($unit->price, 2) }}
                    </td>
                    <td class="left-th">
                        <strong>$</strong> {{ number_format($unit->price / $dollar_rate, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="left-th" colspan="3">
                        <strong>Discount:</strong> {{ $salesOffer->discount }}%
                    </td>
                </tr>
                <tr>
                    <td class="left-th">
                        <strong>Effective Price:</strong>
                    </td>
                    <td class="left-th">
                        <img src="{{ public_path('images/aed_symbol.svg') }}" width="12"
                             alt="AED"/> {{ number_format($salesOffer->offer_price, 2) }}
                    </td>
                    <td class="left-th">
                        <strong>$</strong> {{ number_format($salesOffer->offer_price / $dollar_rate, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="left-th">
                        <strong>Admin fee:</strong>
                    </td>
                    <td class="left-th">
                        <img src="{{ public_path('images/aed_symbol.svg') }}" width="12"
                             alt="AED"/> {{ number_format($plan->admin_fee, 2) }}
                    </td>
                    <td class="left-th">
                        <strong>$</strong> {{ number_format($plan->admin_fee / $dollar_rate, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="left-th">
                        <strong>DLD fee:</strong> {{ (int) $plan->dld_fee_percentage }}%
                    </td>
                    <td class="left-th">
                        <img src="{{ public_path('images/aed_symbol.svg') }}" width="12"
                             alt="AED"/> {{ number_format($plan->dld_fee, 2) }}
                    </td>
                    <td class="left-th">
                        <strong>$</strong> {{ number_format($plan->dld_fee / $dollar_rate, 2) }}
                    </td>
                </tr>
            </table>
            <br/>
            <h3>Payment Schedule</h3>
            @if($plan->installments->count())
                <table class="striped-table">
                    <colgroup>
                        <col>
                        <col style="width:5%">
                        <col>
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
                    @foreach($plan->installments as $installment)
                        <tr>
                            <td>
                                {{ $installment->description }}
                                @if($loop->first)
                                    <br/><small>({{ (int) $installment->percentage }}%
                                        + {{ (int) $plan->dld_fee_percentage }}% DLD fee + Admin fee + EOI)</small>
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
                            <td>
                                <img src="{{ public_path('images/aed_symbol.svg') }}" width="12"
                                     alt="AED"/> {{ number_format($installment->amount, 2) }}
                                <br/>
                                <strong>$ </strong>{{ number_format($installment->amount / $dollar_rate, 2) }}
                            </td>
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

    <div class="page-break"></div>

    <section>
        <h1 style="text-align: center;">{{ $unit->unit_type }}</h1>
        <h3 style="text-align: center;">UNIT NO: {{ $unit->unit_no }}</h3>

        @if($unit->floor_plan)
            <div style="text-align:center; margin-bottom:20px;">
                <img
                    src="file:///{{ str_replace('\\','/', storage_path('app/private/' . $unit->floor_plan)) }}"
                    alt="Floor Plan"
                    style="width:90%; height:auto;"
                >
            </div>
        @endif

        @if($unit->building->image_path)
            <div style="text-align:center;">
                <img
                    src="file:///{{ str_replace('\\','/', storage_path('app/private/' . $unit->building->image_path)) }}"
                    alt="Building image"
                    style="width:90%; height:auto;"
                >
            </div>
        @endif
    </section>
</main>
</body>
</html>
