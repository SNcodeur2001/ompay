<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $email;
    protected ?string $otp;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $email, ?string $otp)
    {
        $this->email = $email;
        $this->otp = $otp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->email || !$this->otp) {
            Log::error("Invalid email or OTP provided to SendOtpEmail job");
            return;
        }

        try {
            // Send OTP email
            Mail::raw("Votre code OTP est : {$this->otp}. Ce code expire dans 10 minutes.", function ($message) {
                $message->to($this->email)
                        ->subject('Votre code OTP - OM PAY');
            });

            Log::info("OTP email sent to {$this->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email to {$this->email}: " . $e->getMessage());
            throw $e; // Re-throw to mark job as failed
        }
    }
}
