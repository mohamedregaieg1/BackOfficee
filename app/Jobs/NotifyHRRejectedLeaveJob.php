<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NotifyHRRejectedLeaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $hrEmail;
    public $authUser;
    public $htmlContent;

    public function __construct($hrEmail, $authUser, $htmlContent)
    {
        $this->hrEmail = $hrEmail;
        $this->authUser = $authUser;
        $this->htmlContent = $htmlContent;
    }

    public function handle()
    {
        Mail::send([], [], function ($message) {
            $message->to($this->hrEmail)
                ->from($this->authUser->email, "{$this->authUser->first_name} {$this->authUser->last_name}")
                ->subject('Demande de congé refusée')
                ->html($this->htmlContent);
        });
    }
}
