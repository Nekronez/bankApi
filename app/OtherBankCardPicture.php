<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class OtherBankCardPicture extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'panNumber',
    ];

    public function otherBankCards()
    {
        return $this->hasMany('App\OtherBankCard');
    }

    public function cardPicture()
    {
        return $this->belongsTo('App\CardPicture');
    }

    protected $table = 'otherBankCardPictures';
    
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    // protected $hidden = [
    //     '',
    // ];
}
