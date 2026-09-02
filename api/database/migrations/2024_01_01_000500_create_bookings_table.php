<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->dateTime('slot_start');
            $table->dateTime('slot_end');
            $table->string('status', 20)->default('confirmed');
            $table->timestamps();

            // Backstop for the double-booking race. Even if two requests slip
            // past the application lock, the database refuses the second write.
            $table->unique(['resource_id', 'slot_start']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
