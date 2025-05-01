<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class HistoriqueInvoiceController extends Controller
{
    public function index()
    {
        try {
            $invoices = Invoice::with('client')->paginate(7);
            $formattedData = collect($invoices->items())->map(function ($invoice) {
                $invoiceArray = $invoice->toArray();
                unset($invoiceArray['client']);

                return [
                    'shipment' => $invoiceArray,
                    'client_name' => $invoice->client?->name,
                ];
            });

            return response()->json([
                'message' => 'Invoices retrieved successfully',
                'data' => $formattedData,
                'meta' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getServicesByInvoice($id)
    {
        try {
            $invoice = Invoice::with('services')->find($id);

            if (!$invoice) {
                return response()->json([
                    'error' => 'Invoice not found',
                ], 404);
            }
            return response()->json([
                'message' => 'Services retrieved successfully for invoice ID: ' . $id,
                'data' => $invoice->services,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


}
