<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * No page may print its own source to the browser.
 *
 * A Blade comment opens with `{{--` and closes with `--}}`. The login page
 * opened one and closed it with `-->`, the HTML form. Blade never found its
 * terminator, so it treated the whole block as ordinary text and printed a
 * paragraph of developer notes under the sign-in form — visible to every user,
 * on the first page anyone sees (2026-08, lead-reported).
 *
 * Nothing else could catch it. The page returned 200, the form worked, the
 * markup was valid, and every existing test passed: they assert what a page
 * CONTAINS, and this was a fault of the page containing something extra.
 *
 * So this asserts the negative — that the rendered HTML carries no Blade
 * delimiter and no stray directive, which is only ever true when every comment
 * and expression is closed properly.
 */
class PagesDoNotLeakSourceTest extends TestCase
{
    use RefreshDatabase;

    /** Fragments that mean Blade failed to parse something. */
    private const LEAKS = ['{{--', '--}}', '{{ $', '@endsection', '@endif', '@foreach'];

    /** @return array<string, array{string}> */
    public static function guestPages(): array
    {
        return [
            'login' => ['/login'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function adminPages(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'vehicles' => ['/vehicles'],
            'drivers' => ['/drivers'],
            'inspections' => ['/inspections'],
            'repairs' => ['/repairs'],
            'pm' => ['/pm'],
            'dispatch' => ['/dispatch'],
            'notifications' => ['/notifications'],
            'reports' => ['/reports'],
            'profile' => ['/profile'],
        ];
    }

    #[DataProvider('guestPages')]
    public function test_a_guest_page_does_not_print_its_own_source(string $path): void
    {
        $this->assertNoLeak($this->get($path)->getContent(), $path);
    }

    #[DataProvider('adminPages')]
    public function test_an_admin_page_does_not_print_its_own_source(string $path): void
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        $html = $this->actingAs($admin)->get($path)->getContent();

        $this->assertNoLeak($html, $path);
    }

    private function assertNoLeak(string $html, string $path): void
    {
        foreach (self::LEAKS as $fragment) {
            $this->assertStringNotContainsString(
                $fragment,
                $html,
                "{$path} rendered the literal text \"{$fragment}\" to the browser. That means a "
                .'Blade comment or expression was never closed, so the page is printing its own '
                .'source where a reader can see it.'
            );
        }
    }
}
