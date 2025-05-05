<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Models\HistoriqueShipment;

class HistoriqueInvoiceController extends Controller
{
    public function index()
    {
        try {
            $year = request()->input('year');
            $month = request()->input('month');
            $type = request()->input('type');

            $query = Invoice::with(['client', 'historiqueShipments']);

            if ($year) {
                $query->whereYear('creation_date', $year);
            }

            if ($month) {
                $query->whereMonth('creation_date', $month);
            }

            if ($type) {
                $query->where('type', $type);
            }

            $invoices = $query->paginate(7);

            $formattedData = collect($invoices->items())->map(callback: function ($invoice) {
                $invoiceArray = $invoice->toArray();
                unset($invoiceArray['client']);
                unset($invoiceArray['historique_shipments']);

                $mergedChanges = [];
                foreach ($invoice->historiqueShipments as $historique) {
                    $changes = json_decode($historique->changes, true);
                    foreach ($changes as $key => $change) {
                        if (!isset($mergedChanges[$key])) {
                            $mergedChanges[$key] = [
                                'old_value' => $change['old_value'],
                                'new_value' => $change['new_value'],
                            ];
                        } else {
                            if ($mergedChanges[$key]['new_value'] !== $change['new_value']) {
                                $mergedChanges[$key]['old_value'] = $mergedChanges[$key]['new_value'];
                                $mergedChanges[$key]['new_value'] = $change['new_value'];
                            }
                        }
                    }
                }
                $response = [
                    'invoice' => $invoiceArray,
                    'client_name' => $invoice->client?->name,
                ];

                if (!empty($mergedChanges)) {
                    $response['historiques'] = [
                        'changes' => $mergedChanges,
                        'created_at' => $invoice->created_at,
                    ];
                }

                return $response;
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
    public function update(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            $newInvoice = $invoice->replicate();
            $newInvoice->number = $this->generateUniqueInvoiceNumber($invoice->type, now());
            $newInvoice->save();
            $updatableFields = [
                'unpaid_amount',
                'amount_paid',
                'payment_status',
                'due_date',
                'payment_mode',
                'client_id',
                'company_id',
                'additional_date'
            ];

            foreach ($updatableFields as $field) {
                if ($request->has($field)) {
                    $newInvoice->$field = $request->input($field);
                }
            }

            if ($request->has('company_id')) {
                $companyId = $request->input('company_id');
                $company = \App\Models\Company::find($companyId);

                if ($company) {
                    $newInvoice->company_name = $company->name;
                } else {
                    return response()->json([
                        'error' => 'Invalid company_id provided.',
                    ], 400);
                }
            }

            if ($invoice->type === 'facture') {
                $this->convertToFactureAvoir($newInvoice);
            } elseif ($request->has('type') && in_array($request->input('type'), ['devis', 'facture'])) {
                $newInvoice->type = $request->input('type');
            }

            $newInvoice->original_invoice_id = $invoice->id;
            $newInvoice->save();
            $changes = [];
            foreach ($updatableFields as $field) {
                if ($request->has($field)) {
                    $oldValue = $invoice->$field;
                    $newValue = $request->input($field);
                    if ($oldValue !== $newValue) {
                        $changes[$field] = [
                            'old_value' => $oldValue,
                            'new_value' => $newValue,
                        ];
                    }
                }
            }

            if ($invoice->type !== $newInvoice->type) {
                $changes['type'] = [
                    'old_value' => $invoice->type,
                    'new_value' => $newInvoice->type,
                ];
            }

            HistoriqueShipment::create([
                'invoice_id' => $newInvoice->id,
                'old_invoice_id' => $invoice->id,
                'changes' => json_encode($changes),
            ]);

            return response()->json([
                'message' => 'Invoice updated successfully',
                'data' => $newInvoice,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    private function convertToFactureAvoir(Invoice $invoice)
    {
        $newNumber = $this->generateUniqueInvoiceNumber('facture_avoir', now());
        $invoice->type = 'facture_avoir';
        $invoice->number = $newNumber;
        $invoice->save();
    }
    private function generateUniqueInvoiceNumber($type, $date)
    {
        $baseNumber = "FAV-" . $date->format('mY') . "-";

        $lastInvoice = Invoice::where('number', 'like', $baseNumber . '%')
            ->orderBy('number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastIncrement = intval(substr($lastInvoice->number, -5));
            $newIncrement = str_pad($lastIncrement + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newIncrement = "00001";
        }
        return $baseNumber . $newIncrement;
    }

    public function updateService(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'nullable|string|max:255',
                'quantity' => 'nullable|numeric|min:0',
                'unit' => 'nullable|string|max:50',
                'price_ht' => 'nullable|numeric|min:0',
                'tva' => 'nullable|numeric|min:0',
                'total_ht' => 'nullable|numeric|min:0',
                'total_ttc' => 'nullable|numeric|min:0',
                'comment' => 'nullable|string',
                'TTotal_HT' => 'nullable|numeric|min:0',
                'TTotal_TVA' => 'nullable|numeric|min:0',
                'TTotal_TTC' => 'nullable|numeric|min:0',
            ]);

            $service = Service::findOrFail($id);
            $updatableFields = [
                'name',
                'quantity',
                'unit',
                'price_ht',
                'tva',
                'total_ht',
                'total_ttc',
                'comment'
            ];
            $changes = [];
            foreach ($updatableFields as $field) {
                if ($request->has($field)) {
                    $oldValue = $service->$field;
                    $newValue = $request->input($field);
                    if ($oldValue !== $newValue) {
                        $service->$field = $newValue;
                        $changes[$field] = [
                            'old_value' => $oldValue,
                            'new_value' => $newValue,
                        ];
                    }
                }
            }

            $service->save();
            $invoice = Invoice::find($service->invoice_id);

            if (!$invoice) {
                return response()->json([
                    'error' => 'No associated invoice found for this service.',
                ], 400);
            }
            if ($invoice->type === 'facture') {
                $factureAvoir = Invoice::where('original_invoice_id', $invoice->id)->first();
                $newInvoice = null;

                if ($factureAvoir) {
                    $invoiceChanges = [];
                    if ($request->has('TTotal_HT')) {
                        $oldHT = $factureAvoir->total_ht;
                        $newHT = $request->input('TTotal_HT');
                        $factureAvoir->total_ht = $newHT;
                        $invoiceChanges['TTotal_HT'] = [
                            'old_value' => $oldHT,
                            'new_value' => $newHT,
                        ];
                    }
                    if ($request->has('TTotal_TVA')) {
                        $oldTVA = $factureAvoir->total_tva;
                        $newTVA = $request->input('TTotal_TVA');
                        $factureAvoir->total_tva = $newTVA;
                        $invoiceChanges['TTotal_TVA'] = [
                            'old_value' => $oldTVA,
                            'new_value' => $newTVA,
                        ];
                    }
                    if ($request->has('TTotal_TTC')) {
                        $oldTTC = $factureAvoir->total_ttc;
                        $newTTC = $request->input('TTotal_TTC');
                        $factureAvoir->total_ttc = $newTTC;
                        $invoiceChanges['TTotal_TTC'] = [
                            'old_value' => $oldTTC,
                            'new_value' => $newTTC,
                        ];
                    }

                    $factureAvoir->save();
                    if (!empty($invoiceChanges)) {
                        HistoriqueShipment::create([
                            'invoice_id' => $factureAvoir->id,
                            'old_invoice_id' => $invoice->id,
                            'changes' => json_encode($invoiceChanges),
                        ]);
                    }
                } else {
                    $newInvoice = $invoice->replicate();
                    $newInvoice->number = $this->generateUniqueInvoiceNumber($invoice->type, now());

                    if ($request->has('TTotal_HT')) {
                        $newInvoice->total_ht = $request->input('TTotal_HT');
                    }
                    if ($request->has('TTotal_TVA')) {
                        $newInvoice->total_tva = $request->input('TTotal_TVA');
                    }
                    if ($request->has('TTotal_TTC')) {
                        $newInvoice->total_ttc = $request->input('TTotal_TTC');
                    }

                    if ($invoice->type === 'facture') {
                        $this->convertToFactureAvoir($newInvoice);
                    } elseif ($request->has('type') && in_array($request->input('type'), ['devis', 'facture'])) {
                        $newInvoice->type = $request->input('type');
                    }

                    $newInvoice->save();
                    HistoriqueShipment::create([
                        'invoice_id' => $newInvoice->id,
                        'old_invoice_id' => $invoice->id,
                        'changes' => json_encode([
                            'TTotal_HT' => ['old_value' => $invoice->total_ht, 'new_value' => $newInvoice->total_ht],
                            'TTotal_TVA' => ['old_value' => $invoice->total_tva, 'new_value' => $newInvoice->total_tva],
                            'TTotal_TTC' => ['old_value' => $invoice->total_ttc, 'new_value' => $newInvoice->total_ttc],
                        ]),
                    ]);
                }
            }

            if (!empty($changes)) {
                $invoiceId = $invoice->id;
                $oldInvoiceId = null;
                if (isset($factureAvoir)) {
                    $invoiceId = $factureAvoir->id;
                    $oldInvoiceId = $invoice->id;
                }

                if (isset($newInvoice)) {
                    $invoiceId = $newInvoice->id;
                    $oldInvoiceId = $invoice->id;
                }
                HistoriqueShipment::create([
                    'invoice_id' => $invoiceId,
                    'old_invoice_id' => $oldInvoiceId,
                    'changes' => json_encode($changes),
                ]);
            }

            return response()->json([
                'message' => 'Service updated successfully',
                'data' => $service,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
