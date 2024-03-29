<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;
    
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'lastName', 'secondName', 'phone',
    ];

    public function accounts()
    {
        return $this->hasMany('App\Account');
    }

    public function otherBankCards()
    {
        return $this->hasMany('App\OtherBankCard');
    }

    public function statusUser()
    {
        return $this->belongsTo('App\StatusUser');
    }

    public function pinCode()
    {
        return $this->hasOne('App\PinCode');
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'pin_code_id', 'status_user_id'
    ];
}
