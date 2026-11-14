<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('reference_date');
            $table->decimal('value', 10, 4);
            $table->timestamps();

            $table->unique(['indicator_id', 'reference_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_values');
    }
};
