<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnimalAbuseReportsTable extends Migration
{
    public function up()
    {
        Schema::create('animal_abuse_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->text('description');

            $table->json('photos')->nullable(); // Store image file paths as array
            $table->json('videos')->nullable(); // Store video file paths as array
            $table->text('rejection_reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('animal_abuse_reports');
    }
}
