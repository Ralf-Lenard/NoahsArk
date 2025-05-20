<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade'); // Sender ID (User or Admin)
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade'); // Receiver ID (Admin or User)
            $table->text('message')->nullable(); // The message content
            $table->string('image_path')->nullable(); // Optional image path (nullable)
            $table->string('video_path')->nullable(); // Optional video path (nullable)
            $table->boolean('seen')->default(false); // Indicates if the message has been seen
            $table->boolean('unread')->default(true); // Indicates if the message is unread
            $table->timestamps(); // Timestamps for when the message was created and updated
          
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
