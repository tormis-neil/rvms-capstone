<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Signed-in dashboard pages must not be kept in the browser's back/forward
 * cache. Without `no-store` the Back button replays a snapshot taken before the
 * admin's last change — showing, for example, a vehicle status the database no
 * longer holds — and the pages stay readable after sign-out by pressing Back.
 */
class NoStoreDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
    }

    public static function pages(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'vehicles' => ['/vehicles'],
            'drivers' => ['/drivers'],
            'inspections & damage' => ['/inspections'],
            'repairs' => ['/repairs'],
            'pm schedules' => ['/pm'],
            'dispatch' => ['/dispatch'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_dashboard_pages_are_never_cached(string $url): void
    {
        $response = $this->actingAs($this->admin)->get($url)->assertOk();

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control'),
            "{$url} may be served from the browser's back/forward cache, so Back can "
            .'show data the database no longer holds.'
        );
    }

    /**
     * The login page is public and deliberately not covered — only the pages
     * behind the admin guard carry agency records.
     */
    public function test_the_login_page_is_not_affected(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeaderMissing('Pragma');
    }
}
