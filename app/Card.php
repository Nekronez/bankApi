<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class Card extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pan', 'name', 'expire_date', 'card_holder_name', 'activation_date', 'priority_card', 'online_shopping', 'overseas_purchases', 'withdraw_cash',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'cvv', 'account_id', 'status_card_id', 'tariff_id', 
    ];

    public function getPanAttribute($value)
    {
        return substr($value, strlen($value) - 4, 4);
    }

    public function statusCard()
    {
        return $this->belongsTo('App\StatusCard');
    }

    public function account()
    {
        return $this->belongsTo('App\Account');
    }
}
