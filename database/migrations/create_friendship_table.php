<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;


class CreateFriendshipTable extends Migration
{

    public function up()
    {

        Schema::create(config('FridayFriendship.tables.friendships'), function (Blueprint $table) {
            $table->id();
            $table->morphs('sender');
            $table->morphs('recipient');
            $table->enum('status', ['pending', 'accepted', 'denied', 'blocked'])->default('pending');
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists(config('FridayFriendship.tables.friendships'));
    }

}
