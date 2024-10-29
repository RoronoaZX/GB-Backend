<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'employee_id',
        'password',
        'role',

    ];

    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'LIKE', "%{$keyword}%")
                     ->orWhere('status', 'LIKE', "%{$keyword}%")
                     ->orWhere('role', 'LIKE', "%{$keyword}%");

    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'id', 'employee_id');
    }

    // public function branchEmployee()
    // {
    //     return $this->hasOne(BranchEmployee::class, 'user_id','id');
    // }

    public function salesReports()
    {
        return $this->hasMany(SalesReports::class);
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


}
