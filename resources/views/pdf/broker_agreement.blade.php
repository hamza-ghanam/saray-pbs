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

        /* Numeric lists (1., 1.1., etc.) */
        ol.numeric,
        ol.numeric ol {
            counter-reset: item;
            list-style: none;
            padding-left: 0;
        }

        ol.numeric li {
            counter-increment: item;
            margin-bottom: 0.5em;
        }

        ol.numeric li > h2 {
            display: inline; /* turn H2 into inline so it doesn’t break */
            margin: 0; /* remove the default top/bottom margins */
            vertical-align: middle; /* align nicely with the number */
        }

        ol.numeric li::before {
            content: counters(item, ".") ". ";
            font-weight: bold;
        }

        ol.numeric ol {
            margin-left: 1.5em;
        }

        /* Alpha lists (a., b., c.) */
        ol.nested-alpha {
            counter-reset: alpha;
            list-style: none;
            padding-left: 1.5em; /* indent to align under parent */
        }

        ol.nested-alpha li {
            counter-increment: alpha;
            margin-bottom: 0.3em;
        }

        ol.nested-alpha li::before {
            content: counter(alpha, lower-alpha) ". ";
            font-weight: bold;
        }

        li.main-title:before {
            font-size: 1.4em;
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

        .section {
            text-align: justify;
            margin-bottom: 15px;
        }

        .page-break {
            /* force a page break before (or after) this element */
            page-break-before: always;
            /* optional newer syntax */
            break-before: page;
        }

        table.signature-tbl {
            border-collapse: collapse;
            border: none;
        }

        table.signature-tbl,
        table.signature-tbl th,
        table.signature-tbl td {
            border: none; /* remove all borders */
        }

        table.commission-tbl th,
        table.commission-tbl td {
            text-align: center; /* horizontal centering */
            vertical-align: middle; /* vertical centering */
            padding: 8px; /* optional padding */
        }
    </style>
    <title>Broker Agreement</title>
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
        <h1 style="text-align: center">AGENCY AGREEMENT</h1>
        <p style="text-align: center">{{ \Carbon\Carbon::now()->format('l Y-m-d') }}</p>
    </div>

    <div class="section">
        <p>
            This REAL ESTATE BROKERAGE AGREEMENT is made on the date stated above (the "Agreement")
        </p>
        <p>
            BETWEEN<br/>
            Unique Saray Development, a company duly registered in the Emirate of Dubai, United Arab Emirates with
            license number 1343857 and having its address at PO Box ………. Bay Square, Business Bay Building, Dubai, United Arab
            Emirates, telephone: 04 554 8787,
            email id: info@uniquesaray.com (the "Seller");

        </p>
        <p>
            AND<br/>
            {{ $user->name }} REALESTATE BROKER, a company duly registered in the Emirate of Dubai, UAE with license
            number {{ optional($user->brokerProfile)->license_number }}, RERA registration
            number {{ optional($user->brokerProfile)->rera_registration_number }} and having
            its address at {{ optional($user->brokerProfile)->address }} Dubai, UAE, PO
            Box {{ optional($user->brokerProfile)->po_box }},
            Dubai, United Arab Emirates, telephone: {{ optional($user->brokerProfile)->telephone }} email
            id: {{ $user->email }} (the
            "Broker"). </p>
    </div>

    <div class="section">
        <h2>RECITALS</h2>
        <ol class="nested-alpha">
            <li>The Seller is the developer of the mixed-use in the UAE (the <strong>"Development"</strong>). The Seller
                intends to sell
                Units to Prospective Purchasers;
            </li>
            <li>The Broker desires to provide real estate Brokerage services to the Seller for the purpose of marketing
                and selling Units; and
            </li>
            <li>The Seller would like to appoint the Broker on a non-exclusive basis for the purposes of marketing and
                selling Units to Prospective Purchasers on the terms and conditions set out in this Agreement
            </li>
        </ol>
    </div>

    <div class="section">
        <h2 style="text-align: center">OPERATIVE PROVISIONS</h2>
        <ol class="numeric">
            <li class="main-title"> <!-- 1. -->
                <h2>DEFINITIONS AND INTERPRETATION</h2>
                <ol class="numeric">
                    <li> <!-- 1.1. -->
                        In this Agreement unless the context otherwise requires the following words and expressions have
                        the respective meanings as set out below: <br/>
                        <strong>"AED"</strong> means UAE Dirhams, the lawful currency of the UAE;<br/>
                        <strong>"Applicable Laws"</strong> means principally By-Law No. 3 of 2022regarding the
                        Regulation of Real Estate Brokers' Register in the Emirate of Dubai and any regulations and
                        codes of conduct issued by RERA, and any other laws, regulations or other guidance enacted or to
                        be enacted either in the Emirate of Dubai or by the Federal Government of the UAE including but
                        not restricted to laws, regulations or other guidance concerning the sales and marketing of real
                        estate and the services to be performed by the Broker under this Agreement;<br/>
                        <strong>"Booking Amount"</strong> means in relation to a Unit, the higher of the sum referred to
                        in the Booking Form or ten percent (10%) of the Sale Price, which shall be collected directly by
                        the Seller from Prospective Purchaser;<br/>
                        <strong>"Booking Form"</strong> means the form to be entered into between the Seller and
                        Prospective Purchaser in respect of the reservation of a Unit, as amended and reissued by the
                        Seller from time to time;<br/>
                        <strong>"Commission"</strong> means the commission relating to marketing and selling Units as
                        set out in further detail at Clause 5 and Schedule 2 of this Agreement (as may be amended by the
                        Seller from time to time);<br/>
                        <strong>"Expression of Interest"</strong> means the form to be entered into between the Seller
                        and Prospective Purchaser in respect of registering interest in a Unit, as amended and reissued
                        by the Seller from time to time;<br/>
                        <strong>"Family Member"</strong> means any spouse, parent, sibling or child;<br/>
                        <strong>"Intellectual Property"</strong> includes the Seller's copyright, patents, know-how,
                        trade secrets, trademarks, trade names, design rights, rights in get-up, database rights, chip
                        topography rights, mask works, utility models, domain names and all similar rights and, in each
                        case:<br/>
                        <ol class="nested-alpha">
                            <li>whether registered or not;</li>
                            <li>including any applications to protect or register such rights;</li>
                            <li>including all renewals and extensions of such rights or
                                applications;
                            </li>
                            <li>whether vested, contingent or future; and</li>
                            <li>wherever existing;</li>
                        </ol>
                        <strong>"Minimum Amount"</strong> means in relation to a Unit, thirty percent (30%) of the sale
                        price of a Unit, as notified to the Broker by the Seller;<br/>
                        <strong>"MOU"</strong> means a memorandum of understanding to be entered into between the Seller
                        and Prospective Purchaser in respect of the purchase of a Unit(s), as may be amended from time
                        to time;<br/>
                        <strong>"Parties"</strong> means the Seller and the Broker and the expression "Party" shall mean
                        any one-off them;<br/>
                        <strong>"Prospective Purchaser"</strong> means a third-party purchaser introduced to the Seller
                        or procured by the Broker during the Term, who is interested in purchasing a Unit, and who is
                        legally entitled to purchase and own freehold property in the Emirate of Dubai in accordance
                        with Applicable Laws;<br/>
                        <strong>"RERA"</strong> means the Real Estate Regulatory Agency of the Government of Dubai;<br/>
                        <strong>"Reservation Form"</strong> means a Booking Form, EOI, or MOU, as the case may be;<br/>
                        <strong>"Sale Price"</strong> means the total purchase price for a Unit as notified to the
                        Broker by the Seller;<br/>
                        <strong>"Services"</strong> means the services relating to marketing and selling Unit(s) to be
                        provided by the Broker, as more fully described in Schedule “1” of this Agreement;<br/>
                        <strong>"SPA"</strong> means the form of a detailed sale and purchase agreement (including a
                        Development Sale and Purchase Agreement for a Unit) with respect to the purchase and development
                        of a Unit that is acceptable to the Seller from time to time;<br/>
                        <strong>"Term"</strong> means the duration of this Agreement commencing on the date of this
                        Agreement and continuing for a period of one (1) year unless otherwise extended pursuant to
                        Clause 2.2 or earlier terminated as provided by the terms of this Agreement;
                        <strong>"UAE"</strong> means the United Arab Emirates; and<br/>
                        <strong>"Unit"</strong> means any property located within the Development and includes but is
                        not limited to a commercial or residential unit, town house, and/or villa, located within the
                        Development as notified to the Broker by the Seller from time to time and "Units" shall have a
                        corresponding meaning.<br/>
                    </li>
                    <li>
                        The headings in this Agreement are for convenience only and shall not affect its interpretation.
                    </li>
                    <li>
                        References in this Agreement to clauses and the schedules are, unless the context otherwise
                        requires, references to the relevant clauses and the schedules to this Agreement.
                    </li>
                    <li>
                        Words importing the masculine gender shall where appropriate include the feminine gender and
                        the neuter gender or vice versa as the case may be and words importing the singular shall where
                        appropriate include the plural number and vice versa
                    </li>
                    <li>References to persons shall include firms, companies and corporations and vice versa</li>
                    <li>
                        References to any laws and to terms defined in such laws shall be replaced with or
                        incorporate (as the case may be) references to any laws replacing, amending, extending,
                        re-enacting or consolidating such laws and the equivalent terms defined in such laws, once in
                        force and applicable. Any statute or statutory provision includes any subordinate legislation
                        made under the statute or statutory provision (as amended, consolidated or re-enacted) from time
                        to time
                    </li>
                </ol>
            </li>
            <li class="main-title"> <!-- 2. -->
                <h2>APPOINTMENT AND TERM</h2>
                <ol class="numeric">
                    <li> <!-- 2.1. -->
                        The Seller hereby appoints the Broker for the Term and grants to the Broker a non-exclusive
                        mandate to offer Units for sale to any Prospective Purchaser on the terms and conditions
                        specified in this Agreement or as otherwise stipulated in writing by the Seller from time to
                        time.
                    </li>
                    <li>
                        Subject to Clause 8.1, the Term may be extended or renewed at the Seller's sole discretion upon
                        seven (7) calendar days' written notice from the Seller to the Broker.
                    </li>
                </ol>
            </li>
            <li class="main-title"> <!-- 3. -->
                <h2>SERVICES</h2>
                <ol class="numeric">
                    <li> <!-- 3.1. -->
                        The Broker shall perform the Services pursuant to the terms of this Agreement with
                        professional skill, care, diligence, prudence and foresight, exercised by, and ordinarily
                        expected from, a properly qualified professional and competent international Broker in the
                        Broker's industry or profession carrying out services of comparable size, scope and complexity,
                        and on the directions of the Seller, acting always in the best interest of the Seller.
                    </li>
                    <li>
                        The Broker warrants that it has, and shall always have, all requisite approvals and
                        licenses required under Applicable Laws to enable it to perform its obligations and the
                        Services. For the duration of the Term, the Broker agrees to comply with Applicable Laws and all
                        other requirements prescribed by RERA from time to time in connection with the Broker's
                        performance of the Services under this Agreement. The Broker shall ensure that all individuals
                        that are engaged in the sale of Units are employed by the Broker, have the requisite experience
                        and qualifications and are registered and approved by RERA to sell Units as per Applicable Laws.
                        For the avoidance of doubt, the Broker acknowledges that no Commission shall be payable by the
                        Seller in respect of any sale that is procured by any person who is not employed by the Broker
                        and/or registered and approved by RERA.
                    </li>
                    <li>
                        During the Term, the Broker shall comply with the Seller's instructions in relation to
                        marketing and selling Units and otherwise in its dealings with Prospective Purchasers, in
                        compliance with all policies, directions and instructions of the Seller, in respect of the
                        Services, from time to time in place.
                    </li>
                </ol>
            </li>
            <li class="main-title"> <!-- 4. -->
                <h2>BROKER'S OBLIGATIONS</h2>
                <ol class="numeric">
                    <li> <!-- 4.1. -->
                        The Broker hereby warrants, represents, and confirms that:
                        <p style="margin-left: 10px;">
                            It is a company duly incorporated, validly existing, and in good standing under the
                            <strong>laws and regulations of the United Arab Emirates</strong>, fully complying with
                            all applicable
                            federal and emirate-level legislation. It ensures strict adherence to legal, regulatory,
                            and industry standards while operating with the highest level of professionalism and
                            integrity.
                        </p>
                        <p style="margin-left: 10px;">
                            It possesses full legal authority and corporate capacity to enter into this Agreement
                            and fulfil its obligations hereunder in full compliance with UAE laws.
                        </p>
                        <p style="margin-left: 10px;">
                            The execution of this Agreement and the fulfilment of its obligations will not result
                            in a breach, default, or violation of any existing agreement, arrangement, or legal
                            obligation to which it is a party.
                        </p>
                        <p style="margin-left: 10px;">
                            It shall perform its obligations under this Agreement <strong>in good faith, with
                                transparency
                                and integrity, adhering to all applicable UAE laws and regulations</strong>,
                            including those related to <strong>anti-bribery, anti-money laundering, and commercial
                                compliance</strong>. Additionally,
                            it shall carry out its duties with efficiency and professionalism, ensuring the
                            protection and enhancement of the Seller’s reputation while strictly complying with all
                            legal, regulatory, and industry requirements applicable in the UAE.
                        </p>
                    </li>
                    <li>
                        The Broker shall not make any representations or give any warranty or guarantee in respect
                        of any Units or the Development without the express written authority of the Seller. In all
                        discussions with any Prospective Purchaser, the Broker must disclose (whether in writing or
                        otherwise) that it is acting as a Broker in a non-exclusive capacity in respect of the sale of
                        Units to Prospective Purchasers and shall not purport to be acting in any other capacity.
                    </li>
                    <li>
                        The Broker is strictly prohibited from instructing or employing a sub-agent/Broker to carry
                        out any of the Broker's obligations set out in this Clause 4. The Broker expressly warrants and
                        undertakes that it shall not share the Commission with any third party (including any employee
                        of the Seller) and shall not appoint any sub-agent/Broker in respect of the sale of any Units.
                        The Seller shall not be obliged to pay any Commission (in whole or part) to any other party
                        except the Broker.
                    </li>
                    <li>
                        The Broker shall not have the right or authority to bind the Seller to any agreement for the
                        reservation or the sale and purchase of any Units.
                    </li>
                    <li>
                        The Broker shall submit a client registration form in the format provided by the Seller, on
                        or before any Prospective Purchaser visits the site/sales office of the Seller and shall submit
                        the same to a staff member of the Seller.
                    </li>
                    <li>
                        Immediately upon receipt of an offer to purchase a Unit from a Prospective Purchaser, the
                        Broker shall check the availability of such Unit with the Seller and inform the Prospective
                        Purchaser of the availability and Sale Price of such Unit (once confirmed by the Seller). Upon
                        confirmation, the Broker shall procure the Prospective Purchaser to complete the Expression of
                        Interest or the relevant Reservation Form, and the name of the Broker must be recorded as the
                        "Referring Party'' in the Expression of Interest or Reservation Form, following which the
                        Seller, so long as the Prospective Purchaser is not already registered with the Seller, shall
                        register the Perspective Purchaser as the Broker's client in respect of the specified Unit in
                        the Seller's internal records ("Seller Registration"). Such reservation shall only be effective
                        upon the Prospective Purchaser providing the Seller with a signed original of the relevant
                        Reservation Form (including all relevant documentation required to be provided by the
                        Prospective Purchaser as prescribed in the relevant Reservation Form) and the Seller having
                        received ten percent (10%) of the Sale Price in clear funds in respect of the relevant Unit.
                    </li>
                    <li>
                        Provided that the formalities set out in Clause (4.5) have been satisfied, a Seller
                        Registration shall remain valid for a period of three (3) months so long as the Broker assists
                        the Prospective Purchaser in satisfying all the Seller's formalities including signing all the
                        necessary documentation required for the purchase of a Unit, including the SPA (as outlined
                        in the Services).
                    </li>
                    <li>
                        The Broker is not authorised to enter into any agreement with any Prospective Purchaser
                        (including the Reservation Form and the SPA) on behalf of the Seller. For the avoidance of
                        doubt, the Broker shall not be entitled to collect the Sale Price (or portion thereof) or any
                        monies from a Prospective Purchaser in its own name with respect to a Unit.
                    </li>
                    <li>
                        The Broker shall bear all costs and expenses incurred by it in performing its obligations under
                        this Agreement.
                    </li>
                    <li>
                        The Broker shall act diligently and in good faith towards the Seller and Prospective Purchasers
                        and seek to enhance the reputation of the Seller.
                    </li>
                    <li>
                        The Broker shall immediately declare in writing to the Seller any conflict of interest
                        and/or potential conflict of interest relating to the Seller, the Development, any Unit(s),
                        and/or the provision of any of the Services. In the event any such conflict of interest and/or
                        potential conflict of interest arises, the Seller shall be entitled to terminate this Agreement
                        forthwith and the Broker shall forfeit its right to any Commission.
                    </li>
                    <li>
                        The Broker shall keep and maintain adequate records of Prospective Purchasers for Units and
                        provide to the Seller upon request details of their names and copies of all correspondence with
                        them.
                    </li>
                    <li>
                        The Broker hereby declares, warrants and undertakes, during the Term, that:
                        <ol class="nested-alpha">
                            <li>
                                none of the employees of the Seiler are a relative or partner of the Broker; and the
                                Broker is not an employee of a competitor of the Seller. The Broker shall be bound to
                                inform the Seller in writing in the event that any employee, relative or partner of the
                                Broker becomes an employee of a competitor of the Seller;
                            </li>
                            <li>
                                the Broker shall not offer any type of inducement (monetary or non- monetary) with any
                                third party (including an employee of the Seller);
                            </li>
                            <li>
                                if there is any change to the corporate status of the Broker, including, contact
                                details, name, bank details, authorized signatories or any other material change in the
                                Broker's circumstances, the Broker shall inform the Seller in writing within seven (7)
                                days of any such change; and
                            </li>
                            <li>
                                it has obtained the contact information of Prospective Purchasers in a lawful manner.
                            </li>
                        </ol>
                    </li>
                    <li>
                        During the Term the Broker shall not:
                        <ol class="nested-alpha">
                            <li>
                                advertise Units in any form of advertising medium including (but not limited to)
                                brochures, magazines, newspapers, signs, and on any digital media including the world
                                wide web or any other globally accessible medium or conduct any publicity campaign or
                                use any promotional materials in connection with the performance of its obligations and
                                Services under this Agreement, without the prior written approval of the Seller.
                            </li>
                            <li>make, receive or accept any secret income, profit or other benefit in connection with
                                this Agreement;
                            </li>
                            <li>by any act or omission, breach any laws, regulations and administrative requirements
                                relating to the prevention and/or sanction of any forms of corrupt behaviour or
                                practices in the UAE (''Anti-Corruption Legislation"), from time to time, and shall not
                                make or receive any bribe (which term shall be construed in accordance with the
                                Anti-Corruption Legislation) or other improper payment or advantage, or allow any such
                                payment or advantage to be made or received on behalf of the Broker, either in the UAE
                                or elsewhere, and will implement and maintain adequate procedures to ensure that such
                                bribes or improper payments or advantages are not made or received directly or
                                indirectly on behalf of the Broker. The Broker shall immediately notify the Seller as
                                soon as it becomes aware of a breach, or possible breach, of any of the requirements of
                                this Clause 4.13 (c); or
                            </li>
                            <li>do anything which shall harm the reputation of the Seller.</li>
                        </ol>
                    </li>
                    <li>
                        The Broker shall comply with all applicable local and international laws and regulations
                        governing real estate brokerage. This includes, but is not limited to, adhering to licensing
                        requirements, ethical standards, and disclosure obligations. Any breach of legal compliance may
                        result in the termination of this agreement without compensation.
                    </li>
                    <li>
                        The Broker's performance shall be evaluated based on predefined success criteria, including
                        the number of properties sold, the number of qualified leads generated, and overall contribution
                        to the developer’s business growth. Failure to meet the agreed-upon performance benchmarks
                        within the contract period may result in renegotiation or termination of the agreement.
                    </li>
                </ol>
            </li>
            <li class="main-title"> <!-- 5. -->
                <h2>COMMISSION</h2>
                <ol class="numeric">
                    <li>
                        The commission payable shall be as per Schedule 2, which outlines the applicable commission
                        percentages based on the type of project.<br/>
                        The Commission shall be payable in two (2) equal instalments subject to the terms and conditions
                        set out below:
                        <ul>
                            <li>
                                The first instalment shall be payable within thirty (30) calendar days of the following
                                conditions being satisfied:
                                <ol type="I">
                                    <li>
                                        The Prospective Purchaser has signed the relevant Reservation Form and
                                        submitted the original executed copy to the Seller, along with all documentation
                                        required under the said Reservation Form, within no later than three (3) months
                                        from the date of the Seller's project registration.;
                                    </li>
                                    <li>
                                        The Seller has received, in clear funds:
                                        <ol class="nested-alpha">
                                            <li>A minimum of ten percent (10%) of the total Sale Price from the
                                                Prospective Purchaser towards the Booking Amount; and
                                            </li>
                                            <li>Payment of the applicable registration fee to the Dubai Land Department
                                                in the amount of four percent (4%) of the total Sale Price, along with
                                                all related administrative costs, via cheque(s) or other acceptable
                                                payment method in favour of the Dubai Land Department.
                                            </li>
                                            <li>all ancillary costs, expenses and fees (which shall be at their
                                                prevailing rates from time to time) in connection with and incidental to
                                                the aforesaid registration; and
                                            </li>
                                        </ol>
                                    </li>
                                    <li>
                                        The Broker not being in default of any of its obligations under this Agreement.
                                    </li>
                                </ol>
                            </li>
                            <li>
                                The final instalment shall be payable within thirty (30) calendar days of the following
                                conditions being satisfied:
                                <ol class="nested-alpha">
                                    <li>The Prospective Purchaser has signed a binding Sale and Purchase Agreement
                                        (SPA) acceptable to the Seller, and the Seller has received the original
                                        executed SPA and is satisfied with all accompanying documentation;
                                    </li>
                                    <li>The Seller has received a cumulative amount of at least twenty percent (20%)
                                        of the total Sale Price in clear funds from the Purchaser in respect of the
                                        relevant Unit;
                                    </li>
                                    <li>If applicable, the Seller has received post-dated cheque(s) from the
                                        Purchaser in line with the Reservation Form requirements; and
                                    </li>
                                    <li>
                                        The Broker not being in default of any of its obligations under this Agreement.
                                    </li>
                                </ol>
                            </li>
                        </ul>
                    </li>
                    <li>
                        In consideration of the Broker carrying out the Services pursuant to the terms of this
                        Agreement, the Seller shall pay Commission to the Broker in accordance with the provisions of
                        Clause 5.1 and Schedule 2 of this Agreement.
                    </li>
                    <li>
                        The Broker acknowledges, warrants and undertakes that:
                        <ol class="nested-alpha">
                            <li>any Commission payable under the terms of this Agreement shall be inclusive of all fees,
                                and other deductions levied by any competent authority as per Applicable Laws and the
                                Broker acknowledges being solely responsible and liable to pay all such fees, taxes
                                (including VAT) and deductions and agrees to indemnify the Seller against any and all
                                consequences occurring from the failure to pay such amounts;
                            </li>
                            <li>it shall not be entitled to any incidental or miscellaneous expenses of whatsoever
                                nature incurred by it in providing the Services other than the Commission; and
                            </li>
                            <li>Any Commission which is subject to VAT shall only become payable upon production of a
                                valid tax invoice, in accordance with the Applicable Laws, addressed to the Seller by
                                the Broker.
                            </li>
                            <li>The Broker hereby agrees and authorizes the Seller to remit the Commission payable by
                                cheque or by transfer to the bank account of the Broker as is notified to the Seller in
                                writing. the Seller shall not remit the Commission to any other beneficiary and/or bank
                                account unless directed to do so by the Broker in writing Once the commission is paid to
                                the Broker and/or it’s representative, the Seller shall be discharged from his
                                obligation and immediate receipt should be issued by the Broker.
                            </li>
                        </ol>
                    </li>
                    <li>
                        The Broker hereby agrees that in the event it introduces a Prospective Purchaser who
                        purchases Unit(s) within the Term but after the expiry of a previous Seller Registration, the
                        Broker must obtain a Seller Registration in respect of such subsequent Unit(s) prior to such
                        purchase, in order to be eligible for Commission.
                    </li>
                    <li>
                        The Broker acknowledges and agrees that the Commission shall not be payable by the Seller
                        where:
                        <ol class="nested-alpha">
                            <li>Prospective Purchaser signs the relevant Reservation Form and provides all the required
                                documentation as prescribed in the relevant Reservation Form more than three (3) months
                                after the date of the Seller Registration;
                            </li>
                            <li>the Seller cancels the Reservation Form signed by a Prospective Purchaser in accordance
                                with the terms of the relevant Reservation Form; or
                            </li>
                            <li>any Units are purchased by the Broker, a shareholder of the Broker or any Family Member
                                of a shareholder of the Broker; or
                            </li>
                            <li>Prospective Purchaser introduced by the Broker has previously been in contact with the
                                Seller or has been introduced to the Sellei by any other sales agent/Broker or third
                                party.
                            </li>
                            <li>The Broker shall provide the Client Registration Form in the format provided by the
                                Seller and shall submit it to a member of the Seller’s staff. In the event that this
                                information is found to be invalid, and a dispute occurs, the Agent is not entitled to
                                obtain any commissions related to the sale of the unit, and in the event of
                                non-compliance
                                with what was mentioned, the Agent is not entitled to claim any commission (as specified
                                in
                                clause 4 below) related to the sale of a unit for such prospective Purchaser.
                            </li>
                        </ol>
                    </li>
                    <li>
                        Where a signed Reservation Form is cancelled by the Seller in accordance with Clause 5.4 (b)
                        after the payment of the first instalment of the Commission (the "Refundable Amount"), the
                        Seller shall be entitled (at its election) to:
                        <ol class="nested-alpha">
                            <li>set off the Refundable Amount against any other amounts that may be payable to the
                                Broker pursuant to this Agreement; or;
                            </li>
                            <li>demand the Broker to return forthwith the Refundable Amount to the Seller, which
                                shall be recoverable as a commercial debt.
                            </li>
                        </ol>
                    </li>
                    <li>
                        The Commission shall be payable in AED only.
                    </li>
                </ol>
            </li>
            <li class="main-title"> <!-- 6. -->
                <h2>INDEMNITY</h2>
                <ol class="numeric">
                    <li>The Broker hereby indemnifies and shall keep the Seller (including its affiliate or group
                        companies, their officers, directors and employees) indemnified from and against (without
                        limitation) all losses, damages, costs, claims, fines, proceedings, liabilities, actions,
                        demands and expenses whatsoever (including, but not limited to any liability for legal fees and
                        expenses), on a full indemnity basis, arising out of or in connection with the Broker's
                        (including its officers, employers and agents):
                        <ol class="nested-alpha">
                            <li>Negligence, tortious act or omission, misconduct, misrepresentation, dishonesty or
                                fraud;
                            </li>
                            <li>Default of any of its obligations under this Agreement, including but not limited to the
                                failure to comply with Applicable Laws, breach of any implied condition, warranty, or
                                any other term of this Agreement; or
                            </li>
                            <li>Breach of the representations and warranties made by the Broker, or any acts or
                                omissions of the Broker under this Agreement or in connection with any action to which
                                the Seller may be exposed by placing reliance on the Broker's undertakings in Clauses:
                                “3.2, 4.1, 4.2, 4.12 and 9” of this Agreement.
                            </li>
                        </ol>
                    </li>
                </ol>
            </li>
            <li class="main-title"> <!-- 7. -->
                <h2>SELLER'S OBLIGATIONS</h2>
                <ol class="numeric">
                    <li>The Seller shall act in a commercially reasonable manner towards the Broker and shall not
                        interfere, hinder or prevent the Broker from carrying out its obligations and Services pursuant
                        to the terms of this Agreement.
                    </li>
                    <li>The Seller may at any time, by written notice, amend the:
                        <ol class="nested-alpha">
                            <li>Services;</li>
                            <li>Commission; or</li>
                            <li>Price or specification of any Unit.</li>
                        </ol>
                    </li>
                    <li>The Seller shall inform the Broker within a reasonable period of:
                        <ol class="nested-alpha">
                            <li>Its acceptance or refusal of an offer to purchase a Unit by a Prospective Purchaser;
                            </li>
                            <li>Cancellation of the Reservation Form and/or SPA of a Prospective Purchaser for a Unit;
                                or
                            </li>
                            <li>Inability to complete the sale of a Unit</li>
                        </ol>
                    </li>
                    <li>In the performance of its obligations under this Agreement, neither the Seller nor any of its
                        group companies shall be liable to the Broker or to any third party... (truncated for brevity)
                    </li>
                    <li>The Broker hereby agrees and accepts that the Seller and its group companies shall in no
                        circumstances be liable to the Broker...
                    </li>
                </ol>
            </li>
            <li class="main-title"> <!-- 8. -->
                <h2>TERMINATION</h2>
                <ol class="numeric">
                    <li>The Seller may terminate this Agreement with or without cause...</li>
                    <li>The Broker shall not be entitled to any Commission...</li>
                    <li>In the event that any license, consent or permission...</li>
                    <li>If a Fee has been paid to the Broker...</li>
                    <li>The Broker shall have no claim or recourse...</li>
                </ol>
            </li>
            <li class="main-title"> <!-- 9. -->
                <h2>INTELLECTUAL PROPERTY</h2>
                <ol class="numeric">
                    <li>The Broker shall:
                        <ol class="nested-alpha">
                            <li>Use the Intellectual Property solely in accordance with the Seller's instructions;</li>
                            <li>Not take or authorise any action whereby...</li>
                            <li>Not use any trademark, trade name...</li>
                            <li>At the Seller's request and expense...</li>
                            <li>Immediately inform the Seller of any actual...</li>
                            <li>At the Seller's request, give the Seller reasonable assistance...</li>
                        </ol>
                    </li>
                    <li>Any breach of this Clause “9” by the Broker shall entitle the Seller to terminate this Agreement
                        with immediate effect...
                    </li>
                </ol>
            </li>
            <li class="main-title"> <!-- 10. -->
                <h2>GENERAL</h2>
                <ol class="numeric">
                    <li>This Agreement constitutes the whole agreement...</li>
                    <li>The Broker agrees that its rights and obligations...</li>
                    <li>Nothing in this Agreement shall create...</li>
                    <li>This Agreement shall be effective and binding...</li>
                    <li>During the Term and after termination...</li>
                    <li>Any indulgence granted by the Seller...</li>
                    <li>No variation of this Agreement shall be of any effect...</li>
                    <li>If any provision of this Agreement is or becomes illegal...</li>
                    <li>Where in this Agreement reference is made to the Broker...</li>
                    <li>Any notice given under this Agreement shall be in writing...
                        <ol class="nested-alpha">
                            <li>If delivered personally, on the date of delivery;</li>
                            <li>In the case of courier, on the date of delivery...</li>
                            <li>In the case of email upon sending it...</li>
                        </ol>
                    </li>
                    <li>This Agreement shall be governed by and construed...</li>
                    <li>In the event of any dispute arising...</li>
                    <li>In the event of any dispute in relation to whether the Agent...</li>
                    <li>The provisions of Clauses 6, 9, 10.11, and 10.12 shall survive...</li>
                </ol>
            </li>
        </ol>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2 style="text-align: center;">SCHEDULE 1 – SERVICES</h2>
        <p>
            Marketing and Sales services relating to the Development, which shall include but not be limited to:
        </p>
        <ol class="numeric">
            <li>Notifying the Seller promptly about the interest of any Prospective Purchasers with respect to the
                purchase of any Unit, taking offers and assisting the Seller to develop lines of communication with
                Prospective Purchasers;
            </li>
            <li>Promoting the sale of Units and introduction and procurement of Prospective Purchasers to purchase
                Units;
            </li>
            <li>Assisting Prospective Purchasers in satisfying all the Seller's formalities including signing all the
                necessary documentation required for the purchase of a Unit, including but not limited to the relevant
                Expression of Interest, Reservation Form and SPA, etc. and facilitating the information required from
                Prospective Purchasers;
            </li>
            <li>Prospective Purchasers and Seller's support management; and</li>
            <li>Performing all other services which may be reasonably requested by the Seller.</li>
        </ol>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2 style="text-align: center;">SCHEDULE 2 – COMMISSION</h2>
        <table class="commission-tbl" style="border-collapse: collapse; border: none; text-align: center;">
            <thead>
            <tr>
                <th>Project Name</th>
                <th>Sale Price Range (AED)</th>
                <th>Applicable Commission Rate</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td rowspan="3">Saray Prime Residence Tower</td>
                <td>0 – 5,000,000</td>
                <td>5%</td>
            </tr>
            <tr>
                <td>Above 5,000,000 – 8,000,000</td>
                <td>6%</td>
            </tr>
            <tr>
                <td>Above 8,000,000</td>
                <td>7%</td>
            </tr>
            </tbody>
        </table>
        <ul>
            <li>The Commission shall be payable in two equal installments, subject to the conditions outlined in Clause
                5.
            </li>
        </ul>
        <p>
            The Seller reserves the right to amend the contents of this Schedule, from time to time, at its sole
            discretion and such changes will be notified to the Broker in writing.
        </p>
    </div>

    <div class="section">
        <p>
            <strong>THIS AGREEMENT </strong> has been entered into by the Parties on the date stated at the commencement
            of this Agreement.
        </p>
    </div>

    <div class="section">
        <p style="text-align: center;">
            Execution page follows
        </p>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2 style="text-align: center;">EXECUTION PAGE</h2>
        <table class="signature-tbl">
            <tr>
                <td>
                    Signed for and on behalf of Seller by its duly authorized representative in the presence of:
                </td>
                <td>
                    Signed for and on behalf of Seller by its duly authorized representative in the presence of:
                </td>
            </tr>
            <tr>
                <td>
                    ________________________________<br/>
                    Signature “Unique Saray Development”
                </td>
                <td>
                    ________________________________<br/>
                    Signature authorized representative.
                </td>
            </tr>
            <tr>
                <td>
                    ________________________________<br/>
                    Print name of authorized representative
                </td>
                <td>
                    ________________________________<br/>
                    Designation.
                </td>
            </tr>
            <tr>
                <td>
                    &nbsp;<br/>
                    &nbsp;
                </td>
                <td>
                    ________________________________<br/>
                    Print name of authorized representative.
                </td>
            </tr>
            <tr>
                <td>
                    Stamp:
                </td>
                <td>
                    Stamp:
                </td>
            </tr>
        </table>
    </div>
</main>
</body>
</html>
