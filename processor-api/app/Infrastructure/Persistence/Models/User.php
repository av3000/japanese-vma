<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\UserRole;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
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

    protected $table = 'users';

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

    /**
     * Boot method - assign default role on creation
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            if (!$user->hasAnyRole(UserRole::values())) {
                $user->assignRole(UserRole::COMMON->value);
            }
        });
    }
}
