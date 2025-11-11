<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('compte_emetteur_id');
            $table->index('compte_destinataire_id');
            $table->index('marchand_id');
            $table->index('reference');
            $table->index(['statut', 'created_at']);
        });

        Schema::table('comptes', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['compte_emetteur_id']);
            $table->dropIndex(['compte_destinataire_id']);
            $table->dropIndex(['marchand_id']);
            $table->dropIndex(['reference']);
            $table->dropIndex(['statut', 'created_at']);
        });

        Schema::table('comptes', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
