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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_type');                  // SINGLE_SMS, OTP, GENERAL_CAMPAIGN, MULTIBODY_CAMPAIGN
            $table->string('message_type')->default('TEXT'); // TEXT or UNICODE
            $table->string('recipient');                     // Single mobile number
            $table->text('message_body');                    // Actual text sent
            $table->string('campaign_uid')->nullable();      // For bulk campaign
            $table->string('sms_uid')->nullable();           // Unique ID for each SMS
            $table->string('status')->default('PENDING');    // PENDING, SUCCESS, FAILED
            $table->integer('api_response_code')->nullable();
            $table->string('api_response_message')->nullable();
            $table->json('api_error')->nullable();       // Store API error details if failed
            $table->foreignId('created_by')->nullable(); // user who created the campaign or System
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
