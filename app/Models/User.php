<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        return [];
    }
    public function getAvatarPathAttribute()
    {
        return $this->attributes['avatar_path']
            ? asset($this->attributes['avatar_path'])
            : null;
    }
    //relation avec table leaverequest
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
    //relation avec table leavebalance
    public function leaveBalances() 
    {
        return $this->hasMany(LeaveBalance::class);
    }

}
