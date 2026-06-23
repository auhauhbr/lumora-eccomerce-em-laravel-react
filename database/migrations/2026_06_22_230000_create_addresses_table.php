<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('zip_code', 8);
            $table->string('street', 180);
            $table->string('number', 30);
            $table->string('complement', 120)->nullable();
            $table->string('neighborhood', 120);
            $table->string('city', 120);
            $table->string('state', 2);
            $table->timestamps();

            $table->index(['user_id', 'zip_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
