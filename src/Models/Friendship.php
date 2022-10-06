<?php


namespace Tomorrow\FridayFriendship\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Friendship
 * @package Tomorrow\FridayFriendship\Models
 */
class Friendship extends Model
{
    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('FridayFriendship.tables.friendships');

        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function sender()
    {
        return $this->morphTo('sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function recipient()
    {
        return $this->morphTo('recipient');
    }

    /**
     * @param  Model  $recipient
     *
     * @return $this
     */
    public function fillRecipient($recipient)
    {
        return $this->fill([
            'recipient_id' => $recipient->getKey(),
            'recipient_type' => $recipient->getMorphClass()
        ]);
    }

    /**
     * @param       $query
     * @param  Model  $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereRecipient($query, $model)
    {
        return $query->where('recipient_id', $model->getKey())
                     ->where('recipient_type', $model->getMorphClass());
    }

    /**
     * @param       $query
     * @param  Model  $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereSender($query, $model)
    {
        return $query->where('sender_id', $model->getKey())
                     ->where('sender_type', $model->getMorphClass());
    }

    /**
     * @param        $query
     * @param  Model  $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */

    /**
     * @param       $query
     * @param  Model  $sender
     * @param  Model  $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenModels($query, $sender, $recipient)
    {
        $query->where(function ($queryIn) use ($sender, $recipient) {
            $queryIn->where(function ($q) use ($sender, $recipient) {
                $q->whereSender($sender)->whereRecipient($recipient);
            })->orWhere(function ($q) use ($sender, $recipient) {
                $q->whereSender($recipient)->whereRecipient($sender);
            });
        });
    }
}
