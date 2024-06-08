<?php

namespace ToneflixCode\LaravelFileable\Tests\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'image',
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

    public function registerFileable()
    {
        $this->fileableLoader([
            'image' => 'avatar',
        ], 'default', true, ['image' => 'avatar']);
    }
}
