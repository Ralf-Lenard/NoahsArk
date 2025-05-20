<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');             // The recipient of the notification
            $table->string('message');                         // The message content
            $table->string('image_path')->nullable();          // Optional image (e.g., animal photo)
            $table->string('type')->nullable();                // Type of notification (e.g., 'AdoptionStatusUpdated')
            $table->timestamp('read_at')->nullable();          // Mark when notification is read
            $table->date('appointment_date')->nullable();
            $table->time('appointment_time')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
