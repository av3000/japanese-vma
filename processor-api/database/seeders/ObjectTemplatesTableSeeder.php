<?php

namespace Database\Seeders;

use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ObjectTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (ObjectTemplateType::cases() as $case) {
            $existing = DB::table('objecttemplates')
                ->where('entity_type_uuid', $case->value)
                ->first();

            if ($existing) {
                DB::table('objecttemplates')
                    ->where('id', $existing->id)
                    ->update(['title' => $case->getTitle()]);

                continue;
            }

            DB::table('objecttemplates')->insert([
                'id' => $case->getLegacyId(),
                'entity_type_uuid' => $case->value,
                'title' => $case->getTitle(),
            ]);
        }

        // Rows are inserted with explicit legacy ids, so move the PostgreSQL
        // sequence past them or the next auto-generated id would collide.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('objecttemplates', 'id'), COALESCE(MAX(id), 1), true) FROM objecttemplates"
            );
        }
    }
}
