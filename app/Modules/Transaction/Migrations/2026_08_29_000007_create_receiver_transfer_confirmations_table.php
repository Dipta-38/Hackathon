<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('receiver_transfer_confirmations')) {
            Schema::create('receiver_transfer_confirmations', function (Blueprint $table) {
                $table->id();
                $table->string('token', 64)->unique();
                $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->string('memo')->nullable();
                $table->string('otp_hash');
                $table->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
                $table->timestamp('expires_at');
                $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
                $table->timestamps();

                $table->index(['to_user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('receiver_transfer_confirmations');
    }
};
