<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
// TODO: use proper role system when implementing Spatie roles package
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
    use Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'uuid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function hasRole(string $role): bool
    {
        return null !== $this->roles()->where('name', $role)->first();
    }

    public function role(): string
    {
        return $this->roles()->first()?->name ?? 'user';
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
