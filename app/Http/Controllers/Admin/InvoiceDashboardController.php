<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceDashboardController extends Controller
{
    // 1. Statistiques des statuts de paiement (année actuelle)
    public function paymentStatusStats()
    {
        $year = Carbon::now()->year;

        $data = Invoice::select('payment_status', DB::raw('COUNT(*) as count'))
            ->whereYear('creation_date', $year)
            ->groupBy('payment_status')
            ->where('type', 'facture')
            ->get();

        return response()->json([
            'message' => 'Payment status statistics',
            'data' => $data
        ]);
    }

    // 2. Statistiques des types de facture (facture, facture_avoir, etc.)
    public function invoiceTypeStats()
    {
        $year = Carbon::now()->year;

        $data = Invoice::select('type', DB::raw('COUNT(*) as count'))
            ->whereYear('creation_date', $year)
            ->groupBy('type')
            ->get();

        return response()->json([
            'message' => 'Invoice type statistics',
            'data' => $data
        ]);
    }

    // 3. Statistiques des modes de paiement
    public function paymentModeStats()
    {
        $year = Carbon::now()->year;

        $data = Invoice::select('payment_mode', DB::raw('COUNT(*) as count'))
            ->whereYear('creation_date', $year)
            ->where('type', 'facture')
            ->groupBy('payment_mode')
            ->get();

        return response()->json([
            'message' => 'Payment mode statistics',
            'data' => $data
        ]);
    }
}
