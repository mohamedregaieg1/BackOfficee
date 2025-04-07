<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Validation\ValidationException;
use Exception;

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
                return response()->json($company);
            }

            return response()->json([
                'id' => null,
                'name' => $validated['name'],
                'tva_number' => null,
                'address' => null,
                'postal_code' => null,
                'country' => null,
                'rib_bank' => null,
                'email' => null,
                'website' => null,
                'phone_number' => null
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation error occurred.',
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving the company.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tva_number' => 'nullable|numeric',
                'address' => 'required|string',
                'postal_code' => 'required|string',
                'rib_bank' => 'nullable|string',
                'email' => 'nullable|email',
                'website' => 'nullable|url',
                'phone_number' => 'required|string|max:15'
            ]);

            $company = Company::create($validated);

            return response()->json([
                'message' => 'Company profile created successfully',
                'data' => $company
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation error occurred.',
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the company.',
                'error' => $e->getMessage(),
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
                'message' => 'Company profile updated successfully',
                'data' => $company
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation error occurred.',
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the company.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
