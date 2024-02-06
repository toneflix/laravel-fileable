<?php

namespace ToneflixCode\LaravelFileable\Tests\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // public function routeNotificationForKudiSms(): Attribute
    // {
    //     return Attribute::make(fn () => $this->phone);
    // }

    public function phoneNumber(): Attribute
    {
        return Attribute::make(fn () => $this->phone);
    }
}