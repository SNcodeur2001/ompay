<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\SendOtpEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            'email' => $data['email'],
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

        // Send OTP via email asynchronously
        SendOtpEmail::dispatch($user->email, $otp, $user->prenom . ' ' . $user->nom);

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
             'code_pin' => Hash::make('0000'), // PIN temporaire
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

         // Add token to user for response
         $user->access_token = $token;

         return $user;
     }

    /**
     * Set definitive PIN after first login with temporary PIN
     */
    public function setDefinitivePin(User $user, string $newPin): bool
    {
        // Allow setting PIN only if setup is not completed
        if ($user->setup_completed) {
            return false; // Setup already completed
        }

        // Update using raw SQL to ensure proper boolean handling for PostgreSQL
        DB::update('UPDATE users SET code_pin = ?, setup_completed = true, updated_at = ? WHERE id = ?', [
            Hash::make($newPin),
            now(),
            $user->id
        ]);

        // Refresh the model
        $user->refresh();

        return true;
    }

    /**
     * Login user with PIN or OTP (for first login after verification)
     */
    public function login(string $telephone, ?string $codePin = null, ?string $otp = null): ?User
     {
         $user = User::where('telephone', $telephone)->first();

         Log::info("User lookup for {$telephone}", [
             'user_exists' => !is_null($user),
             'is_active' => $user ? $user->isActive() : false,
             'is_verified' => $user ? $user->isVerified() : false,
             'setup_completed' => $user ? $user->setup_completed : false
         ]);

         if (!$user || !$user->isActive() || !$user->isVerified()) {
             Log::warning("User {$telephone} failed initial validation", [
                 'user_exists' => !is_null($user),
                 'is_active' => $user ? $user->isActive() : false,
                 'is_verified' => $user ? $user->isVerified() : false
             ]);
             return null;
         }

        Log::info("Login attempt for user {$user->telephone}", [
            'setup_completed' => $user->setup_completed,
            'has_temporary_pin' => Hash::check('0000', $user->code_pin),
            'current_pin_hash' => $user->code_pin,
            'provided_otp' => !empty($otp),
            'provided_pin' => !empty($codePin)
        ]);

        if (!$user->setup_completed) {
            // Setup not completed: accept OTP for first login
            if (!$otp) {
                Log::warning("User {$user->telephone} tried to login without OTP during setup");
                return null;
            }

            // For first login with OTP, proceed (OTP already verified in verifyOtp)
            Log::info("User {$user->telephone} logging in with OTP (setup not completed)");
        } else {
            // Setup completed: require PIN for all subsequent logins
            if (!$codePin || !Hash::check($codePin, $user->code_pin)) {
                Log::warning("User {$user->telephone} failed PIN authentication", [
                    'provided_pin' => $codePin,
                    'pin_matches' => $codePin ? Hash::check($codePin, $user->code_pin) : false
                ]);
                return null;
            }
            Log::info("User {$user->telephone} logged in with PIN");
        }

        Log::info("About to create token for user {$user->telephone}");

        try {
            // Create access token
            $token = $user->createToken('OM Pay API Token')->accessToken;

            // Add token to user for response
            $user->access_token = $token;

            Log::info("Token created successfully for user {$user->telephone}");

            return $user;
        } catch (\Exception $e) {
            Log::error("Failed to create token for user {$user->telephone}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
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
            'code_pin' => Hash::make($newPin),
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
     * Send OTP via email (asynchronous job)
     */
    private function sendOtpEmail(string $email, string $otp): void
    {
        // Dispatch the job to send OTP email
        SendOtpEmail::dispatch($email, $otp);
    }

    /**
     * Get authenticated user with compte
     */
    public function getAuthenticatedUser(): User
    {
        return auth()->user();
    }

}