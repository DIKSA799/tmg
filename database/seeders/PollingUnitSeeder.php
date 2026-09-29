<?php

namespace Database\Seeders;

use App\Services\Geography\GeographyImporter;
use Illuminate\Database\Seeder;

class PollingUnitSeeder extends Seeder
{
    public function run(GeographyImporter $importer): void
    {
        $path = database_path('data/nigeria_polling_units.csv');
        $path = is_file($path) ? $path : base_path('Nigeria_polling_units.csv');

        $counts = $importer->import($path);

        $this->command?->info(sprintf(
            'Geography imported: %d states, %d LGAs, %d wards, %d polling units.',
            $counts['states'],
            $counts['lgas'],
            $counts['wards'],
            $counts['polling_units'],
        ));
    }
}
