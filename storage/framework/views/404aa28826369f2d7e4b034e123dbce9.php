<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Devis / Facture</title>
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
                    <?php switch($invoice->type):
                        case ('facture'): ?>
                            INVOICE
                            <?php break; ?>
                        <?php case ('devis'): ?>
                            QUOTE
                            <?php break; ?>
                        <?php case ('facture_avoir'): ?>
                            CREDIT NOTE
                            <?php break; ?>
                        <?php case ('facture_avoir_partiel'): ?>
                            PARTIAL CREDIT NOTE
                            <?php break; ?>
                        <?php default: ?>
                            DOCUMENT
                    <?php endswitch; ?>
                </h1>

                <p class="invoice-number"><strong>N°<?php echo e($invoice->number); ?></strong></p>

                <?php if(in_array($invoice->type, ['facture_avoir', 'facture_avoir_partiel']) && $invoice->originalInvoice): ?>
                    <p style="margin-top: 10px; font-weight: bold; color: #d9534f;">
                        This credit invoice refers to original invoice number: <strong><?php echo e($invoice->originalInvoice->number); ?></strong>
                    </p>
                <?php endif; ?>
            </div>
            <div class="logo-container">
                <?php if($companyLogoBase64): ?>
                    <img src="data:image/webp;base64,<?php echo e($companyLogoBase64); ?>" alt="Company Logo">
                <?php else: ?>
                    <p>Logo non disponible</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="invoice-details">
            <p><strong>Creation Date :</strong> <?php echo e($invoice->creation_date); ?></p>
            <?php if(!empty($invoice->additional_date_type) && !empty($invoice->additional_date)): ?>
                <p><strong><?php echo e($invoice->additional_date_type); ?> :</strong> <?php echo e($invoice->additional_date); ?></p>
            <?php endif; ?>
        </div>

        <div class="section-divider"></div>

        <div>
            <div class="company-info">
                <p><strong><?php echo e($company->name); ?></strong></p>

                <?php if(!empty($company->address)): ?>
                    <p><?php echo e($company->address); ?></p>
                <?php endif; ?>

                <?php if(!empty($company->postal_code) || !empty($company->country)): ?>
                    <p><?php echo e($company->postal_code); ?>, <?php echo e($company->country); ?></p>
                <?php endif; ?>

                <?php if(!empty($company->phone_number)): ?>
                    <p>Phone Number : <?php echo e($company->phone_number); ?></p>
                <?php endif; ?>

                <?php if(!empty($company->email)): ?>
                    <p>Email : <?php echo e($company->email); ?></p>
                <?php endif; ?>

                <?php if(!empty($company->website)): ?>
                    <p>Web Site : <?php echo e($company->website); ?></p>
                <?php endif; ?>

                <?php if($invoice->type === 'facture' && !empty($company->tva_number)): ?>
                    <p>TVA : <?php echo e($company->tva_number); ?></p>
                <?php endif; ?>

                <?php if(!empty($company->rib_bank)): ?>
                    <p>RIB Bank : <?php echo e($company->rib_bank); ?></p>
                <?php endif; ?>
            </div>

            <div class="client-info">
                <p><strong>Customer</strong></p>

                <p><?php echo e($client->name); ?></p>

                <?php if(!empty($client->address)): ?>
                    <p><?php echo e($client->address); ?></p>
                <?php endif; ?>

                <?php if(!empty($client->phone_number)): ?>
                    <p>Phone Number : <?php echo e($client->phone_number); ?></p>
                <?php endif; ?>

                <?php if(!empty($client->email)): ?>
                    <p>Email : <?php echo e($client->email); ?></p>
                <?php endif; ?>

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
                <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td>
                            <?php echo e($service->name); ?>

                            <?php if(!empty($service->comment)): ?>
                                <br><small><?php echo e($service->comment); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($service->quantity); ?></td>
                        <td><?php echo e(number_format($service->total_ht, 2, '.', ',')); ?></td>
                        <td><?php echo e(number_format($service->tva, 2, '.', ',')); ?>%</td>
                        <td><?php echo e(number_format($service->total_ttc, 2, '.', ',')); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div class="summary">
            <table style="float: right;">
                <tr>
                    <td><strong>Price HT :</strong></td>
                    <td><?php echo e(number_format($invoice->total_ht, 2, '.', ',')); ?></td>
                </tr>
                <tr>
                    <td><strong>Price TVA :</strong></td>
                    <td><?php echo e(number_format($invoice->total_tva, 2, '.', ',')); ?></td>
                </tr>
                <tr class="total-final">
                    <td><strong>TOTAL :</strong></td>
                    <td><?php echo e(number_format($invoice->total_ttc, 2, '.', ',')); ?></td>
                </tr>
                <?php if(isset($invoice->amount_paid) && $invoice->amount_paid > 0 || isset($invoice->unpaid_amount) && $invoice->unpaid_amount > 0): ?>
                    <tr>
                        <td><strong>Amount Paid :</strong></td>
                        <td><?php echo e(number_format($invoice->amount_paid, 2, '.', ',')); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Amount Unpaid :</strong></td>
                        <td><?php echo e(number_format($invoice->unpaid_amount, 2, '.', ',')); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="clear"></div>

        <div class="section-divider"></div>

        <div class="payment-method">
            <h2>Payment Method : <?php if(!empty($invoice->payment_mode)): ?>
                <p><?php echo e(ucfirst($invoice->payment_mode)); ?></p>
            <?php endif; ?></h2>
             <p>RIB Bank : <?php echo e($company->rib_bank); ?></p>
        </div>

        <div class="terms-conditions">
            <h2>Terms &amp; Conditions :</h2>
            <?php if($invoice->type === 'devis'): ?>
                <p>This quote is valid for 1 month from the date of issue.</p>
            <?php else: ?>
                <p>This invoice is valid for 3 months from the date of issue.</p>
            <?php endif; ?>

            <?php if(!empty($invoice->terms_conditions)): ?>
                <p><?php echo e($invoice->terms_conditions); ?></p>
            <?php endif; ?>
        </div>

        <div class="signature">
            <?php if($invoice->type === 'devis'): ?>
                <p><strong>Date and customer signature preceded</strong></p>
                <hr>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>THANK YOU FOR YOUR CONFIDENCE</p>
        </div>
    </div>
</body>

</html>
<?php /**PATH C:\Users\MSI\OneDrive\Documents\GitHub\BackOfficee\resources\views/pdf/invoice.blade.php ENDPATH**/ ?>