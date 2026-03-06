<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('unit')->nullable()->default('piece');
            $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable();
            $table->string('image_url')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['tenant_id', 'is_active']);
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
