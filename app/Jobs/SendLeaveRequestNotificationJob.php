<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLeaveRequestNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $htmlContent, $userEmail;

    public function __construct($htmlContent, $userEmail)
    {
        $this->htmlContent = $htmlContent;
        $this->userEmail = $userEmail;
    }

    public function handle()
    {
        $adminEmail = env('ADMIN_EMAIL');

        Mail::send([], [], function ($message) use ($adminEmail) {
            $message->to($adminEmail)
                ->from(env('MAIL_FROM_ADDRESS'), 'PROCAN HR System')
                ->replyTo($this->userEmail)
                ->subject('New Leave Request Notification')
                ->html($this->htmlContent);
        });
    }
}
