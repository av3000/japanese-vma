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
    }
}
