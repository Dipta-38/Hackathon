<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->unique()->onDelete('cascade');
                $table->decimal('balance', 15, 2)->default(100000);
                $table->decimal('reserved_balance', 15, 2)->default(0);
                $table->unsignedBigInteger('version')->default(0);
                $table->timestamps();
                
                // ✅ Indexes for performance
                $table->index(['user_id', 'version']);
                $table->index('balance');
            });
        }

        if (!Schema::hasTable('balance_history')) {
            Schema::create('balance_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->enum('type', ['credit', 'debit', 'reserve', 'release']);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->unsignedBigInteger('version')->default(0);
                $table->timestamps();
                
                $table->index(['account_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('balance_reservations')) {
            Schema::create('balance_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
                $table->string('reservation_id', 64)->unique();
                $table->decimal('amount', 15, 2);
                $table->timestamp('expires_at');
                $table->timestamps();
                
                $table->index(['account_id', 'expires_at']);
                $table->index('reservation_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('balance_reservations');
        Schema::dropIfExists('balance_history');
        Schema::dropIfExists('accounts');
    }
};