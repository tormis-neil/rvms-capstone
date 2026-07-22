<?php

namespace Tests\Feature;

use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public agency directory (FR-03) — feeds the mobile driver self-registration
 * dropdown so a prospective driver can submit a real agency_id.
 */
class AgencyDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_agencies_are_listed_publicly_with_id_code_name(): void
    {
        Agency::factory()->create(['code' => 'BFP', 'name' => 'Bureau of Fire Protection']);
        Agency::factory()->create(['code' => 'PNP', 'name' => 'Philippine National Police']);

        $response = $this->getJson('/api/v1/agencies');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'BFP')
            ->assertJsonPath('data.0.name', 'Bureau of Fire Protection')
            ->assertJsonStructure(['data' => [['id', 'code', 'name']]]);
    }

    public function test_directory_does_not_leak_sensitive_agency_fields(): void
    {
        Agency::factory()->create(['code' => 'CHO']);

        $response = $this->getJson('/api/v1/agencies');

        $response->assertOk()
            ->assertJsonMissing(['license_expiry_warning_days' => 30])
            ->assertJsonMissingPath('data.0.contact_number')
            ->assertJsonMissingPath('data.0.email');
    }
}
