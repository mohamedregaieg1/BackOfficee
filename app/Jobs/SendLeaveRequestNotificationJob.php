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

    protected $adminEmail;
    protected $htmlContent;

    public function __construct($adminEmail, $htmlContent)
    {
        $this->adminEmail = $adminEmail;
        $this->htmlContent = $htmlContent;
    }

    public function handle()
    {
        Mail::send([], [], function ($message) {
            $message->to($this->adminEmail)
                ->from('noreply@procan.com', 'PROCAN HR System')
                ->subject('New Leave Request Notification')
                ->html($this->htmlContent);
        });
    }
}
