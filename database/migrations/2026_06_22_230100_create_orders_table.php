<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('address_id')->constrained('addresses')->restrictOnDelete();
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('payment_status', 30)->default('pending')->index();
            $table->string('payment_provider', 40)->nullable();
            $table->string('payment_reference', 160)->nullable()->index();
            $table->text('payment_url')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_value', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
