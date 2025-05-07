<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPasswordResetLinkJob implements ShouldQueue
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
        Mail::html($this->htmlContent, function ($message) {
            $message->to($this->email)
                ->subject('Password Reset Link');
        });
    }
}
