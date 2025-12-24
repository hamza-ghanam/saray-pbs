<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>SPA</title>
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

        @page :first {
            margin: 150px 45px 150px 45px; 
            header: html_MyHeader;
            footer: html_FirstFooter;
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

        .centred-text {
            text-align: center;
        }

        table.contract-table, table.contract-table td, table.contract-table th {
            border: 1px solid #ddd; /* light gray, slim border */
            padding: 5px;
        }

        .contract-table {
            width: 100%;
            border-collapse: collapse;
        }

        .contract-table td {
            padding: 15px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        /* English column */
        .contract-table .en {
            width: 49%;
            direction: ltr;
            text-align: justify;
            line-height: 1.3;
        }

        /* Arabic column */
        .contract-table .ar {
            width: 49%;
            direction: rtl;
            text-align: justify;
            line-height: 1.5;
        }

        .header-table {
            width: 100%;
            margin-bottom: 0.5em;
            margin-top: 1.5em;
        }

        .left-th {
            text-align: left;
            padding-left: 3px;
            white-space: pre-line;
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
            line-height: 1.6;
        }

        .meaning {
            text-align: justify !important;
            width: 32%;
            padding: 8px;
        }

        .term-col {
            width: 12%;
        }

        .def-col {
            width: 37%;
        }

        .separator {
            width: 2%;
        }

        .bullet-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bullet-table td {
            border: none;
            padding: 0;
            vertical-align: top;
            font-size: 14px;
        }

        .bullet-cell {
            width: 12px; 
            white-space: nowrap;
        }

        .bullet-text {
            padding-left: 5px; 
            text-align: justify; 
        }

        .bullet-text-rtl {
            direction: rtl;
            text-align: justify;
            padding-right: 5px;
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
    <div style="height: 30px; text-align: center;">
        <table style="border-collapse:collapse; border:none; width: 100%">
            <tr>
                <td style="width: 48%; text-align: left; font-weight: bold;">Buyer Initial</td>
                <td>{PAGENO}</td>
                <td style="width: 48%; text-align: right; font-weight: bold;">Seller Initial</td>
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

<htmlpagefooter name="FirstFooter">
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
    <h1 class="" style="text-align:center;">
        Saray Prime Residence Tower<br/>Residential Building
    </h1>
    <br/>
    @if($unit->building->image_path)
        <div style="text-align:center; margin-bottom: 30px;">
            <img
                src="file:///{{ str_replace('\\','/', storage_path('app/private/' . $unit->building->image_path)) }}"
                alt="Building image"
                style="width:90%; height:auto;"
            >
        </div>
    @endif
    <h2 class="" style="text-align:center;">
        Residential Unit Sale and Purchase Agreement (SPA)
    </h2>
    <br/>
    <h4 class="" style="text-align:center;">
        Wadi Al Safa 5<br/>
        Dubai Land – Dubai<br/>
        Plot No. (6488586)
    </h4>
    <br/>
    @foreach($customerInfos as $customerInfo)
        <h5 style="text-align: center">{{ $customerInfo->name_en }}</h5>
    @endforeach
    <hr/>
    <h5 style="text-align: center">Unit Number: {{ $unit->unit_no }}</h5>

    <div class="page-break"></div>


    <table class="contract-table">valueEn
        <tr>
            <th class="en">
                Saray Primmum Residence Tower – Residential Building <br/>
                Residential Unit Sale and Purchase Agreement <br>
                Dubai Wadi Al Safa 5 – Dubai Land - Dubai
            </th>
            <th class="ar">
                سراي بريميوم ريزدنس - بناية سكنية<br/>
                اتفاقية بيع وشراء وحدة سكنية<br/>
                وادي الصفا 5 – دبي لاند - دبي<br/>
            </th>
        </tr>
    </table>

    <table class="header-table">
        <tr>
            <th class="left-th">PARTICULARS</th>
            <th class="rtl-text right-th">بيانات الاتفاقية</th>
        </tr>
    </table>

    <table class="contract-table">
        <x-contract-clause-row
                valueEn="Name",
                valueAr="الاسم"
        />

        <tr>
            <td class="en">
                <b>1. Effective Date:</b>
                {{ \Carbon\Carbon::now()->format('d-M-Y') }}
            </td>
            <td class="separator"></td>
            <td class="ar">
                <b>1. تاريخ بدء السريان:</b>
                {{ \Carbon\Carbon::now()->locale('ar')->isoFormat('D-MMM-YYYY') }}&nbsp;
            </td>
        </tr>
        <tr>
            <td class="en">
                <b>2. Seller:</b>
                <strong>Unique Saray Properties L.L.C</strong>,Licensed by Dubai Department of Economy and Tourism under
                license No. (1343857), and licensed as a real estate developer by the DLD under license No. (2055).
                office 301 & 308, building 2, , Bay Square, Business bay, Dubai, UAE, PO Box [000000], Dubai, UAE<br/>
                Phone No.: +971 4 55 48787.<br/>
                Email: crm@uniquesaray.com,<br/>
                Its nominees, successors in title and assigns.
            </td>
            <td class="separator"></td>
            <td class="ar">
                <b>2. البائع:</b>
                <strong>شركة يونيك سراي للعقارت ش.ذ.م.م.</strong>
                والمرخصة لدى دبي للاقتصاد والسياحة برقم (1343857)، والمرخصة كمطور عقاري من دائرة الأراضي والأملاك دبي
                برقم (2055)
                الكائنة في دبي، منطقة الخليج التجاري، بي سيكوير، المبنى رقم (2)، مكتب رقم (301،302).<br/>
                هاتف رقم: [+971 4 55 48787.],<br/>
                البريد الالكتروني: crm@uniquesaray.com<br/>
                والأشخاص التي تعينهم والورثة في الملكية والمتنازل إليهم.
            </td>
        </tr>
    </table>
    <table class="contract-table">
        <tr>
            <th class="left-th" style="width: 27%;">3. Purchasers</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th" style="width: 27%;">3. المشترون</th>
        </tr>
        <tr>
            <th class="left-th" style="text-decoration: underline;">For individuals:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th" style="text-decoration: underline;">بالنسبة للأفراد:</th>
        </tr>
        @foreach ($customerInfos as $index => $customerInfo)

            <!-- Name -->
            <x-contract-bilingual-row
                label-en="Name"
                label-ar="الاسم"
                :value-en="$customerInfo->name_en"
                :value-ar="$customerInfo->name_ar"
                :index="'3.' . ($index + 1) . '.'"
            />

            <!-- Nationality -->
            <x-contract-bilingual-row
                label-en="Nationality"
                label-ar="الجنسية"
                :value-en="$customerInfo->nationality_en"
                :value-ar="$customerInfo->nationality_ar"
            />

            <!-- Passport NO. -->
            <tr>
                <th class="left-th">Passport NO.:</th>
                <td class="centred-text" colspan="2"> {{ $customerInfo->passport_number }} </td>
                <th class="rtl-text right-th">رقم جواز السفر:</th>
            </tr>

            <!-- Emirates ID NO. -->
            <tr>
                <th class="left-th">Emirates ID NO.:</th>
                <td class="centred-text" colspan="2"> {{ $customerInfo->emirates_id_number }} </td>
                <th class="rtl-text right-th">رقم الهوية الإماراتية:</th>
            </tr>

            <!-- Address -->
            <x-contract-bilingual-row
                label-en="Address"
                label-ar="العنوان"
                :value-en="$customerInfo->address_en"
                :value-ar="$customerInfo->address_ar"
            />

            <!-- Physical Address -->
            <x-contract-bilingual-row
                label-en="Physical Address"
                label-ar="العنوان الفعلي"
                :value-en="$customerInfo->address_en"
                :value-ar="$customerInfo->address_ar"
            />

            <!-- Phone NO. -->
            <tr>
                <th class="left-th">Phone NO.:</th>
                <td class="centred-text" colspan="2"> {{ $customerInfo->phone_number }} </td>
                <th class="rtl-text right-th">رقم الهاتف:</th>
            </tr>

            <!-- Fax NO. -->
            <tr>
                <th class="left-th">Fax NO.:</th>
                <td class="centred-text" colspan="2"> {{ $customerInfo->fax }} </td>
                <th class="rtl-text right-th">رقم الفاكس:</th>
            </tr>

            <!-- Email address. -->
            <tr>
                <th class="left-th">Email address.:</th>
                <td class="centred-text" colspan="2"> {{ $customerInfo->email }} </td>
                <th class="rtl-text right-th">البريد الإلكتروني:</th>
            </tr>
        @endforeach

        <!-- Corporations -->
        <tr>
            <th class="left-th" style="text-decoration: underline;">For corporations:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th" style="text-decoration: underline;">بالنسبة للشركات:</th>
        </tr>
        <tr>
            <th class="left-th">Nationality:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">الجنسية:</th>
        </tr>
        <tr>
            <th class="left-th">License/Registration NO.:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">رقم الترخيص/رقم التسجيل:</th>
        </tr>
        <tr>
            <th class="left-th">Address:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">العنوان:</th>
        </tr>
        <tr>
            <th class="left-th">Physical Address:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">العنوان الفعلي:</th>
        </tr>
        <tr>
            <th class="left-th">P.O. Box:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">ص.ب.:</th>
        </tr>
        <tr>
            <th class="left-th">Phone NO.:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">رقم الهاتف:</th>
        </tr>
        <tr>
            <th class="left-th">Fax NO.:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">رقم الفاكس:</th>
        </tr>
        <tr>
            <th class="left-th">Email address:</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">البريد الإلكتروني:</th>
        </tr>
        <tr>
            <th class="left-th">4. Property Details</th>
            <td class="centred-text" colspan="2">&nbsp;</td>
            <th class="rtl-text right-th">4. بيانات العقار:</th>
        </tr>
        <tr>
            <th class="left-th">Relevant Unit No</th>
            <td class="centred-text" colspan="2">{{ $unit->unit_no }}</td>
            <th class="rtl-text right-th">رقم الوحدة المعنية</th>
        </tr>
        <tr>
            <th class="left-th">
                Total Unit Area
                <small>
                    (including any balconies & terraces).
                </small>
            </th>
            <td class="centred-text" colspan="2">{{ $unit->total_square }} square
                feet/{{ $unit->getTotalSquareMAttribute() }}square metres
            </td>
            <th class="rtl-text right-th">
                مساحة الوحدة
                <small>(شامل أية بلكونات وشرفات)</small>
            </th>
        </tr>
        <tr>
            <th class="left-th">
                Number of car parking spaces:
                <small>to be allocated in accordance with Clause 4.6)</small>
            </th>
            <td class="centred-text" colspan="2">{{ $unit->parking }}</td>
            <th class="rtl-text right-th">
                عدد مواقف السيارات:
                <small>(تخصص بموجب أحكام البند 4.6)</small>
            </th>
        </tr>
        <tr>
            <th class="left-th">Project:</th>
            <td class="centred-text" colspan="2">{{ $unit->building->name }} Residential Building, Project
                No: {{ $unit->building->project_no }}<br/>
                سراي برايم ريزدنس - بناية سكنية، مشروع رقم: {{ $unit->building->project_no }}
            </td>
            <th class="rtl-text right-th">المشروع:</th>
        </tr>
        <tr>
            <th class="left-th">5. Purchase Price Incl. of VAT
                (<img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/>)
            </th>
            <td class="centred-text" colspan="2">{{ $booking->price }}</td>
            <th class="rtl-text right-th">5. سعر الشراء شـامل الضريبة (<img
                    src="{{ public_path('images/aed_symbol.svg') }}"
                    width="12" alt="AED"/>)
            </th>
        </tr>
        <tr>
            <th class="left-th" style="width: 27%;">6. Payment Schedule</th>
            <td class="centred-text" colspan="2">
                Set out in Schedule A<br/>
                منصوص عليه في الجدول أ
            </td>
            <th class="rtl-text right-th" style="width: 27%;">6. جدول سداد الدفعات</th>
        </tr>
        <tr>
            <th class="left-th">
                7.1. Escrow Account
            </th>
            <td class="centred-text" colspan="2"></td>
            <th class="rtl-text right-th">7.1. حساب الضمان:</th>
        </tr>
        <tr>
            <td class="left-th">
                Bank Name:
            </td>
            <td class="centred-text" colspan="2">Emirates NBD Bank PJSC</td>
            <th class="rtl-text right-th">
                اسم البنك:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Bank Branch Name and Address:
            </td>
            <td class="centred-text" colspan="2">Main Brach</td>
            <th class="rtl-text right-th">
                اسم وعنوان فرع البنك:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Account Name:
            </td>
            <td class="centred-text" colspan="2">SARAY PRIME RESIDENCE</td>
            <th class="rtl-text right-th">
                اسم الحساب:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Account Number:
            </td>
            <td class="centred-text" colspan="2">0205931383803</td>
            <th class="rtl-text right-th">
                رقم الحساب:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Swift Code:
            </td>
            <td class="centred-text" colspan="2">EBILAEADXXX</td>
            <th class="rtl-text right-th">
                رمز SWIFT:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                IBAN No:
            </td>
            <td class="centred-text" colspan="2">AE180260000205931383803</td>
            <th class="rtl-text right-th">
                رقم IBAN:
            </th>
        </tr>
        <tr>
            <th class="left-th">
                7.2. Corporate Account
            </th>
            <td class="centred-text" colspan="2"></td>
            <th class="rtl-text right-th">7.2. حساب الشركات:</th>
        </tr>
        <tr>
            <td class="left-th">
                Bank Name:
            </td>
            <td class="centred-text" colspan="2">Emirates NBD Bank PJSC</td>
            <th class="rtl-text right-th">
                اسم البنك:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Bank Branch Name and Address:
            </td>
            <td class="centred-text" colspan="2">Main Brach</td>
            <th class="rtl-text right-th">
                اسم وعنوان فرع البنك:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Account Name:
            </td>
            <td class="centred-text" colspan="2">UNIQUE SARAY PROPERTIES LLC.</td>
            <th class="rtl-text right-th">
                اسم الحساب:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Account Number:
            </td>
            <td class="centred-text" colspan="2">1015931383801</td>
            <th class="rtl-text right-th">
                رقم الحساب:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Currency:
            </td>
            <td class="centred-text" colspan="2">
                AED (<img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/>)
            </td>
            <th class="rtl-text right-th">
                العملة:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                Swift Code:
            </td>
            <td class="centred-text" colspan="2">EBILAEAD</td>
            <th class="rtl-text right-th">
                رمز SWIFT:
            </th>
        </tr>
        <tr>
            <td class="left-th">
                IBAN No:
            </td>
            <td class="centred-text" colspan="2">AE940260001015931383801</td>
            <th class="rtl-text right-th">
                رقم IBAN:
            </th>
        </tr>
        <tr>
            <th class="left-th">
                8. Anticipated Completion Date
                <small>(subject to extension or earlier completion in accordance with Clause 4.1)</small>
            </th>
            <td class="centred-text" colspan="2">
                {{ \Carbon\Carbon::parse($unit->building->ecd)->format('d-M-Y') }}
            </td>
            <th class="rtl-text right-th">8. التاريخ المتوقع للإنجاز
                <small> (عرضة للتمديد أو الإنجاز المبكر بموجب أحكام البند 4-1)</small>
            </th>
        </tr>
    </table>
    <table class="contract-table">
        <tr>
            <td class="en">
                <b>9. Permitted Use: </b>
                Residential Apartment Use
            </td>
            <td class="separator"></td>
            <td class="ar">
                <b>9. الاستخدام المصرح به: </b>
                الاستخدام للأغراض السكنية
            </td>
        </tr>
        <tr>
            <td class="en">
                <b>10. Late Payment Penalty </b>
                An amount calculated on a daily basis being, the sum of:<br/>
                <ol type="a">
                    <li>
                        <img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/> 500 (Five Hundred
                        UAE Dirhams) per day; plus
                    </li>
                    <li>
                        One percent (1%) per month (or part thereof) of the overdue amount, calculated on a
                        compounding basis.
                    </li>
                </ol>
            </td>
            <td class="separator"></td>
            <td class="ar">
                <b>10. غرامة تأخير السداد</b>
                مبلغ يحسب على أساس يومي وقدره:<br/>
                <ol type="a">
                    <li>
                        500 درهم (خمسمائة درهم إماراتي) في اليوم؛ بالإضافة إلى
                    </li>
                    <li>
                        واحد في المائة (1%) لكل شهر (أو جزء من الشهر) من المبلغ المتأخر، يتم حسابه على أساس متراكم.
                    </li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="en">
                The Seller agrees to sell the Relevant Unit to the Purchaser and the Purchaser agrees to purchase the
                Relevant Unit from the Seller for the Purchase Price set out above.
            </td>
            <td class="separator"></td>
            <td class="ar">
                يوافق البائع على أن يبيع الوحدة المعنية إلى المشتري ويوافق المشتري على أن يشتري الوحدة المعنية من البائع
                في مقابل سعر الشراء المنصوص عليه أعلاه.
            </td>
        </tr>
        <tr>
            <th class="en">
                This Agreement shall comprise and be subject to and the Particulars, and the Schedules which form an
                integral part of this Agreement. The Purchaser hereby confirms that he/she has read and understood this
                Agreement and agrees and undertakes to be bound by its terms:
            </th>
            <td class="separator"></td>
            <th class="ar">
                تضم هذه الاتفاقية بيانات الاتفاقية والملاحق التي تشكل جزءا لا يتجزأ من هذه الاتفاقية. ويؤكد المشتري
                بموجبه أنه قد اطلع على هذه الاتفاقية وفهم مضمونها ويوافق على الالتزام بشروطها ويتعهد بذلك:
            </th>
        </tr>
        <tr>
            <td class="en">
                <b>Signed & delivered for and on behalf of the Seller:</b>
                <h4>Name: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/></h4>
                <h4>Signature: <img src="{{ public_path('images/black_line.svg') }}" width="220" height="2" alt="___"/>
                </h4>
                <h4>Date: &nbsp;&nbsp;&nbsp;{{ \Carbon\Carbon::now()->format('d-M-Y') }}&nbsp;&nbsp;</h4>
            </td>
            <td class="separator"></td>
            <td class="ar">
                <b> وقع على هذه الاتفاقية وإبرامها نيابة عن البائع:</b>
                <h4>الاسم: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/></h4>
                <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="243" height="2" alt="___"/>
                </h4>
                <h4 style="unicode-bidi: embed;">التاريخ:&nbsp;&nbsp;
                    &nbsp;{{ \Carbon\Carbon::now()->locale('ar')->isoFormat('D-MMM-YYYY') }}&nbsp;</h4>
            </td>
        </tr>
        <tr>
            <td class="en">
                <b>Signed and delivered by named Purchaser / Purchaser’s Authorised Signatory:</b>
                <h4>Name: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/></h4>
                <h4>Position: <img src="{{ public_path('images/black_line.svg') }}" width="232" height="2" alt="___"/>
                </h4>
                <h4>Signature: <img src="{{ public_path('images/black_line.svg') }}" width="220" height="2" alt="___"/>
                </h4>
                <h4>Date: &nbsp;&nbsp;&nbsp;{{ \Carbon\Carbon::now()->format('d-M-Y') }}&nbsp;&nbsp;</h4>
            </td>
            <td class="separator"></td>
            <td class="ar">
                <b> أبرم هذه الاتفاقية ووقع عليها المشتري المسمى/ المفوض بالتوقيع نيابة عن المشتري:</b>
                <h4>الاسم: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/></h4>
                <h4>الصفة: <img src="{{ public_path('images/black_line.svg') }}" width="247" height="2" alt="___"/></h4>
                <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="243" height="2" alt="___"/>
                </h4>
                <h4 style="unicode-bidi: embed;">التاريخ:&nbsp;&nbsp;
                    &nbsp;{{ \Carbon\Carbon::now()->locale('ar')->isoFormat('D-MMM-YYYY') }}&nbsp;</h4>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    <table class="contract-table">
        <tr>
            <td class="en">
                <strong>THIS AGREEMENT</strong> is made on the Effective Date.
            </td>
            <td class="separator"></td>
            <td class="ar">
                أبرمت <strong>هذه الاتفاقية</strong> في تاريخ بدء السريان.
            </td>
        </tr>
        <tr>
            <td class="en">
                BETWEEN:
            </td>
            <td class="separator"></td>
            <td class="ar">
                فيما بين كل من:
            </td>
        </tr>
        <tr>
            <td class="en">
                (1) The Seller named in Item 2 of the Particulars; and
            </td>
            <td class="separator"></td>
            <td class="ar">
                (1) البائع المسمى في البند (2) من بيانات الاتفاقية؛
            </td>
        </tr>
        <tr>
            <td class="en">
                (2) The Purchaser named in Item 3 of the Particulars.
            </td>
            <td class="separator"></td>
            <td class="ar">
                (2) المشتري المسمى في البند (3) من بيانات الاتفاقية؛
            </td>
        </tr>
        <tr>
            <td class="en">
                <strong>IT IS AGREED</strong> as follows:
            </td>
            <td class="separator"></td>
            <td class="ar">
                وقد <strong>اتفق الطرفان تراضيا</strong> على ما يلي:
            </td>
        </tr>
        <tr>
            <th class="en">
                1. INTERPRETATION
            </th>
            <td class="separator"></td>
            <th class="ar">
                1. التفسير
            </th>
        </tr>
        <tr>
            <td class="en">
                1.1 In this Agreement, except where the context otherwise requires, the following words shall have the
                following meaning
            </td>
            <td class="separator"></td>
            <td class="ar">
                1.1 في هذه الاتفاقية، ما لم يتطلب السياق خلاف ذلك، يكون للكلمات التالية المعاني المبينة قرين كل منها على
                النحو التالي:
            </td>
        </tr>
    </table>

    <table class="contract-table">
        <tr>
            <th class="left-th" style="width: 17%;">
                Administration Fees
            </th>
            <td class="meaning">
                means the administration fee charged by the Seller pursuant to Clause 14.2(b) provided that such fee
                shall not exceed the maximum prescribed by Applicable Law.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">
                يقصد بها الرسوم الإدارية التي يفرضها البائع طبقا للبند 13-2(ب)، بشرط ألا تتجاوز تلك الرسوم الحد الأقصى
                المنصوص عليه في القانون المعمول به.
            </td>
            <th class="rtl-text right-th" style="width: 17%;">
                الرسوم الإدارية
            </th>
        </tr>
        <tr>
            <th class="left-th">
                AED or <img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/>
            </th>
            <td class="meaning">
                means the lawful currency of the United Arab Emirates, being as of the Effective Date, Dirhams.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">
                يقصد به الدرهم وهو العملة الرسمية لدولة الإمارات العربية المتحدة اعتبارا من تاريخ بدء سريان هذه
                الاتفاقية.
            </td>
            <th class="rtl-text right-th">
                درهم أو <img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/>
            </th>
        </tr>
        <tr>
            <th class="left-th">
                Agreement
            </th>
            <td class="meaning">
                means this Agreement including the Particulars and the Schedules.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">
                يقصد بها هذه الاتفاقية، ويشمل ذلك بيانات الاتفاقية والملاحق.
            </td>
            <th class="rtl-text right-th">
                الاتفاقية
            </th>
        </tr>
        <tr>
            <th class="left-th">
                Anticipated Completion Date
            </th>
            <td class="meaning">
                means the quarter (comprising four (4) consecutive three (3) month periods of calendar year with the
                first of such periods commencing 1 January) stated in Item 7 of the Particulars and as may be extended
                pursuant to Clause 4.1.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">
                يقصد به ربع السنة (الذي يتكون من أربع (4) فترات في السنة الميلادية كل فترة منها عبارة عن ثلاثة أشهر
                متتالية، بحيث تبدأ الفترة الأولى من تلك الفترات الأربع في 1 يناير) المحدد في البند (7) من بيانات
                الاتفاقية، وحسبما يجوز تمديده طبقا للبند (4-1)
            </td>
            <th class="rtl-text right-th">
                التاريخ المتوقع للإنجاز
            </th>
        </tr>
        <tr>
            <th class="left-th">
                Applicable Law
            </th>
            <td class="meaning">
                means the laws, decrees, resolutions, regulations and/or any other applicable legislation, enacted or to
                be enacted either in the Emirate of Dubai or by the Federal Government of the UAE including but not
                restricted to the Jointly Owned Property Law before or after the Effective Date.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">
                يقصد به القوانين والمراسيم والقرارات واللوائح وأية قوانين أخرى معمول بها، أو صادرة أو مقرر إصدارها سواء
                أكان ذلك في إمارة دبي أو من قبل الحكومة الاتحادية لدولة الإمارات العربية المتحدة، ويشمل ذلك، على سبيل
                الذكر وليس الحصر، قانون ملكية العقارات المشتركة الصادر قبل تاريخ بدء السريان أو بعده.
            </td>
            <th class="rtl-text right-th">
                القانون المعمول به
            </th>
        </tr>
        <tr>
            <th class="left-th">Association Constitution</th>
            <td class="meaning">means any constitution of an Owners Association as set out in the Directions.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به أي نظام أساسي لجمعية الملاك حسبما ينص عليه في التوجيهات.
            </td>
            <th class="rtl-text right-th">النظام الأساسي لجمعية الملاك</th>
        </tr>
        <tr>
            <th class="left-th">Booking Form</th>
            <td class="meaning">means any booking form that the Purchaser signs to reserve the Property in their
                name prior to the execution of this Agreement.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به أي نموذج حجز يوقع المشتري عليه لحجز العقار باسمه قبل تحرير
                هذه
                الاتفاقية.
            </td>
            <th class="rtl-text right-th">نموذج الحجز</th>
        </tr>
        <tr>
            <th class="left-th">Common Areas</th>
            <td class="meaning">has the same meaning ascribed to the term within the Jointly Owned Property
                Law.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يكون لهذا المصطلح نفس المعنى المحدد له في قانون ملكية العقارات
                المشتركة.
            </td>
            <th class="rtl-text right-th">المساحات المشتركة</th>
        </tr>
        <tr>
            <th class="left-th">Communal Facilities</th>
            <td class="meaning">means all open areas, services, facilities, roads, pavements, gardens, utility
                and administrative buildings or areas, installations, improvements and any other common assets of the
                Master Community that are intended for use by all Plot Owners and Unit Owners that do not form part of
                the title of any Plot but are residuary lands and buildings owned and managed by the Master Developer.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها كافة المساحات المفتوحة والخدمات والمرافق والطرق والأرصفة
                والحدائق ومباني المرافق والمباني الإدارية أو المساحات والمنشآت والتحسينات، وأية موجودات مشتركة أخرى في
                المجمع الرئيسي مقصود استخدامها من قبل كافة مالكي قطعة الأرض ومالكي الوحدات، ولا تشكل جزءا من ملكية أية
                قطعة أرض ولكنها أراض ومبان متبقية يملكها ويديرها المطور الرئيسي.
            </td>
            <th class="rtl-text right-th">المرافق المشتركة</th>
        </tr>
        <tr>
            <th class="left-th">Community Charges</th>
            <td class="meaning">means the amount payable by a Plot Owner (including any Owners Association where
                a Jointly Owned Plot) and Unit Owners as a contribution towards the common expenses of the Master
                Community, including but not limited to, expenses relating to management, administration, repairs,
                replacements, maintenance, cleaning, security, facilities management, sinking fund, water, electricity,
                gas, sewage, chilled water services and other utility connection and consumption charges, and as are
                assessed and payable pursuant to the terms of the Master Community Declaration and Applicable Law.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها المبلغ المستحق على مالك قطعة الأرض دفعه (ويشمل ذلك أي
                جمعية
                ملاك في حالة قطع الأرض المملوكة ملكية مشتركة) وملاك الوحدات بوصفه إسهاما في دفع مصاريف المجمع الرئيسي،
                بما في ذلك، على سبيل الذكر وليس الحصر، المصاريف المتعلقة بالإدارة والتسيير والإصلاحات والاستبدالات
                والصيانة والنظافة والأمن وإدارة المرافق ومخصص مال الاستهلاك ومصاريف خدمات المياه والكهرباء والغاز والصرف
                الصحي والمياه المبردة ومصاريف توصيلات المرافق الأخرى ومصاريف الاستهلاك، وحسبما يتم تقييمها، ويستحق دفعها
                طبقا لشروط نظام المجمع الرئيسي وطبقا للقانون المعمول به.
            </td>
            <th class="rtl-text right-th">رسوم المجمع</th>
        </tr>
        <tr>
            <th class="left-th">Completion Date</th>
            <td class="meaning">means the date specified in the notice of the Seller to the Purchaser pursuant
                to Clause 4.2 provided the completion date shall not be earlier than the date that the Project is
                completed as certified by the Project Manager whose decision shall be final and binding upon the
                Parties.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به التاريخ المحدد في الإخطار المرسل من البائع إلى المشتري طبقا
                للبند (4-2)، بشرط ألا يكون تاريخ الإنجاز سابقا للتاريخ الذي ينجز فيه المشروع حسب اعتماد مدير المشاريع
                الذي يكون قراره نهائيا وملزما على الطرفين.
            </td>
            <th class="rtl-text right-th">تاريخ الإنجاز</th>
        </tr>
        <tr>
            <th class="left-th">Defects</th>
            <td class="meaning">has the meaning set out in Clause 9.4.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">تستخدم بالمعنى الموضح في البند (9-4)</td>
            <th class="rtl-text right-th">العيوب</th>
        </tr>
        <tr>
            <th class="left-th">Deregister or Deregistration</th>
            <td class="meaning">means the deregistration of the sale of the Property and removal of the
                Purchaser’s details from the Interim Real Estate Register.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به إلغاء تسجيل بيع العقار وشطب بيانات المشتري من السجل العقاري
                المؤقت.
            </td>
            <th class="rtl-text right-th">يلغي التسجيل أو إلغاء التسجيل</th>
        </tr>
        <tr>
            <th class="left-th">Deregistration Fees</th>
            <td class="meaning">means any fees charged by the Land Department from time to time associated with
                Deregistration.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها الرسوم التي تفرضها دائرة الأراضي والأملاك من حين لآخر فيما
                يتعلق بإلغاء التسجيل.
            </td>
            <th class="rtl-text right-th">رسوم إلغاء التسجيل</th>
        </tr>
        <tr>
            <th class="left-th">Directions</th>
            <td class="meaning">means the Directions promulgated pursuant to the Jointly Owned Property Law
                including:
                <ol type="A">
                    <li>the Direction for Association Constitution;</li>
                    <li>the Direction for General Regulation;</li>
                    <li>the Direction for Jointly Owned Property Declaration;</li>
                    <li>and the Survey Directions.</li>
                </ol>
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها التوجيهات الصادرة طبقا لقانون ملكية العقارات المشتركة، بما
                في ذلك:
                <ol type="A">
                    <li>التوجيه بشأن النظام الأساسي لجمعية الملاك؛</li>
                    <li>التوجيه بشأن النظام العام؛</li>
                    <li>التوجيه بشأن نظام الملكية المشتركة؛</li>
                    <li>توجيهات المسح.</li>
                </ol>
            </td>
            <th class="rtl-text right-th">التوجيهات</th>
        </tr>
        <tr>
            <th class="left-th">Effective Date</th>
            <td class="meaning">means the date stated in Item (1) of the Particulars or, where no such date is
                apparent, the date this Agreement is signed by the last of the Parties.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به التاريخ المحدد في البند (1) من بيانات الاتفاقية أو في حال
                إذا
                لم يكن ذلك التاريخ واضحا، تاريخ توقيع آخر طرف من الطرفين على هذه الاتفاقية.
            </td>
            <th class="rtl-text right-th">تاريخ بدء السريان</th>
        </tr>
        <tr>
            <th class="left-th">Entitlement</th>
            <td class="meaning">means the proportion determined in accordance with the Directions in which each
                Unit Owner shares in the Common Areas which will also be used to determine:
                <ol type="A">
                    <li>the value of the vote of the Unit Owner in any case where a poll is called at any General
                        Assembly; and/or
                    </li>
                    <li>the proportion in which a Unit Owner shall contribute towards the Service Charges.</li>
                </ol>
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به النسبة المحددة بموجب التوجيهات التي يشترك فيها كل مالك وحدة
                في
                المساحات المشتركة التي سوف تستخدم لتحديد: قيمة تصويت مالك الوحدة في أي حال من الأحوال إذا دعي للتصويت
                في أي اجتماع من اجتماعات الجمعية العمومية؛ أو النسبة التي يساهم بها مالك الوحدة وفاء بمصاريف
                الخدمة.
            </td>
            <th class="rtl-text right-th">الاحقية</th>
        </tr>
        <tr>
            <th class="left-th">Escrow Account</th>
            <td class="meaning">means an escrow account established by the Seller pursuant to an escrow
                agreement with a financial institution in accordance with Dubai Law No. 8 of 2007 concerning Real Estate
                Development Trust Accounts in the Emirate of Dubai.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به حساب الضمان الذي ينشئه البائع طبقا لاتفاقية الحفظ لدى مؤسسة
                مالية بموجب قانون إمارة دبي رقم (8) لسنة 2007 بشأن حسابات ضمان التطوير العقاري في إمارة دبي.
            </td>
            <th class="rtl-text right-th">حساب الضمان</th>
        </tr>
        <tr>
            <th class="left-th">Fees</th>
            <td class="meaning">means the Land Department’s fees as defined in Clause 5.4.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها الرسوم التي تفرضها دائرة الأراضي والأملاك حسبما ينص عليه
                في
                البند (5-4)
            </td>
            <th class="rtl-text right-th">الرسوم</th>
        </tr>
        <tr>
            <th class="left-th">Final Instalment</th>
            <td class="meaning">means the final instalment of the Purchase Price, as set out in the Payment
                Schedule.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به القسط الأخير من سعر الشراء، حسبما هو منصوص عليه في جدول
                سداد
                الدفعات.
            </td>
            <th class="rtl-text right-th">القسط الأخير</th>
        </tr>
        <tr>
            <th class="left-th">Force Majeure Event(s)</th>
            <td class="meaning">means causes beyond the Seller’s reasonable control including but not limited,
                fire, windstorm, flood, earthquake or other natural disasters; act of any sovereign including but not
                limited to terrorism, acts of war, invasion, act of foreign enemies, hostilities (whether war declared
                or not), civil war, riots, rebellion, revolution, insurrection, military or usurped power or
                confiscation, nationalisation, requisition, destruction, accidents, or damage to property by or under
                the order of any government or public or local authority, decisions of the Relevant Authorities, or
                imposition of government sanction, embargo or similar action; labour disputes, including but not limited
                to strike, lockout, or boycott; interruption or failure of utility service including but not limited to
                electric power, gas, water or telephone services; failure of the transportation of any personnel,
                equipment, machinery or material required by the Seller for completion of the Project; breach of
                contract by any essential contractor or subcontractor or any other matter or cause beyond the reasonable
                control of the Seller.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها الأسباب التي تخرج عن إرادة البائع على النحو المعقول، بما
                في
                ذلك، على سبيل الذكر وليس الحصر، الحريق أو العواصف أو الفيضانات أو الزلازل أو الكوارث الطبيعية الأخرى أو
                أفعال أية سلطة بما فيها، على سبيل المثال وليس الحصر، أعمال الحرب أو الغزو أو عمل الأعداء الأجانب أو
                العداوات (سواء أكانت الحرب معلنة من عدمه)، أو الحرب الأهلية أو أعمال الشغب أو التمرد أو الثورة أو
                العصيان المسلح أو القوة العسكرية أو القوة المغتصبة أو المصادرة أو التأميم أو الاستيلاء أو التدمير أو
                الحوادث أو إلحاق الضرر بالملكية بواسطة أو بموجب أمر صادر عن أية حكومة أو قرارات سلطة عامة أو محلية، أو
                فرض عقوبات حكومية أو فرض حظر أو أي إجراء مشابه، أو النزاعات العمالية ومنها، على سبيل المثال وليس الحصر،
                الإضراب أو الإغلاق التعجيزي أو المقاطعة أو إيقاف العمل أو تعطل خدمة المرافق بما في ذلك، على سبيل المثال
                وليس الحصر، تعطل الطاقة الكهربائية أو تعطل خدمات الغاز أو المياه أو الهاتف أو تعطل نقل أي موظفين أو
                معدات أو آليات أو خامات يطلبها البائع لإنجاز المشروع، أو مخالفة العقد من جانب أي مقاول رئيسي أو مقاول من
                الباطن أو أي أمر آخر أو سبب آخر يخرج عن إرادة البائع على النحو المعقول.
            </td>
            <th class="rtl-text right-th">حدث (أحداث) القوة القاهرة</th>
        </tr>
        <tr>
            <th class="left-th">General Assembly</th>
            <td class="meaning">has the same meaning ascribed to the term within the Association Constitution.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يكون لهذا المصطلح نفس المعنى المحدد له في النظام الأساسي للجمعية.
            </td>
            <th class="rtl-text right-th">الجمعية العمومية</th>
        </tr>
        <tr>
            <th class="left-th">Instalment Date(s)</th>
            <td class="meaning">means any date or dates set out in the Payment Schedule upon which any Payment
                Instalment is due including the Completion Date as may be amended pursuant to Clauses 3.4 and 3.5.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به أي تاريخ أو تواريخ منصوص عليها في جدول سداد الدفعات يستحق
                فيها
                سداد قسط أية دفعة بما في ذلك تاريخ الإنجاز حسبما يجوز تعديله طبقا للبند (3-4) والبند (3-5)
            </td>
            <th class="rtl-text right-th">تاريخ (تواريخ) القسط</th>
        </tr>
        <tr>
            <th class="left-th">Intellectual Property</th>
            <td class="meaning">has the meaning set out in Clause 12.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يكون لها المنصوص عليه في البند (12)</td>
            <th class="rtl-text right-th">الملكية الفكرية</th>
        </tr>
        <tr>
            <th class="left-th">Interim Real Estate Register</th>
            <td class="meaning">has the same meaning ascribed to the term within Law No 13 of 2008 Regulating
                the Interim Register.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يكون لهذا المصطلح المعنى المحدد له في القانون رقم (13) لسنة 2008
                بشأن
                تنظيم السجل المؤقت.
            </td>
            <th class="rtl-text right-th">السجل العقاري المؤقت</th>
        </tr>
        <tr>
            <th class="left-th">Jointly Owned Plot</th>
            <td class="meaning">means a Plot, which by virtue of its subdivision into Units and Common Areas, is
                regulated pursuant to the Jointly Owned Property Law and Directions.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها جزء، بموجب تقسيمها إلى وحدات ومساحات مشتركة، تنظم طبقا
                لقانون ملكية العقارات المشتركة والتوجيهات.
            </td>
            <th class="rtl-text right-th">الأجزاء المشتركة</th>
        </tr>
        <tr>
            <th class="left-th">Jointly Owned Property Declaration</th>
            <td class="meaning">has the meaning ascribed to the term in the Directions, which will include:
                <ol type="A">
                    <li>the community rules governing the management, administration and maintenance of the Common
                        Areas;
                    </li>
                    <li>the relevant Entitlements;</li>
                    <li>the relevant Common Areas plans; the draft form of which, for the Project, is set
                        out in Schedule C, and which is subject to amendment at the sole and absolute discretion of the
                        Seller.
                    </li>
                </ol>
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يكون لهذا المصطلح المعنى المحدد له في التوجيهات، ويتضمن:
                <ol type="A">
                    <li>قواعد
                        المجمع التي تحكم إدارة وتسيير المساحات المشتركة وصيانتها؛
                    </li>
                    <li>
                        المستحقات ذات الصلة؛
                    </li>
                    <li>
                        المخططات ذات
                        الصلة للمساحات المشتركة؛ مسودة نموذج للمشروع حسبما هو مرفق ببيان الإفصاح وتخضع للتعديل حسب
                        التقدير
                        المطلق والوحيد للبائع.
                    </li>
                </ol>
            </td>
            <th class="rtl-text right-th">نظام الملكية المشتركة</th>
        </tr>
        <tr>
            <th class="left-th">Jointly Owned Property Law</th>
            <td class="meaning">means Law No. 27 of 2007 / Law No. 6 of 2019 and directions Concerning Ownership
                of Jointly Owned Property in the Emirate of Dubai.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به القانون رقم (27) لسنة 2007 / القانون رقم( 6 ) لسنة 2019
                والملحقات بشأن ملكية العقارات المشتركة في إمارة دبي.
            </td>
            <th class="rtl-text right-th">قانون الملكية المشتركة</th>
        </tr>
        <tr>
            <th class="left-th">Land Department</th>
            <td class="meaning">means the Land Department of the Government of Dubai.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها دائرة الأراضي والأملاك التابعة لحكومة دبي.</td>
            <th class="rtl-text right-th">دائرة الأراضي والأملاك</th>
        </tr>
        <tr>
            <th class="left-th">Late Payment Penalty</th>
            <td class="meaning">means the fee payable by the Purchaser to the Seller for any delay in making any
                payment pursuant this Agreement and which shall accrue on a daily basis at the rate specified in Item 9
                of the Particulars on the amount outstanding from the due date of the payment to the date of actual
                payment
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها الرسم المستحق على المشتري المفروض دفعه إلى البائع عن أي
                تأخير
                في سداد أية دفعة طبقا لهذه الاتفاقية يستحق دفعها بصفة يومية بالسعر المحدد في البند (9) من بيانات
                الاتفاقية على المبلغ المستحق من تاريخ استحقاق سداد الدفعة إلى تاريخ السداد الفعلي
            </td>
            <th class="rtl-text right-th">غرامة تأخير السداد</th>
        </tr>
        <tr>
            <th class="left-th">Manager</th>
            <td class="meaning">means the association manager appointed by the Seller pursuant to the terms of
                this Agreement to undertake the Owners Association management functions pursuant to the Management
                Agreement.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به مدير الجمعية المعين من قبل البائع وفقا لبنود هذه الاتفاقية
                للقيام بمهام إدارة جمعية الملاك وفقا لاتفاقية الإدارة.
            </td>
            <th class="rtl-text right-th">المدير</th>
        </tr>
        <tr>
            <th class="left-th">Management Agreement</th>
            <td class="meaning">means the agreement between the Owners Association or the Seller (on behalf of
                the Owners Association) and the Manager, on terms acceptable to the Seller in its discretion.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها اتفاقية الإدارة المبرمة فيما بين جمعية الملاك أو البائع
                (بالنيابة عن جمعية الملاك) وبين المدير، بناء على شروط وأحكام يوافق عليها البائع وفقا لتقديره.
            </td>
            <th class="rtl-text right-th">اتفاقية الإدارة</th>
        </tr>
        <tr>
            <th class="left-th">Master Community</th>
            <td class="meaning">means the master community in which the Project Plot is situated currently known
                as Dubai Land (or such other name as the community in which the Project Plot is situated may become
                known) comprising municipal type infrastructure which is or will be subdivided into Plots and Communal
                Facilities generally and includes all and any extensions or contractions of the Master Community from
                time to time.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به المجمع الرئيسي التي تقع فيه أرض المشروع والمعروفة في الوقت
                الحالي باسم (دبي لاند)، (أو أي اسم آخر قد يعرف به المجمع الذي تقع فيه أرض المشروع) ويتضمن
                بنية تحتية بلدية النوع تقسم أو سوف تقسم إلى قطع أرض ومرافق مشتركة بصفة عامة، ويتضمن كافة وأية توسعات
                أو اختصارات للمجمع الرئيسي من حين لآخر.
            </td>
            <th class="rtl-text right-th">المجمع الرئيسي</th>
        </tr>
        <tr>
            <th class="left-th">Master Community Declaration</th>
            <td class="meaning">means the master community declaration as declared by the Master Developer for
                the Master Community from time to time in its sole and absolute discretion.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به نظام المجمع الرئيسي الذي يحرره المطور الرئيسي للمجمع
                الرئيسي
                من حين لآخر حسب تقديره المطلق والوحيد.
            </td>
            <th class="rtl-text right-th">نظام المجمع الرئيسي</th>
        </tr>
        <tr>
            <th class="left-th">Master Developer</th>
            <td class="meaning">means Dubai Land Residences LLC, its subsidiaries or affiliates and any
                successors and assignees, as the master developer of the Master Community or any other entity that may
                assume responsibility for the development and/or management of the Master Community.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها دبي لاند السكني ذ.م.م. أو شركاتها الفرعية أو التابعة وأية
                ورثة لها أو متنازل إليهم، باعتبارها المطور الرئيسي للمجمع الرئيسي أو أي كيان آخر يمكن أن يتحمل المسؤولية
                عن تطوير المجمع الرئيسي أو إدارته.
            </td>
            <th class="rtl-text right-th">المطور الرئيسي</th>
        </tr>
        <tr>
            <th class="left-th">Master Plan</th>
            <td class="meaning">means the master plan of the Master Community as may be amended from time to
                time by the Master Developer in its sole and absolute discretion.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به المخطط الرئيسي للمجمع الرئيسي حسبما يجري المطور الرئيسي
                التعديلات عليه من حين لآخر حسب تقديره المطلق والوحيد.
            </td>
            <th class="rtl-text right-th">المخطط الرئيسي</th>
        </tr>
        <tr>
            <th class="left-th">Owners Association</th>
            <td class="meaning">has the meaning ascribed to the term in the Jointly Owned Property Law.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يكون لهذا المصطلح نفس المعنى المحدد له في قانون ملكية العقارات
                المشتركة.
            </td>
            <th class="rtl-text right-th">جمعية الملاك</th>
        </tr>
        <tr>
            <th class="left-th">Parking Bay(s)</th>
            <td class="meaning">means any parking bay(s) to be allocated to the Purchaser on the Completion Date
                as indicated in Item 4 of the Particulars and allocated in accordance with Clause 4.6.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها أي موقف (مواقف) انتظار يخصص للمشتري في تاريخ الإنجاز حسبما
                هو مبين في البند (4) من بيانات الاتفاقية وتخصص بموجب البند (4-6)
            </td>
            <th class="rtl-text right-th">موقف (مواقف) انتظار السيارات</th>
        </tr>
        <tr>
            <th class="left-th">Particulars</th>
            <td class="meaning">means the particulars of this Agreement.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها بيانات هذه الاتفاقية</td>
            <th class="rtl-text right-th">بيانات الاتفاقية</th>
        </tr>
        <tr>
            <th class="left-th">Parties</th>
            <td class="meaning">means collectively the Seller and the Purchaser and &quot;Party&quot; means either one
                of
                them.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها كل من البائع والمشتري مجتمعين، ويقصد بلفظة &quot;الطرف&quot;
                أي منهما.
            </td>
            <th class="rtl-text right-th">الطرفان ( أو &quot;الطرفين&quot; (حسب السياق))</th>
        </tr>
        <tr>
            <th class="left-th">Payment Instalment(s)</th>
            <td class="meaning">means the individual payment instalment(s) of the Purchase Price as set out in
                the Payment Schedule.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به قسط (أقساط) سداد سعر الشراء الفردي (أو الفردية)، حسبما هو
                منصوص عليه في جدول سداد الدفعات.
            </td>
            <th class="rtl-text right-th">قسط (أقساط) السداد</th>
        </tr>
        <tr>
            <th class="left-th">Payment Schedule</th>
            <td class="meaning">means the payment schedule set out Schedule A.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به جدول سداد الدفعات المنصوص عليه في الملحق (أ).</td>
            <th class="rtl-text right-th">جدول سداد الدفعات</th>
        </tr>
        <tr>
            <th class="left-th">Payment Schedule Notes</th>
            <td class="meaning">means the “Payment Schedule Notes” forming part of the Payment Schedule.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها &quot;الملاحظات على جدول سداد الدفعات&quot; التي تشكل جزءا
                من جدول سداد الدفعات.
            </td>
            <th class="rtl-text right-th">الملاحظات على جدول سداد الدفعات</th>
        </tr>
        <tr>
            <th class="left-th">Permitted Use</th>
            <td class="meaning">means the permitted use set out in Item 8 of the Particulars.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به الاستخدام المصرح به المنصوص عليه في البند (8) من البيانات.
            </td>
            <th class="rtl-text right-th">الاستخدام المصرح به</th>
        </tr>
        <tr>
            <th class="left-th">Plot(s)</th>
            <td class="meaning">means any plot and associated improvements within the Master Community whether a
                Single Ownership Plot or a Jointly Owned Plot.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها أية قطعة أرض، والتحسينات المتعلقة بها، داخل المجمع الرئيسي
                سواء أكانت قطعة أرض مملوكة ملكية فردية أو مملوكة ملكية مشتركة.
            </td>
            <th class="rtl-text right-th">قطعة أرض (قطع الأرض)</th>
        </tr>
        <tr>
            <th class="left-th">Plot Owner(s)</th>
            <td class="meaning">means the owner of a Plot including: an Owners Association (where the Plot is a
                Jointly Owned Plot); and any other person or persons (whether real or corporate) where the Plot is a
                Single Ownership Plot.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها مالك أي قطعة أرض، بما في ذلك:
                <ol>
                    <li>جمعية الملاك (في حال إذا
                        كانت القطعة مملوكة ملكية مشتركة)؛
                    </li>
                    <li>
                        أي شخص آخر أو أشخاص آخرين (سواء أكان شخصا طبيعيا أو
                        اعتباريا) في حال إذا كانت القطعة مملوكة ملكية فردية.
                    </li>
                </ol>
            </td>
            <th class="rtl-text right-th">مالك (ملاك) قطعة الأرض</th>
        </tr>
        <tr>
            <th class="left-th">Project</th>
            <td class="meaning">means the project described in Item 4 of the Particulars and as further set out
                in this Agreement comprising the building and the Project Plot.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به المشروع المبين وصفه في البند (4) من بيانات الاتفاقية
                والمنصوص
                عليه أيضا في هذه الاتفاقية ويتضمن المبنى وقطعة أرض المشروع.
            </td>
            <th class="rtl-text right-th">المشروع</th>
        </tr>
        <tr>
            <th class="left-th">Project Manager</th>
            <td class="meaning">means the project manager for the Project, as may be appointed by the Seller for
                time to time.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به من يقوم بدور مدير المشروع في المشروع، والذي يعينه البائع من
                حين لآخر.
            </td>
            <th class="rtl-text right-th">مدير المشروع</th>
        </tr>
        <tr>
            <th class="left-th">Project Plot</th>
            <td class="meaning">means the Plot (subject to final survey and amendment by the Master Developer)
                set aside in the Master Plan for the Project.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها قطعة الأرض (مع مراعاة المسح النهائي وأي تعديل من قبل
                المطور
                الرئيسي) التي يتم تجنيبها وتخصيصها في المخطط الرئيسي من أجل المشروع.
            </td>
            <th class="rtl-text right-th">أرض المشروع</th>
        </tr>
        <tr>
            <th class="left-th">Property Common Areas</th>
            <td class="meaning">means the Relevant Unit and any Parking Bay(s) together with an undivided share
                in the relevant Common Areas apportioned to the Relevant Unit in accordance with the Entitlement.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به الوحدة المعنية ذات الصلة وأية مواقف انتظار مع حصة مملوكة
                على
                الشيوع في المساحات المشتركة المتعلقة بالوحدة المعنية بحسب المستحق.
            </td>
            <th class="rtl-text right-th">العقار</th>
        </tr>
        <tr>
            <th class="left-th">Purchaser</th>
            <td class="meaning">means the Purchaser named in Item 3 of the Particulars including his heirs,
                successors-in-title and permitted successors or assigns.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به المشتري المسمى في البند (3) من بيانات الاتفاقية ويشمل ذلك
                خلفاءه وورثته في الملكية والورثة أو المتنازل إليهم المصرح لهم.
            </td>
            <th class="rtl-text right-th">المشتري</th>
        </tr>
        <tr>
            <th class="left-th">Purchase Price</th>
            <td class="meaning">means the purchase price of the Relevant Unit as set out in Item 5 of the
                Particulars.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به سعر شراء الوحدة المعنية المنصوص عليه في البند (5) من بيانات
                الاتفاقية.
            </td>
            <th class="rtl-text right-th">سعر الشراء</th>
        </tr>
        <tr>
            <th class="left-th">Real Estate Register</th>
            <td class="meaning">means the real estate register at the Land Department in which title is
                registered.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به السجل العقاري في دائرة الأراضي والأملاك المسجل به الملكية.
            </td>
            <th class="rtl-text right-th">السجل العقاري</th>
        </tr>
        <tr>
            <th class="left-th">Relevant Authority</th>
            <td class="meaning">means the Land Department, RERA or any other government entity or organisation
                having jurisdiction over the issue in question.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها دائرة الأراضي والأملاك أو مؤسسة التنظيم العقاري أو أية جهة
                أو
                مؤسسة حكومية أخرى تختص بالمسألة المعنية.
            </td>
            <th class="rtl-text right-th">السلطة المعنية</th>
        </tr>
        <tr>
            <th class="left-th">Relevant Unit</th>
            <td class="meaning">means the Unit referred to in Item 4 of the Particulars which is shown on the
                Relevant Unit Plan and which is located in the Project.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها الوحدة المشار إليها في البند (4) من بيانات الاتفاقية
                والمبينة
                في مخطط الوحدة المعنية الموجود في موقع المشروع.
            </td>
            <th class="rtl-text right-th">الوحدة المعنية</th>
        </tr>
        <tr>
            <th class="left-th">Relevant Unit Plan</th>
            <td class="meaning">means the draft plan of the Relevant Unit attached at Schedule B, pending the
                issuance of the finalised plan of the Relevant Unit by the Seller in accordance with Clause 6.9 and
                6.11.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها مشروع مخطط الوحدة المعنية المرفق في الملحق (ب)، في انتظار
                إصدار المخطط النهائي للوحدة المعنية من قبل البائع بموجب البند (6-9) والبند (6-11).
            </td>
            <th class="rtl-text right-th">مخطط الوحدة المعنية</th>
        </tr>
        <tr>
            <th class="left-th">Relevant Unit Specifications</th>
            <td class="meaning">means the Relevant Unit Plan and the Schedule of Furniture, Fixtures, Fittings
                and Finishes attached at Schedule B.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها مخطط الوحدة المعنية وجدول التركيبات والتمديدات والتشطيبات
                المرفق في الملحق (ب).
            </td>
            <th class="rtl-text right-th">مواصفات الوحدة المعنية</th>
        </tr>
        <tr>
            <th class="left-th">Residential Apartment Use</th>
            <td class="meaning">means use for residential purposes by a single family.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به الاستخدام السكني لعائلة واحدة.</td>
            <th class="rtl-text right-th">استخدام شقق سكنية</th>
        </tr>
        <tr>
            <th class="left-th">RERA</th>
            <td class="meaning">means the Real Estate Regulatory Agency of Dubai, a division of the Land
                Department.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها مؤسسة التنظيم العقاري في دبي، وهي قسم من أقسام دائرة
                الأراضي
                والأملاك.
            </td>
            <th class="rtl-text right-th">مؤسسة التنظيم العقاري</th>
        </tr>
        <tr>
            <th class="left-th">Schedule</th>
            <td class="meaning">means a schedule to this Agreement.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به ملحق مرفق بهذه الاتفاقية.</td>
            <th class="rtl-text right-th">الملحق</th>
        </tr>
        <tr>
            <th class="left-th">Single Ownership Plot</th>
            <td class="meaning">means a Plot in the Master Community that is owned by one or more persons
                (whether real or corporate) but not a Jointly Owned Plot.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها قطعة أرض في المجمع الرئيسي مملوكة من قبل شخص واحد أو أكثر
                (سواء أكان شخصا طبيعيا أو اعتباريا) ولكنها ليست مملوكة ملكية مشتركة.
            </td>
            <th class="rtl-text right-th">قطعة أرض مملوكة ملكية فردية</th>
        </tr>
        <tr>
            <th class="left-th">Seller</th>
            <td class="meaning">means the Seller named in Item 2 of the Particulars.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">البائع المسمى في البند (2) من بيانات الاتفاقية.</td>
            <th class="rtl-text right-th">البائع</th>
        </tr>
        <tr>
            <th class="left-th">Service Charges</th>
            <td class="meaning">means the amount payable by a Unit Owner in accordance with their Entitlement as
                a contribution towards the common expenses of the Owners Association including but not limited to: (a)
                expenses relating to management, administration, repairs, replacements, maintenance, cleaning, security,
                facilities management, sinking fund, water, electricity, gas, sewage, chilled water services and other
                utility connection and consumption charges for the Project; and (b) the Community Charges as assessed
                against the Project Plot.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها المبلغ المستحق على مالك الوحدة دفعه بموجب الاستحقاق الخاص
                به
                بوصفه مساهمة منه للوفاء بالمصاريف المشتركة لجمعية الملاك، بما في ذلك، على سبيل المثال وليس الحصر: (أ)
                المصاريف التي تتعلق بالإدارة والتسيير والإصلاحات والاستبدالات والصيانة والنظافة والأمن وإدارة المرافق
                وصندوق الاحتياطي ومصاريف خدمات المياه والكهرباء والغاز والصرف الصحي والمياه المبردة ومصاريف توصيلات
                المرافق الأخرى ومصاريف الاستهلاك للمشروع؛ و(ب) رسوم المجمع التي يتم ربطها على قطعة أرض المشروع.
            </td>
            <th class="rtl-text right-th">رسوم الخدمة</th>
        </tr>
        <tr>
            <th class="left-th">Strata Scheme</th>
            <td class="meaning">means the scheme of titling, ownership and management to be comprised of the
                Strata Scheme Documentation.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها القواعد المنظمة لمنح سندات الملكية، والملكية والإدارة،
                والتي
                تتكون من مستندات قواعد الملكية المشتركة.
            </td>
            <th class="rtl-text right-th">قواعد الملكية المشتركة</th>
        </tr>
        <tr>
            <th class="left-th">
                Strata Scheme Documentatio-n
            </th>
            <td class="meaning">means the Jointly Owned Property Declaration, the Master Community Declaration
                and Master Plan.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها نظام الملكية المشتركة ونظام المجمع الرئيسي والمخطط
                الرئيسي.
            </td>
            <th class="rtl-text right-th">مستندات قواعد الملكية المشتركة</th>
        </tr>
        <tr>
            <th class="left-th">Total Unit Area</th>
            <td class="meaning">means, subject to Clause 6.9, the proposed area of the Relevant Unit set out in
                Item 4 of the Particulars, which area includes any terrace or balconies and is determined in accordance
                with the Seller’s criteria for assessing such areas.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها، مع مراعاة الفقرة 6-9، المساحة المقترحة للوحدة المعنية
                والموضحة في البند 4 من التفاصيل، والتي تشمل أي سطحيات أو شرفات ويتم تحديدها وفقا لمعايير البائع لتقييمه
                لهذه المناطق.
            </td>
            <th class="rtl-text right-th">المساحة الكلية للوحدة</th>
        </tr>
        <tr>
            <th class="left-th">UAE</th>
            <td class="meaning">means the United Arab Emirates.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها دولة الإمارات العربية المتحدة.</td>
            <th class="rtl-text right-th">الإمارات العربية المتحدة</th>
        </tr>
        <tr>
            <th class="left-th">Unit(s)</th>
            <td class="meaning">has the meaning ascribed to the term in the Jointly Owned Property Law.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يكون لهذا المصطلح نفس المعنى المحدد له في قانون ملكية العقارات
                المشتركة.
            </td>
            <th class="rtl-text right-th">الوحدة (الوحدات)</th>
        </tr>
        <tr>
            <th class="left-th">Unit Owner(s)</th>
            <td class="meaning">means the owner of a Unit including an owner whose title registration is
                pending.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به مالك الوحدة، بما في ذلك ملاك الوحدات الذي لم يتم الانتهاء
                من
                تسجيل ملكيتهم.
            </td>
            <th class="rtl-text right-th">مالك (ملاك) الوحدة</th>
        </tr>
        <tr>
            <th class="left-th">Utility Provider</th>
            <td class="meaning">means any utility provider in the Emirate of Dubai providing utility services
                such as water, electricity, gas, sewage, waste disposal, telecommunications, cooling services and any
                other similar utilities.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد به أي مزود خدمة مرافق في إمارة دبي يوفر خدمة المرافق مثل
                المياه
                والكهرباء والغاز والصرف الصحي والتخلص من النفايات والاتصالات وخدمات التبريد وأية مرافق أخرى مشابهة.
            </td>
            <th class="rtl-text right-th">مزود خدمة المرافق</th>
        </tr>
        <tr>
            <th class="left-th">Valid Tax Invoice</th>
            <td class="meaning">means a VAT invoice that meets all of the requirements of the Executive
                Regulations on the Federal Decree-Law No. (8) of 2017.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها فاتورة ضريبة القيمة المضافة التي تلبي جميع متطلبات اللائحة
                التنفيذية للمرسوم بقانون الاتحادي رقم (8) لعام 2017.
            </td>
            <th class="rtl-text right-th">فاتورة ضريبية صحيحة</th>
        </tr>
        <tr>
            <th class="left-th">VAT</th>
            <td class="meaning">means the Value Added Tax as imposed by the Federal Decree-Law No. (8) of 2017,
                &quot;Any subsequent legislation or official decision issued in this matter shall be applicable.
            </td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">يقصد بها ضريبة القيمة المضافة التي يفرضها المرسوم بقانون الاتحادي
                رقم
                (8) لعام 2017، وأي قانون لاحق عليه وأي قرار بهذا الشأن.
            </td>
            <th class="rtl-text right-th">ضريبة القيمة المضافة</th>
        </tr>
        <tr>
            <th class="left-th">Working Days</th>
            <td class="meaning">any day which is not a Saturday, Sunday or public holiday in the UAE.</td>
            <td>&nbsp;</td>
            <td class="rtl-text meaning spaced-text">أي يوم ليس يوم السبت أو الأحد أو يوم عطلة عامة في دولة الإمارات
                العربية المتحدة.
            </td>
            <th class="rtl-text right-th">أيام العمل</th>
        </tr>
    </table>
    <table class="contract-table">
        <tr>
            <td class="en">
                1.1. The clause headings are included for convenience only and shall not affect the interpretation of
                this Agreement.
            </td>
            <td class="separator"></td>
            <td class="ar">
                1-1 يتم الاشتمال على عناوين البنود لسهولة الرجوع إليها فقط ولن تؤثر على تفسير هذه الاتفاقية.
            </td>
        </tr>
        <tr>
            <td class="en">
                1.2. All dates and periods shall be determined by reference to the Gregorian calendar.
            </td>
            <td class="separator"></td>
            <td class="ar">
                1-2 تحدد كافة التواريخ والفترات عن طريق الرجوع إلى التقويم الميلادي.
            </td>
        </tr>
        <tr>
            <td class="en">
                1.3. The following Schedules form part of this Agreement and shall have effect as if set out in full in
                the body of this Agreement and any reference to this Agreement includes the Schedules:
            </td>
            <td class="separator"></td>
            <td class="ar">
                1-3 تشكل الملاحق التالية جزءا من هذه الاتفاقية وتسري كما لو كانت قد نص عليها كاملة ضمن هذه الاتفاقية،
                وأية إشارة إلى هذه الاتفاقية تشمل الملاحق:
            </td>
        </tr>
        <tr>
            <td class="en" style="padding: 10px 10px 10px 35px;">
                <strong>Schedule A:</strong> Payment Schedule
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding: 10px 35px 10px 10px;">
                <strong>الملحق (أ):</strong> جدول سداد الدفعات
            </td>
        </tr>
        <tr>
            <td class="en" style="padding: 10px 10px 10px 35px;">
                <strong>Schedule B:</strong> Relevant Unit Specifications, including the Relevant Unit Plan (Draft) and
                the Schedule of Furniture, Fixtures, Fittings and Finishes.
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding: 10px 35px 10px 10px;">
                <strong>الملحق (ب):</strong> مواصفات الوحدة المعنية، وتشمل مخطط الوحدة المعنية (مشروع المخطط) وجدول
                التركيبات والتمديدات والتشطيبات.
            </td>
        </tr>
        <tr>
            <td class="en" style="padding: 10px 10px 10px 35px;">
                <strong>Schedule C:</strong> Jointly Owned Property Declaration
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding: 10px 35px 10px 10px;">
                <strong>الملحق (هـ):</strong> نظام الملكية المشتركة.
            </td>
        </tr>
        <tr>
            <td class="en">
                1.4 If any provision in a definition in this Agreement is a substantive provision conferring rights or
                imposing obligations then, notwithstanding that it is only in the interpretation clause of this
                Agreement, effect shall be given to it as if it were a substantive provision in the body of this
                Agreement.
            </td>
            <td class="separator"></td>
            <td class="ar">
                1-4 في حال إذا كان أي حكم في بند التعريفات في هذه الاتفاقية حكما جوهريا يمنح حقوقا أو يفرض التزامات،
                فحينئذ يصبح ذلك الحكم، رغم أنه قد نص عليه في بند التعريفات والتفسير بهذه الاتفاقية، ساريا كما لو كان
                حكما جوهريا مذكورا في صلب هذه الاتفاقية.
            </td>
        </tr>
        <!-- 2 -->
        <tr>
            <th class="en">
                2. THE SALE
            </th>
            <td class="separator"></td>
            <th class="ar">
                2- البيع
            </th>
        </tr>
        <tr>
            <td class="en">
                2.1. The Seller sells to the Purchaser who hereby purchases the Property on the terms and conditions
                contained in this Agreement.
            </td>
            <td class="separator"></td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                2-1 يبيع البائع إلى المشتري الذي يشتري بموجبه العقار بناء على الشروط والأحكام المنصوص عليها في هذه
                الاتفاقية.
            </td>
        </tr>
        <tr>
            <td class="en">
                2.2. This Agreement is a personal contract between the Seller and the Purchaser, and, for the
                avoidance of doubt, the Parties agree that the Master Developer is not a party to this Agreement and has
                no liability and gives no warranty under it.
            </td>
            <td class="separator"></td>
            <td class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                2-2 هذه الاتفاقية هي عقد شخصي بين البائع والمشتري ، ولتفادي الشك ، يتفق الطرفان على أن المطور الرئيسي
                ليس طرفا في هذه الاتفاقية ولا يتحمل أي مسؤولية ولا يقدم أي ضمان بموجبها. "
            </td>
        </tr>

        <tr>
            <th class="en">
                3. Purchase Price and Payment
            </th>
            <td class="separator"></td>
            <th class="ar">
                3- سعر الشراء ودفعه
            </th>
        </tr>
        <tr>
            <td class="en">
                3.1 The Purchase Price shall be paid by the Purchaser to the Seller free of exchange or variance, and
                without any deduction or set off in accordance with the Payment Schedule and Payment Schedule Notes.
            </td>
            <td class="separator"></td>
            <td class="ar">3-1 يلتزم
                المشتري بأن يدفع سعر الشراء إلى البائع بغض النظر عن تقلب أسعار الصرف، وخالصا من أي استقطاع أو تقاص بموجب
                جدول سداد الدفعات والملاحظات على جدول سداد الدفعات.
            </td>
        </tr>

        <tr>
            <td class="en">3.2. The Purchaser shall pay each
                instalment payment as described in the Payment Schedule by transferring each instalment payment to the
                bank account nominated by the Seller in writing or by delivering the payment via regular cheque or
                manager cheque to the Seller’s office in Dubai, UAE on or before the due date of the instalment payment
                set out in the Payment Schedule.
            </td>
            <td class="separator"></td>
            <td class="ar">3-2 يتعهد
                المشتري بدفع كل قسط على النحو الموضح في جدول الدفع عن طريق تحويل كل قسط إلى الحساب البنكي الذي حدده
                البائع كتابة أو الدفع في شكل شيك عادي أو شيك مصدق إلى مكتب البائع في دبي بالإمارات العربية المتحدة وذلك
                في تاريخ استحقاق القسط الموضح في جدول الدفع أو قبل ذلك التاريخ.
            </td>
        </tr>

        <tr>
            <td class="en">3.3. The payment of each Payment
                Instalment shall be due on the Instalment Date, or in respect of the Final Instalment ten (10) Working
                Days from the date the Seller provides written confirmation to the Purchaser that the Project Manager
                has determined that completion of the Project has occurred. Determination that completion of the Project
                has taken place and ascertaining of the Completion Date shall be at the Project Manager’s sole
                discretion and the Project Manager’s confirmation in writing of the same shall be conclusive proof that
                the same has been attained and shall be final and binding on the Parties.
            </td>
            <td class="separator"></td>
            <td class="ar">3-3 يكون سداد
                دفعة كل قسط في تاريخ القسط؛ أو بالنسبة إلى الدفعة الأخيرة عشرة (10) أيام عمل من تاريخ تقديم البائع
                تأكيدا خطيا إلى المشتري بأن مدير المشاريع قد قرر أن إنجاز المشروع قد تم. ويكون تحديد إنجاز المشروع
                وتأكيد تاريخ الإنجاز حسب تقدير مدير المشروع وحده ويكون تأكيده الخطي على نسبة الإنجاز هذه دليلا قاطعا على
                أن هذه النسبة قد تم بلوغها ويكون نهائيا وملزما على الطرفين.
            </td>
        </tr>

        <tr>
            <td class="en">3.4. Without prejudice to the
                Seller’s
                other rights under this Agreement, in the event of non-payment on the due date of any amount payable by
                the Purchaser pursuant to this Agreement, the Purchaser shall pay the Late Payment Penalty as
                compensation for the delay in payment, In accordance with the provisions set forth in Clause No. 10.
            </td>
            <td class="separator"></td>
            <td class="ar">3-4 مع مراعاة
                عدم الإخلال بحقوق البائع الأخرى المنصوص عليها بموجب هذه الاتفاقية، في حال عدم سداد أي مبلغ مستحق على
                المشتري طبقا لهذه الاتفاقية في تاريخ استحقاقه، يتعين على المشتري أن يدفع غرامة تأخير سداد تعويضا عن
                التأخير في سداد المبلغ المستحق وفقا لأحكام البند رقم 10.
            </td>
        </tr>

        <tr>
            <td class="en">3.5. Without prejudice to Clause
                3.5, in
                the event a cheque is returned unpaid, the Seller may charge a fee of AED 1,500.00 (UAE Dirhams One
                Thousand Five Hundred) as a handling fee in relation to each returned cheque.
            </td>
            <td class="separator"></td>
            <td class="ar">3-5 مع مراعاة
                عدم الإخلال بالبند (5-3)، في حال إذا ارتجع شيك دون دفعه، فحينئذ يجوز للبائع أن يفرض رسما قيمته ألف
                وخمسمئة
                درهم إماراتي (500 درهم) كرسم خدمة فيما يتعلق بكل شيك يرتجع.
            </td>
        </tr>

        <tr>
            <td class="en">3.6. Each payment made by the
                Purchaser
                shall be allocated first to the discharge of any penalties, then to the payment of any other amounts due
                in terms hereof and thereafter to the reduction of the Purchase Price.
            </td>
            <td class="separator"></td>
            <td class="ar">3-6 وتستخدم
                كل دفعة يسددها المشتري للوفاء أولا بأية غرامات، ثم لسداد أية مبالغ أخرى مستحقة بموجب هذه الاتفاقية، ثم
                بعد ذلك للخصم من سعر الشراء.
            </td>
        </tr>


        <tr>
            <td class="en">3.7. The Purchaser hereby
                represents,
                undertakes and warrants that all funds utilised by the Purchaser in respect of the payment of the
                Purchase Price or any other payments anticipated under this Agreement are derived from legitimate
                sources and are not related to proceeds of crime or money laundering either directly or indirectly.
            </td>
            <td class="separator"></td>
            <td class="ar">3-7 ويتعهد
                المشتري ويؤكد بموجبه أن كافة المبالغ التي يستغلها المشتري سدادا لسعر الشراء أو سدادا لأية دفعات أخرى
                متوقعة بموجب هذه الاتفاقية قد حققها المشتري من مصادر مشروعة وليست لها أية صلة، مباشرة كانت أو غير
                مباشرة، بمتحصلات نتجت عن جرم أو غسيل أموال.
            </td>
        </tr>

        <tr>
            <th class="en">4. Possession and
                Risk
            </th>
            <td class="separator"></td>
            <th class="ar">4- الحيازة والمخاطر
            </th>
        </tr>

        <tr>
            <td class="en">4.1. It is recorded that the
                Anticipated
                Completion Date represents the date upon which it is presently expected that the Seller will hand over
                possession of the Property to the Purchaser. The Seller reserves the right, by notice in writing to the
                Purchaser at any time in accordance with Clause 5-1 (or any other method of delivery permitted at law),
                to extend the Anticipated Completion Date on one or more occasions and for one or more periods provided
                the sum of such periods shall not exceed six (6) months calculated from the date set down in Item 7 of
                the Particulars. If the Project is completed before the Anticipated Completion Date, the Seller reserves
                the right, at its sole discretion, to require the Completion Date to occur earlier than the Anticipated
                Completion Date.
            </td>
            <td class="separator"></td>
            <td class="ar">4-1 من المقرر
                أن
                تاريخ الإنجاز المتوقع يمثل التاريخ المتوقع في الوقت الحالي أن يسلم البائع فيه حيازة العقار إلى المشتري.
                يحتفظ البائع بالحق، عن طريق إخطار خطي إلى المشتري في أي وقت وفقا للبند 5-1 (أو أي طريقة أخرى يسمح بها
                القانون)، أن يمدد التاريخ المتوقع للإنجاز في مناسبة واحدة أو أكثر ولفترة واحدة أو أكثر بشرط ألا يتجاوز
                مجموع تلك الفترات ستة (6) أشهر تحسب من التاريخ المنصوص عليه في البند (7) من بيانات الاتفاقية. إذا تم
                الانتهاء من المشروع قبل التاريخ المتوقع للإنجاز، يحتفظ البائع بالحق في أن يطلب، وفقا لتقديره وحده، تقديم
                تاريخ الإنجاز عن تاريخ الإنجاز المتوقع.
            </td>
        </tr>

        <tr>
            <td class="en">4.2. The Seller shall in any event
                give
                the Purchaser not less than thirty (30) days’ notice in writing of the Completion Date and the
                Completion Date shall only be deemed to have been determined when such notice has been given.
            </td>
            <td class="separator"></td>
            <td class="ar">4-2 يتعين على
                البائع، في أي حال من الأحوال، أن يرسل إخطارا خطيا مدته لا تقل عن ثلاثين (30) يوم بتاريخ الإنجاز، ويعتبر
                تاريخ الإنجاز قد حدد فقط عندما يرسل ذلك الإخطار.
            </td>
        </tr>

        <tr>
            <td class="en">4.3. Possession and occupation of
                the
                Property shall be given to and taken by the Purchaser on the Completion Date, subject to Clause 4.4.
            </td>
            <td class="separator"></td>
            <td class="ar">4-3 ويمنح
                المشتري
                ويتسلم حيازة وإشغال العقار في تاريخ الإنجاز، مع مراعاة عدم الإخلال بالبند (4-4).
            </td>
        </tr>

        <tr>
            <td class="en">4.4. All risk and benefit in
                respect of
                the Property shall pass to the Purchaser on the Completion Date, which is also the date that the
                Purchaser is required to take possession of the Property. The Seller is entitled (but not obligated) to
                decline to hand over possession and occupation of the Property to the Purchaser if the Purchaser has
                failed to:
            </td>
            <td class="separator"></td>
            <td class="ar">4-4 تنتقل
                كافة
                المخاطر والفوائد المتعلقة بالعقار إلى المشتري في تاريخ الإنجاز، وهو أيضا التاريخ الذي يطلب فيه من
                المشتري أن يتولى حيازة العقار. يحق للبائع (دون التزام عليه) أن يرفض تسليم حيازة وإشغال العقار إلى
                المشتري إذا قصر المشتري في أي مما يلي:
            </td>
        </tr>

        <tr>
            <td class="en">(a) make any of the payments
                referred
                to in Clauses 3, 6.3 and 6.5;
            </td>
            <td class="separator"></td>
            <td class="ar">أ) سداد أي من
                الدفعات المشار إليها في البند (3) والبند (6-3) والبند (6-5)؛
            </td>
        </tr>

        <tr>
            <td class="en">(b) comply with any other provision
                of
                this Agreement;
            </td>
            <td class="separator"></td>
            <td class="ar">ب) الالتزام
                بأي
                حكم آخر من أحكام هذه الاتفاقية؛
            </td>
        </tr>

        <tr>
            <td class="en">provided that, notwithstanding the
                Seller declining handover pursuant to this Clause 4.4, all risk in the Property shall pass to the
                Purchaser on the Completion Date, irrespective of whether physical possession and occupation has been
                provided to or taken by the Purchaser and, accordingly, the Purchaser shall not be relieved from
                performing any of its obligations under this Agreement that are triggered by the Completion Date,
                (including without limitation the obligation to pay the Final Instalment and/or the Service Charges
                pursuant to Clauses 3, 6.3 and 6.5).
            </td>
            <td class="separator"></td>
            <td class="ar">على أنه
                يشترط، رغم رفض البائع التسليم بموجب هذا البند (4-4)، أن تنتقل كافة المخاطر في العقار إلى المشتري في
                تاريخ الإنجاز، بغض النظر عما إذا كانت الحيازة والإشغال الفعلي قد تم توفيرها إلى المشتري أو قد حصل عليها
                المشتري، ومن ثم لا يعفى المشتري من تنفيذ أي من التزاماته، المنصوص عليها بموجب هذه الاتفاقية، التي تنشأ
                بحلول تاريخ الإنجاز (بما في ذلك، على سبيل المثال وليس الحصر، الالتزام بدفع القسط الأخير ومصاريف الخدمة
                أو أيهما طبقا للبند (3) والبند (3-6) والبند (6-5().
            </td>
        </tr>

        <tr>
            <td class="en">4.5. The Purchaser shall from and
                after
                the Completion Date or transfer of title whichever is earlier indemnify and hold the Seller and their
                respective agents and affiliates harmless against all claims, proceedings, costs, damages, expenses and
                losses including attorney’s fees and expenses arising out of or relating to:
            </td>
            <td class="separator"></td>
            <td class="ar">4-5 يتعين على
                المشتري اعتبارا من تاريخ الإنجاز أو نقل الملكية وبعده، أيهما يقع أولا، أن يعوض البائع والمطور أو أيهما
                ووكلائهم ومنتسبيهم وأن يحجب الضرر عنهم ضد كافة المطالبات والدعاوى والتكاليف والأضرار والمصاريف والخسائر
                بما في ذلك مصاريف وأتعاب المحاماة الناشئة عن أو المرتبطة بأي مما يلي:
            </td>
        </tr>

        <tr>
            <td class="en">(a) defective or damaged condition
                of
                any part of the Property or any other structure constructed thereon and any fixtures, fittings or
                electrical wiring therein caused or installed by (as the case may be) the Purchaser;
            </td>
            <td class="separator"></td>
            <td class="ar">أ) الحالة
                المعيبة أو التالفة لأي جزء من العقار أو أي هيكل آخر أنشئ عليه وأية تركيبات أو تمديدات أو أسلاك كهربائية
                تسبب فيها المشتري أو ركبها المشتري (حسب الأحوال)
            </td>
        </tr>

        <tr>
            <td class="en">(b) the spread of fire or smoke or
                the
                flow of water from any part of the Property cause by the Purchaser; or
            </td>
            <td class="separator"></td>
            <td class="ar">ب) انتشار
                الحريق
                أو الدخان أو تدفق المياه من أي جزء من العقار بسبب المشتري
            </td>
        </tr>

        <tr>
            <td class="en">(c) the act, default or negligence
                of
                the Purchaser or the Purchaser’s occupiers, his agents or contractors.
            </td>
            <td class="separator"></td>
            <td class="ar">ت) أي فعل أو
                تقصير أو إهمال من جانب المشتري أو شاغلي عقار المشتري أو وكلائه أو مقاوليه.
            </td>
        </tr>

        <tr>
            <td class="en">4.6. As soon as reasonably possible
                following the Completion Date, the Seller will allocate any Parking Bay(s) to the Purchaser on a
                temporary basis provided that the exact position of the Parking Bay(s) may not be confirmed until such
                time as the title deed is issued and Jointly Owned Property Declaration is finalised. The Seller shall
                use its best efforts to allocate the Parking Bay(s) in a suitable position in relation to the Relevant
                Unit and the Purchaser shall not be entitled to raise an objection to the allocation and position of
                such Parking Bay(s).
            </td>
            <td class="separator"></td>
            <td class="ar">4-6 في أقرب
                وقت
                ممكن على نحو معقول عقب تاريخ الإنجاز، للبائع أن يخصص أي موقف سيارة (مواقف سيارات) للمشتري بصفة مؤقتة
                بشرط ألا يتم تأكيد الموقف (المواقف) بدقة حتى يتم الانتهاء من استصدار سند الملكية ونظام الملكية المشتركة.
                يتعين على البائع أن يبذل قصارى جهده لتخصيص مواقف للسيارة (مواقف للسيارات) في موقع مناسب فيما يتعلق
                بالوحدة المعنية، ولا يحق للمشتري أن يرفع أي اعتراض على تخصيص وموقع الموقف (المواقف) المذكورة.
            </td>
        </tr>

        <tr>
            <th class="left-th section-heading" style="width:49%; text-align:justify; padding:10px;">5. Transfer of
                Title
            </th>
            <td class="separator"></td>
            <th class="rtl-text right-th spaced-text section-heading"
                style="width:49%; text-align:justify; padding:10px;">5- نقل سند الملكية
            </th>
        </tr>

        <tr>
            <td class="en">5.1. The Purchaser acknowledges and
                agrees that the Seller will not transfer title to the Property to the Purchaser until the Completion
                Date. The Seller shall transfer a clear and unencumbered title in respect of the Property to the
                Purchaser and shall procure registration of such transfer in favour of the Purchaser with the Land
                Department in the Real Estate Register as soon as is reasonably possible on or after the Completion Date
                provided the Purchaser is not otherwise in breach of its obligations under this Agreement (and insofar
                as the Seller is reasonably capable of doing so).
            </td>
            <td class="separator"></td>
            <td class="ar">5-1 يقر
                المشتري
                ويوافق على أن البائع لن ينقل سند ملكية العقار إلى المشتري حتى تاريخ الإنجاز. ويتعين على البائع أن ينقل
                ملكية العقار إلى المشتري خالصة وغير محملة برهون أو أعباء، وأن يضمن تسجيل نقل تلك الملكية لصالح المشتري
                لدى دائرة الأراضي والأملاك في السجل العقاري في أقرب وقت ممكن على نحو معقول في تاريخ الإنجاز أو بعده،
                بشرط ألا يكون المشتري خلاف ذلك مخلا بالتزاماته المنصوص عليها بموجب هذه الاتفاقية (وطالما أن البائع لديه
                القدرة المعقولة على فعل ذلك).
            </td>
        </tr>

        <tr>
            <td class="en">5.2 Once title to the Property has been registered in the name of the Purchaser in the
                Land Department, the Purchaser may deal in his Property. The Purchaser may finance the purchase of the
                Property with the Seller’s prior written consent and upon such terms and conditions as the Seller may
                reasonably require prior to the registration of title. The Seller makes no representations or warranties
                as to the ability of the Purchaser to finance the purchase of the Property, and the Purchaser warrants
                it has sufficient resources to meet its obligations under this Agreement without the need to obtain
                finance.
            </td>
            <td class="separator"></td>
            <td class="ar">5-2 بمجرد تسجيل ملكية العقار باسم المشتري في دائرة الأراضي والأملاك، يجوز للمشتري أن
                يتصرف في العقار. ويجوز للمشتري أن يمول شراء العقار بموافقة خطية مسبقة من البائع وبناء على تلك الشروط
                والأحكام التي يطلبها البائع بشكل معقول قبل تسجيل الملكية. وليس للبائع أن يقدم أية تعهدات أو تأكيدات بشأن
                قدرة المشتري على تمويل العقار، ويؤكد المشتري أنه لديه الموارد الكافية للوفاء بالتزاماته المنصوص عليها
                بموجب هذه الاتفاقية دون حاجة للحصول على التمويل.
            </td>
        </tr>

        <tr>
            <td class="en">5.3. The Purchaser shall supply to
                the
                Seller all information and sign any document as may be reasonably required by the Land Department and
                any other respective authorities to effect transfer of title and give effect to this Agreement and the
                Purchaser shall bear the cost of all fees and expenses as required by the respective authorities to
                effect such transfer and issue of title.
            </td>
            <td class="separator"></td>
            <td class="ar">5-3 يتعين على
                المشتري أن يوفر للبائع كافة المعلومات ويوقع على أي مستند حسبما تتطلب دائرة الأرضي وأية سلطات معنية أخرى
                بشكل معقول لتنفيذ نقل الملكية ووضع هذه الاتفاقية في حيز النفاذ، ويتعين على المشتري أن يتحمل تكلفة كافة
                الرسوم والمصاريف التي تتطلبها السلطات المعنية لتنفيذ نقل وإصدار الملكية المذكور.
            </td>
        </tr>

        <tr>
            <td class="en">5.4. The Purchaser shall accept
                transfer
                of title to the Property subject to such easements and restrictions benefiting or burdening the Property
                in terms of this Agreement, the Strata Scheme Documentation or as imposed by Applicable Law or any
                Relevant Authority.
            </td>
            <td class="separator"></td>
            <td class="ar">5-4 يقبل
                المشتري
                نقل ملكية العقار مع مراعاة عدم الإخلال بحقوق الارتفاق والقيود التي تستفيد من العقار أو تحمله بأعباء
                بالنسبة لهذه الاتفاقية أو مستندات قواعد الملكية المشتركة أو حسبما يفرض القانون المعمول به أو حسبما تفرض
                السلطة المعنية.
            </td>
        </tr>

        <tr>
            <td class="en">5.5. The Purchaser shall on demand
                pay
                any and all costs, expenses and/or fees in connection with and/or incidental to the registration of this
                Agreement and the registration of transfer of title to the Property in the name of the Purchaser at the
                Land Department and, if applicable, in the Interim Real Estate Register (the “Fees”), including any part
                of the Fees assessed on the Seller, any fees that are currently imposed or may be imposed in the future
                and the Seller’s administration in effect from time to time. The Seller offers no warranty or
                confirmation as to the level of the Fees. The Purchaser shall pay the Fees at the prevailing rate as
                determined by the Land Department from time to time (which are currently 4% of the Purchase Price) in
                full. The Purchaser shall reimburse the Seller on demand for any Fees paid by the Seller on behalf of
                the Purchaser together with the Seller’s Administration Fee in effect from time to time. The Purchaser
                shall indemnify the Seller against all costs, claims, or liabilities the Seller may suffer, (including,
                without limitation, fines or penalties levied by the Land Department) arising or in any way connected
                with the Purchaser’s failure to pay the Fees when demanded.
            </td>
            <td class="separator"></td>
            <td class="ar">5-5 يلتزم
                المشتري، عندما يطلب منه، بأن يدفع جميع التكاليف والمصاريف والرسوم المرتبطة بتسجيل هذه الاتفاقية وتسجيل
                نقل ملكية العقار باسم المشتري في دائرة الأراضي والأملاك وفي السجل العقاري المؤقت إذا كان ذلك ينطبق
                ("الرسوم")، بما في ذلك أي جزء من الرسوم التي يتم تقييمها على البائع وأي رسوم تفرض في الوقت الحالي أو
                يمكن أن تفرض في المستقبل وإدارة البائع، التي تسري من حين لآخر. ولن يقدم البائع أية تعهدات أو تأكيدات
                بشأن مستوى الرسوم. ويتعين على المشتري أن يدفع الرسوم كاملة بالسعر السائد الذي تحدده دائرة الأراضي
                والأملاك من حين لآخر (وهي في الوقت الحالي بما نسبته 4% من سعر الشراء). ويلتزم المشتري بأن يعوض البائع
                عند الطلب عن أية رسوم يدفعها البائع نيابة عن المشتري مع الرسوم الإدارية للبائع السارية من حين لآخر.
                ويتعين على المشتري أن يعوض البائع والمطور عن كافة التكاليف أو المطالبات أو الالتزامات التي يمكن أن يتعرض
                لها البائع (بما في ذلك، على سبيل المثال وليس الحصر، الغرامات أو الجزاءات التي تفرضها دائرة الأراضي
                والأملاك) التي تنشأ أو تتعلق بأي حال من الأحوال بتقصير المشتري في سداد الرسوم عند طلبها.
            </td>
        </tr>

        <tr>
            <th class="left-th section-heading" style="width:49%; text-align:justify; padding:10px;">6. Purchaser’s
                Acknowledgements and Undertakings
            </th>
            <td class="separator"></td>
            <th class="rtl-text right-th spaced-text section-heading"
                style="width:49%; text-align:justify; padding:10px;">6- إقرارات وتعهدات المشتري
            </th>
        </tr>

        <tr>
            <td class="en">6.1 The Purchaser shall be
                responsible
                for and shall be liable to pay for water, electricity, gas, sewage, chilled water, telephone,
                information, technology and communication services and other utility connection and consumption charges,
                all charges imposed directly by any Relevant Authority or Utility Provider for these services to the
                Property and any property or local ot federal authority taxes levied on the Property (such as and not
                limited to VAT) from the Completion Date and thereafter. In the event that any utilities are provided to
                the Purchaser directly by the Owners Association, such amounts will be in addition to the Services
                Charges and the Purchaser undertakes to settle such consumption or usage charges as provided by the
                Owners Association promptly and without delay when requested to do so.
            </td>
            <td class="separator"></td>
            <td class="ar">6-1 يكون
                المشتري
                مسؤولا عن دفع قيمة خدمات المياه والكهرباء والغاز والصرف الصحي والمياه المبردة والهاتف وخدمات تكنولوجيا
                المعلومات والاتصالات وتوصيلات المرافق الأخرى ومصاريف الاستهلاك وكافة المصاريف التي تفرضها بشكل مباشر
                السلطة المختصة أو الجهة المقدمة لخدمات المرافق نظير تقديم هذه الخدمات للعقار وأية ضرائب عقارية أو ضرائب
                مفروضة من سلطة محلية أو اتحادية على العقار (بما في ذلك على سبيل المثال لا الحصر ضريبة القيمة المضافة) من
                تاريخ الإنجاز وبعده. وفي حال إذا قدمت جمعية ملاك خدمات أية مرافق إلى المشتري بشكل مباشر، تكون تلك
                المبالغ إضافة إلى مصاريف الخدمة ويتعهد المشتري بتسوية مصاريف الاستهلاك أو الاستخدام هذه حسبما تحددها
                جمعية الملاك على الفور ودون تأخير عندما يطلب منه ذلك.
            </td>
        </tr>

        <tr>
            <td class="en">6.2. The Purchaser acknowledges and
                agrees that the Purchaser shall pay their portion of Service Charges, on and from the Completion Date
                (and regardless of whether the Purchaser takes possession of or title to the Property) calculated and
                payable in accordance with the Entitlement of the Relevant Unit as this forms part of the total
                Entitlement of all Units as well as such other criteria set down in Applicable Law and the Strata Scheme
                Documentation.
            </td>
            <td class="separator"></td>
            <td class="ar">6-2 يقر
                ويوافق
                المشتري على أن يلتزم المشتري بدفع نسبته من مصاريف الخدمة اعتبارا من تاريخ الإنجاز (وبغض النظر عما إذا
                كان المشتري قد حاز العقار أو أصبح له سند ملكيته أم لا) تحسب وتستحق بموجب النصيب المستحق من الوحدة
                المعنية حسبما يشكل ذلك جزءا من إجمالي النصيب المستحق من كافة الوحدات إضافة إلى تلك المعايير الأخرى
                المنصوص عليها في القانون المعمول به وفي مستندات قواعد الملكية المشتركة.
            </td>
        </tr>

        <tr>
            <td class="en">6.3. The Purchaser hereby agrees to comply with all terms, conditions, covenants, and
                obligations set forth in the Master Community Declaration, as may be amended from time to time.
            </td>
            <td class="separator"></td>
            <td class="ar">6-3 يوافق المشتري بموجب هذا على الامتثال لجميع الشروط والأحكام والعهود والالتزامات
                المنصوص عليها في إعلان المجتمع الرئيسي، حسبما يتم تعديله من وقت لآخر.
            </td>
        </tr>

        <tr>
            <td class="en">6.4. The Purchaser acknowledges
                that the
                Service Charges will also include a share of the Community Charges assessed on the Project Plot pursuant
                to the Master Community Declaration and levied upon the relevant Owners Association.
            </td>
            <td class="separator"></td>
            <td class="ar">6-4 يقر
                المشتري
                أن مصاريف الخدمة تشمل أيضا مصاريف المجمع التي يتم تقييمها على أرض المشروع طبقا لنظام المجمع الرئيسي ويتم
                فرضها على جمعية الملاك المعنية.
            </td>
        </tr>

        <tr>
            <td class="en">6.5. The Service Charges for the
                twelve
                (12) month period commencing on the Completion Date shall be payable in advance by the Purchaser to the
                Manager on the Completion Date.
            </td>
            <td class="separator"></td>
            <td class="ar">6-5 تكون
                مصاريف
                الخدمة عن مدة الاثني عشر (12) شهر التي تبدأ في تاريخ الإنجاز مستحقا دفعها على المشتري إلى المدير في
                تاريخ الإنجاز.
            </td>
        </tr>

        <tr>
            <td class="en">6.6. The Purchaser agrees and
                understands that the Property may only be used for the Permitted Use which for the avoidance of doubt
                comprises Residential Apartment Use for family use , The Purchaser shall:
            </td>
            <td class="separator"></td>
            <td class="ar">6-6 يدرك
                المشتري
                ويوافق على أنه لا يجوز له أن يستخدم العقار إلا لغرض الاستخدام المصرح به والذي يعني، قطعا للشك باليقين،
                استخدام العقار كشقة سكنية للسكن العائلي و يجب على المشتري:
            </td>
        </tr>

        <tr>
            <td class="en">- comply in all respects with the
                provisions of all Applicable Law and the terms of Strata Scheme Documentation or rules and regulations
                promulgated there under now or from time to time in force in relation to the Property.
            </td>
            <td class="separator"></td>
            <td class="ar">- أن يلتزم في
                جميع النواحي بكافة أحكام القانون المعمول به وكافة شروط مستندات قواعد الملكية المشتركة أو بالقواعد
                واللوائح الصادرة بموجبها في الوقت الحالي أو السارية من وقت لآخر فيما يخص العقار.
            </td>
        </tr>

        <tr>
            <td class="en">6.7. The Purchaser and its
                successors-in-title of the Property will be required to enter into agreement(s) as referred to and
                required in the Strata Scheme Documentation for the exclusive installation, utilisation and servicing of
                the infrastructure, information technology and communication services, any district cooling water system
                (for air-conditioning purposes) or any other similar arrangements for the delivery or charging of
                infrastructure, utilities and similar services.
            </td>
            <td class="separator"></td>
            <td class="ar">6-7 يطلب من
                المشتري وورثته في ملكية العقار أن يبرموا اتفاقية (اتفاقيات) حسبما يشار إليه ويطلب في مستندات قواعد
                الملكية المشتركة من أجل التركيب الحصري والاستغلال وأداء الخدمة للبنية التحتية وخدمات تكنولوجيا المعلومات
                وخدمات الاتصالات وأية نظم تبريد المناطق (لأغراض تكييف الهواء) أو أية ترتيبات مشابهة لتقديم أو توفير
                خدمات البنية التحتية والمرافق والخدمات المشابهة.
            </td>
        </tr>

        <tr>
            <td class="en">6.8. The Purchaser acknowledges
                that on
                the Completion Date other Units, Common Areas, and similar projects within the Master Community as well
                as the Communal Facilities may be incomplete and that inconvenience may be suffered as a result of the
                building activities which shall be in progress. The Purchaser shall have no claim against the Seller,
                the Manager, the Owners Association or the Master Developer for such inconvenience.
            </td>
            <td class="separator"></td>
            <td class="ar">6-8 يقر
                المشتري
                أنه في تاريخ الإنجاز من الممكن أن تكون وحدات، مساحات مشتركة ومشاريع مشابهة داخل المجمع الرئيسي إضافة إلى
                المرافق المشتركة غير مكتملة وقد تحدث أعطال نتيجة أنشطة المبنى التي يتعين أن تكون مستمرة. وليس للمشتري أن
                يرفع أية مطالبة ضد البائع أو المدير أو جمعية الملاك أو المطور الرئيسي بخصوص تلك الأعطال.
            </td>
        </tr>

        <tr>
            <td class="en">6.9. The Purchaser acknowledges and
                agrees that while the Relevant Unit Plan is as accurate as possible, it is not yet final and adjustments
                to the final measurements and Entitlements may need to be made. If the final measurement of the Relevant
                Unit as determined in accordance with the Seller’s criteria differs by more than five present (5%) from
                the Total Unit Area then the Purchase Price for the Property will be increased or decreased
                proportionally, to the extent permitted by Applicable Law. However, no adjustment to the Purchase Price
                shall be made if the final measurement of the Relevant Unit differs by five per cent (5%) or less than
                the Total Unit Area. In this case, the Purchaser will have no claim against the Seller for the
                deficiency in size of the Relevant Unit. In any event the Purchaser shall not be entitled to withhold
                any payment towards the Purchase Price or delay or refuse to complete the sale and purchase of the
                Property as a result of any change in area of the Relevant Unit or the corresponding change in the
                Entitlement upon full survey being completed or pursuant to this Agreement generally.
            </td>
            <td class="separator"></td>
            <td class="ar">6-9 يقر
                ويوافق
                المشتري على أنه رغم دقة مخطط الوحدة المعنية قدر الإمكان، إلا أنه ليس نهائيا وقد تدعو الحاجة إلى إجراء
                تعديلات على القياسات النهائية والاستحقاقات النهائية. وإذا اختلف القياس النهائي للوحدة المعنية المحدد
                بموجب معايير البائع بما نسبته أكثر من خمسة بالمائة (5%) عن مساحة الوحدة الإجمالية، فحينئذ يتعين زيادة
                سعر شراء العقار أو تخفيضه تناسبيا إلى الحد الذي يسمح به القانون المعمول به. ومع ذلك، لن تجرى أية تعديلات
                على سعر الشراء إذا اختلف القياس النهائي للوحدة المعنية بما نسبته خمسة بالمائة (5%) أو أقل من المساحة
                الإجمالية للوحدة. وفي هذه الحالة، ليس للمشتري أن يرفع أية مطالبة ضد البائع بشأن النقص في مقاسات الوحدة
                المعنية. وفي أي حال من الأحوال، لا يحق للمشتري أن يحتجز أية دفعة من دفعات سعر الشراء أو يؤخر أو يرفض
                إتمام بيع وشراء العقار نتيجة أي تغيير في مساحة الوحدة المعنية أو تغيير مماثل في النصيب المستحق فور إتمام
                المسح أو طبقا لهذه الاتفاقية عموما.
            </td>
        </tr>

        <tr>
            <td class="en">6.10. The Purchaser acknowledges and
                agrees that if any of the materials required by the Seller to construct the Relevant Unit (including as
                set out in the Schedule of Furniture, Fixtures, Fittings and Finishes), the Relevant Unit Specifications
                and the Project are not available within a reasonable time or at a reasonable cost, the Seller may
                substitute such materials with an equivalent materials and which are reasonably obtainable at such time.
            </td>
            <td class="separator"></td>
            <td class="ar">6-10 يقر
                المشتري
                ويوافق على أنه في حال إذا طلب البائع أية خامات لتشييد الوحدة المعنية (بما في ذلك حسبما هو منصوص عليه في
                جدول التركيبات والتمديدات والتشطيبات)، ولم تستوف مواصفات الوحدة المعنية والمشروع في خلال وقت معقول أو
                بتكلفة معقولة، فيجوز للبائع أن يستبدل تلك الخامات بخامات معادلة لها وممكن الحصول عليها في ذلك الوقت.
            </td>
        </tr>

        <tr>
            <td class="en">6.11. The Purchaser acknowledges
                and
                agrees that the Relevant Unit Plan and all architecture details shown on the Relevant Unit Plan are
                indicative only and may not accord with the final as-built architectural details and the Purchaser shall
                be deemed to accept such changes and shall have no right or claim against the Seller should the final
                as-built architectural details differ from that set out in the Relevant Unit Plan.
            </td>
            <td class="separator"></td>
            <td class="ar">6-11 يقر
                ويوافق
                المشتري على أن مخطط الوحدة المعنية وكافة التفاصيل المعمارية المبينة في مخطط الوحدة المعنية ما هي إلا على
                سبيل الإيضاح فقط ومن الممكن ألا تتطابق والتفاصيل المعمارية النهائية، ويعتبر المشتري قد قبل تلك التغييرات
                ولن يكون له أي حق أو مطالبة ضد البائع في حال إذا اختلفت تلك التفاصيل المعمارية النهائية عن تلك المنصوص
                عليها في مخطط الوحدة المعنية.
            </td>
        </tr>

        <tr>
            <td class="en">6.12. The Purchaser undertakes to
                allow
                the Manager, the Seller and/or any of their affiliates to have the right to place logos, promotional
                signage, hoardings and all other forms of signage in the Project and/or in the Common Areas free of
                charge and, other than with respect to the initial capital costs of instating the same, at the Owners
                Associations expense in all respects, together with the exclusive right, free of charge, to organise and
                manage any public events whatsoever in the Project and/or Common Areas and shall allow the Manager, the
                Seller, and/or any of their affiliates to set all public signage standards and controls. For the
                avoidance of doubt the Purchaser cannot claim any compensation for any nuisance or disturbance howsoever
                caused. The Purchaser shall indemnify the Manager, the Seller for any losses, damages, and expenses
                incurred by the Manager, the Seller in defending their rights under this Clause 6.13. For the avoidance
                of doubt, the Seller’s rights pursuant to this Clause shall include the right to erect the signage and
                branding in accordance with the Brand Standards.
            </td>
            <td class="separator"></td>
            <td class="ar">6-12 يتعهد
                المشتري
                بأن يسمح للمدير والبائع و/ أو أي من منتسبيهم بأن يكون لهم الحق في وضع شعارات ولافتات ترويجية وكافة أشكال
                الإعلانات في المبنى والمساحات المشتركة أو أيهما مجانا، بخلاف التكاليف الرأسمالية الأولية لتركيبها على
                حساب جمعية الملاك من جميع النواحي، إلى جانب الحق الحصري في تنظيم وإدارة أية مناسبات عامة أيا كانت في
                المبنى والمساحات المشتركة أو في أيهما، وأن يسمح للمدير والبائع وأي من تابعيهم بوضع كافة المعايير
                والضوابط العامة للإعلان. وقطعا للشك باليقين، لا يجوز للمشتري أن يطالب بأي تعويض عن أي إزعاج أيا كان
                سببه. ويلتزم المشتري بأن يعوض المدير والبائع والمطور عن أية خسائر وأضرار ومصاريف يتحملها المدير والمطور
                أو أحدهما دفاعا عن حقوقه بموجب هذه المادة (6-13). وقطعا للشك باليقين، يجب أن تتضمن حقوق البائع وفقا لهذا
                البند الحق في تركيب اللافتات والعلامات التجارية وفقا لمعايير العلامة التجارية التي يتم تركيبها.
            </td>
        </tr>

        <tr>
            <td class="en">6.13. The Purchaser acknowledges and agrees the:
                <ol type="A">
                    <li>the Purchaser has received, read, and understood, Disclosure Statement;</li>
                    <li>the Seller has prepared the Disclosure Statement on a best endeavours basis and the
                        information therein is in a draft from, and may change from time to time; and
                    </li>
                    <li>the Seller has complied with all of its obligation under applicable law with respect the
                        Disclosure Statement.
                    </li>
                </ol>
            </td>
            <td class="separator"></td>
            <td class="ar">6-13 يقر المشتري على ما يلي:
                <ol type="A">
                    <li>أن المشتري قد تسلم بيان الإفصاح وقرأه وفهمه؛</li>
                    <li>أن البائع قد بذل أقصى جهده في إعداد بيان الإفصاح، إلا أن المعلومات الواردة تمثل مسودة يجوز
                        تغييرها من وقت لأخر؛
                    </li>
                    <li>أن البائع قد التزم بكافة التزاماته المنصوص عليها بموجب القانون المعمول بخصوص بيان الإفصاح.</li>
                </ol>
            </td>
        </tr>

        <tr>
            <td class="en">6.14 The Seller may amend the Strata Scheme Documentation) including for avoidance of
                doubt the Common Areas plans (to create the signage areas and rights referred to in clause 6.12
                including but not limited to the creation of Units, rights of “Exclusive Use” or other rights in
                perpetuity over such signage areas. Such rights shall include a right of access and egress to such
                signage areas as well as the unimpeded flow from and to such signage areas of any utilities. The
                Manager, the Operator, the Seller or their affiliates also have the right to remove such signage at any
                time.
            </td>
            <td class="separator"></td>
            <td class="ar">6-14 يجوز للبائع تعديل وثائق مخطط الملكية المشتركة، بما في ذلك، وعلى سبيل التوضيح لا
                الحصر، مخططات المناطق المشتركة، وذلك لإنشاء مناطق اللوحات الإعلانية والحقوق المشار إليها في البند 6.12،
                بما في ذلك، دون أن تقتصر على، إنشاء وحدات، وحقوق "الاستخدام الحصري"، أو أي حقوق دائمة أخرى على تلك
                المناطق الخاصة باللوحات الإعلانية. وتشمل هذه الحقوق حق الدخول والخروج من وإلى مناطق اللوحات الإعلانية،
                بالإضافة إلى التدفق غير المعيق للمرافق من وإلى تلك المناطق. ويحتفظ المدير، والمشغل، والبائع، أو أي من
                الشركات التابعة لهم، بحق إزالة تلك اللوحات الإعلانية في أي وقت.
            </td>
        </tr>

        <tr>
            <td class="en">6.15 The Purchaser hereby agrees to comply with all terms, conditions, covenants, and
                obligations set forth in the Master Community Declaration, as may be amended from time to time. This
                includes, without limitation, the obligation to pay Community Charges and to adhere to any rules,
                regulations, or architectural guidelines issued by the Master Developer or the relevant community
                management entity.
            </td>
            <td class="separator"></td>
            <td class="ar">6-15 يوافق المشتري بموجب هذا على الامتثال لجميع الشروط والأحكام والعهود والالتزامات
                المنصوص عليها في إعلان المجتمع الرئيسي، حسبما يتم تعديله من وقت لآخر. ويشمل ذلك، دون حصر، الالتزام بدفع
                الرسوم المجتمعية والامتثال لأي قواعد أو لوائح أو إرشادات معمارية تصدر عن المطور الرئيسي أو الجهة المعنية
                بإدارة المجتمع.
            </td>
        </tr>

        <tr>
            <th class="en">7. Seller’s General
                Covenants
            </th>
            <td class="separator"></td>
            <th class="ar">7- التعهدات العامة للبائع
            </th>
        </tr>

        <tr>
            <td class="en">7.1. The Seller will, following the
                Completion Date and provided the Purchaser is not otherwise in breach of this Agreement, assign to the
                Purchaser the benefit of any manufacturer’s warranties in respect of any furniture, fixtures or fittings
                (if any) installed by or on behalf of the Seller in the Relevant Unit insofar as they are current or
                capable of being assigned.
            </td>
            <td class="separator"></td>
            <td class="ar">7-1 يلتزم
                البائع،
                عقب تاريخ الإنجاز وبشرط عدم إخلال المشتري بهذه الاتفاقية، بأن يتنازل إلى المشتري عن الفائدة في أية
                كفالات شركة مصنعة بخصوص أية تركيبات أو تمديدات (إن وجد) تم تركيبها بواسطة البائع أو نيابة عنها في الوحدة
                المعنية طالما أنها سارية وقابلة للتنازل عنها.
            </td>
        </tr>

        <tr>
            <td class="en">7.2. For a period of twelve (12)
                months
                from the Completion Date, the Seller shall use best endeavours to rectify any defective works within the
                Relevant Unit, as soon as reasonably practicable from the date of the Seller receiving written notice of
                any such defective works from the Purchaser, in accordance with the provisions of this Agreement. The
                Purchaser hereby acknowledges that the Seller shall not be responsible in respect of any such defective
                works notified to the Seller after the expiry of the twelve (12) month period.
            </td>
            <td class="separator"></td>
            <td class="ar">7-2 لمدة اثني
                عشر
                (12) شهرا من تاريخ الإنجاز، يتعين على البائع أن يبذل قصارى جهده لتصحيح أية أعمال معيبة داخل الوحدة
                المعنية بمجرد أن يكون ذلك ممكنا تنفيذه على نحو معقول وذلك اعتبارا من تاريخ استلام البائع إخطارا خطيا من
                المشتري بأية أعمال معيبة من هذا القبيل، بموجب أحكام هذه الاتفاقية. ويقر المشتري بموجبه أن البائع لن يكون
                مسؤولا بخصوص أي من تلك الأعمال المعيبة التي أخطر المشتري البائع بها بعد انتهاء مدة الاثني عشر (12) شهر.
            </td>
        </tr>

        <tr>
            <td class="en">
                7.3 Except for any delay caused by Force Majeure circumstances:
                <ol type="A">
                    <li>
                        In the event the Purchaser has fulfilled all of his obligations under this Agreement
                        (including, without limitation, the payment obligations), and the Seller fails to complete of
                        the unit to the Purchaser by the Completion Date, the Purchaser shall have the right—by serving
                        a prior written notice of thirty (30) days to the Seller, which may only be given after the
                        lapse of six (6)
                        months from the final Completion Date—to request that the Seller pay interest at a rate of one
                        percent
                        (1%) per
                        annum on the amounts paid by the Purchaser towards the value of the unit, to be settled in
                        quarterly
                        instalments.
                    </li>
                    <li>
                        b) Furthermore, the Purchaser shall have the right—but not the obligation—to terminate this
                        Agreement
                        upon the lapse of twelve (12) months from the final Completion Date in the event the completion
                        has not
                        occurred. In such case, the Seller shall refund the amounts previously paid by the Purchaser,
                        excluding
                        any payments made to the competent authority, and subject to applicable laws, together with the
                        aforementioned interest, payable in quarterly instalments.
                    </li>
                </ol>
                The Purchaser acknowledges and agrees that such interest in case of delay, along with the refund
                of
                payments and interest in the event of delay exceeding twelve (12) months, shall constitute the
                sole and
                exclusive remedy available to the Purchaser under these circumstances, and the Purchaser hereby
                waives
                and releases the Seller from any and all rights to claim specific performance or any losses,
                costs,
                damages, taxes, expenses, or liabilities of any kind.
            </td>
            <td class="separator"></td>
            <td class="ar">
                7-3 فيما عدا أي تأخير بسبب القوى القاهرة في حال:
                <ol type="A">
                    <li>
                        قام المشترى بالوفاء بكافة التزاماته تحت هذه الاتفاقية (شاملا بدون الحصر التزام المشتري في
                        الدفعات بهذه الاتفاقية)، وكان البائع غير قادر على إنجاز تملك الوحدة السكنية إلى المشتري في تاريخ
                        الانجاز فإنه يجوز للمشتري بإخطار خطي مسبق ب (30) يوما إلى البائع يتم إعطاؤه بعد مضي (6) أشهر من
                        تاريخ الإنجاز النهائي، يطلب فيه من البائع دفع فائدة وقدرها (1%) سنويا من قيمة ما سدد من دفعات
                        قيمة الوحدة، إذ يتم دفعها على أساس أقساط ربع سنوية.
                    </li>
                    <li>
                        قام المشترى بالوفاء بكافة التزاماته تحت هذه الاتفاقية (شاملا بدون الحصر التزام المشتري في
                        الدفعات بهذه الاتفاقية)، وكان البائع غير قادر على إنجاز تملك الوحدة السكنية إلى المشتري في تاريخ
                        الانجاز فإنه يجوز للمشتري بإخطار خطي مسبق ب (30) يوما إلى البائع يتم إعطاؤه بعد مضي (6) أشهر من
                        تاريخ الإنجاز النهائي، يطلب فيه من البائع دفع فائدة وقدرها (1%) سنويا من قيمة ما سدد من دفعات
                        قيمة الوحدة، إذ يتم دفعها على أساس أقساط ربع سنوية.
                    </li>
                </ol>
                يوافق المشتري على أن هذه الفائدة في حال التأخير وإعادة الدفعات ودفع الفائدة في حال التأخير أكثر من (12)
                شهرا تعتبر المعالجة الوحيدة للمشتري في هذه الظروف ويسقط حق المشتري في أي حق لمطالبة البائع عن (ويعفي
                ويبرأ ذمة البائع من) أي أداء محدد وأي خسائر، تكاليف، أضرار، ضرائب أو مصاريف أو مسؤوليات مهما كانت.
            </td>
        </tr>

        <tr>
            <th class="left-th section-heading" style="width:49%; text-align:justify; padding:10px;">8. The STRATA
                SCHEME
            </th>
            <td class="separator"></td>
            <th class="rtl-text right-th spaced-text section-heading"
                style="width:49%; text-align:justify; padding:10px;">8- قواعد الملكية المشتركة
            </th>
        </tr>

        <tr>
            <td class="en">8.1. It is the Seller's intention
                to
                implement the Strata Scheme through use of the Strata Scheme Documentation.
            </td>
            <td class="separator"></td>
            <td class="ar">8-1 تتمثل نية
                البائع في تطبيق قواعد الملكية المشتركة من خلال استخدام مستندات قواعد الملكية المشتركة.
            </td>
        </tr>

        <tr>
            <td class="en">8.2. The Purchaser acknowledges and
                accepts that the Strata Scheme Documentation for the Project are to be finalised on or as soon as
                reasonably practicable after the Completion Date. The Purchaser acknowledges and agrees that the Seller
                shall be entitled to determine, at the Seller’s discretion, the form of the Strata Scheme Documentation
                adopted for the Project provided the same are not determined to be contrary to Applicable Law or
                requirements of the Relevant Authority.
            </td>
            <td class="separator"></td>
            <td class="ar">8-2 ويقر
                ويقبل
                المشتري أن مستندات قواعد الملكية المشتركة بشأن المشروع يتوجب الانتهاء منها في تاريخ الإنجاز أو في أقرب
                وقت ممكن بعده بشكل معقول. كما يقر ويوافق المشتري على أنه يحق للبائع أن يحدد، حسب تقدير البائع، صيغة
                مستندات قواعد الملكية المشتركة المتبعة بشأن المشروع بشرط ألا يقرر أنها مخالفة للقانون المعمول به أو
                لمتطلبات السلطة المختصة.
            </td>
        </tr>

        <tr>
            <td class="en">8.3. In the event that the Owners
                Association for the Project is not yet formed and registered pursuant to Applicable Law, the Purchaser
                acknowledges and understands that the powers and functions of the Owners Association will be delegated
                to the Seller or its agent until such time as it is duly formed and registered pursuant to Applicable
                Law.
            </td>
            <td class="separator"></td>
            <td class="ar">8-3 وفي حال
                إذا
                لم تشكل وتسجل بعد جمعية الملاك بشأن المشروع طبقا للقانون المعمول به، يقر المشتري ويفهم أن صلاحيات
                واختصاصات جمعية الملاك سوف تفوض إلى البائع أو وكيله حتى تشكل وتسجل جمعية الملاك أصوليا طبقا للقانون
                المعمول به.
            </td>
        </tr>

        <tr>
            <td class="en">8.4. The delegation of the powers
                and
                functions of the Owners Association will be performed by the Manager pursuant to the Management
                Agreement.
            </td>
            <td class="separator"></td>
            <td class="ar">8-4 يتعين على
                المدير أن ينفذ التفويض بصلاحيات واختصاصات جمعية الملاك وفقا لاتفاقية الإدارة.
            </td>
        </tr>

        <tr>
            <td class="en">8.5. The Purchaser acknowledges and
                accepts that an Owners Association must comply with and enforce the Strata Scheme Documentation to
                ensure the proper management, administration, maintenance and control of the Project generally and for
                the benefit of all Unit Owners and Plot Owners including, without limitation, to a standard consistent
                with the Brand Standards.
            </td>
            <td class="separator"></td>
            <td class="ar">8-5 يقر
                المشتري
                ويقبل أن جمعية ملاك يجب أن تلتزم بمستندات قواعد الملكية المشتركة وتنفذها لضمان إدارة وتسيير وصيانة
                ومراقبة المشروع عموما على نحو سليم ولمصلحة كافة ملاك الوحدات وقطع الأرض بما في ذلك، على سبيل المثال لا
                الحصر، معيار متوافق مع معايير العلامة التجارية.
            </td>
        </tr>

        <tr>
            <td class="en">8.6. The Purchaser acknowledges and
                agrees that, upon taking transfer of the Property, the Purchaser and his heirs, successors-in-title,
                successors or assignees will become a member of the applicable Owners Association for as long as he is a
                Unit Owner and he agrees and undertakes to be legally bound by and comply with the Strata Scheme
                Documentation.
            </td>
            <td class="separator"></td>
            <td class="ar">8-6 يقر
                ويوافق
                المشتري على أنه، فور استلام نقل ملكية العقار، يصبح المشتري أو خلفاؤه أو ورثته في الملكية أو المتنازل
                إليهم عضوا بجمعية الملاك المعنية طالما أنه مالك وحدة، ويوافق ويتعهد بأن يلتزم قانونا بمستندات قواعد
                الملكية المشتركة.
            </td>
        </tr>

        <tr>
            <td class="en">8.7. Every Unit is sold subject to
                the
                terms of the Strata Scheme Documentation and all possible steps will be undertaken by the Parties so
                that the registration of the Property in the Land Department will be made subject to the terms of the
                Strata Scheme Documentation in the form of a restriction on title. If this is not possible, the
                Purchaser personally and on behalf of his successors or assigns acknowledges, agrees and undertakes for
                the benefit of the Seller and the other Unit Owners that the Strata Scheme Documentation has the form of
                a restriction in a document and is equally binding on each Unit Owner.
            </td>
            <td class="separator"></td>
            <td class="ar">8-7 تباع كل
                وحدة
                شريطة عدم الإخلال بشروط مستندات قواعد الملكية المشتركة ويتولى الطرفان تنفيذ كافة الإجراءات الممكنة بحيث
                ينفذ تسجيل العقار لدى دائرة الأراضي والأملاك مع مراعاة عدم الإخلال بشروط مستندات قواعد الملكية المشتركة
                الواردة في صيغة القيود على الملكية. وإذا لم يكن ذلك ممكنا، يقر المشتري شخصيا ونيابة عن ورثته أو المتنازل
                لهم ويوافق ويتعهد لمصلحة البائع ومالكي الوحدة الآخرين أن مستندات قواعد الملكية المشتركة لها صيغة قيد في
                مستند وتكون ملزمة بالتساوي على كل مالك وحدة.
            </td>
        </tr>

        <tr>
            <td class="en">8.8. To the extent that the Strata
                Scheme Documentation, the Management Agreement or are not finalised by the Seller at the Effective Date
                or at any time thereafter and/or approved by the Relevant Authority (if applicable), the Purchaser
                hereby: (a) irrevocably authorises the Seller as the Purchaser’s agent; and (b) irrevocably appoints the
                Seller as the Purchaser’s proxy; both to the extent required by the Seller to finalise the Strata Scheme
                Documentation, the Management Agreement including, without limitation, to allow the Seller at a General
                Assembly to vote in favour of the Owners Association, to enter into or approve any Strata Scheme
                Documentation, the Management Agreement and (including approving any variations to the Strata Scheme
                Documentation to ensure compliance with the Brand Standards). The Purchaser fully, finally and
                irrevocably waives any claims it has or may have against the Seller in relation to the Strata Scheme
                Documentation, the Management Agreement and the Operating Agreement and the exercise of the Seller’s
                rights under this Clause 8.8.
            </td>
            <td class="separator"></td>
            <td class="ar">8-8 في حال
                إذا لم
                ينته البائع من مستندات قواعد الملكية المشتركة أو اتفاقية الإدارة في تاريخ بدء السريان أو أي وقت بعدها أو
                لم توافق عليها السلطة المختصة (إن كان ذلك مطبقا)، يتعين على المشتري بموجبه: (أ) أن يفوض البائع تفويضا لا
                رجعة فيه بصفته وكيل المشتري؛ (ب) أن يعين البائع تعيينا نهائيا لا رجعة فيه ليكون وكيلا للمشتري، إلى الحد
                الذي يطلبه البائع للانتهاء من مستندات قواعد الملكية المشتركة واتفاقية الإدارة ، بما في ذلك، على سبيل
                الذكر وليس الحصر، أن يسمح للبائع في اجتماع الجمعية العمومية أن يصوت لصالح جمعية الملاك لإبرام مستندات
                قواعد الملكية المشتركة واتفاقية الإدارة (بما في ذلك الموافقة على أي تعديلات لمستندات قواعد الملكية
                المشتركة أو لاتفاقية الإدارة لضمان الامتثال لمعايير العلامة التجارية). ويتنازل المشتري تنازلا كاملا
                ونهائيا وغير قابل للنقض عن أية مطالبات تستحق له أو يمكن أن تستحق له ضد البائع فيما يتعلق بمستندات قواعد
                الملكية المشتركة واتفاقية الإدارة وممارسة حقوق البائع بموجب هذا البند (8-8).
            </td>
        </tr>

        <tr>
            <td class="en">8.9. Without in any way limiting
                this
                Clause 8, the Purchaser acknowledges and understands that the Property and the Project are part of the
                Master Community and that the Project Plot and certain adjoining land may be developed into a
                homogeneous residential, commercial and leisure community which may comprise Plots and Communal
                Facilities. The Purchaser acknowledges and understands that, subject to the terms of the Master
                Community Declaration, the Master Developer will remain the owner of the residual land in the Master
                Community. The Purchaser acknowledges and agrees that the Master Developer shall have the right to
                create, remove or vary the Communal Facilities, amend the Master Plan and the Master Community
                Declaration as deemed necessary by the Master Developer and as may be required by the Relevant
                Authority.
            </td>
            <td class="separator"></td>
            <td class="ar">8-9 دون حصر
                هذا
                البند (8) بأي حال من الأحوال، يقر ويفهم المشتري أن العقار والمشروع جزء من المجمع الرئيسي وأن أرض المشروع
                ومساحة معينة من الأرض المجاورة سوف يتم تطويرها لتصبح مجمعا سكنيا وتجاريا وترفيهيا متجانسا ويمكن أن يتألف
                من قطع الأرض والمرافق المشتركة. يقر ويفهم المشتري، مع مراعاة عدم الإخلال بشروط نظام المجمع الرئيسي، أن
                المطور الرئيسي يظل مالك الأرض المتبقية في المجمع الرئيسي. يقر ويوافق المشتري على أن المطور الرئيسي له
                الحق في أن ينشئ أو يزيل أو يغير المرافق المشاع وأن يعدل المخطط الرئيسي ونظام المجمع الرئيسي حسبما يتراءى
                للمطور الرئيسي ضرورة ذلك وحسبما تتطلب السلطة المختصة.
            </td>
        </tr>

        <tr>
            <td class="en">8.10. The Purchaser further
                acknowledges
                that the Project shall be managed exclusively by the Manager for a term determined by the Seller in its
                absolute discretion. If the term of the Management Agreement determined by the Seller is not acceptable
                under the Applicable Laws then the term of the Management Agreement shall be the maximum duration
                permitted under the Applicable Laws.
            </td>
            <td class="separator"></td>
            <td class="ar">8-10 كما يقر
                المشتري أن المشروع يتعين أن يديره المدير وحده لمدة يحددها البائع وفقا لتقديره المنفرد. وإذا لم تكن المدة
                التي يحددها البائع مقبولة بموجب القوانين المعمول بها، يتعين أن تكون مدة اتفاقية الإدارة هي الحد الأقصى
                للمدة المسموح بها بموجب القوانين المعمول بها.
            </td>
        </tr>

        <tr>
            <th class="left-th section-heading" style="width:49%; text-align:justify; padding:10px;">9. VIEWING</th>
            <td class="separator"></td>
            <th class="rtl-text right-th spaced-text section-heading"
                style="width:49%; text-align:justify; padding:10px;">9- المعاينة
            </th>
        </tr>

        <tr>
            <td class="en">9.1. The Purchaser may after
                receiving
                the notice of the Completion Date, book an appointment with the Seller, at a convenient time to the
                Seller, to view the Relevant Unit provided it is in the sole opinion of the Project Manager safe to do
                so. Such appointment has to be booked for a date no later than two (2) months after receiving the notice
                of the Completion Date. Failure to book an appointment will entitle the Seller to hand over the Relevant
                Unit on an as is basis and the Purchaser shall be deemed to have accepted the physical condition of the
                Relevant Unit.
            </td>
            <td class="separator"></td>
            <td class="ar">9-1 يجوز
                للمشتري،
                بعد استلامه الإخطار بتاريخ الإنجاز، أن يحجز موعدا مع البائع لمعاينة الوحدة المعنية بشرط أن يتراءى لمدير
                المشاريع وحده أن ذلك لا يتعارض مع اشتراطات الأمن والسلامة. ويتعين ألا يتجاوز تاريخ الموعد المحجوز شهرين
                (2) بعد استلامه الإخطار بتاريخ الإنجاز. وفي حال عدم حجز موعد، يحق للبائع أن يسلم الوحدة المعنية على أساس
                حالتها الحالية، ويعتبر المشتري قد قبل حالة الوحدة المعنية على الطبيعة.
            </td>
        </tr>

        <tr>
            <td class="en">9.2. The Purchaser shall be
                accompanied
                by a representative of the Seller and such inspection shall take place during normal business hours.
            </td>
            <td class="separator"></td>
            <td class="ar">9-2 يتعين على
                المشتري أن يرافقه ممثل البائع وتجرى تلك المعاينة أثناء ساعات العمل الاعتيادية.
            </td>
        </tr>

        <tr>
            <td class="en">9.3. The Purchaser will comply with
                all
                safety directions given in regard to access to the Relevant Unit and the Project for all the purposes of
                this clause and will cause no undue interference in exercising access.
            </td>
            <td class="separator"></td>
            <td class="ar">9-3 يتعين على
                المشتري أن يلتزم بكافة توجيهات السلامة الصادرة بخصوص الدخول إلى الوحدة المعنية والمشروع لكافة أغراض هذا
                البند، وألا يتسبب في أي تعارض لا داعي له في ممارسة حق الدخول.
            </td>
        </tr>

        <tr>
            <td class="en">9.4. At the viewing, the Parties
                shall
                prepare and sign a complete list of any defects and deficiencies (the “Defects”), if any, and shall
                agree the date by which the Defects shall be repaired. The Seller shall as soon as reasonable remedy the
                Defects by the date agreed by the Parties provided that the Purchaser shall not be entitled to hold back
                any portion of the Purchase Price or delay the performance of any other obligations due to any Defects.
                In the event of any dispute, a decision by the Project Manager shall be final and binding on the
                Parties. The Purchaser shall be deemed to have accepted the physical condition of the Relevant Unit in
                all other respects. The Purchaser acknowledges that except for this one inspection, the Purchaser shall
                not be allowed access to the Relevant Unit or the Project prior to the Completion Date, without the
                prior written authorisation of the Seller.
            </td>
            <td class="separator"></td>
            <td class="ar">9-4 عند
                المعاينة،
                يتعين على الطرفين أن يعدا ويوقعا على قائمة كاملة بأية عيوب ونواقص ("العيوب")، إن وجد، وأن يتفقا على
                تاريخ يجرى بحلوله إصلاح العيوب. ويتعين على البائع، بمجرد أن يكون ذلك معقولا، أن يتدارك العيوب بحلول
                التاريخ الذي اتفق عليه الطرفان، بشرط ألا يحق للمشتري أن يحتجز أي جزء من سعر الشراء أو يؤخر تنفيذ أية
                التزامات أخرى بسبب أية عيوب. وفي حال حدوث أي نزاع، يكون قرار مدير المشاريع نهائيا وملزما على الطرفين.
                ويعتبر المشتري قد قبل الوضع الحالي للوحدة المعنية بما هي عليه في جميع الجوانب. ويقر المشتري أنه باستثناء
                المعاينة في هذه المرة، لن يسمح للمشتري بدخول الوحدة المعنية أو المشروع قبل تاريخ الإنجاز إلا بعد الحصول
                على تفويض مكتوب من البائع.
            </td>
        </tr>

        <tr>
            <td class="en">9.5. The Purchaser agrees that it
                shall
                not be entitled to make any objection, claim, withhold any payment towards the Purchase Price or delay
                or refuse to complete the sale and purchase of the Property, and/or take hand over of the Property as a
                result of any matters, and/or Defects related to the Property.
            </td>
            <td class="separator"></td>
            <td class="ar">9-5 يوافق
                المشتري
                على أنه لن يحق له أن يبدي أي اعتراض أو يرفع أية مطالبة أو يحتجز أية دفعة من سعر الشراء أو يؤخر أو يرفض
                إتمام بيع وشراء العقار أو يرفض أن يستلم العقار نتيجة أية أمور أو عيوب تتعلق بالعقار.
            </td>
        </tr>

        <tr>
            <th class="left-th section-heading" style="width:49%; text-align:justify; padding:10px;">10. Default and
                Termination
            </th>
            <td class="separator"></td>
            <th class="rtl-text right-th spaced-text section-heading"
                style="width:49%; text-align:justify; padding:10px;">10- الإخلال وفسخ الاتفاقية
            </th>
        </tr>

        <tr>
            <td class="en">10.1. If the Purchaser fails to
                make
                payments to the Seller in accordance with the terms and conditions of this Agreement and/or does not
                fulfil any of the terms and conditions of this Agreement, then the Seller shall be entitled but not
                obligated to give the Purchaser thirty (30) days prior notice in writing calling on the Purchaser to
                remedy such defaults and if the Purchaser fails to comply with such notice, then the Seller shall be
                entitled, without further notice, without the requirement for a court order or other formality, and
                without prejudice to any other rights available in law:
            </td>
            <td class="separator"></td>
            <td class="ar">10-1 في حال
                إذا
                قصر المشتري في سداد الدفعات المستحقة إلى البائع بموجب شروط وأحكام هذه الاتفاقية ولم يف بأي من شروط
                وأحكام هذه الاتفاقية، يحق للبائع، دون إلزام عليه، أن يرسل إلى المشتري إخطارا خطيا مسبقا مدته ثلاثون (30)
                يوما يطالب فيه المشتري أن يتدارك حالات الإخلال التي وقعت، وإذا قصر المشتري في الالتزام بمضمون هذا
                الإخطار، يحق للبائع، دون إخطار آخر، ودون حاجة إلى حكم قضائي أو أي إجراء قانوني ومع مراعاة عدم الإخلال
                بأية حقوق أخرى متاحة بموجب القانون:
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">(a) A) Without
                prejudice to the Seller’s right to terminate this Agreement and any resulting legal consequences, the
                Seller shall be entitled to impose a delay penalty of 2% for each month of delay in the Purchaser’s
                payment of any due installment under the provisions of this Agreement, calculated on the outstanding
                unpaid amount
                (For the avoidance of doubt, the penalty shall be calculated as follows):
                If (30) days pass from the due date of any amount without payment, the Seller shall have the right to
                impose a penalty of 2% of the outstanding amount, calculated from the original due date. One day or any
                part thereof shall be deemed as a full month.
                The penalty amount shall take priority in allocation upon any payment made by the Buyer.To clarify, when
                the Buyer makes any payment to the Seller, the accrued penalties (resulting from delayed payments) shall
                be deducted first, before allocating the remaining amount to the outstanding installments.
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;">
                أ) دون الإخلال بحقه بفسخ الاتفاقية وما يتنتج عنه من أثار قانونية، يحق للبائع فرض غرامة تأخير بنسبة 2% عن
                كل شهر يتأخر فيه المشتري عن سداد أي دفعة مترتبة عليه بموجب أحكام هذه الاتفاقية، وتحتسب من قيمة المبلغ
                غير المسدد.
                (ولإزالة أي غموض، تحتسب الغرامة المذكورة على النحو التالي):
                في حال مضت (30) يوما من تاريخ استحقاق أي مبلغ دون سداد، يحق للبائع فرض غرامة بنسبة 2% من قيمة المبلغ
                المستحق، وتحتسب من تاريخ الاستحقاق. يعتبر اليوم الواحد أو أي جزء منه شهر كامل.
                ويكون لمبالغ الغرامات المفروضة الأولوية في الاستيفاء عند قيام المشتري بسداد أي مبالغ، وبمعنى أوضح، عند
                سداد أي مبلغ للبائع، يتم أولا خصم قيمة الغرامات المتراكمة (الناتجة عن التأخير في السداد)، قبل احتساب
                المبلغ المتبقي من الدفعات الأخرى المستحقة.
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">(b) to terminate
                this Agreement and
                Deregister the Property; and
            </td>
            <td class="separator"></td>
            <td class="rtl-text right-th spaced-text"
                style="width:49%; text-align:justify; padding:10px 35px 10px 10px;">ب) أن يفسخ هذه
                الاتفاقية ويلغي تسجيل العقار.
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">(c) to retain an
                amount equivalent to
                the greater of:
            </td>
            <td class="separator"></td>
            <td class="rtl-text right-th spaced-text"
                style="width:49%; text-align:justify; padding:10px 35px 10px 10px;">ت) أن يحتجز
                مبلغا يعادل أعلى القيمتين التاليتين:
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 50px;">(i) forty per cent
                (40%) of the
                Purchase Price as pre-estimated liquidated damages which the Purchaser expressly agrees is a true and
                reasonable pre-estimate of the damages that will be suffered by the Seller as a result of the
                Purchaser’s default and if the amount of the Purchase Price paid by the Purchaser by the date of
                termination is insufficient to meet the Seller’s entitlement to damages then the Purchaser shall remain
                liable to pay the shortfall on demand; or
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 50px 10px 10px;">1) ما نسبته
                أربعون بالمائة (40%) من سعر الشراء على سبيل التعويض المقدر مسبقا عن الأضرار بقيمة نقدية ويوافق المشتري
                صراحة أن هذه النسبة تقدير مسبق حقيقي ومعقول للأضرار التي سيتعرض لها البائع نتيجة إخلال المشتري، وفي حال
                إذا كان مبلغ سعر الشراء الذي دفعه المشتري بحلول تاريخ فسخ الاتفاقية غير كاف للوفاء بمستحقات البائع
                تعويضا عن الأضرار، يظل المشتري مسؤولا عن دفع مبلغ العجز عند الطلب؛
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 50px;">(ii) such amount as
                the Seller may be
                entitled to retain and/or forfeit in accordance with Applicable Law; and
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 50px 10px 10px;">2) ذلك المبلغ
                الذي يحق للبائع أن يحتجزه أو يصادره بموجب القانون المعمول به؛
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">(d) take any other
                action or uphold any
                further entitlement afforded to the Seller in accordance with the terms of this Agreement and/or
                Applicable Law.
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;"> ث) أن يتخذ أي
                إجراء آخر أو يحتجز أي مستحقات أخرى تجاز للبائع بموجب شروط هذه الاتفاقية والقانون المعمول به أو أيهما.
            </td>
        </tr>

        <tr>
            <td class="en">10.2. For the avoidance of doubt,
                no
                refund of monies paid (once all applicable damages, fees, costs and other charges are deducted by the
                Seller) shall be made by the Seller to the Purchaser until the Property is sold, transferred or
                otherwise disposed of and the Seller is in receipt of the sales proceeds in cleared funds.
            </td>
            <td class="separator"></td>
            <td class="ar">10-2 قطعا
                للشك
                باليقين، لا يلتزم البائع بأن يعيد إلى المشتري أية مبالغ دفعها المشتري (بمجرد استقطاع البائع كافة
                التعويضات المعمول بها عن الأضرار واستقطاع الرسوم والتكاليف والمصاريف الأخرى) حتى يباع العقار أو تنقل
                ملكيته أو خلاف ذلك يتم التصرف فيه وحتى يتسلم البائع متحصلات عمليات البيع نقدا أو بشيكات مستحقة السداد.
            </td>
        </tr>

        <tr>
            <td class="en">10.3. Where termination of this
                Agreement
                takes place pursuant to Clause 10.1 above, and the Seller takes action against the Purchaser to
                repossess the Property, the Purchaser undertakes to fully indemnify the Seller against all third party
                losses, damages, claims, demands, and/or suits arising from or in connection with such repossession.
            </td>
            <td class="separator"></td>
            <td class="ar">10-3 في حال
                إذا
                وقع فسخ هذه الاتفاقية طبقا للبند (10-1) السالف ذكره، واتخذ البائع إجراء قانونيا ضد المشتري لإعادة حيازة
                العقار، يتعهد المشتري أن يعوض البائع تعويضا كاملا عن كافة خسائر الأطراف الخارجية وأضرارهم ومطالباتهم
                ومطالبهم ودعواهم الناشئة عن إعادة الحيازة المذكورة أو المتعلقة بها.
            </td>
        </tr>

        <tr>
            <td class="en">10.4. In the event that the
                Purchaser has
                sold the Property or assigned this Agreement to any third party in breach of this Agreement or
                Applicable Law, which event has resulted in the termination of the Agreement in accordance with this
                Clause 10, then the Purchaser undertakes to: (a) meet any and all claims from such third parties buying
                or accepting a transfer of the Property or assignment of this Agreement; and (b) indemnify the Seller
                from or against any loss, damage or claim from such third parties or otherwise arising out of the
                Purchaser’s breach.
            </td>
            <td class="separator"></td>
            <td class="ar">10-4 في حال
                إذا
                باع المشتري العقار أو تنازل عن هذه الاتفاقية إلى أي طرف خارجي إخلالا بأحكام هذه الاتفاقية أو القانون
                المعمول به، ونتج عن ذلك فسخ الاتفاقية بموجب هذا البند (10)، فحينئذ يتعهد المشتري بما يلي: (أ) أن يفي
                بجميع المطالبات المرفوعة من تلك الأطراف الخارجية الذين يشترون العقار أو يقبلون نقل ملكية العقار أو
                التنازل عن هذه الاتفاقية؛ (ب) أن يعوض البائع عن أية خسارة أو ضرر أو مطالبة ترفعها تلك الأطراف الخارجية
                أو خلاف ذلك نتيجة إخلال المشتري.
            </td>
        </tr>

        <tr>
            <td class="en">10.5. In the event of the Seller,
                the
                Purchaser shall, on demand from the Seller and at the Purchaser’s sole cost, take such steps required by
                the Seller and/or the Land Department to Deregister the Property including but not limited to:
            </td>
            <td class="separator"></td>
            <td class="ar">10-5 وفي حالة
                البائع، يتعين على المشتري، عند الطلب من البائع وعلى نفقة المشتري وحده، أن يتخذ تلك الإجراءات التي
                يتطلبها البائع أو دائرة الأراضي والأملاك لإلغاء تسجيل العقار، وتشمل هذه الإجراءات على سبيل المثال، لا
                الحصر:
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">(a) signing any
                documents required by
                the Seller or the Land Department;
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;">أ) التوقيع على
                أية مستندات يتطلبها البائع أو تتطلبها دائرة الأراضي والأملاك؛
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">(b) paying the
                Deregistration Fees
                including any of such fees assessed against the Seller; and
            </td>
            <td class="separator"></td>
            <
            <td class="ar" style="padding:10px 35px 10px 10px;"> ب) دفع رسوم
                إلغاء التسجيل بما في ذلك أية رسوم يتم تقييمها على البائع؛
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">(c) in the case of
                any termination
                pursuant to Clause 10.1 paying any administration fees levied by the Seller in respect of any such
                Deregistration.
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;"> ت) في حال أي
                فسخ للاتفاقية طبقا للبند (10-1)، دفع أية رسوم إدارية يفرضها البائع بخصوص أي إلغاء تسجيل من هذا القبيل.
            </td>
        </tr>

        <tr>
            <td class="en">10.6. The Purchaser shall indemnify
                the
                Seller against any losses, costs, claims or penalties the Seller may incur arising out of the Purchasers
                failure to comply with Clause 10.5.
            </td>
            <td class="separator"></td>
            <td class="ar">10-6 يتعين
                على
                المشتري أن يعوض البائع عن أية خسائر أو تكاليف أو مطالبات أو غرامات يمكن أن يتحملها البائع نتيجة تقصير
                المشتري في الالتزام بالبند (10-5)
            </td>
        </tr>

        <tr>
            <th class="left-th section-heading" style="width:49%; text-align:justify; padding:10px;">11. Force Majeure
            </th>
            <td class="separator"></td>
            <th class="rtl-text right-th spaced-text section-heading"
                style="width:49%; text-align:justify; padding:10px;">11- القوة القاهرة
            </th>
        </tr>

        <tr>
            <td class="en">11.1. The Seller shall not be
                liable for
                any failure or delay to perform its obligations under this Agreement due to Force Majeure Events
                provided the Seller gives to the Purchaser a written notice within thirty (30) days indicating the
                beginning of such circumstances. Any date, period or deadline imposed upon the Seller by this Agreement
                shall be extended for a period equal to that during which such circumstances exist. The Seller shall in
                such circumstances promptly notify the Purchaser of the new date, period or deadline (including, but not
                limited to the Completion Date) or an estimate of the duration of the delay, followed by a new date,
                period or deadline when it can be determined.
            </td>
            <td class="separator"></td>
            <td class="ar">11-1 لا يكون
                البائع مسؤولا عن أي تقصير أو تأخير في تنفيذ التزاماته المنصوص عليها بموجب هذه الاتفاقية بسبب وقوع أحداث
                القوة القاهرة، بشرط أن يرسل البائع إخطارا خطيا إلى المشتري في خلال ثلاثين (30) يوما يبين بداية تلك
                الظروف. ويتعين تمديد أية تاريخ أو فترة أو موعد نهائي مفروض على البائع في هذه الاتفاقية لمدة تساوي تلك
                المدة التي حدثت خلالها تلك الظروف. ويتعين على البائع في تلك الأحوال أن يخطر المشتري على الفور بالتاريخ
                الجديد أو الفترة الجديدة أو الموعد النهائي الجديد (بما في ذلك، على سبيل الذكر وليس الحصر، تاريخ الإنجاز)
                أو بتقدير لمدة التأخير يليها تاريخ جديد أو فترة جديدة أو موعد نهائي جديد عندما يتسنى تحديد ذلك.
            </td>
        </tr>

        <tr>
            <td class="en">11.2. Payment by the Purchaser of
                any
                part of the Purchase Price or any other amount due under this Agreement when due shall not be excused or
                delayed due to a Force Majeure Event.
            </td>
            <td class="separator"></td>
            <td class="ar">11-2 لن يصبح
                وقوع
                حدث القوة القاهرة سببا يعذر أو يؤخر دفع المشتري سعر الشراء أو أي مبلغ آخر مستحق بموجب هذه الاتفاقية عند
                حلول موعد استحقاقه.
            </td>
        </tr>

        <tr>
            <td class="en">11.3. Upon the occurrence of a
                Force
                Majeure Event, both Parties shall take all reasonable measures to minimise the effect of such event and
                use their best endeavours to continue to perform their obligations under this Agreement insofar as this
                is reasonably practicable.
            </td>
            <td class="separator"></td>
            <td class="ar">11-3 فور وقوع
                حدث
                القوة القاهرة، يتعين على الطرفين أن يتخذا كافة التدابير المعقولة لتقليل أثر ذلك الحدث وأن يبذلا قصارى
                جهدهما للاستمرار في تنفيذ التزاماتهما بموجب هذه الاتفاقية طالما أن ذلك ممكنا إجراؤه على نحو معقول.
            </td>
        </tr>

        <tr>
            <th class="left-th section-heading" style="width:49%; text-align:justify; padding:10px;">12. General</th>
            <td class="separator"></td>
            <th class="rtl-text right-th spaced-text section-heading"
                style="width:49%; text-align:justify; padding:10px;">12- شروط عامة
            </th>
        </tr>

        <tr>
            <td class="en">12.1. No variation of this
                Agreement
                shall be valid unless it is in writing and signed by each of the Parties or their authorised
                representatives.
            </td>
            <td class="separator"></td>
            <td class="ar">12-1 لن يسري
                أي
                تغيير أو تعديل على هذه الاتفاقية ما لم يحرر خطيا ويوقع عليه من كل طرف من الطرفين أو ممثله المخول قانونا.
            </td>
        </tr>

        <tr>
            <td class="en">12.2. This Agreement may not be
                assigned
                or transferred by the Purchaser except:
            </td>
            <td class="separator"></td>
            <td class="ar">12-2 لا يجوز
                للمشتري التنازل عن هذه الاتفاقية أو إسنادها إلا في الحالات التالية:
            </td>
        </tr>

        <tr>
            <
            <td class="en" style="padding:10px 10px 10px 35px;">(a) with the prior
                written consent of
                the Seller given in the terms of a written assignment agreement in a form acceptable to the Seller,
                executed by the Parties and the assignee;
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;">أ) بموافقة خطية
                مسبقة من البائع تمنح بحسب شروط اتفاقية التنازل الخطية بصيغة يقبلها البائع، ويحررها الطرفان والمتنازل
                إليه؛
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">
                (b) obtains a No Objection Certificate (NOC) from the Seller.
                The issuance of the NOC is subject to the following conditions:
                <ul>
                    <li>The Buyer must have paid more than 60% of the purchase price, after deducting any penalties
                        imposed on the Buyer under the provisions of this Agreement.
                    </li>
                    <li>
                        In the event the Buyer has already paid 80% or more of the purchase price, the Buyer must
                        settle the full outstanding balance before the NOC is granted.
                    </li>
                </ul>
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;">
                (ب) حصوله على شهادة عدم ممانعة صادرة من البائع. <br/>
                وتخضع شهادة عدم الممانعة للشروط التالية:
                <ul>
                    <li>
                        أن يكون المشتري قد سدد ما يزيد عن 60% من سعر الشراء، وذلك بعد خصم أي غرامات تم فرضها عليه بموجب
                        أحكام هذه الاتفاقية، بغض النظر عن نسبة إنجاز المشروع.
                    </li>
                    <li>
                        في حال كان المشتري قد سدد أصلا 80% من سعر الشراء أو أكثر، يتعين عليه سداد كامل سعر الشراء قبل
                        منحه شهادة عدم الممانعة.
                    </li>
                </ul>
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">
                (c) upon payment of the Administration Fee by the Purchaser (which is currently set at AED 5,000
                (UAE Dirhams Five Thousand) Or the amount specified by the Owner as applicable in Dubai, and which
                amount may be amended by the Seller from time to time having regard to the maximum allowable pursuant to
                Applicable Law;
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;">
                (ت) بعد دفع المشتري الرسوم الإدارية (التي تبلغ حاليا 5000 درهم (خمسة آلاف درهم) او المبلغ الذي يحدده
                البائع وفقا للمعمول به في امارة دبي حيث يجوز للبائع تعديل ذلك المبلغ من وقت لآخر حسبما يتعلق بالحد
                الأقصى المسموح به وطبقا للقانون المعمول به.
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">
                (d) on the basis that the Seller shall not be liable for the Fees or any other Land Department fees,
                charges or penalties in any way associated with the assignment all of which will be met by the
                Purchaser.
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;">
                (ث) على أساس أن البائع لن يتحمل المسؤولية عن الرسوم أو أية رسوم أو مصاريف أو غرامات أخرى لدائرة الأراضي
                والأملاك ترتبط بأي حال من الأحوال بالتنازل ويتعين على المشتري الوفاء بها جميعا.
            </td>
        </tr>

        <tr>
            <td class="en" style="padding:10px 10px 10px 35px;">
                (e) The Purchaser shall procure that any transferee or assignee of the Unit executes a declaration
                of adherence to the Master Community Declaration, in a form acceptable to the Master Developer, and
                delivers a signed copy thereof to the Seller promptly upon execution or completion of the transfer. This
                obligation shall survive completion and remain binding on the Purchaser until such signed declaration is
                duly provided to the Seller.
            </td>
            <td class="separator"></td>
            <td class="ar" style="padding:10px 35px 10px 10px;">
                (ج) يتعهد المشتري بأن يقوم بضمان توقيع المتنازل له أو المتنازل إليه عن الوحدة، على نسخة من "إقرار
                الالتزام بإعلان المجتمع الرئيسي"، وذلك بصيغة مقبولة من قبل المطور الرئيسي، على أن يتم تسليم نسخة موقعة
                من هذا الإقرار إلى البائع فور استكمال إجراءات التنازل أو نقل الملكية. يبقى هذا الالتزام قائما وملزما
                للمشتري حتى يتم تسليم الإقرار الموقع للبائع حسب الأصول.
            </td>
        </tr>

        <tr>
            <td class="en">12.3. Unless otherwise provided in
                this
                Agreement, once title to the Property has passed to the Purchaser, the Purchaser may exercise all the
                rights of a property owner, including the right to mortgage his Property or (upon issuance of any
                required clearance certificates following execution of declarations of adherence by the prospective
                purchaser in terms of the Strata Scheme Documentation) to sell, transfer or grant his Property to third
                parties. Until the clearance certificates have been issued, the Purchaser shall continue to be jointly
                and severally liable with his successor for the due performance of obligations pursuant to the Strata
                Scheme Documentation.
                <br/>
                “For the avoidance of doubt, any resale under clause 13.3 of this Agreement shall be a personal contract
                between the Purchaser and the party to whom the Property is resold. The Master Developer shall not be a
                party to such resale and shall have no liability nor give any warranty under that sale agreement.”
            </td>
            <td class="separator"></td>
            <td class="ar">12-3 ما لم
                ينص على
                خلاف ذلك في هذه الاتفاقية، بمجرد أن تنتقل ملكية العقار إلى المشتري، يجوز للمشتري أن يمارس كافة حقوق مالك
                العقار بما في ذلك الحق في أن يرهن عقاره أو (فور إصدار أية شهادات مخالصة مطلوبة بعد تحرير المشتري المحتمل
                إقرار الالتزام بالنسبة لمستندات قواعد الملكية المشتركة) أن يبيع أو ينقل ملكية هذا العقار أو يهبه إلى
                أطراف خارجية. وحتى تصدر شهادات المخالصة، يتعين على المشتري أن يظل مسؤولا مسؤولية تضامنية وتكافلية مع
                ورثته عن تنفيذ الالتزامات على نحو قانوني طبقا لمستندات قواعد الملكية المشتركة.
                <br/>
                "لتجنب الشك، يجب أن يكون أي إعادة بيع بموجب المادة 13.3 من هذه الاتفاقية عقدا شخصيا بين المشتري والطرف
                الذي أعيد بيع العقار إليه. لن يكون المطور الرئيسي طرفا في عملية إعادة البيع هذه ولن يتحمل أي مسؤولية ولا
                يقدم أي ضمان بموجب اتفاقية البيع ".
            </td>
        </tr>

        <tr>
            <td class="en">12.4. No concession or other
                indulgence
                granted by the Seller to the Purchaser whether in respect of time for payment or otherwise in regard to
                the terms and conditions of this Agreement or Strata Scheme Documentation shall be deemed to be a waiver
                of its rights in terms of this Agreement or Strata Scheme Documentation.
            </td>
            <td class="separator"></td>
            <td class="ar">12-4 لن يعتبر
                أي
                امتياز أو تساهل آخر يمنحه البائع إلى المشتري، سواء بخصوص موعد الدفع أو خلاف ذلك بخصوص شروط وأحكام هذه
                الاتفاقية أو مستندات قواعد الملكية المشتركة، لن يعتبر تنازلا عن حقوقه المنصوص عليها بموجب هذه الاتفاقية
                أو مستندات قواعد الملكية المشتركة.
            </td>
        </tr>

        <tr>
            <td class="en">12.5. If there is more than one (1)
                Purchaser in terms of this Agreement, the liability of each shall be joint and several.
            </td>
            <td class="separator"></td>
            <td class="ar">12-5 في حال
                وجود
                أكثر من مشتر واحد (1) حسب هذه الاتفاقية، يتعين أن تكون مسؤولية كل منهم على أساس التضامن والتكافل.
            </td>
        </tr>

        <tr>
            <td class="en">12.6. Each of the Parties shall
                immediately upon being requested to do so, sign/execute all such documents in connection with the
                transfer of title and generally as are necessary to give effect to this Agreement.
            </td>
            <td class="separator"></td>
            <td class="ar">12-6 ويتعين
                على كل
                طرف من الطرفين، فور الطلب، أن يوقع/يحرر كافة تلك المستندات المتعلقة بنقل الملكية والضرورية عموما لوضع
                هذه الاتفاقية موضوع التنفيذ.
            </td>
        </tr>

        <tr>
            <td class="en">12.7. This Agreement constitutes
                the
                entire agreement between the Parties relating to the subject matter of this Agreement and supersedes all
                previous verbal or written agreements and negotiations between the Parties, including any Booking Form.
                The Purchaser acknowledges that any advertising or promotional material is indicative only and warrants
                that they have not relied upon any such promotional or advertising material and have relied solely upon
                the terms of this Agreement.
            </td>
            <td class="separator"></td>
            <td class="ar">12-7 تشكل هذه
                الاتفاقية الاتفاق الكامل بين الطرفين بخصوص موضوع هذه الاتفاقية، وتلغي كافة الاتفاقيات والمفاوضات الشفهية
                أو الخطية السابقة بين الطرفين، بما في ذلك أي نموذج حجز. يقر المشتري أن أية مواد دعائية أو ترويجية تكون
                على سبيل الإيضاح فقط، ويؤكد أنه لم يعتمد على أية مواد دعائية أو ترويجية من هذا القبيل وقد اعتمد فقط على
                شروط هذه الاتفاقية.
            </td>
        </tr>

        <tr>
            <td class="en">12.8. The Parties further agree
                that if
                any provision of this Agreement or the Strata Scheme Documentation conflicts with Applicable Law, then
                the relevant provisions of this Agreement or the Strata Scheme Documentation shall be appropriately
                amended, replaced, repealed or varied by Applicable Law provided that the remaining terms and conditions
                in this Agreement or the Strata Scheme Documentation that are in adherence, compliance or do not
                conflict with Applicable Law shall continue to remain in force and shall be effective insofar as they do
                not conflict with any terms and conditions that are amended, replaced, repealed or varied by
                Applicable Law.
            </td>
            <td class="separator"></td>
            <td class="ar">12-8 كما
                يوافق
                الأطراف على أنه في حال إذا تعارض أي حكم بهذه الاتفاقية أو مستندات قواعد الملكية المشتركة مع القانون
                المعمول به، فيتعين أن تعدل الأحكام المعنية من هذه الاتفاقية أو مستندات قواعد الملكية المشتركة أو تستبدل
                أو تلغى على نحو يناسب القانون المعمول به بشرط أن يكون باقي الشروط والأحكام الواردة في هذه الاتفاقية أو
                مستندات قواعد الملكية المشتركة مطابقة وغير متعارضة مع القانون المعمول به وأن يستمر سريانها ونفاذها لما
                لا يتعارض مع أية شروط وأحكام يتم تعديلها أو استبدالها أو إلغاؤها أو تغييرها طبقا للقانون المعمول به.
            </td>
        </tr>

        <tr>
            <td class="en">12.9. The Seller may assign this
                Agreement at any time to any subsidiary or affiliate company or to any other third party without the
                need for the consent of the Purchaser and without the need to notify the Purchaser that any such
                assignment or transfer has taken place.
            </td>
            <td class="separator"></td>
            <td class="ar">12-9 يجوز
                للبائع
                أن يتنازل عن هذه الاتفاقية في أي وقت إلى أي شركة تابعة أو فرعية أو إلى أي طرف خارجي دون حاجة إلى موافقة
                من المشتري ودون حاجة إلى إخطار المشتري بحدوث ذلك التنازل أو نقل الملكية.
            </td>
        </tr>

        <tr>
            <td class="en">12.10. This Agreement has been
                negotiated
                and drafted in Arabic language. If there is any inconsistency between the English and the Arabic
                translation, the Arabic text and interpretation shall prevail.
            </td>
            <td class="separator"></td>
            <td class="ar">12-10 تم
                التفاوض
                على هذه الاتفاقية وصياغتها باللغة العربية. إذا كان هناك أي تناقض بين النص الإنجليزي والنص العربي، فإن
                النص العربي وتفسيره يسود.
            </td>
        </tr>

        <tr>
            <th class="en section-heading" style="width:49%; text-align:justify; padding:10px;">13. Notices</th>
            <td class="separator"></td>
            <th class="ar section-heading">13- الإخطارات
            </th>
        </tr>

        <tr>
            <td class="en">Any notice given under this
                Agreement shall be in writing and shall be served by delivering it personally or sending it by courier
                or email to the address. or email address as set out in this Agreement. Any such notice shall be deemed
                to have been received:
            </td>
            <td class="separator"></td>
            <td class="ar"> يتعين أن
                تحرر خطيا أية إخطارات تقدم بموجب هذه الاتفاقية وأن ترسل عن طريق تسليمها تسليما شخصيا أو إرسالها بالبريد
                السريع أو البريد الإلكتروني إلى العنوان أو عنوان البريد الإلكتروني المنصوص عليه في هذه الاتفاقية. ويعتبر
                أي إخطار من هذا القبيل قد تم استلامه:
            </td>
        </tr>

        <tr>
            <td class="en">(a) if delivered personally, at the
                time of delivery;
            </td>
            <td class="separator"></td>
            <td class="ar">أ) إذا سلم
                تسليما شخصيا، في موعد التسليم؛
            </td>
        </tr>

        <tr>
            <td class="en">(b) in the case of courier, on the
                date
                of delivery as evidenced by the records of the courier;
            </td>
            <td class="separator"></td>
            <td class="ar"> ب) في حال
                الإرسال بالبريد السريع، في تاريخ التسليم حسبما تثبت سجلات البريد السريع؛
            </td>
        </tr>

        <tr>
            <td class="en">(c) in case of an email or on email
                receipt.
            </td>
            <td class="separator"></td>
            <td class="ar"> ت) في حالة
                الإرسال عبر البريد الإلكتروني، عند استلام رسالة البريد الإلكتروني.
            </td>
        </tr>

        <tr>
            <th class="en section-heading" style="width:49%; text-align:justify; padding:10px;">14. Governing Law
                and Jurisdiction
            </th>
            <td class="separator"></td>
            <th class="ar section-heading">14- القانون النافذ والاختصاص القضائي
            </th>
        </tr>

        <tr>
            <td class="en">This Agreement and the rights of
                the Parties hereunder shall be governed by the Laws of Dubai and the Federal Laws of the UAE and the
                Parties agree that any legal action or proceeding with respect to this Agreement shall be subject to the
                exclusive jurisdiction of the Courts of Dubai.
            </td>
            <td class="separator"></td>
            <td class="ar">تخضع هذه
                الاتفاقية وحقوق الطرفين فيها إلى قوانين دبي والقوانين الاتحادية لدولة الإمارات العربية المتحدة، ويوافق
                الطرفان على اختصاص محاكم دبي الحصري بالفصل في أي دعوى أو إجراء قانوني يتعلق بهذه الاتفاقية.
            </td>
        </tr>

        <tr>
            <th class="en section-heading" style="width:49%; text-align:justify; padding:10px;">15. Effective
                Date
            </th>
            <td class="separator"></td>
            <th class="ar section-heading">15- تاريخ بدء السريان
            </th>
        </tr>

        <tr>
            <td class="en">This Agreement shall be effective
                and binding upon the Parties from the Effective Date. Unless terminated earlier pursuant to the
                provisions of Clause 10, this Agreement shall survive the Completion Date insofar as any rights and
                obligations contained herein are of continuing effect.
            </td>
            <td class="separator"></td>
            <td class="ar">تسري هذه
                الاتفاقية وتكون ملزمة على الطرفين اعتبارا من تاريخ بدء السريان. ما لم تفسخ هذه الاتفاقية قبل موعدها طبقا
                لأحكام البند (10)، يستمر سريان هذه الاتفاقية بعد تاريخ الإنجاز طالما استمر سريان أية حقوق والتزامات
                منصوص عليها فيها.
            </td>
        </tr>

        <tr>
            <td class="en"><strong>IN WITNESS WHEREOF</strong>
                the Parties have
                executed this Agreement on the dates set forth below, to be effective as of the Effective Date.
            </td>
            <td class="separator"></td>
            <td class="ar"><strong>واستنادا
                    لما
                    تقدم</strong>، فقد حرر الطرفان هذه الاتفاقية في التاريخ المنصوص عليه أدناه، وتسري هذه الاتفاقية
                اعتبارا من تاريخ
                بدء السريان.
            </td>
        </tr>

        <tr>
            <td class="en">The Purchaser hereby confirms to
                have read and understood the terms of this Agreement, the Particulars and Schedules to this Agreement
                and agrees and undertakes to be bound by them:
            </td>
            <td class="separator"></td>
            <td class="ar">يؤكد المشتري
                بموجبه أنه قد قرأ وفهم شروط هذه الاتفاقية وبيانات الاتفاقية والملاحق المرفقة بهذه الاتفاقية ويوافق
                ويتعهد بأن يلتزم بها.
            </td>
        </tr>

        <tr>
            <th class="left-th" style="width: 49%; text-align: justify; padding: 10px;">
                &nbsp;
            </th>
            <td class="separator"></td>
            <th class="rtl-text right-th spaced-text" style="width: 49%; padding: 10px; text-align: justify;">
                &nbsp;
            </th>
        </tr>
    </table>

    <table class="contract-table">
        <tr>
            <td class="left-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                <strong>Name: Unique Saray Properties L.L.C </strong> by its authorised representative
                <h4>Signed: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/>
                </h4>
                <h4>Date: &nbsp;&nbsp;&nbsp;{{ \Carbon\Carbon::now()->format('d-M-Y') }}&nbsp;&nbsp;</h4>
                <br/>
                <h4>Name: <img src="{{ public_path('images/black_line.svg') }}" width="260" height="2" alt="___"/></h4>
                <h4>Witness: <img src="{{ public_path('images/black_line.svg') }}" width="245" height="2" alt="___"/>
                </h4>
            </td>
            <td class="centred-text"></td>
            <td class="rtl-text right-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                <strong>الاسم: يونيك سراي للعقارت ش.ذ.م.م</strong> من قبل الممثل المفوض
                <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/>
                </h4>
                <h4 style="unicode-bidi: embed;">التاريخ:&nbsp;&nbsp;
                    &nbsp;{{ \Carbon\Carbon::now()->locale('ar')->isoFormat('D-MMM-YYYY') }}&nbsp;</h4>
                <br/>
                <h4>الاسم: <img src="{{ public_path('images/black_line.svg') }}" width="255" height="2" alt="___"/></h4>
                <h4>شاهد: <img src="{{ public_path('images/black_line.svg') }}" width="255" height="2" alt="___"/></h4>
            </td>
        </tr>
        @php
            $signers = collect($customerInfos ?? [])->values();
        @endphp
        @foreach($signers as $customer)
            @php
                $nameEn = data_get($customer, 'name_en');
                $nameAr = data_get($customer, 'name_ar');
            @endphp
            <tr>
                <td class="left-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                    <h4>Name: {{ $nameEn }}  </h4>
                    <h4>Signed: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/>
                    </h4>
                    <h4>Date: &nbsp;&nbsp;<img src="{{ public_path('images/black_line.svg') }}" width="260" height="2"
                                               alt="___"/></h4>
                    <br/>
                    <h4>Name: <img src="{{ public_path('images/black_line.svg') }}" width="260" height="2" alt="___"/>
                    </h4>
                    <h4>Witness: <img src="{{ public_path('images/black_line.svg') }}" width="245" height="2"
                                      alt="___"/></h4>
                </td>
                <td class="separator" style="width: 2%;"></td>
                <td class="rtl-text right-th" style="line-height: 2.5; width: 49%; padding: 7px; text-align: justify;">
                    <h4>الاسم: {{ $nameAr }}  </h4>
                    <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2"
                                      alt="___"/>
                    </h4>
                    <h4>التاريخ: &nbsp;&nbsp;<img src="{{ public_path('images/black_line.svg') }}" width="250"
                                                  height="2"
                                                  alt="___"/></h4>
                    <br/>
                    <h4>الاسم: <img src="{{ public_path('images/black_line.svg') }}" width="255" height="2" alt="___"/>
                    </h4>
                    <h4>شاهد: <img src="{{ public_path('images/black_line.svg') }}" width="255" height="2" alt="___"/>
                    </h4>
                </td>
            </tr>
        @endforeach
    </table>

    <div class="page-break"></div>

    <h3 class="centred-text">Schedule A - Payment Schedule</h3>
    <h3 class="rtl-text centred-text">الملحق (أ) - جدول سداد الدفعات</h3>

    <table class="contract-table">
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
                Amount <img src="{{ public_path('images/aed_symbol.svg') }}" width="12" alt="AED"/>
            </th>
        </tr>
        </thead>
        @foreach($installments as $installment)
            <tr>
                <td class="left-th" style="width: 40%; text-align: justify; padding: 10px;">
                    {{ $installment->description }}
                    @if($loop->first)
                        <br/><small>({{ (int) $installment->percentage }}%
                            + {{ (int) $paymentPlan->dld_fee_percentage }}% DLD fee + Admin fee + EOI)</small>
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

    <div class="page-break"></div>

    <br/>

    <h3 class="centred-text">Schedule B - UNIT PLAN</h3>
    <h3 class="rtl-text centred-text">الملحق (ب) - خطة الوحدة</h3>


    <br/>
    <br/>
    <br/>
    <br/>
    <br/>


    @if($unit->floor_plan)
        <div style="text-align:center; margin-bottom:20px;">
            <img
                src="file:///{{ str_replace('\\','/', storage_path('app/private/' . $unit->floor_plan)) }}"
                alt="Floor Plan"
                style="width:90%; height:auto;"
            >
        </div>
    @endif

    <div class="page-break"></div>

    <h4 class="centred-text">Finishing & Furnishing Specification</h4>
    <h4 class="rtl-text centred-text">مواصفات التشطيب والتأثيث</h4>

    <table class="contract-table">
        <tr>
            <td class="en">
                <ol class="spec-list">

                    <li class="spec-item"><b>Flooring</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Living Room, Dining, Corridors: High-quality porcelain
                                    tiles, scratch-resistant and moisture-resistant.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Bedrooms: Porcelain tiles or engineered parquet as per design.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Bathrooms: Premium anti-slip porcelain tiles.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Kitchen: Heat and humidity-resistant porcelain tiles.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>Wall Finishes</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Living Room, Bedrooms, Corridors: Premium emulsion paint.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Kitchen: Reconstituted stone panels or tiles.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Bathrooms: Full-height porcelain tiling.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Service Room: Paint or tiles as per layout.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>Ceilings</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Gypsum false ceiling with premium paint, spotlights, and cove
                                    lighting.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>Countertops</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Kitchen: High-quality quartz stone.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Bathrooms: Durable engineered stone.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>Joinery</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Bedroom Wardrobes: Moisture-resistant melamine with soft-close
                                    hinges.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Kitchen Cabinets: High-quality cabinetry with superior metal
                                    accessories.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                </ol>
            </td>
            <td class="separator"></td>
            <td class="ar">
                <ol class="spec-list">

                    <li><b>تشطيبات الأرضيات</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">غرف المعيشة، السفرة، والممرات: بلاط بورسلان عالي الجودة،
                                    مقاوم للخدش والرطوبة.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">غرف النوم: بلاط بورسلان أو باركيه هندسي حسب التصميم
                                    المعتمد.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">الحمامات: بلاط بورسلان مضاد للانزلاق بجودة فاخرة.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">المطبخ: بلاط بورسلان مقاوم للحرارة والرطوبة.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>تشطيبات الجدران</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">الريسبشن، غرف النوم، الممرات: دهان فاخر متعدد الطبقات.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">المطبخ: ألواح حجر صناعي أو بلاط حسب التصميم المعتمد.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">الحمامات: بلاط بورسلان كامل الارتفاع.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">غرف الخدمات: دهان أو بلاط حسب المخطط.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>الأسقف</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">سقف جبسي مستعار بطلاء فاخر مع سبوت لايت وإضاءة مخفية.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>أسطح الكاونتر</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">المطبخ: سطح حجر صناعي كوارتز.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">الحمامات: أسطح حجر صناعي متينة.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>الخزائن</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">خزائن غرف النوم: ميلامين مقاوم للرطوبة مع مفصلات إغلاق
                                    ناعم.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">خزائن المطبخ: وحدات عالية الجودة بإكسسوارات ممتازة.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>
                </ol>
            </td>
        </tr>
    </table>

    <table class="contract-table">
        <tr>
            <td class="en">
                <ol start="6" class="spec-list">
                    <li><b>Appliances</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">(Applicable for furnished units)</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Oven – Hood.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Refrigerator – Washing Machine – Microwave.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">All appliances comply with European standards or equivalent.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>Sanitary & Fittings</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">High-quality mixers.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Premium ceramic sanitary fixtures.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Durable bathroom accessories.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>Doors & Ironmongery</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">High-quality wooden or composite internal doors.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Premium hinges and handles.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>Fully Furnished Package</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">(When applicable)</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Living Room: Sofa – Coffee Table – TV Unit – Curtains –
                                    Accessories.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Master Bedroom: Bed – Mattress – Side Tables – Dressing Table –
                                    Curtains – Wall Art.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Other Bedrooms: Full setup.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Dining: Table + Chairs.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Balcony: Outdoor seating.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text">Decorative Lighting: Pendants + LED Lines.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>
                </ol>
                <h5>Important Note:</h5>
                <p style="text-align: justify;">
                    The developer may replace any material with an equivalent or higher-quality product without
                    affecting
                    the overall specifications.
                </p>
            </td>
            <td class="separator"></td>
            <td class="ar">
                <ol start="6" class="spec-list">
                    <li><b>الأجهزة الكهربائية</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">(في حال كانت الوحدة مفروشة بالكامل)</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">فرن – غطاء شفاط.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">ثلاجة – غسالة ملابس – مايكروويف.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">جميع الأجهزة بمعايير أوروبية أو ما يعادلها.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>تجهيزات السباكة</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">خلاطات عالية الجودة.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">مراحيض وأحواض سيراميكية ممتازة.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">إكسسوارات حمام متينة.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>الأبواب والحديديات</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">أبواب خشبية أو مركبة بجودة عالية.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">مقابض ومفصلات عالية الجودة.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>

                    <li><b>الفرش الكامل</b>
                        <table class="bullet-table">
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">(عند توفره بالمشروع)</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">غرفة المعيشة: كنبة – طاولة وسط – طاولة تلفزيون – ستائر –
                                    إكسسوارات.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">غرفة النوم الرئيسية: سرير – فرشة فندقية – كومودينات – طاولة
                                    تزيين – ستائر – لوحات.
                                </td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">غرف النوم الأخرى: تجهيز كامل.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">السفرة: طاولة + كراسي.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">الشرفة: جلسة خارجية.</td>
                            </tr>
                            <tr>
                                <td class="bullet-cell">•</td>
                                <td class="bullet-text-rtl">الإضاءة الديكورية: Pendants + LED Lines.</td>
                            </tr>
                            <tr>
                                <td class="bullet-text">&nbsp;</td>
                            </tr>
                        </table>
                    </li>
                </ol>
                <h5>ملاحظة مهمة:</h5>
                <p class="right-th rtl-text">
                    يحق للمطور استبدال أي مادة بمنتج مكافئ أو أعلى جودة دون الإخلال بالمواصفات.
                </p>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    <h3 class="centred-text">Schedule C</h3>
    <h3 class="rtl-text centred-text">الملحق هـ</h3>

    <h4 class="centred-text">
        DECLARATION OF ADHERENCE TO THE ASSOCIATION CONSTITUTION AND JOINTLY OWNED PROPERTY DECLARATION (RULES OF THE
        ASSOCIATION)
    </h4>
    <h4 class="rtl-text centred-text">
        إعلان الالتزام بالنظام الأساسي للجمعية وإعلان الملكية المشتركة (قواعد الجمعية)
    </h4>

    <br/>

    <table class="contract-table">
        <tr>
            <td class="en">
                WHEREAS: <br/>

                A. The Purchaser proposes to take title to Unit No. {{ $unit->unit_no }} located in the project Saray
                Prime Residence, Dubai, United Arab Emirates.
                <br/>
                B. The Purchaser has read and understood the Association Constitution and the Jointly Owned Property
                Declaration (“Rules of the Association”) and agrees to be bound by their terms.
            </td>
            <td class="separator"></td>
            <td class="ar">
                حيث أن: <br/>

                أ. المشتري يرغب بتملك الوحدة رقم: {{ $unit->unit_no }} ضمن مشروع Saray Prime Residence الكائن في إمارة
                دبي، الإمارات العربية المتحدة.
                <br/>
                ب. وقد قام المشتري بقراءة وفهم دستور الجمعية و نظام الملكية المشتركة ("قواعد الجمعية")، ويقر بالموافقة
                على الالتزام بجميع ما ورد فيها.
            </td>
        </tr>
        <tr>
            <td class="en">
                NOW, THE PURCHASER AGREES AS FOLLOWS: <br/>
                1. Expressions defined in the Constitution and the Jointly Owned Property Declaration (“Rules of the
                Association”) shall have the same meaning when used in this Declaration of Adherence unless the context
                otherwise requires. <br/>
                2. The Purchaser undertakes and covenants to the Association and to the other Owners to comply with all
                the provisions applicable to the Unit and all obligations in the Constitution and Jointly Owned Property
                Declaration (“Rules of the Association”), and to observe and perform such obligations from the date
                hereof and thereafter.
            </td>
            <td class="separator"></td>
            <td class="ar">
                وعليه، يقر المشتري بما يلي: <br/>
                1. يكون للتعابير الواردة في دستور الجمعية ونظام الملكية المشتركة (“قواعد الجمعية”) نفس المعاني أينما
                وردت في هذا الإقرار، ما لم يقتض السياق خلاف ذلك. <br/>
                2. يلتزم المشتري بالتقيد بجميع الأحكام الواردة في دستور الجمعية ونظام الملكية المشتركة (“قواعد
                الجمعية”)، وبكافة الالتزامات المفروضة على مالكي الوحدات، ويتعهد بأدائها والامتثال لها اعتبارا من تاريخ
                التوقيع وما بعده
            </td>
        </tr>
    </table>
    <br>
    <table class="contract-table">
        <tr>
            <td class="en" style="line-height: 2.5;">
                <strong>Name: Unique Saray Properties L.L.C </strong> by its authorised representative
                <h4>Signed: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/>
                </h4>
                <h4>Date: &nbsp;&nbsp;&nbsp;{{ \Carbon\Carbon::now()->format('d-M-Y') }}&nbsp;&nbsp;</h4>
                <br/>
                <h4>Name: <img src="{{ public_path('images/black_line.svg') }}" width="260" height="2" alt="___"/></h4>
                <h4>Witness: <img src="{{ public_path('images/black_line.svg') }}" width="245" height="2" alt="___"/>
                </h4>
            </td>
            <td class="centred-text"></td>
            <td class="ar" style="line-height: 2.5;">
                <strong>الاسم: يونيك سراي للعقارت ش.ذ.م.م</strong> من قبل الممثل المفوض
                <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/>
                </h4>
                <h4 style="unicode-bidi: embed;">التاريخ:&nbsp;&nbsp;
                    &nbsp;{{ \Carbon\Carbon::now()->locale('ar')->isoFormat('D-MMM-YYYY') }}&nbsp;</h4>
                <br/>
                <h4>الاسم: <img src="{{ public_path('images/black_line.svg') }}" width="255" height="2" alt="___"/></h4>
                <h4>شاهد: <img src="{{ public_path('images/black_line.svg') }}" width="255" height="2" alt="___"/></h4>
            </td>
        </tr>
        @php
            $signers = collect($customerInfos ?? [])->values();
        @endphp
        @foreach($signers as $customer)
            @php
                $nameEn = data_get($customer, 'name_en');
                $nameAr = data_get($customer, 'name_ar');
            @endphp
            <tr>
                <td class="en" style="line-height: 2.5;">
                    <h4>Name: {{ $nameEn }}  </h4>
                    <h4>Signed: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2" alt="___"/>
                    </h4>
                    <h4>Date: &nbsp;&nbsp;<img src="{{ public_path('images/black_line.svg') }}" width="260" height="2"
                                               alt="___"/></h4>
                    <br/>
                    <h4>Name: <img src="{{ public_path('images/black_line.svg') }}" width="260" height="2" alt="___"/>
                    </h4>
                    <h4>Witness: <img src="{{ public_path('images/black_line.svg') }}" width="245" height="2"
                                      alt="___"/></h4>
                </td>
                <td class="separator" style="width: 2%;"></td>
                <td class="ar" style="line-height: 2.5;">
                    <h4>الاسم: {{ $nameAr }}  </h4>
                    <h4>التوقيع: <img src="{{ public_path('images/black_line.svg') }}" width="250" height="2"
                                      alt="___"/>
                    </h4>
                    <h4>التاريخ: &nbsp;&nbsp;<img src="{{ public_path('images/black_line.svg') }}" width="250"
                                                  height="2"
                                                  alt="___"/></h4>
                    <br/>
                    <h4>الاسم: <img src="{{ public_path('images/black_line.svg') }}" width="255" height="2" alt="___"/>
                    </h4>
                    <h4>شاهد: <img src="{{ public_path('images/black_line.svg') }}" width="255" height="2" alt="___"/>
                    </h4>
                </td>
            </tr>
        @endforeach
    </table>
</main>
</body>
</html>
