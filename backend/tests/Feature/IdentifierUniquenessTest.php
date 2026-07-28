<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Identifiers that name one physical thing must not be duplicated (FR-05, FR-06).
 *
 * The plate number was already protected by validation plus a unique index. The
 * engine number, chassis number and driver license number were not, so the same
 * vehicle or the same person could be entered twice — and a duplicated licence
 * is counted twice in the FR-08 expiring-licence figures, from two rows that can
 * hold different expiry dates.
 *
 * All three are nullable, so blanks must never collide.
 */
class IdentifierUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    private function vehicle(array $overrides = []): array
    {
        return array_merge([
            'type' => 'Fire Truck',
            'plate_number' => 'ABC-1234',
            'make' => 'Isuzu',
            'model' => 'FTR 850',
            'engine_number' => 'ENG-001',
            'chassis_number' => 'CHS-001',
            'current_mileage' => 100,
        ], $overrides);
    }

    private function driver(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@rvms.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'license_number' => 'N01-23-456789',
            'license_expiry_date' => now()->addYear()->toDateString(),
        ], $overrides);
    }

    public function test_a_duplicate_engine_number_is_refused(): void
    {
        $this->actingAs($this->admin)->from('/vehicles')
            ->post('/vehicles', $this->vehicle())->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->from('/vehicles')
            ->post('/vehicles', $this->vehicle([
                'plate_number' => 'XYZ-9999', 'chassis_number' => 'CHS-002',
            ]))
            ->assertSessionHasErrors('engine_number');

        $this->assertSame(1, Vehicle::withoutGlobalScopes()->count());
    }

    public function test_a_duplicate_chassis_number_is_refused(): void
    {
        $this->actingAs($this->admin)->from('/vehicles')
            ->post('/vehicles', $this->vehicle())->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->from('/vehicles')
            ->post('/vehicles', $this->vehicle([
                'plate_number' => 'XYZ-9999', 'engine_number' => 'ENG-002',
            ]))
            ->assertSessionHasErrors('chassis_number');

        $this->assertSame(1, Vehicle::withoutGlobalScopes()->count());
    }

    /** Nullable: several vehicles may have no engine/chassis number recorded. */
    public function test_blank_vehicle_identifiers_never_collide(): void
    {
        foreach (['AAA-1111', 'BBB-2222', 'CCC-3333'] as $plate) {
            $this->actingAs($this->admin)->from('/vehicles')
                ->post('/vehicles', $this->vehicle([
                    'plate_number' => $plate, 'engine_number' => '', 'chassis_number' => '',
                ]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Vehicle::withoutGlobalScopes()->count());
    }

    /** Editing a vehicle must not collide with its own numbers. */
    public function test_editing_a_vehicle_keeps_its_own_identifiers(): void
    {
        $this->actingAs($this->admin)->from('/vehicles')->post('/vehicles', $this->vehicle());
        $vehicle = Vehicle::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->admin)->from('/vehicles')
            ->put("/vehicles/{$vehicle->id}", $this->vehicle(['current_mileage' => 500]))
            ->assertSessionHasNoErrors();

        $this->assertSame(500, $vehicle->fresh()->current_mileage);
    }

    public function test_a_duplicate_license_number_is_refused(): void
    {
        $this->actingAs($this->admin)->from('/drivers')
            ->post('/drivers', $this->driver())->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->from('/drivers')
            ->post('/drivers', $this->driver([
                'email' => 'juan2@rvms.local', 'name' => 'Juan D. Cruz',
            ]))
            ->assertSessionHasErrors('license_number');

        $this->assertSame(1, User::where('license_number', 'N01-23-456789')->count());
    }

    /** Namesakes are real people — the name is deliberately not an identifier. */
    public function test_two_drivers_may_share_a_name(): void
    {
        $this->actingAs($this->admin)->from('/drivers')->post('/drivers', $this->driver());

        $this->actingAs($this->admin)->from('/drivers')
            ->post('/drivers', $this->driver([
                'email' => 'juan3@rvms.local', 'license_number' => 'N01-99-111111',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, User::where('name', 'Juan Dela Cruz')->count());
    }

    /** Nullable: a driver may be recorded with no licence number yet. */
    public function test_blank_license_numbers_never_collide(): void
    {
        foreach (['a@rvms.local', 'b@rvms.local', 'c@rvms.local'] as $email) {
            $this->actingAs($this->admin)->from('/drivers')
                ->post('/drivers', $this->driver([
                    'email' => $email, 'license_number' => '', 'license_expiry_date' => '',
                ]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, User::query()->drivers()->count());
    }

    /**
     * Per agency, not global: a rejection must never reveal that a DIFFERENT
     * agency holds that identifier (FR-02).
     */
    public function test_identifiers_are_scoped_per_agency(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);

        $this->actingAs($this->admin)->from('/vehicles')->post('/vehicles', $this->vehicle());
        $this->actingAs($this->admin)->from('/drivers')->post('/drivers', $this->driver());

        $this->actingAs($otherAdmin)->from('/vehicles')
            ->post('/vehicles', $this->vehicle())
            ->assertSessionHasNoErrors();

        $this->actingAs($otherAdmin)->from('/drivers')
            ->post('/drivers', $this->driver(['email' => 'pnp.juan@rvms.local']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Vehicle::withoutGlobalScopes()->count());
        $this->assertSame(2, User::where('license_number', 'N01-23-456789')->count());
    }

    /** Email stays globally unique — it is the login identifier. */
    public function test_email_remains_unique_across_agencies(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);

        $this->actingAs($this->admin)->from('/drivers')->post('/drivers', $this->driver());

        $this->actingAs($otherAdmin)->from('/drivers')
            ->post('/drivers', $this->driver(['license_number' => 'N01-88-222222']))
            ->assertSessionHasErrors('email');
    }

    public function test_the_api_refuses_duplicate_vehicle_identifiers(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/vehicles', $this->vehicle())->assertCreated();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/vehicles', $this->vehicle(['plate_number' => 'XYZ-9999']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['engine_number', 'chassis_number']);
    }
}
