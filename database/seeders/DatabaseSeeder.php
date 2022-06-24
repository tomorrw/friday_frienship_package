<?php

namespace Database\Seeders;
use DB;
use Schema;
use App\Models\User;
use Illuminate\Database\Seeder;
use Tomorrow\FridayFriendship\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;


class DatabaseSeeder extends Seeder
{
    use RefreshDatabase;

/**
 * Seed the application's database.
 *
 * @return void
     */
    public function run()
    {   
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();
        DB::table(config('FridayFriendship.tables.groups'))->truncate();
        DB::table(config('FridayFriendship.tables.friendships'))->truncate();
        DB::table(config('FridayFriendship.tables.groupables'))->truncate();
        Schema::enableForeignKeyConstraints();
        $status = ['accepted', 'denied', 'blocked'];
        $users = User::factory()->count(10)->create();
        //check how to pass the owner
        $groups = Group::factory()->count(10)->create();
        foreach($groups as $group) {
            $randomUser = User::all()->random();
            $group->groupables()->attach($randomUser);
            $groupOwner = User::find($group->owner_id);
            $group->groupables()->attach($groupOwner);
        }
        $randomUsers = User::all()->random(10);
        foreach($randomUsers as $user){
            $randomFriend = User::all()->random();
            $user->befriend($randomFriend);
            $randomFriend-> changeFriendshipStatus(($user),$status[array_rand($status)]);
        }       

    }
}