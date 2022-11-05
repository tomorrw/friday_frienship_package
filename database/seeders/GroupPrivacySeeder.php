<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tomorrow\FridayFriendship\Models\GroupPrivacy;
use Illuminate\Support\Facades\Schema;

class GroupPrivacySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('group_privacy')->truncate();
        Schema::enableForeignKeyConstraints();
        
        GroupPrivacy::insert([
            [
                'title' => 'Invitable',
            ],
            [
                'title' => 'Sharable',
            ],
            [
                'title' => 'Joinable',
            ],
        ]);
    }
}
