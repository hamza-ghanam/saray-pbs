<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <style>
        /* 1) Define your page size, margins, and hook up header/footer */
        @page {
            margin: 150px 45px 180px 45px;
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

        .centred-text {
            text-align: center;
        }

        .footer-note {
            font-size: 11px;
            color: #666;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

{{-- Cancelled watermark --}}
@if($invoice->status->value === 'cancelled')
    <div class="watermark-cancelled">CANCELLED</div>
@endif

<htmlpageheader name="MyHeader">
    <div style="margin-left: -45px; height: 150px;">
        <div style="height: 50px">&nbsp;</div>
        <img
                src="{{ public_path('images/app_header.png') }}"
                alt="Company Header"
                style="width:50%; max-width:200mm;"
        />
    </div>
</htmlpageheader>

<!-- Named footer block (no html_ in the name) -->
<htmlpagefooter name="MyFooter">
    <div style="height: 30px; text-align: center;">
        <table style="border-collapse:collapse; border:none; width: 100%">
            <tr>
                <td style="text-align: center;">{PAGENO}</td>
            </tr>
        </table>
    </div>

    <div style="      position: center;
      bottom: 0;       /* stick to the bottom of the page */
      left:   0;       /* ignore the left margin entirely */
      right:  0;       /* ignore the right margin entirely */
      height: 50px;">
        <img
                src="{{ public_path('images/tail_img.png') }}"
                alt="Company Footer"
                style="width:95%; max-width:200mm; height:auto;"
        />
    </div>
    <div style="height: 50px; background-color: #404040; margin-left: -45px; margin-right: -45px">&nbsp;</div>
</htmlpagefooter>

<main>
    {{-- Title --}}
    <h1 style="text-align: center">
        @if($invoice->type->value === 'booking_confirmation')
            Booking Confirmation Receipt / إيصال تأكيد الحجز
        @elseif($invoice->type->value === 'payment_receipt')
            Payment Receipt / إيصال دفعة
        @else
            Tax Invoice / فاتورة ضريبية
        @endif
    </h1>

    {{-- Invoice Meta --}}
    <table class="header-table">
        <tr>
            <th class="left-th">INVOICE INFORMATION</th>
            <th class="rtl-text right-th">معلومات الفاتورة</th>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <th class="left-th" style="width: 30%;">Invoice No.</th>
            <td class="centred-text" style="width: 40%;">
                <strong>{{ $invoice->invoice_number }}</strong>
            </td>
            <th class="rtl-text right-th" style="width: 30%;">رقم الفاتورة</th>
        </tr>
        <tr>
            <th class="left-th">Issue Date</th>
            <td class="centred-text">{{ $invoice->issue_date->format('d-M-Y') }}</td>
            <th class="rtl-text right-th">تاريخ الإصدار</th>
        </tr>
        <tr>
            <th class="left-th">Status</th>
            <td class="centred-text">
                <span class="badge badge-confirmed">{{ $invoice->status->label() }}</span>
            </td>
            <th class="rtl-text right-th">الحالة</th>
        </tr>
        <tr>
            <th class="left-th">Issued By</th>
            <td class="centred-text">{{ $invoice->issuedBy->name ?? '-' }}</td>
            <th class="rtl-text right-th">صادرة بواسطة</th>
        </tr>
    </table>

    {{-- Seller & Buyer --}}
    <table class="header-table">
        <tr>
            <th class="left-th">SELLER INFORMATION</th>
            <th class="rtl-text right-th">معلومات البائع</th>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <th class="left-th" style="width: 30%;">Company</th>
            <td class="centred-text" style="width: 40%;">{{ $company['name'] }}</td>
            <th class="rtl-text right-th" style="width: 30%;">الشركة</th>
        </tr>
    </table>

    <table class="header-table">
        <tr>
            <th class="left-th">BUYER INFORMATION</th>
            <th class="rtl-text right-th">معلومات المشتري</th>
        </tr>
    </table>

    <table class="info-table">
        @foreach($customerInfos as $customerInfo)
            <tr>
                <th class="left-th" style="width: 30%;">Name</th>
                <td class="centred-text" style="width: 40%;">{{ $customerInfo->name_en }}</td>
                <th class="rtl-text right-th" style="width: 30%;">الاسم</th>
            </tr>
            <tr>
                <th class="left-th">Passport No.</th>
                <td class="centred-text">{{ $customerInfo->passport_number }}</td>
                <th class="rtl-text right-th">رقم جواز السفر</th>
            </tr>
            <tr>
                <th class="left-th">Phone</th>
                <td class="centred-text">{{ $customerInfo->phone_number }}</td>
                <th class="rtl-text right-th">الهاتف</th>
            </tr>
            <tr>
                <th class="left-th">Email</th>
                <td class="centred-text">{{ $customerInfo->email }}</td>
                <th class="rtl-text right-th">البريد الإلكتروني</th>
            </tr>
        @endforeach
    </table>

    {{-- Property & Booking --}}
    <table class="header-table">
        <tr>
            <th class="left-th">PROPERTY INFORMATION</th>
            <th class="rtl-text right-th">معلومات العقار</th>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <th class="left-th" style="width: 30%;">Project</th>
            <td class="centred-text" style="width: 40%;">{{ $booking->unit->building->name }}</td>
            <th class="rtl-text right-th" style="width: 30%;">المشروع</th>
        </tr>
        <tr>
            <th class="left-th">Unit No.</th>
            <td class="centred-text">{{ $booking->unit->unit_no }}</td>
            <th class="rtl-text right-th">رقم الوحدة</th>
        </tr>
        <tr>
            <th class="left-th">Unit Type</th>
            <td class="centred-text">{{ $booking->unit->unit_type }}</td>
            <th class="rtl-text right-th">نوع الوحدة</th>
        </tr>
        <tr>
            <th class="left-th">Total Purchase Price</th>
            <td class="centred-text">
                <img src="{{ public_path('images/aed_symbol.svg') }}"
                     width="12" alt="AED"/>
                {{ number_format($booking->price, 2) }}
            </td>
            <th class="rtl-text right-th">إجمالي سعر الشراء</th>
        </tr>
    </table>

    {{-- Payment Details (for payment_receipt type) --}}
    @if($installmentPayment)
        <table class="header-table">
            <tr>
                <th class="left-th">PAYMENT DETAILS</th>
                <th class="rtl-text right-th">تفاصيل الدفعة</th>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <th class="left-th" style="width: 30%;">Installment</th>
                <td class="centred-text" style="width: 40%;">{{ $installmentPayment->installment->description }}</td>
                <th class="rtl-text right-th" style="width: 30%;">القسط</th>
            </tr>
            <tr>
                <th class="left-th">Payment Date</th>
                <td class="centred-text">{{ $installmentPayment->payment_date->format('d-M-Y') }}</td>
                <th class="rtl-text right-th">تاريخ الدفع</th>
            </tr>
            <tr>
                <th class="left-th">Payment Method</th>
                <td class="centred-text">{{ ucfirst(str_replace('_', ' ', $installmentPayment->payment_method ?? '-')) }}</td>
                <th class="rtl-text right-th">طريقة الدفع</th>
            </tr>
            @if($installmentPayment->reference_number)
                <tr>
                    <th class="left-th">Reference No.</th>
                    <td class="centred-text">{{ $installmentPayment->reference_number }}</td>
                    <th class="rtl-text right-th">رقم المرجع</th>
                </tr>
            @endif
            <tr>
                <th class="left-th">Verified By</th>
                <td class="centred-text">{{ $installmentPayment->verifiedBy->name ?? '-' }}</td>
                <th class="rtl-text right-th">تم التحقق بواسطة</th>
            </tr>
            <tr>
                <th class="left-th">Verified At</th>
                <td class="centred-text">{{ $installmentPayment->verified_at?->format('d-M-Y H:i') ?? '-' }}</td>
                <th class="rtl-text right-th">تاريخ التحقق</th>
            </tr>
        </table>
    @endif

    {{-- Amount Summary --}}
    <table class="header-table">
        <tr>
            <th class="left-th">AMOUNT SUMMARY</th>
            <th class="rtl-text right-th">ملخص المبلغ</th>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <th class="left-th" style="width: 30%;">Subtotal</th>
            <td class="centred-text" style="width: 40%;">
                <img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/>
                {{ number_format($invoice->subtotal, 2) }}
            </td>
            <th class="rtl-text right-th" style="width: 30%;">المجموع الفرعي</th>
        </tr>
        @if($invoice->vat_rate > 0)
            <tr>
                <th class="left-th">VAT ({{ $invoice->vat_rate }}%)</th>
                <td class="centred-text">
                    <img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/>
                    {{ number_format($invoice->vat_amount, 2) }}
                </td>
                <th class="rtl-text right-th">ضريبة القيمة المضافة ({{ $invoice->vat_rate }}%)</th>
            </tr>
        @endif
        <tr class="amount-row">
            <th class="left-th">Total Amount</th>
            <td class="centred-text">
                <img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/>
                {{ number_format($invoice->total, 2) }}
            </td>
            <th class="rtl-text right-th">المبلغ الإجمالي</th>
        </tr>
    </table>

    @if($invoice->notes)
        <br/>
        <table class="info-table">
            <tr>
                <th class="left-th" style="width: 15%;">Notes</th>
                <td>{{ $invoice->notes }}</td>
                <th class="rtl-text right-th" style="width: 15%;">ملاحظات</th>
            </tr>
        </table>
    @endif

    {{-- Footer Note --}}
    <p class="footer-note">
        .هذه الوثيقة صادرة آليا من النظام وتعتبر إيصالا رسميا
        <br/>
        This document is system-generated and serves as an official receipt.
    </p>

    {{-- Signatures --}}
    <br/>
    <table class="info-table">
        <tr>
            <td class="left-th" style="width: 49%; line-height: 2.5; padding: 7px;">
                <h4>Issued By: {{ $company['name'] }}</h4>
                <h4>Signed:
                    <img
                            src="file:///{{ str_replace('\\','/', storage_path('app/private/signatures/company/cso_signature.png')) }}"
                            height="30" alt="Company Signature"
                    />
                </h4>
                <h4>Date: {{ $invoice->issue_date->format('d-M-Y') }}</h4>
            </td>
            <td style="text-align: center;"></td>
            <td class="rtl-text right-th" style="width: 49%; line-height: 2.5; padding: 7px;">
                <h4>صادرة من: {{ $company['name_ar'] }}</h4>
                <h4>التوقيع:
                    <img
                            src="file:///{{ str_replace('\\','/', storage_path('app/private/signatures/company/cso_signature.png')) }}"
                            height="30" alt="Company Signature"
                    />
                </h4>
                <h4>التاريخ: {{ $invoice->issue_date->locale('ar')->isoFormat('D-MMM-YYYY') }}</h4>
            </td>
        </tr>
    </table>
    <table class="header-table">
        <tr>
            <th style="text-align: center;">
                <img
                        src="file:///{{ str_replace('\\','/', storage_path('app/private/stamps/company_stamp_sales.png')) }}"
                        alt="Seller Initial" height="80" style="margin-right: 5px;"
                >
            </th>
        </tr>
    </table>
</main>
</body>
</html>