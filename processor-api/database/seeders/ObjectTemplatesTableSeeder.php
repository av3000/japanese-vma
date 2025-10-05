<?php

namespace Database\Seeders;

use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Support\Facades\DB;
use App\Http\Models\ObjectTemplate;
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
            DB::table('objecttemplates')->updateOrInsert(
                ['entity_type_uuid' => $case->value],
                ['title' => $case->getTitle()]
            );
        }
    }
}
