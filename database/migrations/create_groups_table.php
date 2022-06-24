<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('FridayFriendship.tables.groups'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->morphs('owner');
            $table->enum('privacy', ['public', 'private']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('FridayFriendship.tables.groups'));
    }
}
