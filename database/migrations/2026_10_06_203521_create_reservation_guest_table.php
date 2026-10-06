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
        Schema::create('reservation_guest', function (Blueprint $table) {
            $table->foreignId('reservation_id')
                ->constrained('reservations')
                ->cascadeOnDelete();

            $table->foreignId('guest_id')
                ->constrained('guests')
                ->cascadeOnDelete();

            $table->primary(['reservation_id', 'guest_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_guest');
    }
};
