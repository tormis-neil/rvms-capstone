<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Concerns\BelongsToAgency;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Fixture standing in for the agency-scoped domain models added in later
 * phases, so multi-admin isolation can be proven at R0 already.
 */
class MultiAdminFixture extends Model
{
    use BelongsToAgency;

    protected $table = 'multi_admin_fixtures';

    protected $guarded = [];

    public $timestamps = false;
}

/**
 * An agency may have MORE THAN ONE administrator account (per the
 * interviews — e.g., logistics and operations officers). No code may
 * assume a single admin per agency (CLAUDE.md design decision 6).
 */
class MultiAdminAgencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('multi_admin_fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable();
            $table->string('name');
        });
    }

    public function test_two_admins_of_the_same_agency_both_authenticate(): void
    {
        $agency = Agency::factory()->create();
        $first = User::factory()->admin()->create(['agency_id' => $agency->id, 'email' => 'a1@example.com', 'password' => 'password']);
        $second = User::factory()->admin()->create(['agency_id' => $agency->id, 'email' => 'a2@example.com', 'password' => 'password']);

        foreach ([$first, $second] as $admin) {
            $this->postJson('/api/v1/login', ['email' => $admin->email, 'password' => 'password'])
                ->assertOk()
                ->assertJsonPath('user.agency.id', $agency->id);
        }
    }

    public function test_two_admins_of_the_same_agency_see_the_same_records(): void
    {
        $agency = Agency::factory()->create();
        $first = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $second = User::factory()->admin()->create(['agency_id' => $agency->id]);

        MultiAdminFixture::withoutGlobalScopes()->insert([
            ['agency_id' => $agency->id, 'name' => 'shared-1'],
            ['agency_id' => $agency->id, 'name' => 'shared-2'],
            ['agency_id' => Agency::factory()->create()->id, 'name' => 'foreign'],
        ]);

        Sanctum::actingAs($first);
        $this->assertSame(['shared-1', 'shared-2'], MultiAdminFixture::pluck('name')->sort()->values()->all());

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($second);
        $this->assertSame(['shared-1', 'shared-2'], MultiAdminFixture::pluck('name')->sort()->values()->all());
    }

    public function test_a_second_admin_still_cannot_see_another_agencys_records(): void
    {
        $agencyA = Agency::factory()->create();
        $agencyB = Agency::factory()->create();
        User::factory()->admin()->create(['agency_id' => $agencyA->id]); // first admin
        $second = User::factory()->admin()->create(['agency_id' => $agencyA->id]);

        MultiAdminFixture::withoutGlobalScopes()->insert([
            ['agency_id' => $agencyA->id, 'name' => 'ours'],
            ['agency_id' => $agencyB->id, 'name' => 'theirs'],
        ]);

        Sanctum::actingAs($second);

        $this->assertSame(['ours'], MultiAdminFixture::pluck('name')->all());
    }
}
