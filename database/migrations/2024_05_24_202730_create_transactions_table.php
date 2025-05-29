<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payment_intent_id')->unique();
            $table->integer('amount');
            $table->integer('platform_fee')->default(12); // €0.12 in cents
            $table->string('currency')->default('eur');
            $table->string('status');
            $table->string('payment_method_type')->default('card_present');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
