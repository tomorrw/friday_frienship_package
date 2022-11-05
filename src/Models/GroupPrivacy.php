<?php

namespace Tomorrow\FridayFriendship\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tomorrow\FridayFriendship\Models\Group;

class GroupPrivacy extends Model
{
    use HasFactory;
    protected $fillable = [
        'title'
    ];
    
    protected $table = 'group_privacy';


    public function groups()
    {
        return $this->hasOne(Group::class, 'privacy_id');
    }
}
