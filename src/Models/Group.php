<?php

namespace Tomorrow\FridayFriendship\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    public function groupables()
    {
        return $this->morphedByMany(config('FridayFriendship.groupable_model'), config('FridayFriendship.tables.groupables'))->withTimestamps();
    }

}   