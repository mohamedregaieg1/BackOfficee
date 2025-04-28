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
                    <?php echo e($invoice->type === 'facture' ? 'FACTURE' : 'DEVIS'); ?>

                </h1>
                <p class="invoice-number"><strong>N°<?php echo e($invoice->number); ?></strong></p>
            </div>
            <div class="logo-container">
                <?php if($companyLogoBase64): ?>
                    <img src="data:image/webp;base64,<?php echo e($companyLogoBase64); ?>" alt="Company Logo">
                <?php else: ?>
                    <p>Logo non disponible</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informations générales -->
        <div class="invoice-details">
            <p><strong>Creation Date :</strong> <?php echo e($invoice->creation_date); ?></p>
            <p><strong><?php echo e($invoice->additional_date_type); ?> :</strong> <?php echo e($invoice->additional_date); ?></p>
        </div>

        <!-- Section Divider -->
        <div class="section-divider"></div>

        <!-- Informations de l'entreprise et du client -->
        <div>
            <div class="company-info">
                <p><strong><?php echo e($company->name); ?></strong></p>
                <p><?php echo e($company->address); ?></p>
                <p><?php echo e($company->postal_code); ?>,<?php echo e($company->country); ?></p>
                <p>Phone Number : <?php echo e($company->phone_number); ?></p>
                <p>Email : <?php echo e($company->email); ?></p>
                <p>Web Site : <?php echo e($company->website); ?></p>
                <?php if($invoice->type === 'facture'): ?>
                    <p>TVA : <?php echo e($company->tva_number); ?></p>
                <?php endif; ?>

            </div>
            <div class="client-info">
                <p><strong>Customer</strong></p>
                <p><?php echo e($client->name); ?></p>
                <p><?php echo e($client->address); ?></p>
                <p>Phone Number : <?php echo e($client->phone_number); ?></p>
                <p>Email : <?php echo e($client->email); ?></p>
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
                <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($service->name); ?><br><small><?php echo e($service->comment); ?></small></td>
                        <td><?php echo e($service->quantity); ?></td>
                        <td><?php echo e($service->price_ht); ?></td>
                        <td><?php echo e($service->tva); ?></td>
                        <td><?php echo e($service->total_ttc); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <!-- Résumé financier -->
        <div class="summary">
            <table style="float: right;">
                <tr>
                    <td><strong>Price HT :</strong></td>
                    <td><?php echo e($totalPriceHT); ?> </td> <!-- Somme des prix HT -->
                </tr>
                <tr class="total-final">
                    <td><strong>TOTAL :</strong></td>
                    <td><?php echo e($totalPriceTTC); ?> </td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>

        <!-- Section Divider -->
        <div class="section-divider"></div>

        <!-- Informations de paiement -->
        <div class="payment-method">
            <h2>Payment Method :</h2>
            <p><?php echo e($invoice->payment_mode); ?></p>
            <p>Amount Paid : <?php echo e($invoice->amount_paid); ?></p>
            <p>RIB Bank : <?php echo e($company->rib_bank); ?></p>
        </div>

        <!-- Termes et conditions -->
        <div class="terms-conditions">
            <h2>Terms &amp; Conditions :</h2>
            <?php if($invoice->type === 'devis'): ?>
            <p>This quote is valid for 1 month from the date of issue.</p>
            <?php else: ?>
            <p>This invoice is valid for 3 month from the date of issue.</p>
            <?php endif; ?>

        </div>

        <!-- Signature -->
        <div class="signature">
            <?php if($invoice->type === 'devis'): ?>
            <p><strong>Date and customer signature preceded</strong></p>
            <hr>                <?php endif; ?>

        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>THANK YOU FOR YOUR CONFIDENCE</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\MSI\OneDrive\Documents\GitHub\BackOfficee\resources\views/pdf/invoice.blade.php ENDPATH**/ ?>