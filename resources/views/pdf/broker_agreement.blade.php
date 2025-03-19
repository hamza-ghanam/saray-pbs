<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Broker Agreement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
            color: #333;
            line-height: 1.4;
        }
        h1 {
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .label {
            font-weight: bold;
        }
    </style>
</head>
<body>

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

</body>
</html>
