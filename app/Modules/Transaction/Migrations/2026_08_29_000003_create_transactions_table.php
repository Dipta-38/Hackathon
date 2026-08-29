<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
                $table->string('idempotency_key', 64)->unique();
                $table->json('metadata')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->unsignedBigInteger('version')->default(0);
                $table->integer('attempts')->default(0);
                $table->timestamps();
                
                // ✅ Indexes for performance
                $table->index(['from_user_id', 'status', 'created_at']);
                $table->index(['to_user_id', 'status', 'created_at']);
                $table->index(['status', 'processed_at']);
                $table->index('idempotency_key');
            });
        }

        if (!Schema::hasTable('transaction_attempts')) {
            Schema::create('transaction_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('idempotency_key', 64);
                $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->string('status', 20);
                $table->text('error_message')->nullable();
                $table->timestamp('attempted_at');
                $table->timestamps();
                
                $table->index(['idempotency_key', 'attempted_at']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('transaction_attempts');
        Schema::dropIfExists('transactions');
    }
};