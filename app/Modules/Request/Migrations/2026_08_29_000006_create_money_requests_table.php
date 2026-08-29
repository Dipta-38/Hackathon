<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('money_requests')) {
            Schema::create('money_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->text('message')->nullable();
                $table->enum('status', ['pending', 'accepted', 'rejected', 'expired', 'processing'])->default('pending');
                $table->string('idempotency_key', 64)->unique();
                $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
                $table->timestamp('expires_at');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->unsignedBigInteger('version')->default(0);
                $table->timestamps();
                
                $table->index(['to_user_id', 'status']);
                $table->index(['from_user_id', 'status']);
                $table->index('expires_at');
                $table->index('idempotency_key');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('money_requests');
    }
};