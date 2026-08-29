<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'dob')) {
                $table->date('dob')->nullable()->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('dob');
            }
            if (!Schema::hasColumn('users', 'nid_no')) {
                $table->string('nid_no', 30)->nullable()->unique()->after('address');
            }
            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('nid_no');
            }
            if (!Schema::hasColumn('users', 'email_verification_otp')) {
                $table->string('email_verification_otp', 6)->nullable()->after('profile_photo_path');
            }
            if (!Schema::hasColumn('users', 'email_verification_expires_at')) {
                $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_otp');
            }
            if (!Schema::hasColumn('users', 'login_otp')) {
                $table->string('login_otp', 6)->nullable()->after('email_verification_expires_at');
            }
            if (!Schema::hasColumn('users', 'login_otp_expires_at')) {
                $table->timestamp('login_otp_expires_at')->nullable()->after('login_otp');
            }
            if (!Schema::hasColumn('users', 'smart_contact_name_check')) {
                $table->boolean('smart_contact_name_check')->default(false)->after('login_otp_expires_at');
            }
            if (!Schema::hasColumn('users', 'otp_receiver_confirmation')) {
                $table->boolean('otp_receiver_confirmation')->default(false)->after('smart_contact_name_check');
            }
        });

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'dob',
                'address',
                'nid_no',
                'profile_photo_path',
                'email_verification_otp',
                'email_verification_expires_at',
                'login_otp',
                'login_otp_expires_at',
                'smart_contact_name_check',
                'otp_receiver_confirmation',
            ]);
        });

        Schema::dropIfExists('notifications');
    }
};
