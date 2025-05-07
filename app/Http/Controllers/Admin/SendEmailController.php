<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Jobs\SendInvoiceEmailJob;


class SendEmailController extends Controller
{
    public function sendEmail($id)
    {
        try {
            $invoice = Invoice::with(['client', 'company', 'services'])->findOrFail($id);

            if (!$invoice->client) {
                return response()->json(['error' => 'Client not found for this invoice'], 404);
            }

            $companyLogoUrl = ($invoice->company && $invoice->company->company_name === 'Procan')
                ? secure_asset('dist/img/logo-procan.webp')
                : secure_asset('dist/img/logo-Adequate.webp');

            $companyLogoPath = public_path(str_replace(url('/'), '', $companyLogoUrl));

            $totalPriceHT = $invoice->services->sum('price_ht');
            $totalPriceTTC = $invoice->services->sum('total_ttc');

            $invoiceContent = view('emails.invoice-email', [
                'invoice' => $invoice,
                'client' => $invoice->client,
                'company' => $invoice->company,
                'services' => $invoice->services,
                'totalPriceHT' => $totalPriceHT,
                'totalPriceTTC' => $totalPriceTTC,
                'companyLogoUrl' => $companyLogoUrl,
            ])->render();

            $htmlContent = view('emails.email-template', [
                'invoiceContent' => $invoiceContent,
                'invoice' => $invoice,
                'client' => $invoice->client,
                'company' => $invoice->company,
                'totalPriceHT' => $totalPriceHT,
                'totalPriceTTC' => $totalPriceTTC,
                'companyLogoUrl' => $companyLogoUrl,
            ])->render();

            dispatch(new SendInvoiceEmailJob($invoice->client->email, $htmlContent));

            return response()->json(['message' => 'Email sent successfully to the client'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Invoice not found'], 404);
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de l'email : " . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.', 'details' => $e->getMessage()], 500);
        }
    }
}
