<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->string('product_sku');
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->string('location')->nullable();
            $table->string('unit')->nullable()->default('piece');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'product_id']);
            $table->unique(['tenant_id', 'product_id', 'warehouse_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('inventories'); }
};
