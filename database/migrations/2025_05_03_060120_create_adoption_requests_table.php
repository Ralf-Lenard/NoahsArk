<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdoptionRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('adoption_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who is adopting
            $table->foreignId('animal_profile_id')->constrained()->onDelete('cascade'); // which animal
            $table->text('question1');
            $table->text('question2');
            $table->text('question3');
            $table->string('valid_id'); // file path
            $table->string('selfie_with_id'); // file path
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('adoption_requests');
    }
}

