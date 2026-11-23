<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Issue #612: `articles.create`, `catalogues.create` and `comments.update` were in the panel
 * permission catalogue but no policy or action ever checked them. A role holding only one of
 * them could still enter /admin and see nothing. PermissionSeeder keeps permissions that leave
 * the catalogue, so the rows are removed here, with every role and user grant of them.
 *
 * The names are listed literally on purpose: a migration must not depend on code that changes.
 */
return new class extends Migration
{
    private const REMOVED = ['articles.create', 'catalogues.create', 'comments.update'];

    private const GUARD = 'api';

    public function up(): void
    {
        $ids = DB::table('permissions')
            ->where('guard_name', self::GUARD)
            ->whereIn('name', self::REMOVED)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Restores the permission rows only; the grants they had are gone.
     */
    public function down(): void
    {
        foreach (self::REMOVED as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => self::GUARD,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
