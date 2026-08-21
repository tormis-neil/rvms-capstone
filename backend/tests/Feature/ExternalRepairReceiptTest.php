<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Proof of an external repair (FR-13, FR-14 — adviser consultation, 2026-08).
 *
 * These agencies are government offices spending public money at private
 * shops, and both modules that could name an external shop stored only a typed
 * name with nothing to verify it against. The supporting document now lives
 * with the record.
 *
 * Scoped to the EXTERNAL source in both modules, deliberately: work done by the
 * Internal Office or the GSO Motorpool has no third-party receipt to attach, so
 * demanding one would make those two sources impossible to record.
 */
class ExternalRepairReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        Sanctum::actingAs($this->admin);
    }

    private function repairPayload(string $source, array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->vehicle->id,
            'repair_date' => now()->toDateString(),
            'scope_of_work' => 'Engine overhaul',
            'repair_source' => $source,
        ], $overrides);
    }

    /* ---------------------------- repair logs ----------------------------- */

    public function test_an_external_repair_is_refused_without_a_receipt(): void
    {
        $this->postJson('/api/v1/repairs', $this->repairPayload(RepairLog::SOURCE_EXTERNAL, [
            'external_shop_name' => 'Calbayog Auto Works',
        ]))->assertStatus(422)->assertJsonValidationErrors('receipt');

        $this->assertDatabaseCount('repair_logs', 0);
    }

    public function test_an_external_repair_stores_the_receipt(): void
    {
        $this->postJson('/api/v1/repairs', $this->repairPayload(RepairLog::SOURCE_EXTERNAL, [
            'external_shop_name' => 'Calbayog Auto Works',
            'receipt' => UploadedFile::fake()->create('or-1234.pdf', 200, 'application/pdf'),
        ]))->assertCreated();

        $repair = RepairLog::query()->firstOrFail();

        $this->assertNotNull($repair->receipt_path);
        Storage::disk('public')->assertExists($repair->receipt_path);
    }

    /**
     * The two in-house sources must stay recordable without one — there is no
     * third-party document for work the office did itself.
     */
    public function test_in_house_repairs_need_no_receipt(): void
    {
        foreach ([RepairLog::SOURCE_INTERNAL, RepairLog::SOURCE_GSO] as $source) {
            $this->postJson('/api/v1/repairs', $this->repairPayload($source))->assertCreated();
        }

        $this->assertDatabaseCount('repair_logs', 2);
    }

    /** A photo of a receipt is as good as a scan — most will be phone photos. */
    public function test_an_image_is_accepted_as_well_as_a_pdf(): void
    {
        $this->postJson('/api/v1/repairs', $this->repairPayload(RepairLog::SOURCE_EXTERNAL, [
            'external_shop_name' => 'Calbayog Auto Works',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ]))->assertCreated();
    }

    /**
     * SVG stays refused, for the reason recorded on the damage photo: it is a
     * document that can carry scripts, and served back from /storage it would
     * execute in the dashboard's own origin when an admin clicks View (stored
     * XSS, security audit R10.2). Widening the upload surface must not widen
     * that hole.
     */
    public function test_an_svg_is_refused(): void
    {
        $this->postJson('/api/v1/repairs', $this->repairPayload(RepairLog::SOURCE_EXTERNAL, [
            'external_shop_name' => 'Calbayog Auto Works',
            'receipt' => UploadedFile::fake()->create('receipt.svg', 10, 'image/svg+xml'),
        ]))->assertStatus(422)->assertJsonValidationErrors('receipt');
    }

    public function test_a_file_over_five_megabytes_is_refused(): void
    {
        $this->postJson('/api/v1/repairs', $this->repairPayload(RepairLog::SOURCE_EXTERNAL, [
            'external_shop_name' => 'Calbayog Auto Works',
            'receipt' => UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf'),
        ]))->assertStatus(422)->assertJsonValidationErrors('receipt');
    }

    /**
     * Editing a record that already has a receipt must not demand it again —
     * otherwise correcting a typo in the shop name would mean hunting down the
     * original document. It also lets records created before this rule existed
     * still be edited.
     */
    public function test_editing_a_repair_that_already_has_a_receipt_does_not_demand_another(): void
    {
        $repair = RepairLog::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'repair_source' => RepairLog::SOURCE_EXTERNAL,
            'external_shop_name' => 'Calbayog Auto Works',
            'receipt_path' => 'repair-receipts/existing.pdf',
        ]);

        $this->putJson("/api/v1/repairs/{$repair->id}", $this->repairPayload(RepairLog::SOURCE_EXTERNAL, [
            'external_shop_name' => 'Calbayog Auto Works Inc.',
        ]))->assertOk();

        // The original attachment survives an edit that did not replace it.
        $this->assertSame('repair-receipts/existing.pdf', $repair->fresh()->receipt_path);
    }

    /**
     * Correcting the source clears the shop NAME but keeps the DOCUMENT
     * (2026-08, revised after lead review).
     *
     * The name identifies a private business and means nothing on a record that
     * now says the GSO Motorpool did the work. The document is different: every
     * source may carry one now, so wiping it on a source change would destroy
     * the job order that the correction was made to record.
     */
    public function test_correcting_the_source_clears_the_shop_name_but_keeps_the_document(): void
    {
        $repair = RepairLog::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'repair_source' => RepairLog::SOURCE_EXTERNAL,
            'external_shop_name' => 'Calbayog Auto Works',
            'receipt_path' => 'repair-receipts/existing.pdf',
        ]);

        $this->putJson("/api/v1/repairs/{$repair->id}", $this->repairPayload(RepairLog::SOURCE_GSO))
            ->assertOk();

        $this->assertNull($repair->fresh()->external_shop_name);
        $this->assertSame('repair-receipts/existing.pdf', $repair->fresh()->receipt_path);
    }

    /* ----------------- the GSO Motorpool, specifically -------------------- */

    /**
     * A GSO Motorpool job order can be attached (2026-08, lead-reported).
     *
     * The Motorpool is another City office, not a private shop — the vehicle
     * still leaves and comes back with paperwork, so the record should be able
     * to hold it.
     */
    public function test_a_gso_job_order_can_be_attached(): void
    {
        $this->postJson('/api/v1/repairs', $this->repairPayload(RepairLog::SOURCE_GSO, [
            'receipt' => UploadedFile::fake()->create('job-order-88.pdf', 150, 'application/pdf'),
        ]))->assertCreated();

        $repair = RepairLog::query()->firstOrFail();

        $this->assertNotNull($repair->receipt_path, 'A GSO job order was accepted but not stored.');
        Storage::disk('public')->assertExists($repair->receipt_path);
    }

    /**
     * ...but it is not demanded. The interviews established that the Motorpool
     * does the work, not that it always issues paperwork the agency keeps.
     * Requiring it on that assumption would leave the repair unrecordable, and
     * no record is worse than one without an attachment.
     */
    public function test_a_gso_repair_without_a_job_order_is_still_recordable(): void
    {
        $this->postJson('/api/v1/repairs', $this->repairPayload(RepairLog::SOURCE_GSO))
            ->assertCreated();

        $this->assertDatabaseCount('repair_logs', 1);
    }

    /**
     * The list is the single switch. If the Motorpool later confirms a job
     * order is always issued, adding SOURCE_GSO to it is the whole change —
     * both form requests and both dashboards read it.
     */
    public function test_only_the_external_shop_is_on_the_mandatory_list(): void
    {
        $this->assertSame([RepairLog::SOURCE_EXTERNAL], RepairLog::REQUIRED_DOCUMENT_SOURCES);

        // Every source is named in the hints, so the form can always say what
        // it is asking for.
        foreach (RepairLog::SOURCES as $source) {
            $this->assertArrayHasKey($source, RepairLog::DOCUMENT_HINTS);
        }
    }

    /* --------------------------- PM completion ---------------------------- */

    private function schedule(): PmSchedule
    {
        return PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => PmSchedule::STATUS_DUE,
        ]);
    }

    public function test_completing_a_pm_externally_is_refused_without_a_receipt(): void
    {
        $schedule = $this->schedule();

        $this->patchJson("/api/v1/pm-schedules/{$schedule->id}/complete", [
            'date_serviced' => now()->toDateString(),
            'completion_repair_source' => RepairLog::SOURCE_EXTERNAL,
            'completion_external_shop_name' => 'Calbayog Diesel Works',
        ])->assertStatus(422)->assertJsonValidationErrors('completion_receipt');

        $this->assertNotSame(PmSchedule::STATUS_COMPLETED, $schedule->fresh()->status);
    }

    public function test_completing_a_pm_externally_stores_the_receipt(): void
    {
        $schedule = $this->schedule();

        $this->patchJson("/api/v1/pm-schedules/{$schedule->id}/complete", [
            'date_serviced' => now()->toDateString(),
            'completion_repair_source' => RepairLog::SOURCE_EXTERNAL,
            'completion_external_shop_name' => 'Calbayog Diesel Works',
            'completion_receipt' => UploadedFile::fake()->create('or-9876.pdf', 200, 'application/pdf'),
        ])->assertOk();

        $schedule = $schedule->fresh();

        $this->assertSame(PmSchedule::STATUS_COMPLETED, $schedule->status);
        $this->assertNotNull($schedule->completion_receipt_path);
        Storage::disk('public')->assertExists($schedule->completion_receipt_path);
    }

    public function test_completing_a_pm_in_house_needs_no_receipt(): void
    {
        $schedule = $this->schedule();

        $this->patchJson("/api/v1/pm-schedules/{$schedule->id}/complete", [
            'date_serviced' => now()->toDateString(),
            'completion_repair_source' => RepairLog::SOURCE_INTERNAL,
        ])->assertOk();

        $this->assertSame(PmSchedule::STATUS_COMPLETED, $schedule->fresh()->status);
    }

    /**
     * The two modules record the same fact from the same three sources, so a
     * rule that held in one and not the other would be a hole rather than a
     * design. Both refusals must also read identically.
     */
    public function test_both_modules_word_the_refusal_the_same_way(): void
    {
        $repairMessage = $this->postJson('/api/v1/repairs', $this->repairPayload(RepairLog::SOURCE_EXTERNAL, [
            'external_shop_name' => 'Calbayog Auto Works',
        ]))->json('errors.receipt.0');

        $pmMessage = $this->patchJson("/api/v1/pm-schedules/{$this->schedule()->id}/complete", [
            'date_serviced' => now()->toDateString(),
            'completion_repair_source' => RepairLog::SOURCE_EXTERNAL,
            'completion_external_shop_name' => 'Calbayog Diesel Works',
        ])->json('errors.completion_receipt.0');

        $this->assertSame($repairMessage, $pmMessage);
    }
}
