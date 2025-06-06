<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    // Afficher tous les clients avec filtrage et pagination
    public function index(Request $request)
    {
        try {
            $query = Client::query();

            if ($request->filled('name')) {
                $query->where('name', 'LIKE', '%' . $request->name . '%');
            }

            if ($request->filled('email')) {
                $query->where('email', 'LIKE', '%' . $request->email . '%');
            }

            if ($request->filled('phone_number')) {
                $query->where('phone_number', 'LIKE', '%' . $request->phone_number . '%');
            }

            $query->orderBy('created_at', 'desc');

            $clients = $query->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Clients retrieved successfully.',
                'data' => $clients->items(),
                'meta' => [
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Création client avec règles selon type (individual/professional)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_type' => 'required|in:professional,individual',
                'civility' => 'nullable|string',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'name' => 'nullable|string|max:255',
                'tva_number_client' => 'nullable|numeric',
                'address' => 'required|string',
                'postal_code' => 'required|string',
                'rib_bank' => 'nullable|string',
                'country' => 'required|string',
                'email' => 'nullable|email',
                'phone_number' => 'required|string|max:15',
            ]);

            $clientData = [
                'client_type' => $validated['client_type'],
                'address' => $validated['address'],
                'postal_code' => $validated['postal_code'],
                'rib_bank' => $validated['rib_bank'] ?? null,
                'country' => $validated['country'],
                'email' => $validated['email'] ?? null,
                'phone_number' => $validated['phone_number'],
            ];

            if ($validated['client_type'] === 'individual') {
                $fullName = trim(($validated['civility'] ?? '') . ' ' . ($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
                $clientData['name'] = $fullName;
                $clientData['tva_number_client'] = null;
            } else {
                $clientData['name'] = $validated['name'];
                $clientData['tva_number_client'] = $validated['tva_number_client'] ?? null;
            }

            $client = Client::create($clientData);

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully.',
                'data' => $client,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Mise à jour client
    public function update(Request $request, $id)
    {
        try {
            $client = Client::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'tva_number_client' => 'nullable|numeric',
                'address' => 'sometimes|string',
                'postal_code' => 'sometimes|string',
                'rib_bank' => 'nullable|string',
                'country' => 'sometimes|string',
                'email' => 'nullable|email',
                'phone_number' => 'sometimes|string|max:15',
            ]);

            $client->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully.',
                'data' => $client,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Suppression client
    public function destroy($id)
    {
        try {
            $client = Client::findOrFail($id);
            $unpaidInvoices = $client->invoices()
                ->whereIn('payment_status', ['unpaid', 'partially paid'])
                ->exists();

            if ($unpaidInvoices) {
                return response()->json([
                    'success' => false,
                    'message' => 'This client cannot be deleted due to unpaid associated invoices.',
                ], 400);
            }

            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


}
