<?php


namespace Tomorrow\FridayFriendship\Traits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tomorrow\FridayFriendship\Models\Group;
use Illuminate\Support\Facades\Log;
use App\Models\Users\User as UserModel;


trait Groupable
{   



    public static function bootGroupable()
    {
        static::deleting(function ($model) {
            $groups = $model->morphToMany(Group::class, config('FridayFriendship.tables.groupables'))->get();
            foreach ($groups as $group) {
                $model->removeFromGroup($group->id);
            }
        });
    }


    /**
     * @param  integer  $groupId
     *
     * @return true|error
     */
    public function addToGroup($groupId)
    {
        return $this->morphToMany(Group::class, config('FridayFriendship.tables.groupables'))->attach($groupId, [
                'created_at' => now(),
                'updated_at' => now()
        ]);
    }

    /**
     * @param  integer  $groupId
     *
     * @return true|error
     */

    public function removeFromGroup($groupId)
    {
        $this->morphToMany(Group::class, config('FridayFriendship.tables.groupables'))->detach($groupId);
        if($this->isOwner($groupId))
        {
            $members = $this->getAllMembers($groupId);
            $newOwner = $members[0];
            foreach ($members as $member) 
            {
                if ($newOwner->pivot->created_at > $member->pivot->created_at)
                {
                    $newOwner = $member;
                }
                $group = Group::findOrFail($groupId);
                $group->owner_id = $newOwner->getKey();
                $group->owner_type = $newOwner->getMorphClass();
                $group->save();
            }
            return $newOwner;
        }
        return true;
             
    }
    
    /**
     * @param object $info {name, privacy}
     *
     * @return true|error
     */
    public function createGroup($info)
    {   
            $group = new Group;
            $group->name = $info->name;
            // $group->privacy = $info->privacy;
            $group->description = $info->description;
            $group->owner_id = $this->getKey();
            $group->owner_type = $this->getMorphClass();
            $members = $info->members;
            $group->save();
            foreach ($members as $member) {
               $user = UserModel::findOrFail($member['id']);
               $user->addToGroup($group->id);
            }    
            $this->addToGroup($group->id);
            $group->save();
            return $group;
    }

    /**
     * @param integer $groupId
     *
     * @return true|error
     */
    public function removeGroup($groupId)
    {
        if ($this->isOwner($groupId))
        {
            return  Group::destroy($groupId);
        }
        else {
            return "this user is not the owner";
        }
        
    }

    public function getAllMembers($groupId)
    {
        $group = Group::findOrFail($groupId);
        return $group->groupables()->get();
    }

    /**
     * @param int $groupId
     *
     * @return true|false
     */
    public function isOwner($groupId)
    {
        $group = Group::findOrFail($groupId);
        if($this->getKey() === $group->owner_id && $this->getMorphClass() === $group->owner_type) 
        {
            return true;
        }
        else {
            return false;
        }
    }

    public function getAllGroups() 
    {
        $groups = $this->morphToMany(Group::class, config('FridayFriendship.tables.groupables'))->get();
        return $groups;
    }
}

