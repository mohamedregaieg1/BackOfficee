<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $appends = ['avatar_path'];
    protected $fillable = [
        'id',
        'avatar_path',
        'first_name',
        'last_name',
        'sex',
        'username',
        'phone',
        'address',
        'password',
        'email',
        'company',
        'role',
        'job_description',
        'start_date',
        'leave_balance',
        'token_version',

    ];

    protected $casts = [
        'start_date' => 'date',
        'initial_leave_balance' => 'double',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
       return [
            'token_version' => $this->token_version,
        ];
    }
    public function getAvatarPathAttribute()
    {
        return $this->attributes['avatar_path']
            ? asset($this->attributes['avatar_path'])
            : null;
    }
    public function leave()
    {
        return $this->hasMany(Leave²::class);
    }
    public function leaveBalances()
    {
        return $this->hasMany(LeavesBalance::class);
    }
    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function receivedNotifications()
    {
        return $this->hasMany(Notification::class, 'receiver_id');
    }



}
