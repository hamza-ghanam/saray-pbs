<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Sales Offer</title>
    <style>
        /* 1) Define your page size, margins, and hook up header/footer */
        @page {
            margin: 150px 45px 100px 45px;
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
            border: 1px solid #ddd;    /* light gray, slim border */
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
    </style>
</head>
<body>

<!-- 7) Your named header block (no html_ in the name) -->
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

<!-- 8) Your named footer block (no html_ in the name) -->
<htmlpagefooter name="MyFooter">
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

<!-- 9) Your main content -->
<main>
    <h1 class="rtl-text" style="text-align:center;">
        نموذج حجز / RESERVATION FORM
    </h1>

    <table class="header-table">
        <tr>
            <th class="left-th">SELLER AND PROJECT INFORMATION</th>
            <th class="rtl-text right-th">معلومات البائع والمشروع</th>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <th class="left-th" style="width: 32%;">Date:</th>
            <td style="text-align: center;">{{ \Carbon\Carbon::now()->format('d-M-Y') }}</td>
            <th class="rtl-text right-th" style="width: 32%;">التاريخ:</th>
        </tr>
        <tr>
            <th class="left-th">Seller:</th>
            <td style="text-align: center;">Unique Saray Properties L.L.C</td>
            <th class="rtl-text right-th">البائع:</th>
        </tr>
        <tr>
            <th class="left-th">Plot No:</th>
            <td style="text-align: center;"></td>
            <th class="rtl-text right-th">رقم الأرض:</th>
        </tr>
        <tr>
            <th class="left-th">Project:</th>
            <td style="text-align: center;">{{ $unit->building->name }}</td>
            <th class="rtl-text right-th">المشروع:</th>
        </tr>
        <tr>
            <th class="left-th">Unit:</th>
            <td style="text-align: center;">{{ $unit->unit_no }}</td>
            <th class="rtl-text right-th">الوحدة:</th>
        </tr>
        <tr>
            <th class="left-th">Unit Internal Area:</th>
            <td style="text-align: center;">{{ $unit->internal_square }}</td>
            <th class="rtl-text right-th">المساحة الداخلية للوحدة:</th>
        </tr>
        <tr>
            <th class="left-th">Balcony/Terrace Area (if applicable):</th>
            <td style="text-align: center;">{{ $unit->external_square }}</td>
            <th class="rtl-text right-th">مساحة الشرفة / التراس (إذا كان منطبقا):</th>
        </tr>
        <tr>
            <th class="left-th">Unit Gross Area</th>
            <td style="text-align: center;">{{ $unit->total_square }}</td>
            <th class="rtl-text right-th">مساحة الوحدة الإجمالية</th>
        </tr>
        <tr>
            <th class="left-th">Number of car parking spaces (if applicable)</th>
            <td style="text-align: center;">{{ $unit->parking }}</td>
            <th class="rtl-text right-th">عدد مواقف السيارات: (إذا كان منطبقا)</th>
        </tr>
        <tr>
            <th class="left-th">No. of Bedrooms</th>
            <td style="text-align: center;">{{ $unit->unit_type }}</td>
            <th class="rtl-text right-th">عدد الغرف</th>
        </tr>
        <tr>
            <th class="left-th">Purchase Price Incl. of VAT (<img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED" />)</th>
            <td style="text-align: center;">{{ $unit->price }}</td>
            <th class="rtl-text right-th">سعر الشراء شامل الضريبة (<img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED" />)</th>
        </tr>
        <tr>
            <th class="left-th">Unit Permitted Use</th>
            <td style="text-align: center;">Residential</td>
            <th class="rtl-text right-th">الاستخدام المسموح للوحدة</th>
        </tr>
        <tr>
            <th class="left-th">Reservation Deposit (Non-refundable Deposit)</th>
            <td style="text-align: center;">{{ $paymentPlan->EOI }}</td>
            <th class="rtl-text right-th">مبلغ دفعة الحجز (عربون غير مسترد)</th>
        </tr>
        <tr>
            <th class="left-th">Registration Fees</th>
            <td style="text-align: center;">4%</td>
            <th class="rtl-text right-th">رسوم التسجيل</th>
        </tr>
    </table>

    <table class="header-table">
        <tr>
            <th class="left-th">PURCHASER INFORMATION</th>
            <th class="rtl-text right-th">معلومات المشتري</th>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <th class="left-th" style="width: 25%;">&nbsp;</th>
            <th class="left-th" style="width: 25%; text-align: center;">Purchaser</th>
            <th class="left-th" style="width: 25%; text-align: center;">Jointly Purchaser</th>
            <th class="left-th" style="width: 25%;">&nbsp;</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Purchaser Name</th>
            <td class="" style="width: 25%; text-align: center">{{ $customerInfos[0]->name }}</td>
            <td class="" style="width: 25%; text-align: center">{{ ($customerInfos[1] ?? null)?->name ?? '-' }}</td>
            <th class="right-th" style="width: 25%;">اسم المشتري</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Nationality</th>
            <td class="" style="width: 25%; text-align: center">{{ $customerInfos[0]->nationality }}</td>
            <td class="" style="width: 25%; text-align: center">{{ ($customerInfos[1] ?? null)?->nationality  ?? '-' }}</td>
            <th class="right-th" style="width: 25%;">الجنسية</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Passport No.</th>
            <td class="" style="width: 25%; text-align: center">{{ $customerInfos[0]->passport_number }}</td>
            <td class="" style="width: 25%; text-align: center">{{ ($customerInfos[1] ?? null)?->passport_number  ?? '-' }}</td>
            <th class="right-th" style="width: 25%;">رقم جواز السفر</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Emirates ID No.</th>
            <td class="" style="width: 25%; text-align: center"></td>
            <td class="" style="width: 25%; text-align: center"></td>
            <th class="right-th" style="width: 25%;">رقم بطاقة الهوية الإماراتية</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">P.O. Box/Postal Code</th>
            <td class="" style="width: 25%; text-align: center"></td>
            <td class="" style="width: 25%; text-align: center"></td>
            <th class="right-th" style="width: 25%;">صندوق بريد/الرمز البريدي</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Country</th>
            <td class="" style="width: 25%; text-align: center">{{ $customerInfos[0]->nationality }}</td>
            <td class="" style="width: 25%; text-align: center">{{ ($customerInfos[1] ?? null)?->nationality ?? '-' }}</td>
            <th class="right-th" style="width: 25%;">الدولة</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">City</th>
            <td class="" style="width: 25%; text-align: center"></td>
            <td class="" style="width: 25%; text-align: center"></td>
            <th class="right-th" style="width: 25%;">المدينة</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Address</th>
            <td class="" style="width: 25%; text-align: center">{{ $customerInfos[0]->address }}</td>
            <td class="" style="width: 25%; text-align: center">{{ ($customerInfos[1] ?? null)?->address ?? '-' }}</td>
            <th class="right-th" style="width: 25%;">العنوان</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Mobile No.</th>
            <td class="" style="width: 25%; text-align: center">{{ $customerInfos[0]->phone_number }}</td>
            <td class="" style="width: 25%; text-align: center">{{ ($customerInfos[1] ?? null)?->phone_number ?? '-' }}</td>
            <th class="right-th" style="width: 25%;">الهاتف المتحرك</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Telephone No.</th>
            <td class="" style="width: 25%; text-align: center">{{ $customerInfos[0]->phone_number }}</td>
            <td class="" style="width: 25%; text-align: center">{{ ($customerInfos[1] ?? null)?->phone_number ?? '-' }}</td>
            <th class="right-th" style="width: 25%;">الهاتف</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Email</th>
            <td class="" style="width: 25%; text-align: center">{{ $customerInfos[0]->email }}</td>
            <td class="" style="width: 25%; text-align: center">{{ ($customerInfos[1] ?? null)?->email ?? '-' }}</td>
            <th class="right-th" style="width: 25%;">البريد الإلكتروني</th>
        </tr>
        <tr>
            <th class="left-th justified" style="width: 25%; font-weight: bold; padding-right: 5px;" lang="en">Note: If the Purchaser is an entity, complete details above for company representative and insert details below</th>
            <td class="" style="width: 25%; text-align: center"></td>
            <td class="" style="width: 25%; text-align: center"></td>
            <th class="right-th" style="width: 25%; font-weight: bold;  text-align: justify; direction: rtl;">ملاحظة: إذا كان المشتري جهة اعتبارية، يرجى تعبئة التفاصيل أعلاه لممثل الشركة وإدخال التفاصيل أدناه.</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Entity Name</th>
            <td class="" style="width: 25%; text-align: center"></td>
            <td class="" style="width: 25%; text-align: center"></td>
            <th class="right-th" style="width: 25%;">اسم الجهة</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Registration No</th>
            <td class="" style="width: 25%; text-align: center"></td>
            <td class="" style="width: 25%; text-align: center"></td>
            <th class="right-th" style="width: 25%;">رقم التسجيل</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 25%;">Registration Authority</th>
            <td class="" style="width: 25%; text-align: center"></td>
            <td class="" style="width: 25%; text-align: center"></td>
            <th class="right-th" style="width: 25%;">جهة التسجيل</th>
        </tr>
    </table>

    <table class="header-table">
        <tr>
            <th class="left-th">AGENCY INFORMATION (if any) </th>
            <th class="rtl-text right-th">معلومات الوسيط (إن وجد)</th>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <th class="left-th" style="width: 32%;">Agency Company Name</th>
            <td style="text-align: center;"> </td>
            <th class="rtl-text right-th" style="width: 32%;">اسم شركة الوساطة</th>
        </tr>
        <tr>
            <th class="left-th" style="width: 32%;">Agent Name</th>
            <td style="text-align: center;"> </td>
            <th class="rtl-text right-th" style="width: 32%;">اسم الوسيط</th>
        </tr>
    </table>
    <br/>
    <table class="info-table">
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol>
                    <li>
                        The Purchaser acknowledges that the above-mentioned address and contact information are considered the Purchaser’s primary address and chosen domicile and will be printed on the Seller’s Sale and Purchase Agreement (SPA). Such contact information, including email and phone number will be used for all communications/notifications/correspondences with the Purchaser.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol>
                    <li>
                        يقر المشتري بأن العنوان ومعلومات الاتصال المشار إليها أعلاه تعد عنوانه الرئيسي وموطنه المختار، وسيتم إدراجها في اتفاقية البيع والشراء (SPA) الخاصة بالبائع. وسيتم استخدام معلومات الاتصال هذه، بما في ذلك البريد الإلكتروني ورقم الهاتف، لجميع وسائل التواصل/الإشعارات/المراسلات مع المشتري.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="2">
                    <li>
                        This unconditional and irrevocable Offer to Purchase (“Offer”) is made by the Purchaser to the Seller for the purchase of the unit and is binding upon the Purchaser.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="2">
                    <li>
                        يقدم المشتري هذا العرض غير المشروط وغير القابل للإلغاء (“العرض”) إلى البائع لشراء الوحدة، ويعد ملزما قانونا للمشتري.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="3">
                    <li>
                        The sale of the Property is, ultimately, subject to the Seller’s approval at its sole discretion.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="3">
                    <li>
                        إن بيع العقار خاضع في نهاية المطاف لموافقة البائع وفقا لتقديره المطلق.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="4">
                    <li>
                        The Purchaser unconditionally, irrevocably and finally agrees to acquire the Property from the Seller at the above-mentioned Purchase Price, in accordance with the below-mentioned table (“Payment Schedule”):
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="4">
                    <li>
                        يوافق المشتري دون قيد أو شرط، وبشكل نهائي وغير قابل للإلغاء، على شراء العقار من البائع بالسعر المذكور أعلاه، وذلك وفقا للجدول المبين أدناه ("جدول الدفعات"):
                    </li>
                </ol>
            </td>
        </tr>
    </table>
    <div class="page-break"></div>
    <table class="info-table">
        <colgroup>
            <col>
            <col>
            <col style="width:5%">
            <col>
        </colgroup>
        <thead>
        <tr style="background-color: lightgrey;">
            <th class="left-th" style="width: 40%; text-align: justify; padding: 10px;">
                Installment
            </th>
            <th class="left-th" style="width: 25%; text-align: justify; padding: 10px;">
                Date
            </th>
            <th class="left-th" style="width: 10%; text-align: justify; padding: 10px;">
                %
            </th>
            <th class="left-th" style="text-align: justify; padding: 10px;">
                Amount <img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED" />
            </th>
        </tr>
        </thead>
        @foreach($installments as $installment)
            <tr>
                <td class="left-th" style="width: 40%; text-align: justify; padding: 10px;">
                    {{ $installment->description }}
                    @if($loop->first)
                        <br/><small>({{ (int) $installment->percentage }}%
                            + {{ (int) $paymentPlan->dld_fee_percentage }}% DLD fee + Admin fee - EOI)</small>
                    @endif
                </td>
                <td class="left-th" style="width: 25%; text-align: center; padding: 10px;">
                    {{ \Carbon\Carbon::parse($installment->date)->format('Y-m-d') }}
                </td>
                <td class="left-th" style="width: 10%; text-align: justify; padding: 10px;">
                    @if($loop->first)
                        -
                    @else
                        {{ (int) $installment->percentage }}%
                    @endif
                </td>
                <td class="left-th" style="text-align: right; padding: 10px;">
                    {{ number_format($installment->amount, 2) }}
                </td>
            </tr>
        @endforeach
    </table>
    <br/>
    <table class="info-table">
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="5">
                    <li>
                        The Purchaser shall pay the above-mentioned Reservation Deposit to the Seller on the date of signing hereof, or on any other date specified by the Seller in writing for this purpose. The payment of the Reservation Deposit by the Purchaser to the Seller prior to the formal execution of the (SPA) shall be irrevocably deemed an integral part of the First Installment and shall be credited accordingly.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="5">
                    <li>
                        يلتزم المشتري بدفع مبلغ الحجز المشار إليه أعلاه إلى البائع في تاريخ توقيع هذا النموذج، أو في أي تاريخ آخر يحدده البائع كتابيا لهذا الغرض. ويعتبر دفع المبلغ من قبل المشتري إلى البائع قبل توقيع اتفاقية البيع والشراء (SPA) رسميا جزءا لا يتجزأ من الدفعة الأولى ولا يمكن إلغاؤه، وسيحسب ضمنها.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="6">
                    <li>
                        Within (120) days of the date of this Reservation Form and the clearance of the Reservation Deposit  along with registration fees (whichever occurs later) the Seller shall provide to the Purchaser its standard form agreement containing the terms and conditions of the sale and purchase of the Unit “SPA”. The Purchaser must deliver the duly executed SPA to the Seller within (21) days of the date of the Developer issuing the SPA to the Purchaser “Prescribed Date”. For the avoidance of doubt, the signature of the Purchaser on the Seller’s SPA shall not affect the rights of the Seller under this Offer.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="6">
                    <li>
                        خلال مدة قدرها (120) يوما من تاريخ هذا النموذج وتسوية مبلغ الحجز مع رسوم التسجيل (أيهما لاحق)، يلتزم البائع بتزويد المشتري بنموذجه القياسي لاتفاقية البيع والشراء للوحدة، متضمنا الشروط والأحكام ذات الصلة. ويجب على المشتري توقيع اتفاقية البيع والشراء وتسليمها إلى البائع خلال (21) يوما من تاريخ إصدار المطور للاتفاقية ("التاريخ المحدد"). دفعا لأي غموض، فإن توقيع المشتري على اتفاقية البيع والشراء الخاصة بالبائع لا يؤثر على حقوق البائع بموجب هذا العرض.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="7">
                    <li>
                        The Purchaser agrees and accepts the foregoing provisions and that, except in case of unilateral termination by the Seller or Seller’s failure to provide the SPA in accordance with clause 6 above, the Reservation Deposit is not refundable to the Purchaser for any reason whatsoever.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="7">
                    <li>
                        يوافق المشتري ويقر بالأحكام السابقة، وأنه – باستثناء الحالات التي ينهي فيها البائع العقد من طرف واحد أو إخفاقه في تقديم اتفاقية البيع والشراء وفقا للبند 6 أعلاه – فإن مبلغ الحجز غير قابل للاسترداد لأي سبب كان.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="8">
                    <li>
                        In addition to the Purchase Price, and as per the Payment Schedule, the Purchaser agrees to pay all pre-registration/registration charges, along with  any other related amounts, upon the execution of this Offer, Such charges may be levied, from time to time, by DLD or any other relevant authority.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="8">
                    <li>
                        بالإضافة إلى سعر الشراء، ووفقا لجدول الدفعات، يوافق المشتري على دفع جميع رسوم ما قبل التسجيل / التسجيل، وأي مبالغ أخرى ذات صلة، عند توقيع هذا العرض. ويجوز فرض هذه الرسوم من وقت لآخر من قبل دائرة الأراضي والأملاك أو أي جهة مختصة أخرى.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="9">
                    <li>
                        For the avoidance of doubt, the Purchaser acknowledges and agrees that if the Purchaser fails to execute and deliver to the Seller the SPA within the Prescribed Period ( [21] days following the date on which the Seller shared the SPA to the Purchaser), for any reason whatsoever, this Offer shall automatically terminate upon the expiry of the Prescribed Period, the reservation of the Unit in the name of the Purchaser will immediately lapse, and this Reservation Form shall be terminated without prior notice to the Purchaser and without need for any court order, and the Reservation Deposit shall be forfeited to the Seller without the need for notice or any further action.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="9">
                    <li>
                        ودفعا لأي غموض، يقر المشتري ويوافق بأنه في حال فشل في توقيع وتقديم اتفاقية البيع والشراء إلى البائع خلال الفترة المحددة ([21] يوما من تاريخ إرسال الاتفاقية من قبل البائع)، لأي سبب كان، فإن هذا العرض يعتبر مفسوخا تلقائيا عند انتهاء الفترة المحددة، وتلغى حجز الوحدة باسم المشتري فورا، ويعتبر هذا النموذج ملغيا دون الحاجة إلى إشعار مسبق أو أمر قضائي، ويتم مصادرة مبلغ الحجز لصالح البائع دون الحاجة إلى أي إجراء إضافي.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="10">
                    <li>
                        If any payment, under this Offer, is made by a cheque and the cheque is not cleared by the bank, if the pre-registration/registration charges, and any other related amounts have not been settled by the Purchaser in a timely manner and/or if the Reservation Deposit has not been received by the Seller in full within the Prescribed Period, the Seller may immediately terminate this Offer and the reservation of the Unit in the name of the Purchaser will immediately lapse, and this Reservation Form shall be terminated without prior notice to the Purchaser and without need for any court order. Any cleared Reservation Deposit shall be forfeited to the Seller without the need for notice or any further action.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="10">
                    <li>
                        في حال تم دفع أي مبلغ بموجب هذا العرض بواسطة شيك ولم يتم صرفه من قبل البنك، أو في حال لم يتم تسوية رسوم التسجيل وأي مبالغ ذات صلة من قبل المشتري في الوقت المحدد، و/أو في حال لم يستلم البائع مبلغ الحجز بالكامل خلال الفترة المحددة، يحق للبائع إنهاء هذا العرض فورا، وتلغى حجز الوحدة باسم المشتري، ويعتبر هذا النموذج ملغيا دون إشعار مسبق أو أمر قضائي. ويصادر أي مبلغ حجز تم صرفه لصالح البائع دون الحاجة إلى إشعار أو إجراء إضافي.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="11">
                    <li>
                        Until signing the SPA, this Offer shall constitute a legally binding and enforceable contract on the Purchaser for the Purchase of the Property. For the avoidance of doubt this Offer does not obligate the Seller to complete the sale, and no binding obligation to transfer the Property exists unless and until the Sale and Purchase Agreement (SPA) is signed by both parties. Where the Purchaser consists of more than one person such parties will be jointly and severally liable under the terms and conditions of this Reservation Form. Upon entry into the SPA, the SPA shall supersede this Reservation Form and the Reservation Deposit shall be credited to the instalments of the Purchase Price.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="11">
                    <li>
                        حتى توقيع اتفاقية البيع والشراء، يعد هذا العرض عقدا ملزما وقابلا للتنفيذ قانونا ضد المشتري لشراء العقار. دفعا لأي غموض، لا يلزم هذا العرض البائع بإتمام البيع، ولا تنشأ أي التزامات ملزمة لنقل ملكية العقار ما لم يتم توقيع اتفاقية البيع والشراء من كلا الطرفين. وفي حال كان المشتري مكونا من أكثر من شخص، فإن جميع هؤلاء الأشخاص يكونون مسؤولين بالتضامن والتكافل بموجب شروط وأحكام هذا النموذج. وبمجرد توقيع اتفاقية البيع والشراء، فإنها تحل محل هذا النموذج، ويتم احتساب مبلغ الحجز ضمن دفعات سعر الشراء.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="12">
                    <li>
                        The Purchaser represents and confirms with full responsibility, that it has complied with all regulations, laws and requirements in all relevant jurisdictions including relevant exchange control requirements, and obtained all licenses, consents and permissions that are required to enter into this Offer and perform its obligations hereunder.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="12">
                    <li>
                        يصرح المشتري ويؤكد – بكامل المسؤولية – أنه قد التزم بجميع القوانين واللوائح والمتطلبات ذات الصلة في جميع الاختصاصات القضائية المعنية، بما في ذلك متطلبات الرقابة على تحويل العملات، وأنه قد حصل على كافة التراخيص والموافقات والتصاريح اللازمة لإبرام هذا العرض وتنفيذ التزاماته بموجبه.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="13">
                    <li>
                        The Purchaser guarantees that all payments of any kind made under or pursuant to this Offer, whether made by the Purchaser or by any third party payor on behalf of the Purchaser, are paid by funds of legitimate source and that the same are not the proceed of any crime or illegal activity and are not, or is could not reasonable be considered to be, the subject matter of money laundering in any way whatsoever.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="13">
                    <li>
                        يضمن المشتري أن جميع المدفوعات من أي نوع بموجب أو نتيجة لهذا العرض – سواء تم دفعها من قبل المشتري أو من طرف ثالث نيابة عنه – قد تم دفعها من مصادر مشروعة، وأنها ليست ناتجة عن أي جريمة أو نشاط غير قانوني، ولا تعتبر، أو يمكن اعتبارها بشكل معقول، موضوعا لغسل الأموال بأي شكل من الأشكال.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="14">
                    <li>
                        The Purchaser shall pay the Reservation Deposit either by current dated cheque issued in the name of the Developer or by bank transfer/deposit to the Developer’s bank account as detailed below:
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="14">
                    <li>
                        يلتزم المشتري بدفع مبلغ الحجز إما بشيك بتاريخ اليوم صادر باسم المطور، أو عن طريق التحويل البنكي/الإيداع في الحساب المصرفي الخاص بالمطور والمبين أدناه:
                    </li>
                </ol>
            </td>
        </tr>
    </table>

    <br/>
    <table class="info-table">
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="15">
                    <li>
                        This Offer shall be governed and construed in accordance with the laws of the Emirate of Dubai and the Federal Laws of the United Arab Emirates as applied in the Emirate of Dubai.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="15">
                    <li>
                        يخضع هذا العرض ويفسر وفقا لقوانين إمارة دبي والقوانين الاتحادية لدولة الإمارات العربية المتحدة المعمول بها في إمارة دبي.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="16">
                    <li>
                        All disputes arising between the parties in connection with this Offer to Purchase shall be referred to the courts of Dubai, for the avoidance of doubt.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="16">
                    <li>
                        تحال جميع المنازعات الناشئة بين الطرفين فيما يتعلق بهذا العرض إلى محاكم دبي، دفعا لأي غموض.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                <ol start="17">
                    <li>
                        The Seller shall have all rights at any time to freely assign this Offer or any part thereof or any benefit, right, obligation or interest therein or thereunder to any of its affiliates and subsidiaries (including to any joint venture company) without need for the Purchaser’s consent.
                    </li>
                </ol>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                <ol start="17">
                    <li>
                        يحتفظ البائع بكامل الحق، في أي وقت، في تحويل هذا العرض أو أي جزء منه أو أي منفعة أو حق أو التزام أو مصلحة مترتبة عليه أو بموجبه إلى أي من الشركات التابعة له أو الشركات الشقيقة (بما في ذلك شركات المشاريع المشتركة) دون الحاجة إلى الحصول على موافقة المشتري.
                    </li>
                </ol>
            </td>
        </tr>
    </table>
    <br/>
    <div class="page-break"></div>
    <table class="info-table">
        <tr>
            <th class="left-th" style="width: 49%; text-align: center;">
                Purchaser Acknowledgements
            </th>
            <td style="text-align: center;"> </td>
            <th class="rtl-text right-th" style="width: 49%; padding: 10px; text-align: center;">
                تعهدات المشتري
            </th>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                (a)	I / We confirm having read and understood this Reservation Form and that it governs and relates only to the application and reservation procedures for the Unit and does not grant the Purchaser any priority interest or other rights in the Unit.
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                ‌أ.	أقر/نقر بأننا قرأنا وفهمنا هذا النموذج الخاص بالحجز، وأنه ينظم ويقتصر فقط على إجراءات تقديم الطلب وحجز الوحدة، ولا يمنح المشتري أي أولوية أو مصلحة أو حقوق أخرى في الوحدة.
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                (b)	I / We acknowledge and agree that the Purchase Price does not include other fees and charges applicable in relation to the SPA or the Unit from time to time, all of which are payable by the Purchaser, including but not limited to registration fees, master community service charges, building service charges, administration fees, utility fees and charges, applicable taxes, and visa application charges.
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                ‌ب.	أقر/نقر ونتفق على أن سعر الشراء لا يشمل أية رسوم أو تكاليف أخرى تطبق من حين لآخر فيما يتعلق باتفاقية البيع والشراء (SPA) أو الوحدة، وجميعها تقع ضمن مسؤولية المشتري للدفع، بما في ذلك – على سبيل المثال لا الحصر – رسوم التسجيل، ورسوم خدمات المجتمع الرئيسي، ورسوم خدمات المبنى، والرسوم الإدارية، ورسوم وفواتير المرافق، والضرائب السارية، ورسوم تقديم طلبات التأشيرة.
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                (c)	I / We acknowledge that any brochures, visuals, or marketing materials provided by the Seller are for illustrative purposes only and shall not form part of the contractual obligations unless expressly incorporated in the signed Sale and Purchase Agreement (SPA).
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                ‌ج.	أقر/نقر بأن أي كتيبات أو مواد مرئية أو تسويقية مقدمة من قبل البائع هي لأغراض توضيحية فقط، ولا تعد جزءا من الالتزامات التعاقدية ما لم يتم إدراجها صراحة في اتفاقية البيع والشراء (SPA) الموقعة.
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                (d)	I / We acknowledge and agree that the final plans for the Project and the Unit are subject to approval by the competent authorities, including the master developer. I am / We are aware that at the time of this Reservation Form, the designs of the Unit, unit number, and the Project are not final and may be subject to change.
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                ‌د.	أقر/نقر ونتفق على أن المخططات النهائية للمشروع والوحدة تخضع لموافقة الجهات المختصة، بما في ذلك المطور الرئيسي. ونحن على علم بأن تصميم الوحدة، ورقم الوحدة، والمشروع، في وقت توقيع نموذج الحجز هذا، ليست نهائية وقد تكون عرضة للتغيير.
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                (e)	I / We acknowledge that this Reservation Form is personal to the Purchaser/s and not capable of assignment by the Purchaser/s and is not subject to my/our ability to secure a mortgage loan or finance from a bank or any third party.
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                ‌هـ.	أقر/نقر بأن هذا النموذج للحجز هو شخصي ويقتصر على المشتري/المشترين، ولا يجوز تحويله أو التنازل عنه من قبل المشتري/المشترين، كما أنه غير مشروط بقدرتي/قدرتنا على الحصول على قرض أو تمويل من بنك أو طرف ثالث.
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                (f)	I/We acknowledge that neither the Seller, nor any of their respective officers, directors, employees, representatives, servants or agents (the <strong>“Relevant Parties”</strong>) will have any obligation or liability whatsoever and howsoever arising to Purchaser or any third party arising from, out of, or in connection with, this Reservation Form, the transaction contemplated herein or attributable to any acts, errors or omission of the Relevant Parties or any applicable governmental authority. None of the Relevant Parties has, or will be deemed to have, made or given any terms, conditions, covenants, warranties or representations, express or implied (whether statutory or otherwise), with respect to this Reservation Form or the transaction contemplated herein.
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                ‌و.	أقر/نقر بأن البائع، أو أي من مسؤوليه أو مديريه أو موظفيه أو ممثليه أو خدامه أو وكلائه (ويشار إليهم بـ <strong>"الأطراف المعنية"</strong>)، لا يتحملون أي التزام أو مسؤولية على الإطلاق، أيا كان نوعها أو سببها، تجاه المشتري أو أي طرف ثالث، ناتجة عن أو متعلقة بهذا النموذج أو بالصفقة المتوقعة بموجبه، أو ناجمة عن أي فعل أو خطأ أو إغفال من قبل الأطراف المعنية أو أي جهة حكومية مختصة. كما لم ولن يعتبر أي من الأطراف المعنية قد قدم أو ضمن أو تعهد بأي شروط أو أحكام أو ضمانات أو إقرارات – صريحة كانت أم ضمنية (سواء كانت قانونية أم غير ذلك) – فيما يتعلق بهذا النموذج أو الصفقة المتوقعة بموجبه.
            </td>
        </tr>
        <tr>
            <td class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                (g)	I/We acknowledge and agree that the Purchaser/s shall promptly provide all information requested by the Seller for verification of any Payments, including identity documents, evidence relating to source of funds and bank details related to the transfer of the Payments to confirm payer details. Failure to comply with this requirement may result in the Seller returning or withholding any payments and the Purchaser/s shall have no claim against the Seller in this regard.
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                ‌ز.	أقر/نقر ونتفق على أن المشتري/المشترين يجب عليه/عليهم تزويد البائع فورا بكافة المعلومات المطلوبة للتحقق من أي مدفوعات، بما في ذلك مستندات الهوية، وأدلة على مصدر الأموال، وتفاصيل الحساب المصرفي المتعلقة بتحويل المدفوعات وذلك لتأكيد هوية الدافع. وقد يؤدي عدم الامتثال لهذا المتطلب إلى قيام البائع بإعادة أو حجز أي مبالغ مدفوعة، ولن يكون للمشتري/المشترين أي حق أو مطالبة ضد البائع في هذا الشأن.
            </td>
        </tr>
    </table>
    <br/>
    <table class="info-table">
        <tr>
            <td class="left-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                <h4>Name: Unique Saray Properties L.L.C </h4>
                <h4>Signed: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
                <h4>Date: &nbsp;&nbsp;&nbsp;{{ \Carbon\Carbon::now()->format('d-M-Y') }}&nbsp;&nbsp;</h4>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                <h4>الاسم: يونيك سراي للعقارت ش.ذ.م.م</h4>
                <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
                <h4 style="unicode-bidi: embed;">التاريخ:&nbsp;&nbsp; &nbsp;{{ \Carbon\Carbon::now()->locale('ar')->isoFormat('D-MMM-YYYY') }}&nbsp;</h4>
            </td>
        </tr>
        <tr>
            <td class="left-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                <h4>Name:  {{ $customerInfos[0]->name }} </h4>
                <h4>Signed: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
                <h4>Date: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                <h4>الاسم: {{ $customerInfos[0]->name }}  </h4>
                <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
                <h4>التاريخ: &nbsp;&nbsp;<img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
            </td>
        </tr>
        @isset($customerInfos[1])
        <tr>
            <td class="left-th" style="width: 49%; padding: 7px; text-align: justify; line-height: 2.5;">
                <h4>Name:  {{ ($customerInfos[1] ?? null)?->name }} </h4>
                <h4>Signed: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
                <h4>Date: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
            </td>
            <td style="text-align: center;"> </td>
            <td class="rtl-text right-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                <h4>الاسم: {{ ($customerInfos[1] ?? null)?->name }}  </h4>
                <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
                <h4>التاريخ: &nbsp;&nbsp;<img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___" /> </h4>
            </td>
        </tr>
        @endisset
    </table>
    <!-- Example page break
<div class="page-break"></div>
-->
</main>
</body>
</html>
