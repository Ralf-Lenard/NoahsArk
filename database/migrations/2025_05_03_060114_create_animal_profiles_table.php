<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnimalProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('animal_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->string('breed');
            $table->string('species');
            $table->date('birthdate');
            $table->string('color');
            $table->string('gender');
            $table->text('description')->nullable();
            $table->string('image')->nullable(); // animal photo
            $table->text('medical_records')->nullable(); // optional JSON or text
            $table->boolean('is_temporarily_adopted')->default(false); // trial period or pending approval
            $table->boolean('is_adopted')->default(false); // final adoption approval
            $table->string('device_id')->nullable(); // for tracking device
            $table->unsignedBigInteger('traccar_id')->nullable(); // foreign ID to Traccar system
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('animal_profiles');
    }
}

