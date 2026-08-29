<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ✅ The class name MUST match the file name pattern:
// "Create" + TableName + "Table"
// So for "transaction_attempts" it should be:
return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('transaction_attempts');
    }
};