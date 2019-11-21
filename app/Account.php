<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class Account extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number_account', 'rate', 'balance',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'currency_id', 'type_account_id', 'user_id'
    ];

    
    public function cards()
    {
        return $this->hasMany('App\Card');
    }

    public function currency()
    {
        return $this->belongsTo('App\Currency');
    }

    public function typeAccount()
    {
        return $this->belongsTo('App\TypeAccount');
    }
}
