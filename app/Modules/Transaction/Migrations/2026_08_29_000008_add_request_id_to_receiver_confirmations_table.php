<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('receiver_transfer_confirmations') && ! Schema::hasColumn('receiver_transfer_confirmations', 'request_id')) {
            Schema::table('receiver_transfer_confirmations', function (Blueprint $table) {
                $table->foreignId('request_id')->nullable()->after('transaction_id')->constrained('money_requests')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receiver_transfer_confirmations') && Schema::hasColumn('receiver_transfer_confirmations', 'request_id')) {
            Schema::table('receiver_transfer_confirmations', function (Blueprint $table) {
                $table->dropForeign(['request_id']);
                $table->dropColumn('request_id');
            });
        }
    }
};
