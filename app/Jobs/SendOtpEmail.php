<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\BrevoMailService;
use Illuminate\Support\Facades\Log;

class SendOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $email;
    protected ?string $otp;
    protected ?string $name;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $email, ?string $otp, ?string $name = null)
    {
        $this->email = $email;
        $this->otp = $otp;
        $this->name = $name ?? 'Utilisateur';
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
            // Send OTP email using Brevo
            $success = BrevoMailService::sendOtp($this->email, $this->name, $this->otp);

            if (!$success) {
                Log::error("Failed to send OTP email to {$this->email} via Brevo");
                throw new \Exception("Brevo API call failed");
            }

            Log::info("OTP email sent to {$this->email} via Brevo");
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email to {$this->email}: " . $e->getMessage());
            throw $e; // Re-throw to mark job as failed
        }
    }
}
