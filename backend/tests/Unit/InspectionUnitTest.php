<?php

namespace Tests\Unit;

use App\Models\Agency;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use Database\Seeders\InspectionChecklistItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InspectionUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistItemSeeder::class);
    }

    /** Attach items (by name => status/remarks) to an inspection. */
    private function addItems(Inspection $inspection, array $statuses): void
    {
        foreach ($statuses as $name => $status) {
            $inspection->items()->create([
                'checklist_item_id' => InspectionChecklistItem::where('name', $name)->first()->id,
                'status' => $status,
                'remarks' => $status === 'Has Issue' ? 'issue noted' : null,
            ]);
        }
    }

    public function test_issue_count_and_result_label_compute_correctly(): void
    {
        $allOk = Inspection::factory()->create();
        $this->addItems($allOk, ['Battery' => 'OK', 'Lights' => 'OK']);
        $allOk->load('items');
        $this->assertSame(0, $allOk->issueCount());
        $this->assertSame('All OK', $allOk->resultLabel());

        $oneIssue = Inspection::factory()->create();
        $this->addItems($oneIssue, ['Battery' => 'Has Issue', 'Lights' => 'OK']);
        $oneIssue->load('items');
        $this->assertSame(1, $oneIssue->issueCount());
        $this->assertSame('1 issue', $oneIssue->resultLabel());

        $twoIssues = Inspection::factory()->create();
        $this->addItems($twoIssues, ['Battery' => 'Has Issue', 'Oil' => 'Has Issue', 'Lights' => 'OK']);
        $twoIssues->load('items');
        $this->assertSame('2 issues', $twoIssues->resultLabel());
    }

    public function test_frequent_issues_aggregates_and_orders_by_count(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        $first = Inspection::factory()->create(['agency_id' => $agency->id]);
        $this->addItems($first, ['Brakes' => 'Has Issue', 'Oil' => 'Has Issue']);
        $second = Inspection::factory()->create(['agency_id' => $agency->id]);
        $this->addItems($second, ['Brakes' => 'Has Issue', 'Lights' => 'OK']);

        // Another agency's issues must not leak into the aggregation.
        $foreign = Inspection::factory()->create();
        $this->addItems($foreign, ['Brakes' => 'Has Issue']);

        Sanctum::actingAs($admin);

        $issues = Inspection::frequentIssues();

        $this->assertSame(['Brakes' => 2, 'Oil' => 1], $issues->pluck('count', 'name')->map(fn ($c) => (int) $c)->all());
    }

    public function test_for_agency_returns_bfp_extended_list_only_for_bfp(): void
    {
        $bfp = Agency::factory()->create(['code' => 'BFP']);
        $cho = Agency::factory()->create(['code' => 'CHOX']);

        $bfpItems = InspectionChecklistItem::forAgency($bfp)->get();
        $choItems = InspectionChecklistItem::forAgency($cho)->get();

        $this->assertCount(14, $bfpItems);
        $this->assertCount(12, $choItems);
        $this->assertTrue($bfpItems->pluck('name')->contains('Fire Pump'));
        $this->assertTrue($bfpItems->pluck('name')->contains('Hydraulic System'));
        $this->assertFalse($choItems->pluck('name')->contains('Fire Pump'));
        // Standard items keep their catalog order.
        $this->assertSame('Battery', $choItems->first()->name);
    }
}
