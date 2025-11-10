<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(array $data): User
    {
        // Generate OTP
        $otp = $this->generateOtp();

        $user = User::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'],
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10), // OTP expires in 10 minutes
            'is_verified' => false,
            'code_pin' => null, // Explicitly set to null
            'type' => 'client',
        ]);

        // Create compte and generate QR code
        $compte = $user->compte()->create([
            'numero_compte' => \App\Models\Compte::generateNumeroCompte(),
            'qr_code_data' => null, // Will be generated after verification
        ]);

        // Send OTP via SMS (simulated for now)
        $this->sendOtpSms($user->telephone, $otp);

        return $user;
    }

    /**
     * Verify OTP and set temporary PIN (0000) for first login
     */
    public function verifyOtp(string $telephone, string $otp): ?User
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user || !$user->isOtpValid($otp)) {
            return null;
        }

        // Set temporary PIN (0000) and mark as verified
        $user->update([
            'code_pin' => '0000', // PIN temporaire
            'is_verified' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Generate QR code for compte
        $compte = $user->compte;
        if ($compte) {
            $compte->update([
                'qr_code_data' => $compte->generateQrCodeData(),
            ]);
        }

        // Create access token
        $token = $user->createToken('OM Pay API Token')->accessToken;
        $user->access_token = $token;

        return $user;
    }

    /**
     * Set definitive PIN after first login with temporary PIN
     */
    public function setDefinitivePin(User $user, string $newPin): bool
    {
        // Vérifier que l'utilisateur utilise encore le PIN temporaire
        if (!Hash::check('0000', $user->code_pin)) {
            return false; // PIN déjà changé
        }

        $user->update([
            'code_pin' => $newPin,
        ]);

        return true;
    }

    /**
     * Login user with PIN (after verification)
     */
    public function login(string $telephone, string $codePin): ?User
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user || !Hash::check($codePin, $user->code_pin)) {
            return null;
        }

        if (!$user->isActive() || !$user->isVerified()) {
            return null;
        }

        // Create access token
        $token = $user->createToken('OM Pay API Token')->accessToken;

        // Add token to user for response
        $user->access_token = $token;

        return $user;
    }

    /**
     * Change user PIN
     */
    public function changePin(User $user, string $oldPin, string $newPin): bool
    {
        if (!Hash::check($oldPin, $user->code_pin)) {
            return false;
        }

        $user->update([
            'code_pin' => $newPin,
        ]);

        return true;
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(User $user): void
    {
        $user->token()->revoke();
    }

    /**
     * Generate OTP code
     */
    private function generateOtp(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via SMS (simulated)
     */
    private function sendOtpSms(string $telephone, string $otp): void
    {
        // TODO: Implement actual SMS sending service
        // For now, just log the OTP
        \Log::info("OTP for {$telephone}: {$otp}");
    }

    /**
     * Get authenticated user with compte
     */
    public function getAuthenticatedUser(): User
    {
        return auth()->user();
    }
}