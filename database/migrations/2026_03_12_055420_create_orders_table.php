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
            $table->string('order_number', 50)->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->text('customer_address');
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->integer('total_pax')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('dp_percentage', 5, 2)->default(0);
            $table->decimal('dp_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->enum('status', ['pending', 'dp_paid', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'dp_paid', 'fully_paid', 'refunded'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
