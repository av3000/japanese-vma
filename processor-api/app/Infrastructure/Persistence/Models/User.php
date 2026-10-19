<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Shared\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(View::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function catalogues(): HasMany
    {
        return $this->hasMany(Catalogue::class);
    }

    /**
     * Any catalogue permission opens the panel; each resource policy then narrows what is visible.
     * Recovery when nobody can sign in: `php artisan tinker` then
     * `User::where('email', '...')->first()->assignRole('admin')`.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->isPanelSuperuser()
            || $this->hasAnyPermission(PermissionCatalog::allPermissionNames());
    }

    /**
     * The `admin` role passes every panel permission check without holding the permission rows,
     * so a missed seeder run cannot lock the only administrator out. Policy invariants such as
     * "users are never deleted" still apply after this check.
     */
    public function hasPanelPermission(string $permission): bool
    {
        return $this->isPanelSuperuser() || $this->checkPermissionTo($permission);
    }

    public function isPanelSuperuser(): bool
    {
        return $this->hasRole(UserRole::ADMIN->value);
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
