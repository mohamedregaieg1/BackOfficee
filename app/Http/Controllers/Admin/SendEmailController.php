<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;

class SendEmailController extends Controller
{
    public function sendEmail($id)
    {
        try {
            $invoice = Invoice::with(['client', 'company', 'services'])->findOrFail($id);

            if (!$invoice->client) {
                return response()->json(['error' => 'Client not found for this invoice'], 404);
            }

            $companyLogoPath = ($invoice->company && $invoice->company->company_name === 'Procan')
                ? public_path('dist/img/logo-procan.webp')
                : public_path('dist/img/logo-Adequate.webp');

            $companyLogoBase64 = null;
            if (file_exists($companyLogoPath)) {
                $companyLogoBase64 = base64_encode(file_get_contents($companyLogoPath));
            } else {
                \Log::error("Le fichier logo n'existe pas : " . $companyLogoPath);
                return response()->json(['error' => 'Logo file not found'], 404);
            }

            $totalPriceHT = $invoice->services->sum('price_ht');
            $totalPriceTTC = $invoice->services->sum('total_ttc');

            $invoiceContent = view('pdf.invoice', [
                'invoice' => $invoice,
                'client' => $invoice->client,
                'company' => $invoice->company,
                'services' => $invoice->services,
                'totalPriceHT' => $totalPriceHT,
                'totalPriceTTC' => $totalPriceTTC,
                'companyLogoBase64' => $companyLogoBase64,
            ])->render();

            $htmlContent = view('emails.email-template', [
                'invoiceContent' => $invoiceContent,
                'invoice' => $invoice,
                'client' => $invoice->client,
                'company' => $invoice->company,
                'totalPriceHT' => $totalPriceHT,
                'totalPriceTTC' => $totalPriceTTC,
                'companyLogoBase64' => $companyLogoBase64,
            ])->render();

            Mail::to($invoice->client->email)->send(new InvoiceMail($htmlContent));

            return response()->json(['message' => 'Email sent successfully to the client'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Invoice not found'], 404);
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de l'email : " . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.', 'details' => $e->getMessage()], 500);
        }
    }
}
