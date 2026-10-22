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
        Schema::create('entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_menu_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->timestamps();

            $table->unique(['entry_id', 'visit_menu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_items');
    }
};
