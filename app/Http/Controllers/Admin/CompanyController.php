<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;

class CompanyController extends Controller
{
    public function showByName(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
            ]);

            $company = Company::where('name', $validated['name'])->first();

            if ($company) {
                return response()->json([
                    'success' => true,
                    'message' => 'Company found.',
                    'data' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'tva_number' => $company->tva_number,
                        'address' => $company->address,
                        'postal_code' => $company->postal_code,
                        'country' => $company->country,
                        'rib_bank' => $company->rib_bank,
                        'email' => $company->email,
                        'website' => $company->website,
                        'phone_number' => $company->phone_number,
                        'image_path' => $company->image_path,
                    ],
                ]);
            }

            // Si pas trouvé, on renvoie quand même succès false mais pas d'erreur technique
            return response()->json([
                'success' => false,
                'message' => 'Company not found.',
                'data' => [
                    'id' => null,
                    'name' => $validated['name'],
                    'tva_number' => null,
                    'address' => null,
                    'postal_code' => null,
                    'country' => null,
                    'rib_bank' => null,
                    'email' => null,
                    'website' => null,
                    'phone_number' => null,
                    'image_path' => null,
                ],
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

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|in:Procan,Adequate',
                'tva_number' => 'nullable|numeric',
                'address' => 'required|string',
                'postal_code' => 'required|string',
                'rib_bank' => 'nullable|string',
                'email' => 'nullable|email',
                'website' => 'nullable|url',
                'phone_number' => 'required|string|max:15'
            ]);

            $logoFileName = $validated['name'] === 'Procan' ? 'logo-procan.webp' : 'logo-Adequate.webp';
            $logoUrl = url('/dist/img/' . $logoFileName);
            $validated['image_path'] = $logoUrl;

            $company = Company::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Company profile created successfully',
                'data' => $company,
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

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string',
                'tva_number' => 'nullable|numeric',
                'address' => 'sometimes|string',
                'postal_code' => 'sometimes|string',
                'country' => 'required|in:France,Tunisia',
                'rib_bank' => 'nullable|string',
                'email' => 'nullable|email',
                'website' => 'nullable|url',
                'phone_number' => 'sometimes|string|max:15'
            ]);

            $company = Company::findOrFail($id);
            $company->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Company profile updated successfully',
                'data' => $company,
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
}
