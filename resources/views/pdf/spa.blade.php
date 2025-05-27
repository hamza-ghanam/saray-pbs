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

        h1, h2, h3 {
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
        }

        .value {
            margin-left: 5px;
        }

        table.payments {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.payments th, table.payments td {
            text-align: left;
            padding: 6px;
            border: 1px solid #ccc;
        }

        table.customers {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table.customers td {
            vertical-align: top;
            padding-left: 15px;
            width: 50%;
            border: none;
        }

        table.customers td:first-child {
            border-right: 1px solid #999;
            padding-right: 15px;
        }

        table.signature td {
            vertical-align: top;
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



        .label {
            font-weight: bold;
        }

        .label-line {
            display: flex;
            gap: 1em;
            margin-bottom: 8px;
        }
        .label-line span.label {
            min-width: 80px;
            display: inline-block;
            font-weight: bold;
        }

        .section {
            text-align: justify;
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

    <div class="section">
        <h3><span class="value">{{ $unit->building->name }}</span></h3>
        <p><span class="value">{{ $unit->building->location }}</span></p>
    </div>

    <div class="section">
        <h2>Particulars</h2>
        <p><span class="label">1. Effective Date:</span><span class="value">{{ $booking->latest_approved_at->toDateString() }}</span></p>
        <p><span class="label">2. Seller:</span><span class="value">
            Unique Saray Properties L.L.C, of office 301 & 308, building 2,
                Bay Square, Business bay, Dubai, UAE, Dubai, UAE, its nominees,
                successors in title and assigns.
            </span>
        </p>
    </div>

        <!-- Customer Info Section -->
        <div class="section">
            <h2>3. Purchaser(s)</h2>
            @if($customerInfos)
                <table class="customers">
                    @foreach ($customerInfos->chunk(2) as $pair)
                        <tr>
                            @foreach ($pair as $customerInfo)
                                <td>
                                    <h3 style="background-color: #ECECEC;"><span class="label"
                                                                                 style="padding-top: 0; margin-top: 0;">{{ $customerInfo->name }}
                                    </h3>
                                    <p><span class="label">Nationality:</span> {{ $customerInfo->nationality }}</p>
                                    <p><span class="label">Passport #:</span> {{ $customerInfo->passport_number }}</p>
                                    <p><span class="label">Address:</span> {{ $customerInfo->address }}</p>
                                    <p><span class="label">Phone Number:</span> {{ $customerInfo->phone_number }}</p>
                                    <p><span class="label">Email:</span> {{ $customerInfo->email }}</p>
                                    <p><span class="label">Birth Date:</span> {{ $customerInfo->birth_date }}</p>
                                </td>
                            @endforeach

                            {{-- If odd number of customers, add empty cell to fill row --}}
                            @if ($pair->count() < 2)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <p>No customer data available.</p>
            @endif
        </div>

        <!-- Unit Section -->
        <div class="section">
            <h2>4. Property Details</h2>
            @if($unit)
                <p><span class="label">Relevant Unit #:</span><span class="value">{{ $unit->unit_no }}</span></p>
                <p><span class="label">Unit Type:</span><span class="value">{{ $unit->unit_type }}</span></p>
                <p><span class="label">No. of Parking Bay(s):</span><span class="value">{{ $unit->parking }}</span>
                    <small>
                        Parking Bay(s) (to be allocated in accordance with Clause 4.6).
                    </small></p>
                <p><span class="label">Project: </span><span class="value">{{ $unit->building->name }}</span></p>
            @else
                <p>No unit data available.</p>
            @endif
        </div>

        <div class="section">
            <h2>5. Purchase Price</h2>
            <p>
                <span class="value">
                    AED {{ number_format($unit->price, 2) }} (UAE Dirhams "{{ number_to_words($unit->price) }}" only)
                </span>
                </p>
            </div>

            <div class="section">
                <h2>6.  Payment Schedule</h2>
                Set out in Schedule A
            </div>

            <div class="section">
                <h2>7. Anticipated Completion Date</h2>
                {{ $unit->building->ecd }}
                <small>(subject to extension or earlier completion in accordance with Clause 4.1)</small>
            </div>

            <div class="section">
                <h2>8. Permitted Use</h2>
                Residential Apartment Use.
            </div>

            <div class="section">
                <h2>9. Late Payment Penalty</h2>
                An amount calculated on a daily basis being, the sum of:
                <ol type="a">
                    <li>AED 500 (Five Hundred UAE Dirhams) per day; plus</li>
                    <li>One percent (1%) per month (or part thereof) of the overdue amount,
                        calculated on a compounding basis.
                    </li>
                </ol>
                <span>The Seller agrees to sell the Relevant Unit to the Purchaser and the Purchaser
                    agrees to purchase the Relevant Unit from the Seller for the Purchase Price set out above.
                </span>
            </div>
        <br/>
            <div class="section">
                <span style="text-align: justify;">
                    This Agreement shall comprise and be subject to and the Particulars,
                    and the Schedules which form an integral part of this Agreement.
                    The Purchaser hereby confirms that he/she has read and understood
                    this Agreement and agrees and undertakes to be bound by its terms:
                </span>
            </div>
    <br/>
            <table class="signature" style="border: none; width: 100%; border-collapse: collapse;">
                <tr style="border: none;">
                    <td>
                        <div class="section">
                            <p>Signed and delivered for and on behalf of the Seller</p>
                            <div class="label-line" style="margin-top: 15px;"><span class="label">Name:</span> __________________________</div>
                            <div class="label-line"><span class="label">Signature:</span> __________________________</div>
                            <div class="label-line"><span class="label">Date:</span> __________________________</div>
                        </div>
                    </td>
                    <td style="padding-left: 30px;">
                        <div class="section">
                            <p>Signed and delivered by named Purchaser/Purchaser’s Authorised Signatory</p>
                            <div class="label-line" style="margin-top: 15px;"><span class="label">Name:</span> __________________________</div>
                            <div class="label-line"><span class="label">Position:</span> __________________________</div>
                            <div class="label-line"><span class="label">Signature:</span> __________________________</div>
                            <div class="label-line"><span class="label">Date:</span> __________________________</div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Signatures Section -->

            <div style="page-break-after: always;"></div>
            <div class="section">
                <p>
                    <strong>THIS AGREEMENT </strong> is made on the Effective Date.
                </p>
                <p>
                    <strong>BETWEEN: </strong> <br/>
                </p>
                <ol>
                    <li><strong>The Seller</strong> named in Item 2 of the Particulars; and</li>
                    <li><strong>The Purchaser</strong> named in Item 3 of the Particulars.</li>
                </ol>
                <p>
                    <strong>IT IS AGREED </strong> as follows.
                </p>

                <h3>1. INTERPRETATION</h3>
                <p>
                <strong>1.1 In this Agreement</strong>, except where the context otherwise requires,
                    the following words shall have the following meaning
                </p>
                <p>
                    <strong>Administration Fee</strong> means the administration fee charged by the Seller pursuant to
                    Clause 14.2(b) provided that such fee shall not exceed the maximum prescribed by Applicable Law.
                </p>
                <p>
                    <strong>AED</strong> means the lawful currency of the United Arab Emirates, being as of the Effective
                    Date, Dirhams.
                </p>

                <p>
                    <strong>Agreement</strong> means this Agreement including the Particulars and the Schedules.
                </p>

                <p>
                    <strong>Anticipated Completion Date</strong> means the quarter (comprising four (4) consecutive three
                    (3) month periods of calendar year with the first of such periods commencing 1 January) stated in Item 7
                    of the Particulars and as may be extended pursuant to Clause 4.1.
                </p>

                <p>
                    <strong>Applicable Law</strong> means the laws, decrees, resolutions, regulations and/or any other
                    applicable legislation, enacted or to be enacted either in the Emirate of Dubai or by the Federal
                    Government of the UAE including but not restricted to the Jointly Owned Property Law before or after the
                    Effective Date.
                </p>

                <p>
                    <strong>Association Constitution</strong> means any constitution of an Owners Association as set out in
                    the Directions.
                </p>

                <p>
                    <strong>Booking Form</strong> means any booking form that the Purchaser signs to reserve the Property in
                    their name prior to the execution of this Agreement.
                </p>

                <p>
                    <strong>Common Areas</strong> has the same meaning ascribed to the term within the Jointly Owned
                    Property Law.
                </p>

                <p>
                    <strong>Communal Facilities</strong> means all open areas, services, facilities, roads, pavements,
                    gardens, utility and administrative buildings or areas, installations, improvements and any other common
                    assets of the Master Community that are intended for use by all Plot Owners and Unit Owners that do not
                    form part of the title of any Plot but are residuary lands and buildings owned and managed by the Master
                    Developer.
                </p>

                <p>
                    <strong>Community Charges</strong> means the amount payable by a Plot Owner (including any Owners
                    Association where a Jointly Owned Plot) and Unit Owners as a contribution towards the common expenses of
                    the Master Community, including but not limited to, expenses relating to management, administration,
                    repairs, replacements, maintenance, cleaning, security, facilities management, sinking fund, water,
                    electricity, gas, sewage, chilled water services and other utility connection and consumption charges,
                    and as are assessed and payable pursuant to the terms of the Master Community Declaration and Applicable
                    Law.
                </p>

                <p>
                    <strong>Completion Date</strong> means the date specified in the notice of the Seller to the Purchaser
                    pursuant to Clause 4.2 provided the completion date shall not be earlier than the date that the Project
                    is completed as certified by the Project Manager whose decision shall be final and binding upon the
                    Parties.
                </p>

                <p>
                    <strong>Defects</strong> has the meaning set out in Clause 9.4.
                </p>

                <p>
                    <strong>Deregister or Deregistration</strong> means the deregistration of the sale of the Property and
                    removal of the Purchaser’s details from the Interim Real Estate Register.
                </p>

                <p>
                    <strong>Deregistration Fees</strong> means any fees charged by the Land Department from time to time
                    associated with Deregistration.
                </p>
                <p>
                    <strong>Deregistration Fees</strong> means any fees charged by the Land Department from time to time associated with Deregistration.
                </p>


                <p>
                    <strong>Directions</strong> means the Directions promulgated pursuant to the Jointly Owned Property Law including:
                    (a) the Direction for Association Constitution;
                    (b) the Direction for General Regulation;
                    (c) the Direction for Jointly Owned Property Declaration; and
                    (d) the Survey Directions.
                </p>

                <p>
                    <strong>Effective Date</strong> means the date stated in Item 1 of the Particulars or, where no such date is apparent, the date this Agreement is signed by the last of the Parties.
                </p>

                <p>
                    <strong>Entitlement</strong> means the proportion determined in accordance with the Directions in which each Unit Owner shares in the Common Areas which will also be used to determine:
                    (a) the value of the vote of the Unit Owner in any case where a poll is called at any General Assembly; and/or
                    (b) the proportion in which a Unit Owner shall contribute towards the Service Charges.
                </p>

                <p>
                    <strong>Escrow Account</strong> means an escrow account established by the Seller pursuant to an escrow agreement with a financial institution in accordance with Dubai Law No. 8 of 2007 concerning Real Estate Development Trust Accounts in the Emirate of Dubai.
                </p>

                <p>
                    <strong>Fees</strong> means the Land Department’s fees as defined in Clause 5.4.
                </p>

                <p>
                    <strong>Final Instalment</strong> means the final instalment of the Purchase Price, as set out in the Payment Schedule.
                </p>

                <p>
                    <strong>Force Majeure Event(s)</strong> means causes beyond the Seller’s reasonable control including but not limited, fire, windstorm, flood, earthquake or other natural disasters; act of any sovereign including but not limited to terrorism, acts of war, invasion, act of foreign enemies, hostilities (whether war declared or not), civil war, riots, rebellion, revolution, insurrection, military or usurped power or confiscation, nationalisation, requisition, destruction, accidents, or damage to property by or under the order of any government or public or local authority, decisions of the Relevant Authorities, or imposition of government sanction, embargo or similar action; labour disputes, including but not limited to strike, lockout, or boycott; interruption or failure of utility service including but not limited to electric power, gas, water or telephone services; failure of the transportation of any personnel, equipment, machinery or material required by the Seller for completion of the Project; breach of contract by any essential contractor or subcontractor or any other matter or cause beyond the reasonable control of the Seller.
                </p>

                <p>
                    <strong>General Assembly</strong> has the same meaning ascribed to the term within the Association Constitution.
                </p>
                <p>
                    <strong>Instalment Date(s)</strong> means any date or dates set out in the Payment Schedule upon which any Payment Instalment is due including the Completion Date as may be amended pursuant to Clauses 3.4 and 3.5.
                </p>

                <p>
                    <strong>Intellectual Property</strong> has the meaning set out in Clause 12.
                </p>

                <p>
                    <strong>Interim Real Estate Register</strong> has the same meaning ascribed to the term within Law No 13 of 2008 Regulating the Interim Register.
                </p>

                <p>
                    <strong>Jointly Owned Plot</strong> means a Plot, which by virtue of its subdivision into Units and Common Areas, is regulated pursuant to the Jointly Owned Property Law and Directions.
                </p>

                <p>
                    <strong>Jointly Owned Property Declaration</strong> has the meaning ascribed to the term in the Directions, which will include:
                    (a) the community rules governing the management, administration and maintenance of the Common Areas;
                    (b) the relevant Entitlements;
                    (c) the relevant Common Areas plans;
                    the draft form of which, for the Project, is set out in Schedule C, and which is subject to amendment at the sole and absolute discretion of the Seller.
                </p>

                <p>
                    <strong>Jointly Owned Property Law</strong> means Law No. 27 of 2007 / Law No. 6 of 2019 and directions Concerning Ownership of Jointly Owned Property in the Emirate of Dubai.
                </p>

                <p>
                    <strong>Land Department</strong> means the Land Department of the Government of Dubai.
                </p>

                <p>
                    <strong>Late Payment Penalty</strong> means the fee payable by the Purchaser to the Seller for any delay in making any payment pursuant this Agreement and which shall accrue on a daily basis at the rate specified in Item 9 of the Particulars on the amount outstanding from the due date of the payment to the date of actual payment
                </p>

                <p>
                    <strong>Manager</strong> means the association manager appointed by the Seller pursuant to the terms of this Agreement to undertake the Owners Association management functions pursuant to the Management Agreement.
                </p>

                <p>
                    <strong>Management Agreement</strong> means the agreement between the Owners Association or the Seller (on behalf of the Owners Association) and the Manager, on terms acceptable to the Seller in its discretion.
                </p>


                <p>
                    <strong>Master Community</strong> means the master community in which the Project Plot is situated currently known as BUSINESS BAY (or such other name as the community in which the Project Plot is situated may become known)...
                </p>

                <p>
                    <strong>Master Community Declaration</strong> means the master community declaration as declared by the Master Developer for the Master Community from time to time in its sole and absolute discretion.
                </p>

                <p>
                    <strong>Master Developer</strong> means Dubai Land Residences LLC, its subsidiaries or affiliates and any successors and assignees...
                </p>

                <p>
                    <strong>Master Plan</strong> means the master plan of the Master Community as may be amended from time to time by the Master Developer...
                </p>

                <p>
                    <strong>Owners Association</strong> has the meaning ascribed to the term in the Jointly Owned Property Law.
                </p>

                <p>
                    <strong>Parking Bay(s)</strong> means any parking bay(s) to be allocated to the Purchaser on the Completion Date as indicated in Item 4 of the Particulars...
                </p>

                <p>
                    <strong>Particulars</strong> means the particulars of this Agreement.
                </p>

                <p>
                    <strong>Parties</strong> means collectively the Seller and the Purchaser and “Party” means either one of them.
                </p>

                <p>
                    <strong>Payment Instalment(s)</strong> means the individual payment instalment(s) of the Purchase Price as set out in the Payment Schedule.
                </p>

                <p>
                    <strong>Payment Schedule</strong> means the payment schedule set out Schedule A.
                </p>

                <p>
                    <strong>Payment Schedule Notes</strong> means the “Payment Schedule Notes” forming part of the Payment Schedule.
                </p>

                <p>
                    <strong>Permitted Use</strong> means the permitted use set out in Item 8 of the Particulars.
                </p>

                <p>
                    <strong>Plot(s)</strong> means any plot and associated improvements within the Master Community whether a Single Ownership Plot or a Jointly Owned Plot.
                </p>

                <p>
                    <strong>Plot Owner(s)</strong> means the owner of a Plot including: (a) an Owners Association (where the Plot is a Jointly Owned Plot); and (b) any other person or persons (whether real or corporate) where the Plot is a Single Ownership Plot.
                </p>

                <p>
                    <strong>Project</strong> means the project described in Item 4 of the Particulars and as further set out in this Agreement comprising the building and the Project Plot.
                </p>

                <p>
                    <strong>Project Manager</strong> means the project manager for the Project, as may be appointed by the Seller for time to time.
                </p>

                <p>
                    <strong>Project Plot</strong> means the Plot (subject to final survey and amendment by the Master Developer) set aside in the Master Plan for the Project.
                </p>

                <p>
                    <strong>Property Common Areas</strong> means the Relevant Unit and any Parking Bay(s) together with an undivided share in the relevant Common Areas apportioned to the Relevant Unit in accordance with the Entitlement.
                </p>

                <p>
                    <strong>Purchaser</strong> means the Purchaser named in Item 3 of the Particulars including his heirs, successors-in-title and permitted successors or assigns.
                </p>

                <p>
                    <strong>Purchase Price</strong> means the purchase price of the Relevant Unit as set out in Item 5 of the Particulars.
                </p>

                <p>
                    <strong>Real Estate Register</strong> means the real estate register at the Land Department in which title is registered.
                </p>

                <p>
                    <strong>Relevant Authority</strong> means the Land Department, RERA or any other government entity or organisation having jurisdiction over the issue in question.
                </p>

                <p>
                    <strong>Relevant Unit</strong> means the Unit referred to in Item 4 of the Particulars which is shown on the Relevant Unit Plan and which is located in the Project.
                </p>

                <p>
                    <strong>Relevant Unit Plan</strong> means the draft plan of the Relevant Unit attached at Schedule B, pending the issuance of the finalised plan of the Relevant Unit by the Seller...
                </p>

                <p>
                    <strong>Relevant Unit Specifications</strong> means the Relevant Unit Plan and the Schedule of Furniture, Fixtures, Fittings and Finishes attached at Schedule B.
                </p>

                <p>
                    <strong>Residential Apartment Use</strong> means use for residential purposes by a single family.
                </p>

                <p>
                    <strong>RERA</strong> means the Real Estate Regulatory Agency of Dubai, a division of the Land Department.
                </p>

                <p>
                    <strong>Schedule</strong> means a schedule to this Agreement.
                </p>

                <p>
                    <strong>Single Ownership Plot</strong> means a Plot in the Master Community that is owned by one or more persons (whether real or corporate) but not a Jointly Owned Plot.
                </p>

                <p>
                    <strong>Seller</strong> means the Seller named in Item 2 of the Particulars.
                </p>

                <p>
                    <strong>Service Charges</strong> means the amount payable by a Unit Owner in accordance with their Entitlement as a contribution towards the common expenses of the Owners Association including but not limited to: (a) expenses relating to management, administration, repairs, replacements, maintenance, cleaning, security, facilities management, sinking fund, water, electricity, gas, sewage, chilled water services and other utility connection and consumption charges for the Project; and (b) the Community Charges as assessed against the Project Plot.
                </p>

                <p>
                    <strong>Strata Scheme</strong> means the scheme of titling, ownership and management to be comprised of the Strata Scheme Documentation.
                </p>

                <p>
                    <strong>Strata Scheme Documentation</strong> means the Jointly Owned Property Declaration, the Master Community Declaration and Master Plan.
                </p>

                <p>
                    <strong>Total Unit Area</strong> means, subject to Clause 6.9, the proposed area of the Relevant Unit set out in Item 4 of the Particulars, which area includes any terrace or balconies and is determined in accordance with the Seller’s criteria for assessing such areas.
                </p>

                <p>
                    <strong>UAE</strong> means the United Arab Emirates.
                </p>

                <p>
                    <strong>Unit(s)</strong> has the meaning ascribed to the term in the Jointly Owned Property Law.
                </p>

                <p>
                    <strong>Unit Owner(s)</strong> means the owner of a Unit including an owner whose title registration is pending.
                </p>

                <p>
                    <strong>Utility Provider</strong> means any utility provider in the Emirate of Dubai providing utility services such as water, electricity, gas, sewage, waste disposal, telecommunications, cooling services and any other similar utilities.
                </p>

                <p>
                    <strong>Valid Tax Invoice</strong> means a VAT invoice that meets all of the requirements of the Executive Regulations on the Federal Decree-Law No. (8) of 2017.
                </p>

                <p>
                    <strong>VAT</strong> means the Value Added Tax as imposed by the Federal Decree-Law No. (8) of 2017, 'Any subsequent legislation or official decision issued in this matter shall be applicable.'
                </p>

                <p>
                    <strong>Working Days</strong> any day which is not a Saturday, Sunday or public holiday in the UAE.
                </p>


                <p>
                    <strong>Master Community</strong> means the master community in which the Project Plot is situated currently known as BUSINESS BAY (or such other name as the community in which the Project Plot is situated may become known)...
                </p>

                <p>
                    <strong>Master Community Declaration</strong> means the master community declaration as declared by the Master Developer for the Master Community from time to time in its sole and absolute discretion.
                </p>

                <p>
                    <strong>Master Developer</strong> means Dubai Land Residences LLC, its subsidiaries or affiliates and any successors and assignees...
                </p>

                <p>
                    <strong>Master Plan</strong> means the master plan of the Master Community as may be amended from time to time by the Master Developer...
                </p>

                <p>
                    <strong>Owners Association</strong> has the meaning ascribed to the term in the Jointly Owned Property Law.
                </p>

                <p>
                    <strong>Parking Bay(s)</strong> means any parking bay(s) to be allocated to the Purchaser on the Completion Date as indicated in Item 4 of the Particulars...
                </p>

                <p>
                    <strong>Particulars</strong> means the particulars of this Agreement.
                </p>

                <p>
                    <strong>Parties</strong> means collectively the Seller and the Purchaser and “Party” means either one of them.
                </p>

                <p>
                    <strong>Payment Instalment(s)</strong> means the individual payment instalment(s) of the Purchase Price as set out in the Payment Schedule.
                </p>

                <p>
                    <strong>Payment Schedule</strong> means the payment schedule set out Schedule A.
                </p>

                <p>
                    <strong>Payment Schedule Notes</strong> means the “Payment Schedule Notes” forming part of the Payment Schedule.
                </p>

                <p>
                    <strong>Permitted Use</strong> means the permitted use set out in Item 8 of the Particulars.
                </p>

                <p>
                    <strong>Plot(s)</strong> means any plot and associated improvements within the Master Community whether a Single Ownership Plot or a Jointly Owned Plot.
                </p>

                <p>
                    <strong>Plot Owner(s)</strong> means the owner of a Plot including: (a) an Owners Association (where the Plot is a Jointly Owned Plot); and (b) any other person or persons (whether real or corporate) where the Plot is a Single Ownership Plot.
                </p>

                <p>
                    <strong>Project</strong> means the project described in Item 4 of the Particulars and as further set out in this Agreement comprising the building and the Project Plot.
                </p>

                <p>
                    <strong>Project Manager</strong> means the project manager for the Project, as may be appointed by the Seller for time to time.
                </p>

                <p>
                    <strong>Project Plot</strong> means the Plot (subject to final survey and amendment by the Master Developer) set aside in the Master Plan for the Project.
                </p>

                <p>
                    <strong>Property Common Areas</strong> means the Relevant Unit and any Parking Bay(s) together with an undivided share in the relevant Common Areas apportioned to the Relevant Unit in accordance with the Entitlement.
                </p>

                <p>
                    <strong>Purchaser</strong> means the Purchaser named in Item 3 of the Particulars including his heirs, successors-in-title and permitted successors or assigns.
                </p>

                <p>
                    <strong>Purchase Price</strong> means the purchase price of the Relevant Unit as set out in Item 5 of the Particulars.
                </p>

                <p>
                    <strong>Real Estate Register</strong> means the real estate register at the Land Department in which title is registered.
                </p>

                <p>
                    <strong>Relevant Authority</strong> means the Land Department, RERA or any other government entity or organisation having jurisdiction over the issue in question.
                </p>

                <p>
                    <strong>Relevant Unit</strong> means the Unit referred to in Item 4 of the Particulars which is shown on the Relevant Unit Plan and which is located in the Project.
                </p>

                <p>
                    <strong>Service Charges</strong> means the amount payable by a Unit Owner in accordance with their Entitlement as a contribution towards the common expenses of the Owners Association including but not limited to: (a) expenses relating to management, administration, repairs, replacements, maintenance, cleaning, security, facilities management, sinking fund, water, electricity, gas, sewage, chilled water services and other utility connection and consumption charges for the Project; and (b) the Community Charges as assessed against the Project Plot.
                </p>

                <p>
                    <strong>Relevant Unit Specifications</strong> means the Relevant Unit Plan and the Schedule of Furniture, Fixtures, Fittings and Finishes attached at Schedule B.
                </p>

                <p>
                    <strong>Residential Apartment Use</strong> means use for residential purposes by a single family.
                </p>

                <p>
                    <strong>RERA</strong> means the Real Estate Regulatory Agency of Dubai, a division of the Land Department.
                </p>

                <p>
                    <strong>Schedule</strong> means a schedule to this Agreement.
                </p>

                <p>
                    <strong>Single Ownership Plot</strong> means a Plot in the Master Community that is owned by one or more persons (whether real or corporate) but not a Jointly Owned Plot.
                </p>

                <p>
                    <strong>Seller</strong> means the Seller named in Item 2 of the Particulars.
                </p>

                <p>
                    <strong>Service Charges</strong> means the amount payable by a Unit Owner in accordance with their Entitlement as a contribution towards the common expenses of the Owners Association including but not limited to: (a) expenses relating to management, administration, repairs, replacements, maintenance, cleaning, security, facilities management, sinking fund, water, electricity, gas, sewage, chilled water services and other utility connection and consumption charges for the Project; and (b) the Community Charges as assessed against the Project Plot.
                </p>

                <p>
                    <strong>Strata Scheme</strong> means the scheme of titling, ownership and management to be comprised of the Strata Scheme Documentation.
                </p>

                <p>
                    <strong>Strata Scheme Documentation</strong> means the Jointly Owned Property Declaration, the Master Community Declaration and Master Plan.
                </p>

                <p>
                    <strong>Total Unit Area</strong> means, subject to Clause 6.9, the proposed area of the Relevant Unit set out in Item 4 of the Particulars, which area includes any terrace or balconies and is determined in accordance with the Seller’s criteria for assessing such areas.
                </p>

                <p>
                    <strong>UAE</strong> means the United Arab Emirates.
                </p>

                <p>
                    <strong>Unit(s)</strong> has the meaning ascribed to the term in the Jointly Owned Property Law.
                </p>

                <p>
                    <strong>Unit Owner(s)</strong> means the owner of a Unit including an owner whose title registration is pending.
                </p>

                <p>
                    <strong>Utility Provider</strong> means any utility provider in the Emirate of Dubai providing utility services such as water, electricity, gas, sewage, waste disposal, telecommunications, cooling services and any other similar utilities.
                </p>

                <p>
                    <strong>Valid Tax Invoice</strong> means a VAT invoice that meets all of the requirements of the Executive Regulations on the Federal Decree-Law No. (8) of 2017.
                </p>

                <p>
                    <strong>VAT</strong> means the Value Added Tax as imposed by the Federal Decree-Law No. (8) of 2017, 'Any subsequent legislation or official decision issued in this matter shall be applicable.'
                </p>

                <p>
                    <strong>Working Days</strong> any day which is not a Saturday, Sunday or public holiday in the UAE.
                </p>

                <p>
                    <strong>1.2  The clause headings </strong>, are included for convenience only and shall not affect the interpretation of this Agreement.
                </p>

                <p>
                    <strong>1.3  All dates and periods </strong> shall be determined by reference to the Gregorian calendar.
                </p>

                <p>
                    <strong>1.4  The following Schedules </strong> form part of this Agreement and shall have effect as if set out in full in the body of this Agreement and any reference to this Agreement includes the Schedules:
                    <span>
                        <strong>Schedule A:</strong>	Payment Schedule
                    </span>
                    <span>
                        <strong>Schedule B:</strong>	Relevant Unit Specifications, including the Relevant Unit Plan (Draft) and the Schedule of Furniture, Fixtures, Fittings and Finishes
                    </span>
                    <span>
                        <strong>Schedule C:</strong>	Jointly Owned Property Declaration
                    </span>
                </p>

                <p>
                    <strong>1.5 	If any provision in a definition  </strong> in this Agreement is a substantive provision conferring rights or imposing obligations then, notwithstanding that it is only in the interpretation clause of this Agreement, effect shall be given to it as if it were a substantive provision in the body of this Agreement.
                </p>

                <h3>2.	THE SALE </h3>
                <p>
                    <strong>2.1.	The Seller sells </strong>to the Purchaser who hereby purchases the Property on the terms and conditions contained in this Agreement.
                </p>
                <p>
                    <strong>2.2.	This Agreement is </strong>a personal contract between the Seller and the Purchaser, and, for the avoidance of doubt, the Parties agree that the Master Developer is not a party to this Agreement and has no liability and gives no warranty under it.
                </p>

                <h3>1. PURCHASE PRICE AND PAYMENT</h3>
                <p>
                    <strong>1.1.</strong> The Purchase Price shall be paid by the Purchaser to the Seller free of exchange or variance, and without any deduction or set off in accordance with the Payment Schedule and Payment Schedule Notes.
                </p>
                <p>
                    <strong>1.2.</strong> The Purchaser shall pay each instalment payment as described in the Payment Schedule by transferring each instalment payment to the bank account nominated by the Seller in writing or by delivering the payment via regular cheque or manager cheque to the Seller’s office in Dubai, UAE on or before the due date of the instalment payment set out in the Payment Schedule.
                </p>
                <p>
                    <strong>1.3.</strong> The payment of each Payment Instalment shall be due on the Instalment Date, or in respect of the Final Instalment ten (10) Working Days from the date the Seller provides written confirmation to the Purchaser that the Project Manager has determined that completion of the Project has occurred. Determination that completion of the Project has taken place and ascertaining of the Completion Date shall be at the Project Manager’s sole discretion and the Project Manager’s confirmation in writing of the same shall be conclusive proof that the same has been attained and shall be final and binding on the Parties.
                </p>
                <p>
                    <strong>1.4.</strong> Without prejudice to the Seller’s other rights under this Agreement, in the event of non-payment on the due date of any amount payable by the Purchaser pursuant to this Agreement, the Purchaser shall pay the Late Payment Penalty as compensation for the delay in payment.
                </p>
                <p>
                    <strong>1.5.</strong> Without prejudice to Clause 3.5, in the event a cheque is returned unpaid, the Seller may charge a fee of AED 500.00 (UAE Dirhams Five Hundred) as a handling fee in relation to each returned cheque.
                </p>
                <p>
                    <strong>1.6.</strong> Each payment made by the Purchaser shall be allocated first to the discharge of any penalties, then to the payment of any other amounts due in terms hereof and thereafter to the reduction of the Purchase Price.
                </p>
                <p>
                    <strong>1.7.</strong> The Purchaser hereby represents, undertakes and warrants that all funds utilised by the Purchaser in respect of the payment of the Purchase Price or any other payments anticipated under this Agreement are derived from legitimate sources and are not related to proceeds of crime or money laundering either directly or indirectly.
                </p>

                <h3>4. POSSESSION AND RISK</h3>
                <p>
                    <strong>4.1.</strong> It is recorded that the Anticipated Completion Date represents the date upon which it is presently expected that the Seller will hand over possession of the Property to the Purchaser. The Seller reserves the right, by notice in writing to the Purchaser at any time in accordance with Clause 5.1 (or any other method of delivery permitted at law), to extend the Anticipated Completion Date on one or more occasions and for one or more periods provided the sum of such periods shall not exceed six (6) months calculated from the date set down in Item 7 of the Particulars. If the Project is completed before the Anticipated Completion Date, the Seller reserves the right, at its sole discretion, to require the Completion Date to occur earlier than the Anticipated Completion Date.
                </p>
                <p>
                    <strong>4.2.</strong> The Seller shall in any event give the Purchaser not less than thirty (30) days’ notice in writing of the Completion Date and the Completion Date shall only be deemed to have been determined when such notice has been given.
                </p>
                <p>
                    <strong>4.3.</strong> Possession and occupation of the Property shall be given to and taken by the Purchaser on the Completion Date, subject to Clause 4.4.
                </p>
                <p>
                    <strong>4.4.</strong> All risk and benefit in respect of the Property shall pass to the Purchaser on the Completion Date, which is also the date that the Purchaser is required to take possession of the Property. The Seller is entitled (but not obligated) to decline to hand over possession and occupation of the Property to the Purchaser if the Purchaser has failed to:
                    <br>(a) make any of the payments referred to in Clauses 3, 6.3 and 6.5;
                    <br>(b) comply with any other provision of this Agreement;
                    <br>provided that, notwithstanding the Seller declining handover pursuant to this Clause 4.4, all risk in the Property shall pass to the Purchaser on the Completion Date, irrespective of whether physical possession and occupation has been provided to or taken by the Purchaser and, accordingly, the Purchaser shall not be relieved from performing any of its obligations under this Agreement that are triggered by the Completion Date, (including without limitation the obligation to pay the Final Instalment and/or the Service Charges pursuant to Clauses 3, 6.3 and 6.5).
                </p>
                <p>
                    <strong>4.5.</strong> The Purchaser shall from and after the Completion Date or transfer of title whichever is earlier indemnify and hold the Seller and their respective agents and affiliates harmless against all claims, proceedings, costs, damages, expenses and losses including attorney’s fees and expenses arising out of or relating to:
                    <br>(a) defective or damaged condition of any part of the Property or any other structure constructed thereon and any fixtures, fittings or electrical wiring therein caused or installed by (as the case may be) the Purchaser;
                    <br>(b) the spread of fire or smoke or the flow of water from any part of the Property caused by the Purchaser; or
                    <br>(c) the act, default or negligence of the Purchaser or the Purchaser’s occupiers, his agents or contractors.
                </p>
                <p>
                    <strong>4.6.</strong> As soon as reasonably possible following the Completion Date, the Seller will allocate any Parking Bay(s) to the Purchaser on a temporary basis provided that the exact position of the Parking Bay(s) may not be confirmed until such time as the title deed is issued and Jointly Owned Property Declaration is finalised. The Seller shall use its best efforts to allocate the Parking Bay(s) in a suitable position in relation to the Relevant Unit and the Purchaser shall not be entitled to raise an objection to the allocation and position of such Parking Bay(s).
                </p>

                <h3>5. TRANSFER OF TITLE</h3>
                <p>
                    <strong>5.1.</strong> The Purchaser acknowledges and agrees that the Seller will not transfer title to the Property to the Purchaser until the Completion Date. The Seller shall transfer a clear and unencumbered title in respect of the Property to the Purchaser and shall procure registration of such transfer in favour of the Purchaser with the Land Department in the Real Estate Register as soon as is reasonably possible on or after the Completion Date provided the Purchaser is not otherwise in breach of its obligations under this Agreement (and insofar as the Seller is reasonably capable of doing so).
                </p>
                <p>
                    <strong>5.2.</strong> Once title to the Property has been registered in the name of the Purchaser in the Land Department, the Purchaser may deal in his Property as set out in Clause 14.3. The Purchaser may finance the purchase of the Property with the Seller’s prior written consent and upon such terms and conditions as the Seller may reasonably require prior to the registration of title. The Seller makes no representations or warranties as to the ability of the Purchaser to finance the purchase of the Property, and the Purchaser warrants it has sufficient resources to meet its obligations under this Agreement without the need to obtain finance.
                </p>
                <p>
                    <strong>5.3.</strong> The Purchaser shall supply to the Seller all information and sign any document as may be reasonably required by the Land Department and any other respective authorities to effect transfer of title and give effect to this Agreement and the Purchaser shall bear the cost of all fees and expenses as required by the respective authorities to effect such transfer and issue of title.
                </p>
                <p>
                    <strong>5.4.</strong> The Purchaser shall accept transfer of title to the Property subject to such easements and restrictions benefiting or burdening the Property in terms of this Agreement, the Strata Scheme Documentation or as imposed by Applicable Law or any Relevant Authority.
                </p>
                <p>
                    <strong>5.5.</strong> The Purchaser shall on demand pay any and all costs, expenses and/or fees in connection with and/or incidental to the registration of this Agreement and the registration of transfer of title to the Property in the name of the Purchaser at the Land Department and, if applicable, in the Interim Real Estate Register (the “Fees”), including any part of the Fees assessed on the Seller, any fees that are currently imposed or may be imposed in the future and the Seller’s administration in effect from time to time. The Seller offers no warranty or confirmation as to the level of the Fees. The Purchaser shall pay the Fees at the prevailing rate as determined by the Land Department from time to time (which are currently 4% of the Purchase Price) in full. The Purchaser shall reimburse the Seller on demand for any Fees paid by the Seller on behalf of the Purchaser together with the Seller’s Administration Fee in effect from time to time. The Purchaser shall indemnify the Seller against all costs, claims, or liabilities the Seller may suffer, (including, without limitation, fines or penalties levied by the Land Department) arising or in any way connected with the Purchaser’s failure to pay the Fees when demanded.
                </p>
                <h3>6. PURCHASER’S ACKNOWLEDGEMENTS AND UNDERTAKINGS</h3>
                <p><strong>6.1.</strong> The Purchaser shall be responsible for and shall be liable to pay for water, electricity, gas, sewage, chilled water, telephone, information, technology and communication services and other utility connection and consumption charges, all charges imposed directly by any Relevant Authority or Utility Provider for these services to the Property and any property or local or federal authority taxes levied on the Property (such as and not limited to VAT) from the Completion Date and thereafter... </p>

                <p><strong>6.2.</strong> The Purchaser acknowledges and agrees that the Purchaser shall pay their portion of Service Charges, on and from the Completion Date (and regardless of whether the Purchaser takes possession of or title to the Property)...</p>

                <p><strong>6.3.</strong> The Purchaser acknowledges that the Service Charges will also include a share of the Community Charges assessed on the Project Plot pursuant to the Master Community Declaration and levied upon the relevant Owners Association.</p>

                <p><strong>6.4.</strong> The Service Charges for the twelve (12) month period commencing on the Completion Date shall be payable in advance by the Purchaser to the Manager on the Completion Date.</p>

                <p><strong>6.5.</strong> The Purchaser agrees and understands that the Property may only be used for the Permitted Use which for the avoidance of doubt comprises Residential Apartment Use for family use... </p>

                <p><strong>6.6.</strong> The Purchaser and its successors-in-title of the Property will be required to enter into agreement(s) as referred to and required in the Strata Scheme Documentation for the exclusive installation, utilisation and servicing of the infrastructure...</p>

                <p><strong>6.7.</strong> The Purchaser acknowledges that on the Completion Date other Units, Common Areas, and similar projects within the Master Community as well as the Communal Facilities may be incomplete...</p>

                <p><strong>6.8.</strong> The Purchaser acknowledges and agrees that while the Relevant Unit Plan is as accurate as possible, it is not yet final and adjustments to the final measurements and Entitlements may need to be made...</p>

                <p><strong>6.9.</strong> The Purchaser acknowledges and agrees that if any of the materials required by the Seller to construct the Relevant Unit... are not available within a reasonable time or at a reasonable cost...</p>

                <p><strong>6.10.</strong> The Purchaser acknowledges and agrees that the Relevant Unit Plan and all architecture details shown on the Relevant Unit Plan are indicative only...</p>

                <p><strong>6.11.</strong> The Purchaser undertakes to allow the Manager, the Seller and/or any of their affiliates to have the right to place logos, promotional signage... The Purchaser shall indemnify the Manager, the Seller for any losses... </p>

                <h3>7. SELLER’S GENERAL COVENANTS</h3>
                <p><strong>7.1.</strong> The Seller will, following the Completion Date and provided the Purchaser is not otherwise in breach of this Agreement, assign to the Purchaser the benefit of any manufacturer’s warranties...</p>

                <p><strong>7.2.</strong> For a period of twelve (12) months from the Completion Date, the Seller shall use best endeavours to rectify any defective works...</p>

                <h3>8. THE STRATA SCHEME</h3>
                <p><strong>8.1.</strong> It is the Seller's intention to implement the Strata Scheme through use of the Strata Scheme Documentation.</p>

                <p><strong>8.2.</strong> The Purchaser acknowledges and accepts that the Strata Scheme Documentation for the Project are to be finalised on or as soon as reasonably practicable after the Completion Date...</p>

                <p><strong>8.3.</strong> In the event that the Owners Association for the Project is not yet formed and registered pursuant to Applicable Law, the Purchaser acknowledges and understands that the powers and functions of the Owners Association will be delegated to the Seller or its agent...</p>

                <p><strong>8.4.</strong> The delegation of the powers and functions of the Owners Association will be performed by the Manager pursuant to the Management Agreement.</p>

                <p><strong>8.5.</strong> The Purchaser acknowledges and accepts that an Owners Association must comply with and enforce the Strata Scheme Documentation...</p>

                <p><strong>8.6.</strong> The Purchaser acknowledges and agrees that, upon taking transfer of the Property, the Purchaser and his heirs... will become a member of the applicable Owners Association...</p>

                <p><strong>8.7.</strong> Every Unit is sold subject to the terms of the Strata Scheme Documentation and all possible steps will be undertaken by the Parties so that the registration of the Property...</p>

                <p><strong>8.8.</strong> To the extent that the Strata Scheme Documentation, the Management Agreement... are not finalised by the Seller... the Purchaser hereby irrevocably authorises the Seller as the Purchaser’s agent...</p>

                <p><strong>8.9.</strong> Without in any way limiting this Clause 8, the Purchaser acknowledges and understands that the Property and the Project are part of the Master Community...</p>

                <p><strong>8.10.</strong> The Purchaser further acknowledges that the Project shall be managed exclusively by the Manager for a term determined by the Seller...</p>

                <h3>9. VIEWING</h3>
                <p><strong>9.1.</strong> The Purchaser may after receiving the notice of the Completion Date, book an appointment with the Seller... Failure to book an appointment will entitle the Seller to hand over the Relevant Unit on an as-is basis...</p>

                <p><strong>9.2.</strong> The Purchaser shall be accompanied by a representative of the Seller and such inspection shall take place during normal business hours.</p>

                <p><strong>9.3.</strong> The Purchaser will comply with all safety directions given in regard to access to the Relevant Unit and the Project...</p>

                <p><strong>9.4.</strong> At the viewing, the Parties shall prepare and sign a complete list of any defects and deficiencies... The Purchaser shall be deemed to have accepted the physical condition of the Relevant Unit in all other respects.</p>

                <p><strong>9.5.</strong> The Purchaser agrees that it shall not be entitled to make any objection, claim, withhold any payment... as a result of any matters, and/or Defects related to the Property.</p>

                <h3>10. DEFAULT AND TERMINATION</h3>
                <p><strong>10.1.</strong> If the Purchaser fails to make payments... the Seller shall be entitled... to:
                    <br>(a) terminate this Agreement and Deregister the Property; and
                    <br>(b) retain an amount equivalent to the greater of:
                    <br>&emsp;(i) 40% of the Purchase Price... or
                    <br>&emsp;(ii) such amount as the Seller may be entitled to retain and/or forfeit...
                    <br>(c) take any other action... in accordance with the terms of this Agreement and/or Applicable Law.
                </p>

                <p><strong>10.2.</strong> For the avoidance of doubt, no refund of monies paid (once all applicable damages, fees, costs and other charges are deducted by the Seller) shall be made by the Seller to the Purchaser until the Property is sold...</p>

                <p><strong>10.3.</strong> Where termination of this Agreement takes place pursuant to Clause 10.1... the Purchaser undertakes to fully indemnify the Seller against all third party losses...</p>

                <p><strong>10.4.</strong> In the event that the Purchaser has sold the Property or assigned this Agreement to any third party in breach... then the Purchaser undertakes to:
                    <br>(a) meet any and all claims from such third parties...
                    <br>(b) indemnify the Seller from or against any loss, damage or claim...
                </p>

                <p><strong>10.5.</strong> In the event of the Seller, the Purchaser shall, on demand... take such steps required by the Seller and/or the Land Department to Deregister the Property including but not limited to:
                    <br>(a) signing any documents...
                    <br>(b) paying the Deregistration Fees...
                    <br>(c) in the case of any termination pursuant to Clause 10.1...
                </p>

                <p><strong>10.6.</strong> The Purchaser shall indemnify the Seller against any losses, costs, claims or penalties the Seller may incur arising out of the Purchaser’s failure to comply with Clause 10.5.</p>

                <h3>11. FORCE MAJEURE</h3>
                <p><strong>11.1.</strong> The Seller shall not be liable for any failure or delay to perform its obligations under this Agreement due to Force Majeure Events provided the Seller gives to the Purchaser a written notice within thirty (30) days indicating the beginning of such circumstances. Any date, period or deadline imposed upon the Seller by this Agreement shall be extended for a period equal to that during which such circumstances exist. The Seller shall in such circumstances promptly notify the Purchaser of the new date, period or deadline (including, but not limited to the Completion Date) or an estimate of the duration of the delay, followed by a new date, period or deadline when it can be determined.</p>

                <p><strong>11.2.</strong> Payment by the Purchaser of any part of the Purchase Price or any other amount due under this Agreement when due shall not be excused or delayed due to a Force Majeure Event.</p>

                <p><strong>11.3.</strong> Upon the occurrence of a Force Majeure Event, both Parties shall take all reasonable measures to minimise the effect of such event and use their best endeavours to continue to perform their obligations under this Agreement insofar as this is reasonably practicable.</p>

                <h3>12. GENERAL</h3>
                <p><strong>13.1.</strong> No variation of this Agreement shall be valid unless it is in writing and signed by each of the Parties or their authorised representatives.</p>

                <p><strong>12.2.</strong> This Agreement may not be assigned or transferred by the Purchaser except:
                    <br>(a) with the prior written consent of the Seller given in the terms of a written assignment agreement in a form acceptable to the Seller, executed by the Parties and the assignee;
                    <br>(b) upon payment of the Administration Fee by the Purchaser (which is currently set at AED 5,000 (UAE Dirhams Five Thousand) or the amount specified by the Owner as applicable in Dubai, and which amount may be amended by the Seller from time to time having regard to the maximum allowable pursuant to Applicable Law;
                    <br>(c) provided always that the Purchaser shall have paid a minimum of twenty five per cent (25%) of the Purchase Price to the Seller; and
                    <br>(d) on the basis that the Seller shall not be liable for the Fees or any other Land Department fees, charges or penalties in any way associated with the assignment all of which will be met by the Purchaser.
                </p>

                <p><strong>12.3.</strong> Unless otherwise provided in this Agreement, once title to the Property has passed to the Purchaser, the Purchaser may exercise all the rights of a property owner... “For the avoidance of doubt, any resale under clause 13.3 of this Agreement shall be a personal contract between the Purchaser and the party to whom the Property is resold. The Master Developer shall not be a party to such resale and shall have no liability nor give any warranty under that sale agreement.”</p>

                <p><strong>12.4.</strong> No concession or other indulgence granted by the Seller to the Purchaser whether in respect of time for payment or otherwise in regard to the terms and conditions of this Agreement or Strata Scheme Documentation shall be deemed to be a waiver of its rights in terms of this Agreement or Strata Scheme Documentation.</p>

                <p><strong>12.5.</strong> If there is more than one (1) Purchaser in terms of this Agreement, the liability of each shall be joint and several.</p>

                <p><strong>12.6.</strong> Each of the Parties shall immediately upon being requested to do so, sign/execute all such documents in connection with the transfer of title and generally as are necessary to give effect to this Agreement.</p>

                <p><strong>12.7.</strong> This Agreement constitutes the entire agreement between the Parties... The Purchaser acknowledges that any advertising or promotional material is indicative only and warrants that they have not relied upon any such promotional or advertising material and have relied solely upon the terms of this Agreement.</p>

                <p><strong>12.8.</strong> The Parties further agree that if any provision of this Agreement or the Strata Scheme Documentation conflicts with Applicable Law, then the relevant provisions shall be appropriately amended... the remaining terms and conditions that are in adherence shall remain in force.</p>

                <p><strong>12.9.</strong> The Seller may assign this Agreement at any time to any subsidiary or affiliate company or to any other third party without the need for the consent of the Purchaser and without the need to notify the Purchaser that any such assignment or transfer has taken place.</p>

                <p><strong>12.10.</strong> This Agreement has been negotiated and drafted in Arabic language. If there is any inconsistency between the English and the Arabic translation, the Arabic text and interpretation shall prevail.</p>

                <h3>13. NOTICES</h3>
                <p>Any notice given under this Agreement shall be in writing and shall be served by delivering it personally or sending it by courier or email to the address or email address as set out in this Agreement. Any such notice shall be deemed to have been received:
                    <br>(a) if delivered personally, at the time of delivery;
                    <br>(b) in the case of courier, on the date of delivery as evidenced by the records of the courier;
                    <br>(c) in case of an email, on email receipt.
                </p>

                <h3>14. GOVERNING LAW AND JURISDICTION</h3>
                <p>This Agreement and the rights of the Parties hereunder shall be governed by the Laws of Dubai and the Federal Laws of the UAE and the Parties agree that any legal action or proceeding with respect to this Agreement shall be subject to the exclusive jurisdiction of the Courts of Dubai.</p>

                <h3>15. EFFECTIVE DATE</h3>
                <p>This Agreement shall be effective and binding upon the Parties from the Effective Date. Unless terminated earlier pursuant to the provisions of Clause 10, this Agreement shall survive the Completion Date insofar as any rights and obligations contained herein are of continuing effect.</p>

                <p><strong>IN WITNESS WHEREOF</strong> the Parties have executed this Agreement on the dates set forth below, to be effective as of the Effective Date.</p>

                <p><strong>The Purchaser hereby confirms</strong> to have read and understood the terms of this Agreement, the Particulars and Schedules to this Agreement and agrees and undertakes to be bound by them:</p>

            </div>
<br/>
    <table class="signature" style="border: none; width: 100%;">
        <tr style="border: none;">
            <td style="width: 50%;">
                <div class="section">
                    <div class="label-line" style="margin-top: 15px;"><span class="label">Name:</span> Tiger Properties LLC by its authorised representative</div>
                    <div class="label-line"><span class="label">Signed:</span>____________________</div>
                    <div class="label-line"><span class="label">Date:</span> {{ $booking->latest_approved_at->toDateString() }}</div>
                    <div class="label-line"><span class="label">Name:</span>____________________</div>
                    <div class="label-line"><span class="label">Witness:</span>____________________</div>
                </div>
            </td>
            <td style="padding-left: 20px; width: 50%;">
                <div class="section">
                    @php
                        $names = $customerInfos->pluck('name')->toArray();
                        $formattedNames = '';

                        if (count($names) === 1) {
                            $formattedNames = $names[0];
                        } elseif (count($names) === 2) {
                            $formattedNames = $names[0] . ' & ' . $names[1];
                        } else {
                            $last = array_pop($names);
                            $formattedNames = implode(', ', $names) . ' & ' . $last;
                        }
                @endphp
                <div class="label-line" style="margin-top: 15px;"><span class="label">Name:</span> {{ $formattedNames }} </div>
                <div class="label-line"><span class="label">Signed:</span>____________________</div>
                <div class="label-line"><span class="label">Date:</span> {{ $booking->latest_approved_at->toDateString() }}</div>
                <div class="label-line"><span class="label">Name:</span>____________________</div>
                <div class="label-line"><span class="label">Witness:</span>____________________</div>
            </div>
            </td>
        </tr>
    </table>

    <!-- Payment Plan Section -->
        <div class="section">
            <h2>Schedule A - Payment Plan</h2>
            @if($paymentPlan)
                <p><span class="label">Plan Name:</span><span class="value">{{ $paymentPlan->name }}</span></p>
                <p><span class="label">Selling Price:</span><span
                        class="value">AED {{ number_format($unit->price, 2) }}</span></p>
                @if($booking->discount > 0)
                    <p><span class="label">Discount:</span> {{ $booking->discount }}%</p>
                    <p><span class="label">Effective Price:</span> AED {{ number_format($booking->price, 2) }}</p>
                @endif
                <p><strong>DLD Fee:</strong> {{ (int) $paymentPlan->dld_fee_percentage }}% |
                    AED {{ number_format($paymentPlan->dld_fee, 2) }}</p>
                <!-- Add more payment plan details or installments if needed -->
            @else
                <p>No payment plan assigned.</p>
            @endif

            @if($booking->installments->count())
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
                    @foreach($booking->installments as $installment)
                        <tr>
                            <td>
                                {{ $installment->description }}
                                @if($loop->first)
                                    <br/><small>({{ (int) $installment->percentage }}% + {{ (int) $paymentPlan->dld_fee_percentage }}% DLD fee + Admin fee - EOI)</small>
                                @endif
                            </td>
                            <td>{{ (int) $installment->percentage }}%</td>
                            <td>{{ \Carbon\Carbon::parse($installment->date)->format('Y-m-d') }}</td>
                            <td>AED {{ number_format($installment->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

        </div>
</main>
</body>
</html>
