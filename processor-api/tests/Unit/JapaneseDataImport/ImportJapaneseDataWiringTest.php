<?php

namespace Tests\Unit\JapaneseDataImport;

use App\Console\Commands\ImportJapaneseData;
use App\Support\JapaneseDataImport\JapaneseDataImporter;
use App\Support\JapaneseDataImport\LegacySqlInsertParser;
use PHPUnit\Framework\TestCase;

class ImportJapaneseDataWiringTest extends TestCase
{
    public function testImporterAndCommandCanBeInstantiated(): void
    {
        $importer = new JapaneseDataImporter(new LegacySqlInsertParser());
        $command = new ImportJapaneseData($importer);

        $this->assertInstanceOf(JapaneseDataImporter::class, $importer);
        $this->assertInstanceOf(ImportJapaneseData::class, $command);
    }
}
