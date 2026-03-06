<?php

namespace App\Listeners;

use App\Events\UserCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserCreated $event): void
    {
        try {
            // Mail::to($event->user->email)->send(new \App\Mail\WelcomeMail($event->user));
            Log::info('SendWelcomeEmail: Welcome email queued for user', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendWelcomeEmail: Failed to send welcome email', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
