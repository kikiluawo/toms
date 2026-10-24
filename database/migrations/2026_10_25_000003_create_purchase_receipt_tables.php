<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 40)->unique();
            $table->string('doc_number', 60);
            $table->timestamp('received_at');
            // The receipt repeats the destination because a PO can be delivered to a
            // different location than the one planned when it was drafted.
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('fleet_number', 20)->nullable();
            $table->string('driver_name', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 13, 3);

            $table->unique(['purchase_receipt_id', 'purchase_order_line_id'], 'receipt_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_lines');
        Schema::dropIfExists('purchase_receipts');
    }
};
