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
class InvoiceController extends Controller
{
    public function stepOne(Request $request)
{
    try {
        // Récupération des entreprises disponibles
        $companies = Company::all();

        // Validation des données reçues dans la requête
        $validated = Validator::make($request->all(), [
            'type' => 'required|in:facture,devis',
            'creation_date' => 'required|date',
            'additional_date_type' => 'nullable|in:Date of sale,Expiry date,Withdrawal date until',
            'additional_date' => 'nullable|date',
            'company_name' => 'required|exists:companies,name',
            'number' => 'required|string|unique:invoices,number', // Numéro saisi par l'utilisateur
        ])->validate();

        // Vérification de l'existence de l'entreprise sélectionnée
        $selectedCompany = Company::where('name', $request->company_name)->first();

        if (!$selectedCompany) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        // Calcul de l'incrémentation pour l'année en cours
        $year = date('Y', strtotime($request->creation_date)); // Année actuelle
        $increment = DB::table('invoices')
            ->where('type', $request->type)
            ->whereRaw('DATE_FORMAT(created_at, "%Y") = ?', [$year]) // Filtrage par année seulement
            ->count() + 1;

        // Formatage de l'incrémentation avec une longueur fixe de 5 caractères
        $incrementFormatted = str_pad($increment, 5, '0', STR_PAD_LEFT);

        // Création du numéro final : {number}/{incr}
        $finalNumber = "{$request->number}/{$incrementFormatted}";

        // Ajout du numéro final aux données validées
        $validated['number'] = $finalNumber;

        // Création des données à stocker dans le cookie
        $cookieData = json_encode($validated + ['company' => $selectedCompany]);

        // Création du cookie avec les données validées
        $cookie = cookie('invoice_step1', $cookieData, 120);

        // Réponse JSON avec les données validées
        return response()->json([
            'message' => 'Step 1 completed successfully',
            'data' => json_decode($cookieData, true)
        ])->cookie($cookie);
    } catch (\Illuminate\Validation\ValidationException $ve) {
        // Gestion des erreurs de validation
        return response()->json($ve->errors(), 422);
    } catch (\Exception $e) {
        // Gestion des autres exceptions
        return response()->json([
            'error' => 'An unexpected error occurred.',
            'details' => $e->getMessage(),
        ], 500);
    }
}

public function getAllClients(Request $request)
{
    try {
        // Vérifier si le paramètre 'name' est fourni
        if ($request->has('name')) {
            // Recherche d'un client spécifique par son nom
            $client = Client::where('name', $request->name)->first();

            if (!$client) {
                return response()->json([
                    'error' => 'Client not found.',
                ], 404);
            }

            // Retourner les détails du client trouvé
            return response()->json([
                'id' => $client->id,
                'name' => $client->name,
            ]);
        } else {
            // Récupérer tous les clients avec leurs id et name
            $clients = Client::select('id', 'name')->get();

            // Retourner la liste des clients
            return response()->json($clients);
        }
    } catch (\Illuminate\Validation\ValidationException $ve) {
        // Gestion des erreurs de validation
        return response()->json($ve->errors(), 422);
    } catch (\Exception $e) {
        // Gestion des autres exceptions
        return response()->json([
            'error' => 'An unexpected error occurred.',
            'details' => $e->getMessage(),
        ], 500);
    }
}

public function getClientById($id)
{
    try {
        // Validation directe de l'id passé en paramètre de route
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'error' => 'Client not found.',
            ], 404);
        }

        // Retourner toutes les informations du client
        return response()->json($client);
    } catch (\Exception $e) {
        // Gestion des autres exceptions
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
                'payment_mode' => 'nullable|in:bank transfer,credit card,cash,paypal,cheque,other',
                'due_date' => 'nullable|string',
                'payment_status' => 'nullable|in:paid,partially paid',
                'amount_paid' => 'nullable|numeric',

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

    public function downloadPdf($invoiceId)
{
    try {
        // Récupérer l'invoice avec les relations nécessaires
        $invoice = Invoice::with(['client', 'services', 'company'])->findOrFail($invoiceId);
        $totalPriceHT = $invoice->services->sum('price_ht'); // Somme des prix HT
        $totalPriceTTC = $invoice->services->sum('total_ttc'); // Somme des prix TTC


        // Déterminer le chemin du logo en fonction de l'entreprise liée à la facture
        $company = $invoice->company; // Récupérer l'entreprise associée à la facture
        $companyLogoPath = ($company && $company->name === 'Procan')
            ? public_path('dist/img/logo-procan.webp')
            : public_path('dist/img/logo-Adequate.webp');
        // Encoder le logo en base64 pour l'intégrer dans le PDF
        if (file_exists($companyLogoPath)) {
            $companyLogoBase64 = base64_encode(file_get_contents($companyLogoPath));
        } else {
            $companyLogoBase64 = null; // Gérer le cas où le fichier n'existe pas
        }

        // Passer les données à la vue
        $data = [
            'invoice' => $invoice,
            'client' => $invoice->client,
            'services' => $invoice->services,
            'company'=>$invoice->company,
            'totalPriceHT' => $totalPriceHT, // Ajouter le total des prix HT
            'totalPriceTTC' => $totalPriceTTC, // Ajouter le total des prix TTC
            'companyLogoBase64' => $companyLogoBase64, // Ajouter le logo encodé
        ];

        // Configuration des options de Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Activer le chargement de ressources externes
        $options->set('defaultFont', 'Arial'); // Définir une police par défaut

        // Créer une instance de Dompdf
        $dompdf = new Dompdf($options);

        // Charger la vue Blade et passer les données
        $html = view('pdf.invoice', $data)->render();

        // Charger le contenu HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Configurer les dimensions et marges
        $dompdf->setPaper('A4', 'portrait');

        // Rendre le PDF
        $dompdf->render();

        // Retourner le PDF au format de téléchargement
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
