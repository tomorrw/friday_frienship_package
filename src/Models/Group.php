<?php

namespace Tomorrow\FridayFriendship\Models;
use Tomorrow\FridayFriendship\Models\GroupPrivacy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    public function groupables()
    {
        return $this->morphedByMany(config('FridayFriendship.groupable_model'), config('FridayFriendship.tables.groupables'))->withTimestamps();
    }

    public function privacies()
    {
        return $this->belongsToMany(GroupPrivacy::class, 'groups_privacies', 'group_id', 'privacy_id');
    }

}   