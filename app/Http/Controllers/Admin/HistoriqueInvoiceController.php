<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Models\HistoriqueInvoice;

class HistoriqueInvoiceController extends Controller
{
    public function index()
    {
        try {
            $startDate = request()->input('start_date');
            $endDate = request()->input('end_date');
            $clientName = request()->input('client_name');
            $sortByPaymentStatus = request()->boolean('sort_by_payment_status');

            if ($endDate && !$startDate) {
                return response()->json([
                    'error' => 'start_date is required when end_date is provided.',
                ], 422);
            }

            $query = Invoice::with('client');

            if ($startDate && $endDate) {
                $query->whereBetween('creation_date', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->whereDate('creation_date', '>=', $startDate);
            }

            if ($clientName) {
                $query->whereHas('client', function ($q) use ($clientName) {
                    $q->where('name', 'like', '%' . $clientName . '%');
                });
            }

            if ($sortByPaymentStatus) {
                $query->orderByRaw("FIELD(payment_status, 'paid', 'partially paid', 'unpaid')");
            }

            $invoices = $query->paginate(7);

            $formattedData = collect($invoices->items())->map(function ($invoice) {
                $invoiceArray = $invoice->toArray();
                unset($invoiceArray['client']);

                $originalInvoiceNumber = null;
                if ($invoice->original_invoice_id) {
                    $originalInvoice = Invoice::find($invoice->original_invoice_id);
                    $originalInvoiceNumber = $originalInvoice?->number;
                }

                return [
                    'invoice' => $invoiceArray,
                    'client_name' => $invoice->client?->name,
                    'original_invoice_number' => $originalInvoiceNumber,
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

    public function updatePaymentStatus(Request $request, $invoiceId)
    {
        try {
            $request->validate([
                'amount_paid' => 'nullable|numeric|min:0',
                'unpaid_amount' => 'nullable|numeric|min:0',
            ]);

            $invoice = Invoice::findOrFail($invoiceId);

            $status = $invoice->payment_status;
            $totalTTC = $invoice->total_ttc;
            $amountPaid = $request->input('amount_paid', $invoice->amount_paid);
            $unpaidAmount = $request->input('unpaid_amount', $invoice->unpaid_amount);

            if ($status === 'paid') {
                return response()->json([
                    'error' => 'Invoice is fully paid. No updates allowed.'
                ], 403);
            }

            if ($status === 'unpaid') {
                if ($amountPaid > $totalTTC) {
                    return response()->json([
                        'error' => 'Amount paid cannot exceed total TTC.'
                    ], 422);
                }

                $invoice->amount_paid = $amountPaid;
            }

            if ($status === 'partially paid') {
                if ($amountPaid < 0 || $unpaidAmount < 0) {
                    return response()->json([
                        'error' => 'Amounts cannot be negative.'
                    ], 422);
                }

                if (($amountPaid + $unpaidAmount) > $totalTTC) {
                    return response()->json([
                        'error' => 'Combined amounts cannot exceed total TTC.'
                    ], 422);
                }

                $invoice->amount_paid = $amountPaid;
                $invoice->unpaid_amount = $unpaidAmount;
            }

            $invoice->save();

            HistoriqueInvoice::create([
                'invoice_id' => $invoice->id,
                'old_invoice_id' => null,
                'changes' => json_encode([
                    'payment_status_updated' => true,
                    'new_values' => [
                        'amount_paid' => $invoice->amount_paid,
                        'unpaid_amount' => $invoice->unpaid_amount,
                    ],
                ]),
            ]);

            return response()->json([
                'message' => 'Invoice payment information updated successfully.',
                'invoice' => $invoice,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getHistoriqueByInvoiceId($id)
    {
        try {
            $historiqueRecords = HistoriqueInvoice::where('invoice_id', $id)
                ->orWhere('old_invoice_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($historiqueRecords->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun historique trouvé pour cette facture.',
                    'data' => [],
                ], 404);
            }

            $formattedData = $historiqueRecords->map(function ($historique) {
                return [
                    'id' => $historique->id,
                    'invoice_id' => $historique->invoice_id,
                    'old_invoice_id' => $historique->old_invoice_id,
                    'changes' => json_decode($historique->changes, true),
                    'created_at' => $historique->created_at,
                ];
            });

            return response()->json([
                'message' => 'Historique récupéré avec succès.',
                'data' => $formattedData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue.',
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

    public function updateService(Request $request)
    {
        try {
            $request->validate([
                'services' => 'required|array|min:1',
                'services.*.id' => 'required|integer|exists:services,id',
                'services.*.name' => 'nullable|string|max:255',
                'services.*.quantity' => 'nullable|numeric|min:0',
                'services.*.unit' => 'nullable|string|max:50',
                'services.*.price_ht' => 'nullable|numeric|min:0',
                'services.*.tva' => 'nullable|numeric|min:0',
                'services.*.total_ht' => 'nullable|numeric|min:0',
                'services.*.total_ttc' => 'nullable|numeric|min:0',
                'services.*.comment' => 'nullable|string',
                'TTotal_HT' => 'required|numeric',
                'TTotal_TVA' => 'required|numeric',
                'TTotal_TTC' => 'required|numeric',
            ]);

            $firstService = Service::findOrFail($request->input('services.0.id'));
            $invoice = Invoice::findOrFail($firstService->invoice_id);

            if ($invoice->type === 'facture') {
                [$factureAvoir, $newInvoice] = $this->createAvoirAndUpdatedInvoiceForMultiple($invoice, $request->input('services'), $request);
            } elseif ($invoice->type === 'devis') {
                $this->updateDevisAndServices($invoice, $request->input('services'), $request);
            }

            return response()->json([
                'message' => 'Services updated successfully via avoir and new facture',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function createAvoirAndUpdatedInvoiceForMultiple(Invoice $originalInvoice, array $servicesData, Request $request)
    {
        $now = now();
        $avoirInvoice = $originalInvoice->replicate();
        $avoirInvoice->number = $this->generateUniqueInvoiceNumber('facture_avoir', now());
        $avoirInvoice->type = 'facture_avoir';
        $avoirInvoice->creation_date = $now;
        $avoirInvoice->total_ht = -$originalInvoice->total_ht;
        $avoirInvoice->total_tva = -$originalInvoice->total_tva;
        $avoirInvoice->total_ttc = -$originalInvoice->total_ttc;
        $avoirInvoice->original_invoice_id = $originalInvoice->id;
        $avoirInvoice->save();

        $newInvoice = $originalInvoice->replicate();
        $newInvoice->number = $this->generateUniqueInvoiceNumber('facture', now());
        $newInvoice->type = 'facture';
        $newInvoice->creation_date = $now;
        $newInvoice->total_ht = $request->input('TTotal_HT');
        $newInvoice->total_tva = $request->input('TTotal_TVA');
        $newInvoice->total_ttc = $request->input('TTotal_TTC');
        $newInvoice->original_invoice_id = $originalInvoice->id;
        $newInvoice->save();

        foreach ($servicesData as $serviceData) {
            $originalService = Service::findOrFail($serviceData['id']);

            $avoirService = $originalService->replicate();
            $avoirService->invoice_id = $avoirInvoice->id;
            $avoirService->quantity = -$originalService->quantity;
            $avoirService->price_ht = -$originalService->price_ht;
            $avoirService->tva = -$originalService->tva;
            $avoirService->total_ht = -$originalService->total_ht;
            $avoirService->total_ttc = -$originalService->total_ttc;
            $avoirService->save();

            $newService = $originalService->replicate();
            $newService->invoice_id = $newInvoice->id;

            foreach (['name', 'quantity', 'unit', 'price_ht', 'tva', 'total_ht', 'total_ttc', 'comment'] as $field) {
                if (isset($serviceData[$field])) {
                    $newService->$field = $serviceData[$field];
                }
            }
            $newService->save();
        }

        HistoriqueInvoice::create([
            'invoice_id' => $avoirInvoice->id,
            'old_invoice_id' => $originalInvoice->id,
            'changes' => json_encode([
                'facture_avoir_created' => true,
                'reason' => 'Multiple services updated via avoir',
            ]),
        ]);

        HistoriqueInvoice::create([
            'invoice_id' => $newInvoice->id,
            'old_invoice_id' => $originalInvoice->id,
            'changes' => json_encode([
                'facture_updated_created' => true,
                'services_updated' => $servicesData,
            ]),
        ]);

        return [$avoirInvoice, $newInvoice];
    }
    private function updateDevisAndServices(Invoice $invoice, array $servicesData, Request $request)
    {
        foreach ($servicesData as $serviceData) {
            $service = Service::findOrFail($serviceData['id']);

            foreach (['name', 'quantity', 'unit', 'price_ht', 'tva', 'total_ht', 'total_ttc', 'comment'] as $field) {
                if (isset($serviceData[$field])) {
                    $service->$field = $serviceData[$field];
                }
            }

            $service->save();
        }

        $invoice->total_ht = $request->input('TTotal_HT');
        $invoice->total_tva = $request->input('TTotal_TVA');
        $invoice->total_ttc = $request->input('TTotal_TTC');
        $invoice->save();

        HistoriqueInvoice::create([
            'invoice_id' => $invoice->id,
            'old_invoice_id' => null,
            'changes' => json_encode([
                'devis_updated' => true,
                'services_updated' => $servicesData,
                'totals_updated' => [
                    'total_ht' => $request->input('TTotal_HT'),
                    'total_tva' => $request->input('TTotal_TVA'),
                    'total_ttc' => $request->input('TTotal_TTC'),
                ]
            ]),
        ]);
    }


    private function generateUniqueInvoiceNumber($type, $date)
    {
        $prefix = $type === 'facture_avoir' ? 'FAV' : 'F';
        $baseNumber = $prefix . '-' . $date->format('mY') . '-';

        $lastInvoice = Invoice::where('number', 'like', $baseNumber . '%')
            ->orderBy('number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastIncrement = intval(substr($lastInvoice->number, -5));
            $newIncrement = str_pad($lastIncrement + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newIncrement = '00001';
        }

        return $baseNumber . $newIncrement;
    }
}
