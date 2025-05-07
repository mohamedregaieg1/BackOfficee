<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $htmlContent;

    public function __construct($email, $htmlContent)
    {
        $this->email = $email;
        $this->htmlContent = $htmlContent;
    }

    public function handle()
    {
        Mail::to($this->email)->send(new InvoiceMail($this->htmlContent));
    }
}
