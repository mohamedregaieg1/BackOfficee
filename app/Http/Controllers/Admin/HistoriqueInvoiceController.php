<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistoriqueInvoiceController extends Controller
{
    public function index()
    {
        try {
            $startDate = request()->input('start_date');
            $endDate = request()->input('end_date');
            $search = request()->input('search');
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

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', '%' . $search . '%')
                        ->orWhereHas('client', function ($q2) use ($search) {
                            $q2->where('name', 'like', '%' . $search . '%');
                        });
                });
            }

            if ($sortByPaymentStatus) {
                $query->orderByRaw("FIELD(payment_status, 'paid', 'partially paid', 'unpaid')");
            } else {
                $query->orderBy('created_at', 'desc');
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
                'amount_paid' => 'required|numeric|min:0',
            ]);

            $invoice = Invoice::findOrFail($invoiceId);
            $status = $invoice->payment_status;
            $totalTTC = $invoice->total_ttc;
            $inputAmount = $request->input('amount_paid');

            // ----- Condition 1 : Paid -----
            if ($status === 'paid') {
                return response()->json([
                    'error' => 'Invoice is fully paid. No updates allowed.'
                ], 403);
            }

            // ----- Condition 2 : Unpaid -----
            if ($status === 'unpaid') {
                $newAmountPaid = $invoice->amount_paid + $inputAmount;

                if ($newAmountPaid > $totalTTC) {
                    return response()->json([
                        'error' => 'Amount paid cannot exceed total TTC.'
                    ], 422);
                }

                $invoice->amount_paid = $newAmountPaid;
                $invoice->unpaid_amount = $totalTTC - $newAmountPaid;

                $invoice->payment_status = ($newAmountPaid == $totalTTC) ? 'paid' : 'partially paid';
            }

            // ----- Condition 3 : Partially Paid -----
            if ($status === 'partially paid') {
                $newAmountPaid = $invoice->amount_paid + $inputAmount;

                if ($newAmountPaid > $totalTTC) {
                    return response()->json([
                        'error' => 'Amount paid cannot exceed total TTC.'
                    ], 422);
                }

                $invoice->amount_paid = $newAmountPaid;
                $invoice->unpaid_amount = $totalTTC - $newAmountPaid;

                $invoice->payment_status = ($newAmountPaid == $totalTTC) ? 'paid' : 'partially paid';
            }

            $invoice->save();

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

    }


    private function generateUniqueInvoiceNumber($type, $date)
    {
        $prefix = $type === 'facture_avoir' ? 'AV' : 'F';
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

    public function transferAVP(Request $request)
    {
        $request->validate([
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'required|integer|exists:services,id',
            'total_ht' => 'required|numeric',
            'total_tva' => 'required|numeric',
            'total_ttc' => 'required|numeric',
        ]);

        $serviceIds = $request->input('service_ids');
        $services = Service::whereIn('id', $serviceIds)->get();

        if ($services->isEmpty()) {
            return response()->json(['error' => 'No valid services found.'], 404);
        }

        $originalInvoice = $services->first()->invoice;
        $now = now();
        $monthYear = $now->format('mY');
        $count = Invoice::where('type', 'facture_avoir_partiel')
            ->where('number', 'like', "AVP-$monthYear%")
            ->count() + 1;
        $formattedCount = str_pad($count, 5, '0', STR_PAD_LEFT);
        $avpNumber = "AVP-$monthYear-$formattedCount";

        $avpInvoice = $originalInvoice->replicate();
        $avpInvoice->type = 'facture_avoir_partiel';
        $avpInvoice->number = $avpNumber;
        $avpInvoice->creation_date = $now;
        $avpInvoice->total_ht = $request->input('total_ht');
        $avpInvoice->total_tva = $request->input('total_tva');
        $avpInvoice->total_ttc = $request->input('total_ttc');
        $avpInvoice->original_invoice_id = $originalInvoice->id;
        $avpInvoice->save();
        foreach ($services as $service) {
            $avpService = $service->replicate();
            $avpService->invoice_id = $avpInvoice->id;
            $avpService->price_ht = -abs($service->price_ht);
            $avpService->tva = -abs($service->tva);
            $avpService->total_ht = -abs($service->total_ht);
            $avpService->total_ttc = -abs($service->total_ttc);
            $avpService->save();
        }

        return response()->json([
            'message' => 'AVP invoice created successfully.',
            'invoice' => $avpInvoice,
        ], 201);
    }


}
