<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * One status form, on every screen that can write a status (2026-08).
 *
 * Vehicle Management used to carry its own copy of the Update Vehicle Status
 * modal, and the two drifted: the shared partial said "Remarks (Optional)"
 * while the Vehicles copy required a reason. Both controllers enforced the
 * requirement server-side, so nothing was ever saved without one — but three
 * screens let the admin submit and then bounced them back with an error, and
 * one asked properly up front (lead-reported).
 *
 * Fixing the two files to match would have left two files to drift again, so
 * the copy is gone. These assertions are what stops it coming back.
 */
class StatusFormConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        Vehicle::factory()->create([
            'agency_id' => $agency->id,
            'plate_number' => 'BFP-0001',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    /** Every page that offers a status change. */
    public static function statusWritingPages(): array
    {
        return [
            'vehicles' => ['/vehicles'],
            'repairs' => ['/repairs'],
            'pm' => ['/pm'],
            'inspections' => ['/inspections'],
        ];
    }

    /**
     * The rendered form asks for the reason BEFORE submission, not after.
     *
     * The `required` attribute is the whole point: without it the admin fills
     * the form, submits, and is bounced back by a server rule they were never
     * told about.
     */
    #[DataProvider('statusWritingPages')]
    public function test_the_status_form_demands_a_reason_up_front(string $path): void
    {
        $html = $this->actingAs($this->admin)->get($path)->assertOk()->getContent();

        $this->assertStringContainsString('Reason for this change', $html,
            "{$path} does not label the status remarks field as a required reason.");

        $this->assertMatchesRegularExpression(
            '/<textarea[^>]*name="remarks"[^>]*\brequired\b/',
            $html,
            "{$path} renders the status remarks field without `required`, so the admin "
            .'submits and is bounced back by the server instead of being asked up front.'
        );

        $this->assertStringNotContainsString('Remarks (Optional)', $html,
            "{$path} still calls the status reason optional. It is required.");
    }

    /**
     * Every page renders the SAME markup, because every page includes the same
     * file. Comparing the modal's own fragments is the cheapest way to assert
     * that without parsing HTML.
     */
    public function test_all_four_pages_render_the_identical_modal(): void
    {
        $fragments = [];

        foreach (array_column(self::statusWritingPages(), 0) as $path) {
            $html = $this->actingAs($this->admin)->get($path)->getContent();

            preg_match('/<div class="modal fade" id="updateStatusModal".*?<\/form>/s', $html, $m);

            $this->assertNotEmpty($m, "{$path} does not render the shared status modal.");
            $fragments[$path] = $m[0];
        }

        $first = array_key_first($fragments);

        foreach ($fragments as $path => $fragment) {
            $this->assertSame(
                $fragments[$first],
                $fragment,
                "{$path} renders a DIFFERENT status modal than {$first}. There should be "
                .'exactly one, in partials/update-status-modal.'
            );
        }
    }

    /** The duplicate is gone from the source, not merely matching. */
    public function test_only_the_partial_defines_the_modal(): void
    {
        $definitions = [];

        foreach (glob(resource_path('views/*.blade.php')) as $file) {
            if (str_contains(file_get_contents($file), 'id="updateStatusModal"')) {
                $definitions[] = basename($file);
            }
        }

        $this->assertSame([], $definitions,
            'These page templates define their own status modal: '.implode(', ', $definitions)
            .'. It belongs only in partials/update-status-modal.');
    }

    /**
     * Design decision 9: the admin must see WHERE the current status came from.
     * The Vehicles copy showed the assigned driver instead, which lost it — the
     * merged form shows both.
     */
    public function test_the_form_still_shows_the_status_origin_and_the_driver(): void
    {
        $html = $this->actingAs($this->admin)->get('/vehicles')->getContent();

        $this->assertStringContainsString('id="usOrigin"', $html);
        $this->assertStringContainsString('id="usDriver"', $html);
    }

    /**
     * The reason box must open EMPTY.
     *
     * The old Vehicles copy prefilled it with the stored note, which explains
     * the PREVIOUS change. Carrying that forward would let an admin submit last
     * month's reason for today's change and satisfy the rule without writing
     * anything true.
     */
    public function test_the_reason_box_is_not_prefilled_with_the_previous_note(): void
    {
        $html = $this->actingAs($this->admin)->get('/vehicles')->getContent();

        $this->assertMatchesRegularExpression(
            "/getElementById\('usRemarks'\)\.value = ''/",
            $html,
            'The reason box is not cleared when the modal opens.'
        );
    }
}
