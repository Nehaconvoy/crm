<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use \App\Models\TeamLeader;
use \App\Models\Manger;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'department',
        'manager',
        'team_lead',
        'office',
        'contact_number',
        'status',
        
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

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function department() 
    {
        return $this->belongsTo(Department::class, 'department');
    }

    public function managers()
    {
        return $this->belongsTo(Manger::class, 'manager'); 
    }

    public function teamleader()
    {
        return $this->belongsTo(TeamLeader::class, 'team_lead');
    } 

    public function office()
    {
        return $this->belongsTo(Office::class, 'office');
    }

    public function officedata()
    {
        return $this->belongsTo(Office::class, 'office');
    }

    public function team_lead()
    {
        return $this->hasOne(TeamLeader::class); // Assuming 'user_id' is the foreign key in 'team_leads' table
    }

    public function teamLeaderInfo()
    {
         return $this->belongsTo(TeamLeader::class, 'team_lead', 'id');
    }

    public function managerInfo()
    {
		return $this->belongsTo(Manger::class, 'manager', 'id');
    }

    



}