<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

     public function roles()
    {
        return $this->belongsToMany(Role::class, "user_role");
    }

    public function role() {
        if (empty($this->roles()->first())) {
          return '---';
        }
  
        return $this->roles()->first()->first()->name;
        
    }

    public function articles() 
    {
        return $this->hasMany(Article::class);
    }

    public function likes() 
    {
        return $this->hasMany(Like::class);
    }

    public function downloads() 
    {
        return $this->hasMany(Download::class);
    }

    public function views() 
    {
        return $this->hasMany(View::class);
    }

    public function comments() 
    {
        return $this->hasMany(Comment::class);
    }

    public function posts() 
    {
        return $this->hasMany(Post::class);
    }

    public function lists() 
    {
        return $this->hasMany(CustomList::class);
    }
}
