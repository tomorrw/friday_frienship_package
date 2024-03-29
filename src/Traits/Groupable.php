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
     * @return Group
     */
    public function getGroup($groupId)
    {
        return  Group::findOrFail($groupId);
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

        if (!$this->isOwner($groupId)) return;

        $members = $this->getAllMembers($groupId);

        $newOwner = $members->where('pivot.created_at', $members->min(function ($member) {
            return $member->pivot->created_at;
        }))->first();

        if (!$newOwner) return;

        $group = Group::findOrFail($groupId);
        $group->owner_id = $newOwner->getKey();
        $group->owner_type = $newOwner->getMorphClass();
        $group->save();

        return $newOwner;
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
        $policies = $info->privacy;
        $group->description = $info->description;
        $group->owner_id = $this->getKey();
        $group->owner_type = $this->getMorphClass();
        $group->profile_image = $info->profile_image;
        $group->header_image = $info->header_image;
        $members = $info->members;
        $group->save();
        foreach ($members as $member) {
            $user = UserModel::findOrFail($member);
            $user->addToGroup($group->id);
        }    
        $this->addToGroup($group->id);
        $group->save();
        foreach( array_keys($policies) as $policy)
        {   
            if($policies[$policy])
            {
                $group->privacies()->sync($policy, false);
            }
        }
        return $group;
    }

    public function editGroup($info)
    {
        $group = Group::findOrFail($info->groupId);
        if ($this->isOwner($group->id))
        {

            $group->name = $info->name;
            $group->description = $info->description;
            $members = $info->members;
            $group->profile_image = $info->profileImage;
            $policies = $info->privacy;
            $group->save();
            foreach ($members as $member)
            {
                $user = UserModel::findOrFail($member);
                if(!$user->isInGroup($group->id))
                {
                    $user->addToGroup($group->id);
                }
            }
            $group->privacies()->detach();
            foreach(array_keys($policies) as $policy)
            {   
                if($policies[$policy])
                {
                    $group->privacies()->sync($policy, false);
                }
            }    
            $group->save();
            return $group;
        }
        throw "Only the group owner can change the info of the group";
    }

    public function editGroupProfileImage($url, $groupId)
    {
        if ($this->isOwner($groupId))
        {
        $group = Group::findOrFail($groupId);
        $group->profile_image = $url;
        $group->save();
        return $group;
        }
        throw "Only the group owner can change the info of the group";
    }

    public function editGroupHeaderImage($url, $groupId)
    {
        if ($this->isOwner($groupId))
        {
            $group = Group::findOrFail($groupId);
            $group->header_image = $url;
            $group->save();
            return $group;
        }
        throw "Only the group owner can change the info of the group";
    }

    public function deleteGroupProfileImage($groupId)
    {
        if ($this->isOwner($groupId))
        {
            $group = Group::findOrFail($groupId);
            {
                $group->profile_image = null;
                $group->save();
                return $group;
            }
        }
        throw "Only the group owner can change the info of the group";
    }
    

    public function deleteGroupHeaderImage($groupId)
    {
        if ($this->isOwner($groupId))
        {
            $group = Group::findOrFail($groupId);
            {
                $group->header_image = null;
                $group->save();
                return $group;
        }
        throw "Only the group owner can change the info of the group";
        }
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
     * @param  integer  $userId
     * 
     * @return true|false
     */
    public function isInGroup($groupId)
    {
        $members = $this->getAllMembers($groupId);
        return $members->contains('id', $this->id);
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

