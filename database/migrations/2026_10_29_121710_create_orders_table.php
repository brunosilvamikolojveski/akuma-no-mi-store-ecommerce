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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            // Shipping address snapshot keeps the order history stable if the saved address changes.
            $table->string('shipping_recipient', 120);
            $table->string('shipping_postal_code', 9);
            $table->char('shipping_state', 2);
            $table->string('shipping_city', 100);
            $table->string('shipping_neighborhood', 100);
            $table->string('shipping_street', 160);
            $table->string('shipping_number', 20);
            $table->string('shipping_complement', 100)->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
