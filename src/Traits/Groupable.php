<?php


namespace Tomorrow\FridayFriendship\Traits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tomorrow\FridayFriendship\Models\Group;
use Illuminate\Support\Facades\Log;

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
        if($this->isInGroup($groupId))
        {
            return response()->error("user already in group", 500);
        }
        $this->morphToMany(Group::class, config('FridayFriendship.tables.groupables'))->attach($groupId, [
                'created_at' => now(),
                'updated_at' => now()
        ]);

        return response()->success($this->getAllMembers($groupId));
    }

    /**
     * @param  integer  $groupId
     *
     * @return true|error
     */

    public function removeFromGroup($groupId)
    {
        if(! $this->isInGroup)
        {
            return response()->error("user not in group", 404);
        }
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
            return response()->success($newOwner);
        }
        return response()->success("user removed from the group");
             
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
            $group->privacy = $info->privacy;
            $group->owner_id = $this->getKey();
            $group->owner_type = $this->getMorphClass();
            $group->save();
            $this->addToGroup($group->id);
            return response()->success($group);
    }

    /**
     * @param integer $groupId
     *
     * @return true|error
     */
    public function deleteGroup($groupId)
    {
        if ($this->isOwner($groupId))
        {
            return  response()->success(Group::destroy($groupId));
        }
        else {
            return response()->error("this user is not the owner");
        }
        
    }

    public function getAllMembers($groupId)
    {
        
        $group = Group::findOrFail($groupId);
        return $group->groupables()->get();
    }

    public function isInGroup($groupId)
    {
        $members = $this->getAllMembers($groupId);
        // return $members;
        if($members->contains('id', $this->id))
        {
            return true;
        }
        else 
        {
            return false;
        }
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
}

