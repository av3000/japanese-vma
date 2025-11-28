<?php

namespace App\Http;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Http\Models\Role;
use App\Http\Models\Article;
use App\Http\Models\Like;
use App\Http\Models\Download;
use App\Http\Models\View;
use App\Http\Models\Comment;
use App\Http\Models\Post;
use App\Http\Models\CustomList;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, HasRoles;

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // TODO: Left for potential referencing, but should be deleted when migrated from legacy user/role tables
    //  public function roles()
    // {
    //     return $this->belongsToMany(Role::class, "user_role");
    // }

    // /**
    //  * Check one role
    //  * @param string $role
    //  */
    // public function hasRole($role)
    // {
    //   return null !== $this->roles()->where('name', $role)->first();
    // }

    // /**
    //  * Return first role
    //  * @param string $role
    //  */
    // public function role() {
    //     if (empty($this->roles()->first())) {
    //       return '---';
    //     }

    //     return $this->roles()->first()->name;

    // }

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
