<?php


namespace Tomorrow\FridayFriendship\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tomorrow\FridayFriendship\Interaction;
use Tomorrow\FridayFriendship\Models\FriendFriendshipGroups;
use Tomorrow\FridayFriendship\Models\Friendship;
use Tomorrow\FridayFriendship\Status;

/**
 * Class Friendable
 * @package Tomorrow\FridayFriendship\Traits
 */
trait Friendable

{
    public static function bootFriendable()
    {
        static::deleting(function ($model) {
            $friendships = $model->getAllFriendships();
            $friendships->each->delete();
        });
    }


    /**
     * @param  Model  $recipient
     *
     * @return \Tomorrow\FridayFriendship\Models\Friendship|false
     */
    public function befriend(Model $recipient)
    {

        if ( ! $this->canBefriend($recipient)) {
            return false;
        }

        $friendshipModelName = Interaction::getFriendshipModelName();
        $friendship = (new $friendshipModelName)->fillRecipient($recipient)->fill([
            'status' => Status::PENDING,
        ]);

        $this->friends()->save($friendship);

        Event::dispatch('acq.friendships.sent', [$this, $recipient]);

        return $friendship;

    }

      /**
     * @param  Model  $recipient
     * @param String $status
     * @return bool|int
     */
    public function changeFriendshipStatus(Model $recipient, $status)
    {
        Event::dispatch('acq.friendships.accepted', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => $status,
        ]);
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function unfriend(Model $recipient)
    {
        Event::dispatch('acq.friendships.cancelled', [$this, $recipient]);

        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasFriendRequestFrom(Model $recipient)
    {
        return $this->findFriendship($recipient)->whereSender($recipient)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasSentFriendRequestTo(Model $recipient)
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $friendshipModelName::whereRecipient($recipient)->whereSender($this)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function isFriendWith(Model $recipient)
    {
        return $this->findFriendship($recipient)->where('status', Status::ACCEPTED)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool|int
     */
    public function acceptFriendRequest(Model $recipient)
    {
        Event::dispatch('acq.friendships.accepted', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::ACCEPTED,
        ]);
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool|int
     */
    public function denyFriendRequest(Model $recipient)
    {
        Event::dispatch('acq.friendships.denied', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::DENIED,
        ]);
    }

    /**
     * @param  Model  $recipient
     *
     * @return \Tomorrow\FridayFriendship\Models\Friendship
     */
    public function blockFriend(Model $recipient)
    {
        // if there is a friendship between the two users and the sender is not blocked
        // by the recipient user then delete the friendship
        if ( ! $this->isBlockedBy($recipient)) {
            $this->findFriendship($recipient)->delete();
        }

        $friendshipModelName = Interaction::getFriendshipModelName();
        $friendship = (new $friendshipModelName)->fillRecipient($recipient)->fill([
            'status' => Status::BLOCKED,
        ]);

        Event::dispatch('acq.friendships.blocked', [$this, $recipient]);

        return $this->friends()->save($friendship);
    }

    /**
     * @param  Model  $recipient
     *
     * @return mixed
     */
    public function unblockFriend(Model $recipient)
    {
        Event::dispatch('acq.friendships.unblocked', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereSender($this)->delete();
    }

    /**
     * @param  Model  $recipient
     *
     * @return \Tomorrow\FridayFriendship\Models\Friendship
     */
    public function getFriendship(Model $recipient)
    {
        return $this->findFriendship($recipient)->first();
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getAllFriendships(int $perPage = 0, array $fields = ['*'], string $type = 'all')
    {
        return $this->getOrPaginate($this->findFriendships(null, $type), $perPage, $fields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getPendingFriendships(int $perPage = 0, array $fields = ['*'], string $type = 'all')
    {
        return $this->getOrPaginate($this->findFriendships(Status::PENDING, $type), $perPage, $fields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getAcceptedFriendships(int $perPage = 0, array $fields = ['*'], string $type = 'all')
    {
        return $this->getOrPaginate($this->findFriendships(Status::ACCEPTED, $type), $perPage, $fields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getDeniedFriendships(int $perPage = 0, array $fields = ['*'])
    {
        return $this->getOrPaginate($this->findFriendships(Status::DENIED), $perPage, $fields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getBlockedFriendships(int $perPage = 0, array $fields = ['*'])
    {
        return $this->getOrPaginate($this->findFriendships(Status::BLOCKED), $perPage, $fields);
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasBlocked(Model $recipient)
    {
        return $this->friends()->whereRecipient($recipient)->whereStatus(Status::BLOCKED)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function isBlockedBy(Model $recipient)
    {
        return $recipient->hasBlocked($this);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getFriendRequests()
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $friendshipModelName::whereRecipient($this)->whereStatus(Status::PENDING)->get();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  int  $perPage  Number
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0,array $fields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->getFriendsQueryBuilder(), $perPage, $fields, $cursor);
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  Model  $other
     * @param  int  $perPage  Number
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMutualFriends(Model $other, $perPage = 0, array $fields = ['*'])
    {
        return $this->getOrPaginate($this->getMutualFriendsQueryBuilder($other), $perPage, $fields);
    }

    /**
     * Get the number of friends
     *
     * @return integer
     */
    public function getMutualFriendsCount($other)
    {
        return $this->getMutualFriendsQueryBuilder($other)->count();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  int  $perPage  Number
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsOfFriends($perPage = 0, array $fields = ['*'])
    {
        return $this->getOrPaginate($this->friendsOfFriendsQueryBuilder(), $perPage, $fields);
    }


    /**
     * Get the number of friends
     *
     * @param  string  $type
     *
     * @return integer
     */
    public function getFriendsCount($type = 'all')
    {
        $friendsCount = $this->findFriendships(Status::ACCEPTED, $type)->count();

        return $friendsCount;
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function canBefriend($recipient)
    {
        // if user has Blocked the recipient and changed his mind
        // he can send a friend request after unblocking
        if ($this->hasBlocked($recipient)) {
            $this->unblockFriend($recipient);

            return true;
        }
        //if the sender is the recipient
        if ($this == $recipient){
            return false;
        }
        // if sender has a friendship with the recipient return false
        if ($friendship = $this->getFriendship($recipient)) {
            // if previous friendship was Denied then let the user send fr
            if ($friendship->status != Status::DENIED) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Model  $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFriendship(Model $recipient)
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $friendshipModelName::betweenModels($this, $recipient);
    }

    /**
     * @param        $status
     * @param string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = null, string $type = 'all')
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        $query = $friendshipModelName::where(function ($query) use ($type) {
            switch ($type) {
                case 'all':
                    $query->where(function ($q) {$q->whereSender($this);})->orWhere(function ($q) {$q->whereRecipient($this);});
                    break;
                case 'sender':
                    $query->where(function ($q) {$q->whereSender($this);});
                    break;
                case 'recipient':
                    $query->where(function ($q) {$q->whereRecipient($this);});
                    break;
            }
        });

        //if $status is passed, add where clause
        if ( ! is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the query builder of the 'friend' model
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder()
    {

        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getMutualFriendsQueryBuilder(Model $other)
    {
        $user1['friendships'] = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user1['recipients'] = $user1['friendships']->pluck('recipient_id')->all();
        $user1['senders'] = $user1['friendships']->pluck('sender_id')->all();

        $user2['friendships'] = $other->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user2['recipients'] = $user2['friendships']->pluck('recipient_id')->all();
        $user2['senders'] = $user2['friendships']->pluck('sender_id')->all();

        $mutualFriendships = array_unique(
            array_intersect(
                array_merge($user1['recipients'], $user1['senders']),
                array_merge($user2['recipients'], $user2['senders'])
            )
        );

        return $this->whereNotIn('id', [$this->getKey(), $other->getKey()])->whereIn('id', $mutualFriendships);
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model)
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder()
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();

        $friendIds = array_unique(array_merge($recipients, $senders));

        $friendshipModelName = Interaction::getFriendshipModelName();
        $fofs = $friendshipModelName::where('status', Status::ACCEPTED)
                          ->where(function ($query) use ($friendIds) {
                              $query->where(function ($q) use ($friendIds) {
                                  $q->whereIn('sender_id', $friendIds);
                              })->orWhere(function ($q) use ($friendIds) {
                                  $q->whereIn('recipient_id', $friendIds);
                              });
                          })
                          ->get(['sender_id', 'recipient_id']);

        $fofIds = array_unique(
            array_merge($fofs->pluck('sender_id')->all(), $fofs->pluck('recipient_id')->all())
        );
        return $this->whereIn('id', $fofIds)->whereNotIn('id', $friendIds);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friends()
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $this->morphMany($friendshipModelName, 'sender');
    }

    protected function getOrPaginate($builder, $perPage, array $fields = ['*'], bool $cursor = false)
    {
        if ($perPage == 0) {
            return $builder->get($fields);
        }

        if ($cursor) {
            return $builder->cursorPaginate($perPage, $fields);
        }

        return $builder->paginate($perPage, $fields);
    }
}
