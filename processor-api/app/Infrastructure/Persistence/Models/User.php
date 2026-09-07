<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\UserRole;
use App\Http\Models\Article;
use App\Http\Models\Comment;
use App\Http\Models\CustomList;
use App\Http\Models\Download;
use App\Http\Models\Post;
use App\Http\Models\View;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

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

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->hasRole(UserRole::ADMIN->value);
    }

    /**
     * Boot method - assign default role on creation
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            if (! $user->hasAnyRole(UserRole::values())) {
                $user->assignRole(UserRole::COMMON->value);
            }
        });
    }
}
