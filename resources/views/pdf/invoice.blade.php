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
            color: #000; /* Texte en noir */
            background-color: #fff;
            line-height: 1.2; /* Diminuer l'espace entre les lignes globalement */
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative; /* Permet de positionner le footer en bas */
            min-height: 100vh; /* Hauteur minimale pour occuper toute la page */
        }
        .logo-container img {
            width: 200px; /* Augmenter la largeur du logo */
            height: auto; /* Hauteur proportionnelle */
            margin-left: 500px;
            margin-top: -90px;
        }
        h1, h2 {
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px; /* Réduction de la taille du padding */
            text-align: left;
            font-size: 12px; /* Taille de police réduite */
            line-height: 1.2; /* Diminuer l'espace entre les lignes dans le tableau */
        }
        th {
            background-color: #129bf7; /* Gris clair pour l'en-tête du tableau */
            color: #ffffff; /* Texte en noir */
            font-size: 12px; /* Taille de police réduite */
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-title {
            color: #129bf7; /* Titre en noir */
            font-size: 24px; /* Taille de police pour "DEVIS" */
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 16px; /* Taille de police pour le numéro de devis */
            color: #000; /* Numéro de devis en noir */
            margin-bottom: 20px;
        }
        .company-info, .client-info {
            font-size: 12px; /* Taille de police réduite */
            color: #000; /* Informations en noir */
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
            font-size: 12px; /* Taille de police réduite */
            color: #000; /* Détails en noir */
        }
        .summary {
            margin-top: 20px;
        }
        .total-final {
            color: #fff;
            background-color: #ff332c; /* Fond noir pour le total final */
            padding: 10px;
            font-size: 14px; /* Taille de police réduite */
            text-align: center;
            border-radius: 4px;
        }
        .section-divider {
            height: 1px; /* Ligne fine en noir */
            background-color: #000; /* Couleur noire pour la ligne */
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #000; /* Pied de page en noir */
            font-size: 12px; /* Taille de police réduite */
            position: absolute; /* Fixer le footer en bas */
            bottom: 20px; /* Distance du bas */
            width: 100%; /* Largeur complète */
        }
        /* Styles spécifiques */
        .table-header th {
            padding: 8px; /* Réduction de la taille du padding */
            text-align: center;
            font-size: 12px; /* Taille de police réduite */
        }
        .payment-method, .terms-conditions {
            margin-top: 20px;
        }
        .payment-method h2, .terms-conditions h2 {
            color: #000; /* Titres en noir */
            font-size: 14px;
        }
        .signature {
            margin-top: 20px;
        }
        .signature p {
            font-weight: bold;
            color: #000; /* Signature en noir */
        }
        .signature hr {
            border: none;
            height: 1px; /* Ligne fine en noir */
            margin: 40px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête avec le titre "DEVIS", le numéro de devis et le logo -->
        <div class="header">
            <div>
                <h1 class="header-title">
                    {{ $invoice->type === 'facture' ? 'INVOICE' : 'DEVIS' }}
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

        <!-- Informations générales -->
        <div class="invoice-details">
            <p><strong>Creation Date :</strong> {{ $invoice->creation_date }}</p>
            <p><strong>{{ $invoice->additional_date_type }} :</strong> {{ $invoice->additional_date }}</p>
        </div>

        <!-- Section Divider -->
        <div class="section-divider"></div>

        <!-- Informations de l'entreprise et du client -->
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

        <!-- Tableau des services -->
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
                        <td>{{ $service->price_ht }}</td>
                        <td>{{ $service->tva }}</td>
                        <td>{{ $service->total_ttc }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Résumé financier -->
        <div class="summary">
            <table style="float: right;">
                <tr>
                    <td><strong>Price HT :</strong></td>
                    <td>{{ $totalPriceHT }} </td> <!-- Somme des prix HT -->
                </tr>
                <tr class="total-final">
                    <td><strong>TOTAL :</strong></td>
                    <td>{{ $totalPriceTTC }} </td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>

        <!-- Section Divider -->
        <div class="section-divider"></div>

        <!-- Informations de paiement -->
        <div class="payment-method">
            <h2>Payment Method :</h2>
            <p>{{ $invoice->payment_mode }}</p>
            <p>Amount Paid : {{$invoice->amount_paid}}</p>
            <p>RIB Bank : {{$company->rib_bank}}</p>
        </div>

        <!-- Termes et conditions -->
        <div class="terms-conditions">
            <h2>Terms &amp; Conditions :</h2>
            @if ($invoice->type === 'devis')
            <p>This quote is valid for 1 month from the date of issue.</p>
            @else
            <p>This invoice is valid for 3 month from the date of issue.</p>
            @endif

        </div>

        <!-- Signature -->
        <div class="signature">
            @if ($invoice->type === 'devis')
            <p><strong>Date and customer signature preceded</strong></p>
            <hr>                @endif

        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>THANK YOU FOR YOUR CONFIDENCE</p>
        </div>
    </div>
</body>
</html>
