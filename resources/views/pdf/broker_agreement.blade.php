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

        h1, h2, h3 {
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
        }
        .value {
            margin-left: 5px;
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

<h1>Broker Agreement</h1>

<div class="section">
    <p><span class="label">Broker Name:</span> {{ $user->name }}</p>
    <p><span class="label">Email:</span> {{ $user->email }}</p>
    <p><span class="label">User Type:</span> {{ $userType }}</p>
</div>

<div class="section">
    <p>
        Dear <strong>{{ $user->name }}</strong>, thank you for registering as a <strong>{{ $userType }}</strong>.
        This agreement outlines the terms and conditions under which you will operate as a licensed Broker.
    </p>
    <ul>
        <li>You confirm that all uploaded documents (RERA Certificate, Trade License, Bank Account, Tax Registration) are valid and up to date.</li>
        <li>You agree to comply with all relevant regulations and guidelines in your jurisdiction.</li>
        <li>You will maintain accurate records of all transactions and cooperate with any audit or inspection as required.</li>
    </ul>
</div>

<div class="section">
    <p>
        By signing this agreement, you acknowledge that you have read, understood, and agreed
        to the terms stated herein. You also affirm that all provided documents are accurate
        and authentic.
    </p>
</div>

<div class="section">
    <p>Please sign and return this agreement to complete your registration process.</p>
    <p>
        <strong>Signature:</strong> ______________________________
    </p>
    <p>Date: ______________________________</p>
</div>

<div class="section">
    <p>Once we receive your signed agreement, your account will remain in <strong>Pending</strong> status until an administrator reviews and approves it.</p>
</div>
</main>
</body>
</html>
