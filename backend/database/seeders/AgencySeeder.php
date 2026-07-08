<?php

namespace Database\Seeders;

use App\Models\Agency;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    /**
     * Seed the four Calbayog City rescue agencies (FR-02).
     */
    public function run(): void
    {
        $agencies = [
            [
                'code' => 'BFP',
                'name' => 'Bureau of Fire Protection',
                'location' => 'Calbayog City Fire Station, Calbayog City, Samar',
                'contact_number' => '(055) 209-1234',
                'email' => 'bfp.calbayog@bfp.gov.ph',
                'logo_path' => 'logos/bfp-logo.jpg',
            ],
            [
                'code' => 'PNP',
                'name' => 'Philippine National Police',
                'location' => 'Calbayog City Police Station, Calbayog City, Samar',
                'contact_number' => '(055) 209-2345',
                'email' => 'pnp.calbayog@pnp.gov.ph',
                'logo_path' => 'logos/pnp-logo.jpg',
            ],
            [
                'code' => 'CDRRMO',
                'name' => 'City Disaster Risk Reduction and Management Office',
                'location' => 'Calbayog City Hall Compound, Calbayog City, Samar',
                'contact_number' => '(055) 209-3456',
                'email' => 'cdrrmo@calbayogcity.gov.ph',
                'logo_path' => 'logos/cdrrmo-logo.jpg',
            ],
            [
                'code' => 'CHO',
                'name' => 'City Health Office',
                'location' => 'Calbayog City Health Compound, Calbayog City, Samar',
                'contact_number' => '(055) 209-4567',
                'email' => 'cho@calbayogcity.gov.ph',
                'logo_path' => 'logos/cho-logo.png',
            ],
        ];

        foreach ($agencies as $agency) {
            Agency::updateOrCreate(
                ['code' => $agency['code']],
                $agency + ['license_expiry_warning_days' => 30],
            );
        }
    }
}
