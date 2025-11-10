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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->enum('type', ['paiement', 'transfert', 'depot']);
            $table->enum('statut', ['en_cours', 'reussi', 'echoue'])->default('en_cours');
            $table->foreignUuid('compte_emetteur_id')->nullable()->constrained('comptes');
            $table->foreignUuid('compte_destinataire_id')->nullable()->constrained('comptes');
            $table->foreignUuid('marchand_id')->nullable();
            $table->decimal('montant', 15, 2);
            $table->decimal('frais', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
