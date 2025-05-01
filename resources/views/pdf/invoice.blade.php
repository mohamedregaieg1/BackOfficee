<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Devis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            background-color: #fff;
            line-height: 1.2;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            min-height: 100vh;
        }

        .logo-container img {
            width: 200px;
            height: auto;
            margin-left: 500px;
            margin-top: -90px;
        }

        h1,
        h2 {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            font-size: 12px;
            line-height: 1.2;
        }

        th {
            background-color: #129bf7;
            color: #ffffff;
            font-size: 12px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            color: #129bf7;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .invoice-number {
            font-size: 16px;
            color: #000;
            margin-bottom: 20px;
        }

        .company-info,
        .client-info {
            font-size: 12px;
            color: #000;
        }

        .company-info {
            float: left;
            width: 48%;
        }

        .client-info {
            float: right;
            width: 48%;
            text-align: right;
        }

        .clear {
            clear: both;
        }

        .invoice-details {
            margin-top: 7px;
            font-size: 12px;
            color: #000;
        }

        .summary {
            margin-top: 20px;
        }

        .total-final {
            color: #fff;
            background-color: #ff332c;
            padding: 10px;
            font-size: 14px;
            text-align: center;
            border-radius: 4px;
        }

        .section-divider {
            height: 1px;
            background-color: #000;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            color: #000;
            font-size: 12px;
            position: absolute;
            bottom: 20px;
            width: 100%;
        }

        .table-header th {
            padding: 8px;
            text-align: center;
            font-size: 12px;
        }

        .payment-method,
        .terms-conditions {
            margin-top: 20px;
        }

        .payment-method h2,
        .terms-conditions h2 {
            color: #000;
            font-size: 14px;
        }

        .signature {
            margin-top: 20px;
        }

        .signature p {
            font-weight: bold;
            color: #000;
        }

        .signature hr {
            border: none;
            height: 1px;
            margin: 40px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                <h1 class="header-title">
                    {{ $invoice->type === 'facture' ? 'INVOICE' : 'QUOTE' }}
                </h1>
                <p class="invoice-number"><strong>N°{{ $invoice->number }}</strong></p>
            </div>
            <div class="logo-container">
                @if ($companyLogoBase64)
                    <img src="data:image/webp;base64,{{ $companyLogoBase64 }}" alt="Company Logo">
                @else
                    <p>Logo non disponible</p>
                @endif
            </div>
        </div>

        <div class="invoice-details">
            <p><strong>Creation Date :</strong> {{ $invoice->creation_date }}</p>
            <p><strong>{{ $invoice->additional_date_type }} :</strong> {{ $invoice->additional_date }}</p>
        </div>

        <div class="section-divider"></div>

        <div>
            <div class="company-info">
                <p><strong>{{$company->name}}</strong></p>
                <p>{{$company->address}}</p>
                <p>{{$company->postal_code}},{{$company->country}}</p>
                <p>Phone Number : {{$company->phone_number}}</p>
                <p>Email : {{$company->email}}</p>
                <p>Web Site : {{$company->website}}</p>
                @if ($invoice->type === 'facture')
                    <p>TVA : {{$company->tva_number}}</p>
                @endif

            </div>
            <div class="client-info">
                <p><strong>Customer</strong></p>
                <p>{{ $client->name }}</p>
                <p>{{ $client->address }}</p>
                <p>Phone Number : {{ $client->phone_number }}</p>
                <p>Email : {{ $client->email }}</p>
            </div>
            <div class="clear"></div>
        </div>

        <table class="table-header">
            <thead>
                <tr>
                    <th style="width: 50%;">Designation</th>
                    <th style="width: 15%;">Quantity</th>
                    <th style="width: 15%;">Price HT</th>
                    <th style="width: 10%;">TVA</th>
                    <th style="width: 15%;">Price TTC</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $service)
                    <tr>
                        <td>{{ $service->name }}<br><small>{{ $service->comment }}</small></td>
                        <td>{{ $service->quantity }}</td>
                        <td>{{ $service->total_ht }}</td>
                        <td>{{ $service->tva }}</td>
                        <td>{{ $service->total_ttc }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <table style="float: right;">
                <tr>
                    <td><strong>Price HT :</strong></td>
                    <td>{{ $invoice->total_ht }} </td>
                </tr>
                <tr>
                    <td><strong>Price TVA :</strong></td>
                    <td>{{ $invoice->total_tva }} </td>
                </tr>
                <tr class="total-final">
                    <td><strong>TOTAL :</strong></td>
                    <td>{{ $invoice->total_ttc }} </td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>

        <div class="section-divider"></div>

        <div class="payment-method">
            <h2>Payment Method :</h2>
            <p>{{ $invoice->payment_mode }}</p>
            <p>Amount Paid : {{$invoice->amount_paid}}</p>
            <p>RIB Bank : {{$company->rib_bank}}</p>
        </div>

        <div class="terms-conditions">
            <h2>Terms &amp; Conditions :</h2>
            @if ($invoice->type === 'devis')
                <p>This quote is valid for 1 month from the date of issue.</p>
            @else
                <p>This invoice is valid for 3 month from the date of issue.</p>
            @endif

        </div>

        <div class="signature">
            @if ($invoice->type === 'devis')
                <p><strong>Date and customer signature preceded</strong></p>
            <hr> @endif

        </div>

        <div class="footer">
            <p>THANK YOU FOR YOUR CONFIDENCE</p>
        </div>
    </div>
</body>

</html>
