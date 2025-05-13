<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function stepOne(Request $request)
    {
        try {
            $companies = Company::all();

            $validated = Validator::make($request->all(), [
                'type' => 'required|in:facture,devis',
                'creation_date' => 'required|date',
                'additional_date_type' => 'nullable|in:Date of sale,Expiry date,Withdrawal date until',
                'additional_date' => 'nullable|date',
                'company_name' => 'required|exists:companies,name',
                'number' => ['required', 'string', 'unique:invoices,number', 'regex:/^(F-|D-)[0-9]{6}$/', 'size:8'],
            ], [
                'additional_date_type.required_if' => 'Le champ additional_date_type est requis si additional_date est présent.',
                'number.regex' => 'Le numéro de facture doit commencer par "F-" ou "D-" et avoir une longueur de 8 caractères.',
            ]);

            if ($validated->fails()) {
                return response()->json($validated->errors(), 422);
            }

            $selectedCompany = Company::where('name', $request->company_name)->first();

            if (!$selectedCompany) {
                return response()->json(['message' => 'Company not found'], 404);
            }

            $year = date('Y', strtotime($request->creation_date));
            $increment = DB::table('invoices')
                ->where('type', $request->type)
                ->whereRaw('DATE_FORMAT(created_at, "%Y") = ?', [$year])
                ->count() + 1;

            $incrementFormatted = str_pad($increment, 5, '0', STR_PAD_LEFT);
            $finalNumber = "{$request->number}-{$incrementFormatted}";
            $data = $validated->validated();
            $data['number'] = $finalNumber;

            $cookieData = json_encode($data + ['company' => $selectedCompany]);
            $cookie = cookie('invoice_step1', $cookieData, 120);

            return response()->json([
                'message' => 'Step 1 completed successfully',
                'data' => json_decode($cookieData, true)
            ])->cookie($cookie);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



    public function getAllClients(Request $request)
    {
        try {
            if ($request->has('name')) {
                $client = Client::where('name', $request->name)->first();

                if (!$client) {
                    return response()->json([
                        'error' => 'Client not found.',
                    ], 404);
                }

                return response()->json([
                    'id' => $client->id,
                    'name' => $client->name,
                ]);
            } else {
                $clients = Client::select('id', 'name')->get();

                return response()->json($clients);
            }
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getClientById($id)
    {
        try {
            $client = Client::find($id);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client introuvable.',
                ], 404);
            }

            $response = [
                'id' => $client->id,
            ];

            if ($client->client_type === 'individual') {
                $nameParts = explode(' ', $client->name, 3);
                $response['civility'] = $nameParts[0] ?? null;
                $response['first_name'] = $nameParts[1] ?? null;
                $response['last_name'] = $nameParts[2] ?? null;
            } else {
                $response['name'] = $client->name;
            }

            foreach ($client->getAttributes() as $key => $value) {
                if (!in_array($key, ['id', 'name'])) {
                    $response[$key] = $value;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Client récupéré avec succès.',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue s’est produite.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function stepTwo(Request $request)
    {
        try {
            $countries = ['Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cambodia', 'Cameroon', 'Canada', 'Cape Verde', 'Central African Republic', 'Chad', 'Chile', 'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia', 'Fiji', 'Finland', 'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 'Korea (North)', 'Korea (South)', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau', 'Palestine', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Sweden', 'Switzerland', 'Syria', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe'];

            $validated = Validator::make($request->all(), [
                'client_type' => 'required|in:professional,individual',
                'name' => 'required_if:client_type,professional',
                'client_id' => 'sometimes|required_if:client_type,individual|exists:clients,id',
                'civility' => 'sometimes|required_if:client_type,individual|',
                'first_name' => 'sometimes|required_if:client_type,individual|',
                'last_name' => 'sometimes|required_if:client_type,individual|',
                'tva_number_client' => 'nullable|numeric',
                'address' => 'required|string',
                'postal_code' => 'required|string',
                'rib_bank' => 'nullable|string',
                'country' => 'required|in:' . implode(',', $countries),
                'email' => 'required|email',
                'phone_number' => 'required|string|max:15',
            ])->validate();

            $data = [];

            // === Cas INDIVIDUAL ===
            if ($request->client_type === 'individual') {
                $validated['tva_number_client'] = null;

                if ($request->client_id) {
                    $existingClient = Client::find($request->client_id);
                    if ($existingClient) {
                        $data = [
                            'client_id' => $existingClient->id,
                            'name' => $existingClient->name,
                            'client_type' => $existingClient->client_type,
                            'address' => $existingClient->address,
                            'postal_code' => $existingClient->postal_code,
                            'rib_bank' => $existingClient->rib_bank,
                            'country' => $existingClient->country,
                            'email' => $existingClient->email,
                            'phone_number' => $existingClient->phone_number,
                        ];
                    } else {
                        return response()->json(['error' => 'Client not found'], 404);
                    }
                } else {
                    // Nouveau client individuel (non enregistré ici, juste mis en cookie)
                    $name = $request->civility . ' ' . $request->first_name . ' ' . $request->last_name;
                    $data = [
                        'name' => $name,
                        'client_type' => 'individual',
                        'address' => $request->address,
                        'postal_code' => $request->postal_code,
                        'rib_bank' => $request->rib_bank,
                        'country' => $request->country,
                        'email' => $request->email,
                        'phone_number' => $request->phone_number,
                    ];
                }
            }

            // === Cas PROFESSIONAL ===
            if ($request->client_type === 'professional') {
                $existingClient = Client::where('client_type', 'professional')
                    ->where('name', $request->name)
                    ->where('email', $request->email)
                    ->first();

                if ($existingClient) {
                    $data = [
                        'client_id' => $existingClient->id,
                        'name' => $existingClient->name,
                        'client_type' => $existingClient->client_type,
                        'tva_number_client' => $existingClient->tva_number_client,
                        'address' => $existingClient->address,
                        'postal_code' => $existingClient->postal_code,
                        'rib_bank' => $existingClient->rib_bank,
                        'country' => $existingClient->country,
                        'email' => $existingClient->email,
                        'phone_number' => $existingClient->phone_number,
                    ];
                } else {
                    // Sinon, utilise les données du formulaire (non enregistrées pour le moment)
                    $data = [
                        'name' => $request->name,
                        'client_type' => 'professional',
                        'tva_number_client' => $request->tva_number_client,
                        'address' => $request->address,
                        'postal_code' => $request->postal_code,
                        'rib_bank' => $request->rib_bank,
                        'country' => $request->country,
                        'email' => $request->email,
                        'phone_number' => $request->phone_number,
                    ];
                }
            }

            // Stocker dans le cookie
            $cookie = cookie('invoice_step2', json_encode($data), 120);

            if (!$request->cookie('invoice_step1')) {
                return response()->json(['error' => 'Step 1 data not found in cookie'], 400);
            }

            return response()->json([
                'message' => 'Step 2 completed successfully',
                'data' => $data
            ])->cookie($cookie);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function stepThree(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'services' => 'required|array',
                'services.*.name' => 'required|string',
                'services.*.quantity' => 'required|numeric',
                'services.*.unit' => 'nullable|string',
                'services.*.price_ht' => 'required|numeric',
                'services.*.tva' => 'required|numeric',
                'services.*.total_ht' => 'required|numeric',
                'services.*.total_ttc' => 'required|numeric',
                'services.*.comment' => 'nullable|string',
                'TTotal_HT' => 'required|numeric',
                'TTotal_TVA' => 'required|numeric',
                'TTotal_TTC' => 'required|numeric',
                'payment_mode' => 'nullable|in:bank transfer,credit card,cash,paypal,cheque,other',
                'due_date' => 'nullable|string',
                'payment_status' => 'nullable|in:paid,partially paid,unpaid',
                'amount_paid' => 'nullable|numeric|min:0',
            ])->validate();

            $paymentStatus = $request->payment_status;
            $amountPaid = $request->amount_paid ?? 0;

            if ($paymentStatus === 'partially paid') {
                $validated['unpaid_amount'] = $request->TTotal_TTC - $amountPaid;
            } elseif ($paymentStatus === 'unpaid') {
                $validated['amount_paid'] = 0;
                $validated['unpaid_amount'] = $request->TTotal_TTC;
            } elseif ($paymentStatus === 'paid') {
                $validated['amount_paid'] = $request->TTotal_TTC;
                $validated['unpaid_amount'] = 0;
            }

            // Enregistrer les données dans un cookie
            $cookieData = json_encode($validated);
            $cookie = cookie('invoice_step3', $cookieData, 120);

            $mergedData = array_merge(
                json_decode($request->cookie('invoice_step1'), true) ?? [],
                json_decode($request->cookie('invoice_step2'), true) ?? [],
                json_decode($cookieData, true) ?? []
            );

            return response()->json([
                'message' => 'Step 3 completed successfully',
                'data' => $mergedData
            ])->cookie($cookie);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // mateb3th chy fi function store
    public function store(Request $request)
    {
        try {
            \Log::debug('Step 1 Cookie Data:', json_decode($request->cookie('invoice_step1'), true));
            \Log::debug('Step 2 Cookie Data:', json_decode($request->cookie('invoice_step2'), true));
            \Log::debug('Step 3 Cookie Data:', json_decode($request->cookie('invoice_step3'), true));

            $data = array_merge(
                json_decode($request->cookie('invoice_step1'), true),
                json_decode($request->cookie('invoice_step2'), true),
                json_decode($request->cookie('invoice_step3'), true)
            );

            if (!$data) {
                return response()->json(['error' => 'Missing invoice data'], 400);
            }

            if (empty($data['client_type']) || empty($data['name']) || empty($data['address'])) {
                return response()->json(['error' => 'Client data is missing'], 400);
            }

            $data['company_id'] = $data['company']['id'] ?? null;

            $client = null;
            if (isset($data['client_id']) && $data['client_id']) {
                $client = Client::find($data['client_id']);
                if (!$client) {
                    return response()->json(['error' => 'Client not found'], 404);
                }
            }

            if (!$client) {
                $client = Client::create([
                    'client_type' => $data['client_type'],
                    'name' => $data['name'],
                    'civility' => $data['civility'] ?? null,
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'tva_number_client' => $data['tva_number_client'] ?? null,
                    'address' => $data['address'],
                    'postal_code' => $data['postal_code'],
                    'rib_bank' => $data['rib_bank'] ?? null,
                    'country' => $data['country'],
                    'email' => $data['email'] ?? null,
                    'phone_number' => $data['phone_number'],
                    'company_id' => $data['company_id']
                ]);
            }

            // Gérer les montants selon le statut de paiement
            $amountPaid = $data['amount_paid'] ?? 0;
            $unpaidAmount = 0;

            if ($data['payment_status'] === 'partially paid') {
                $unpaidAmount = $data['TTotal_TTC'] - $amountPaid;
            } elseif ($data['payment_status'] === 'unpaid') {
                $amountPaid = 0;
                $unpaidAmount = $data['TTotal_TTC'];
            }

            $invoice = Invoice::create(array_merge($data, [
                'client_id' => $client->id,
                'total_ht' => $data['TTotal_HT'],
                'total_tva' => $data['TTotal_TVA'],
                'total_ttc' => $data['TTotal_TTC'],
                'amount_paid' => $amountPaid,
                'unpaid_amount' => $unpaidAmount,
            ]));

            foreach ($data['services'] as $service) {
                Service::create([
                    'invoice_id' => $invoice->id,
                    'name' => $service['name'],
                    'quantity' => $service['quantity'],
                    'unit' => $service['unit'],
                    'price_ht' => $service['price_ht'],
                    'tva' => $service['tva'],
                    'total_ht' => $service['total_ht'],
                    'total_ttc' => $service['total_ttc'],
                    'comment' => $service['comment'] ?? null,
                ]);
            }

            Cookie::queue(Cookie::forget('invoice_step1'));
            Cookie::queue(Cookie::forget('invoice_step2'));
            Cookie::queue(Cookie::forget('invoice_step3'));

            return response()->json([
                'message' => 'Invoice saved successfully!',
                'invoice_id' => $invoice->id
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadPdf($invoiceId)
    {
        try {
            $invoice = Invoice::with(['client', 'services', 'company'])->findOrFail($invoiceId);


            $company = $invoice->company;

            $companyLogoPath = ($company && $company->name === 'Procan')
                ? public_path('dist/img/logo-procan.webp')
                : public_path('dist/img/logo-Adequate.webp');

            $companyLogoBase64 = null;
            if (file_exists($companyLogoPath)) {
                $companyLogoBase64 = base64_encode(file_get_contents($companyLogoPath));
            } else {
                \Log::error("Le fichier logo n'existe pas : " . $companyLogoPath);
            }

            $data = [
                'invoice' => $invoice,
                'client' => $invoice->client,
                'services' => $invoice->services,
                'company' => $company,
                'companyLogoBase64' => $companyLogoBase64,
            ];

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);

            $html = view('pdf.invoice', $data)->render();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="devis_' . $invoice->number . '.pdf"');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Devis non trouvé.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur inattendue s\'est produite.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
