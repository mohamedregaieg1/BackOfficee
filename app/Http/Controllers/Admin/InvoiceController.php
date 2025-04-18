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
                'company_name' => 'required|exists:companies,name'
            ])->validate();

            $selectedCompany = Company::where('name', $request->company_name)->first();

            if (!$selectedCompany) {
                return response()->json(['message' => 'Company not found'], 404);
            }

            $prefix = $request->type === 'facture' ? 'F' : 'D';
            $monthYear = date('mY', strtotime($request->creation_date));
            $increment = DB::table('invoices')
                ->where('type', $request->type)
                ->whereRaw('DATE_FORMAT(created_at, "%m%Y") = ?', [$monthYear])
                ->count() + 1;

            $incrementFormatted = str_pad($increment, 6, '0', STR_PAD_LEFT);
            $number = "{$prefix}/{$monthYear}/{$incrementFormatted}";

            $cookieData = json_encode($validated + ['number' => $number, 'company' => $selectedCompany]);
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

    public function getAllClients()
    {
        try {
            $clients = Client::select('id', 'name')->get();
            return response()->json($clients);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
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
                'civility' => 'nullable',
                'first_name' => 'nullable',
                'last_name' => 'nullable',
                'tva_number_client' => 'nullable|numeric',
                'address' => 'required|string',
                'postal_code' => 'required|string',
                'rib_bank' => 'nullable|string',
                'country' => 'required|in:' . implode(',', $countries),
                'email' => 'nullable|email',
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
                            'client_type' => $request->client_type,
                            'address' => $existingClient->address,
                            'postal_code' => $existingClient->postal_code,
                            'rib_bank' => $existingClient->rib_bank ?? null,
                            'country' => $existingClient->country,
                            'email' => $existingClient->email ?? null,
                            'phone_number' => $existingClient->phone_number,
                        ];
                    } else {
                        return response()->json(['error' => 'Client not found'], 404);
                    }
                } else {
                    $name = $request->civility . ' ' . $request->first_name . ' ' . $request->last_name;
                    $data = [
                        'name' => $name,
                        'client_type' => $request->client_type,
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
                $data = [
                    'name' => $request->name,
                    'client_type' => $request->client_type,
                    'tva_number_client' => $request->tva_number_client ?? null,
                    'address' => $request->address,
                    'postal_code' => $request->postal_code,
                    'rib_bank' => $request->rib_bank ?? null,
                    'country' => $request->country,
                    'email' => $request->email,
                    'phone_number' => $request->phone_number,
                ];
            }

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
                'services.*.unit' => 'required|in:hours,days',
                'services.*.price_ht' => 'required|numeric',
                'services.*.tva' => 'required|numeric',
                'services.*.total_ht' => 'required|numeric',
                'services.*.total_ttc' => 'required|numeric',
                'services.*.comment' => 'nullable|string',
                'TTotal_HT' => 'required|numeric',
                'TTotal_TVA' => 'required|numeric',
                'TTotal_TTC' => 'required|numeric',
            ])->validate();

            $cookieData = json_encode($validated);
            $cookie = cookie('invoice_step3', $cookieData, 120);

            return response()->json([
                'message' => 'Step 3 completed successfully',
                'data' => array_merge(
                    json_decode($request->cookie('invoice_step1'), true),
                    json_decode($request->cookie('invoice_step2'), true),
                    json_decode($cookieData, true)
                )
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

            $invoice = Invoice::create(array_merge($data, [
                'client_id' => $client->id
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

            return response()->json(['message' => 'Invoice saved successfully!'], 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
