<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = Carbon::now()->year;
        $paymentModes = ['bank transfer', 'credit card', 'cash', 'paypal', 'cheque', 'other'];
        $paymentStatuses = ['paid', 'partially paid', 'unpaid'];
        $types = ['facture', 'devis', 'facture_avoir', 'facture_avoir_partiel'];
        $additionalDateTypes = ['Date of sale', 'Expiry date', 'Withdrawal date until'];
        $companies = ['company X', 'company XX'];

        $invoices = [];
        $invoiceCounter = [
            'facture' => 0,
            'devis' => 0,
            'facture_avoir' => 0,
            'facture_avoir_partiel' => 0,
        ];

        // Créer au moins 25 factures
        for ($i = 0; $i < 30; $i++) {
            $type = $types[array_rand($types)];
            $invoiceCounter[$type]++;

            // Choisir une company aléatoire
            $companyName = $companies[array_rand($companies)];
            $companyId = ($companyName === 'Procan') ? 1 : 2;

            // Date de création dans l'année courante
            $creationDate = Carbon::create($currentYear, rand(1, Carbon::now()->month), rand(1, 28));

            // Générer le numéro de facture
            $prefix = match($type) {
                'facture' => 'F',
                'devis' => 'D',
                'facture_avoir' => 'FA',
                'facture_avoir_partiel' => 'FAP',
            };
            $number = $prefix . '-' . $creationDate->format('mY') . '-' . str_pad($invoiceCounter[$type], 5, '0', STR_PAD_LEFT);

            // Client (1-10 pour individuels, 11-20 pour professionnels)
            $clientId = rand(1, 20);

            // Additional date
            $additionalDateType = $additionalDateTypes[array_rand($additionalDateTypes)];
            $additionalDate = $creationDate->copy()->addDays(rand(15, 90));

            // Due date
            $dueDate = $creationDate->copy()->addDays(rand(30, 120));

            // Payment mode
            $paymentMode = $paymentModes[array_rand($paymentModes)];

            // Calcul des montants HT, TVA, TTC
            $totalHT = round(rand(100, 10000) + rand(0, 99) / 100, 2);
            $tvaRate = 0.19; // 19%
            $totalTVA = round($totalHT * $tvaRate, 2);
            $totalTTC = round($totalHT + $totalTVA, 2);

            // Statut de paiement et calcul des montants payés/non payés
            $paymentStatus = $paymentStatuses[array_rand($paymentStatuses)];

            switch ($paymentStatus) {
                case 'paid':
                    $amountPaid = $totalTTC;
                    $unpaidAmount = 0.00;
                    break;

                case 'partially paid':
                    // Payer entre 30% et 80% du total
                    $percentagePaid = rand(30, 80) / 100;
                    $amountPaid = round($totalTTC * $percentagePaid, 2);
                    $unpaidAmount = round($totalTTC - $amountPaid, 2);
                    break;

                case 'unpaid':
                    $amountPaid = 0.00;
                    $unpaidAmount = $totalTTC;
                    break;

                default:
                    $amountPaid = $totalTTC;
                    $unpaidAmount = 0.00;
            }

            // Pour les factures d'avoir, ajuster les montants
            if ($type == 'facture_avoir' || $type == 'facture_avoir_partiel') {
                // Réduire les montants de 30% à 70%
                $reductionRate = rand(30, 70) / 100;
                $totalTTC = round($totalTTC * $reductionRate, 2);
                $totalTVA = round($totalTVA * $reductionRate, 2);
                $totalHT = round($totalHT * $reductionRate, 2);

                // Recalculer les paiements
                switch ($paymentStatus) {
                    case 'paid':
                        $amountPaid = $totalTTC;
                        $unpaidAmount = 0.00;
                        break;

                    case 'partially paid':
                        $percentagePaid = rand(30, 80) / 100;
                        $amountPaid = round($totalTTC * $percentagePaid, 2);
                        $unpaidAmount = round($totalTTC - $amountPaid, 2);
                        break;

                    case 'unpaid':
                        $amountPaid = 0.00;
                        $unpaidAmount = $totalTTC;
                        break;
                }
            }

            $invoices[] = [
                'type' => $type,
                'creation_date' => $creationDate,
                'number' => $number,
                'additional_date_type' => $additionalDateType,
                'additional_date' => $additionalDate,
                'company_name' => $companyName,
                'company_id' => $companyId,
                'client_id' => $clientId,
                'payment_mode' => $paymentMode,
                'due_date' => $dueDate,
                'payment_status' => $paymentStatus,
                'amount_paid' => $amountPaid,
                'unpaid_amount' => $unpaidAmount,
                'total_ttc' => $totalTTC,
                'total_tva' => $totalTVA,
                'total_ht' => $totalHT,
                'original_invoice_id' => null,
                'created_at' => $creationDate,
                'updated_at' => $creationDate,
            ];
        }

        // Insérer toutes les factures
        DB::table('invoices')->insert($invoices);

        // Créer des relations original_invoice_id pour facture_avoir et facture_avoir_partiel
        $avoirInvoices = DB::table('invoices')
            ->whereIn('type', ['facture_avoir', 'facture_avoir_partiel'])
            ->get();

        $factures = DB::table('invoices')
            ->where('type', 'facture')
            ->get();

        foreach ($avoirInvoices as $avoir) {
            if ($factures->isNotEmpty()) {
                $originalInvoice = $factures->random();
                DB::table('invoices')
                    ->where('id', $avoir->id)
                    ->update(['original_invoice_id' => $originalInvoice->id]);
            }
        }

        // Log des statistiques
        $counts = DB::table('invoices')
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->get();

        echo "\n=== Statistiques des factures créées ===\n";
        foreach ($counts as $count) {
            echo "{$count->type}: {$count->total}\n";
        }
        echo "Total: " . count($invoices) . " factures\n";

        $statusCounts = DB::table('invoices')
            ->select('payment_status', DB::raw('count(*) as total'))
            ->groupBy('payment_status')
            ->get();

        echo "\n=== Statuts de paiement ===\n";
        foreach ($statusCounts as $status) {
            echo "{$status->payment_status}: {$status->total}\n";
        }

        $companyCounts = DB::table('invoices')
            ->select('company_name', DB::raw('count(*) as total'))
            ->groupBy('company_name')
            ->get();

        echo "\n=== Répartition par company ===\n";
        foreach ($companyCounts as $company) {
            echo "{$company->company_name}: {$company->total}\n";
        }
    }
}
