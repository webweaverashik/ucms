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
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_title');
            $table->string('message_type')->default('TEXT'); // TEXT or UNICODE
            $table->text('message_body');
            $table->text('recipients'); // Comma-separated or JSON list
            $table->boolean('exclude_inactive')->default(true);
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('is_approved')->default(false); // Needs admin approval
            $table->foreignId('created_by');
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_campaigns');
    }
};
