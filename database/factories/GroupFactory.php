<?php

namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tomorrow\FridayFriendship\Models\Group;
use App\Models\User;

class GroupFactory extends Factory
{
    
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Group::class;

    public function definition()
    {
        $privacyValues = ['public', 'private'];
        $groupable = $this->groupable();

        return [
            'name' => $this->faker->company,
            'owner_id' => $groupable::factory(),
            'owner_type' => $groupable,
            'privacy' =>  $privacyValues[rand(0,1)],
        ];
    }

    public function groupable()
    {
        return $this->faker->randomElement([
            User::class
            // you can insert here any additional models you want them to be used as group owner
        ]);
    }

    
}
