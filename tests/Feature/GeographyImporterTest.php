<?php

namespace Tests\Feature;

use App\Services\Geography\GeographyImporter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GeographyImporterTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fixture(): string
    {
        return base_path('tests/Fixtures/polling_units.csv');
    }

    public function test_import_builds_the_full_hierarchy_from_csv(): void
    {
        $counts = app(GeographyImporter::class)->import($this->fixture());

        $this->assertSame(['states' => 2, 'lgas' => 3, 'wards' => 3, 'polling_units' => 4], $counts);

        $this->assertDatabaseHas('states', ['slug' => 'delta', 'name' => 'Delta', 'code' => '10']);
        $this->assertDatabaseHas('states', ['slug' => 'abia', 'name' => 'Abia', 'code' => '01']);
        $this->assertDatabaseHas('lgas', ['name' => 'Ndokwa West', 'code' => '12']);
        $this->assertDatabaseHas('wards', ['name' => 'Utagba Ogbe', 'code' => '01']);
        $this->assertDatabaseHas('polling_units', ['code' => '10/12/01/033', 'name' => 'in front of new post office, i']);
    }

    public function test_import_is_idempotent_when_run_twice(): void
    {
        $importer = app(GeographyImporter::class);

        $importer->import($this->fixture());
        $second = $importer->import($this->fixture());

        $this->assertSame(['states' => 0, 'lgas' => 0, 'wards' => 0, 'polling_units' => 0], $second);
        $this->assertDatabaseCount('polling_units', 4);
        $this->assertDatabaseCount('wards', 3);
    }
}
