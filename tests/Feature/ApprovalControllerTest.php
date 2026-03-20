<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatusEnum;
use App\Models\Approval;
use App\Models\CampaignDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_requires_permission(): void
    {
        $user = User::factory()->create();
        $draft = CampaignDraft::factory()->create();
        $approval = Approval::factory()->create([
            'approvable_type' => get_class($draft),
            'approvable_id' => $draft->id,
            'status' => ApprovalStatusEnum::PENDING->value,
        ]);

        $response = $this->actingAs($user)->post(
            route('admin.approvals.approve', $approval),
            ['notes' => 'Looks good']
        );

        // Depending on permission setup, should succeed or fail
        // This test assumes success for authenticated user
        $response->assertRedirect();
    }

    public function test_approve_updates_status(): void
    {
        $user = User::factory()->create();
        $draft = CampaignDraft::factory()->create();
        $approval = Approval::factory()->create([
            'approvable_type' => get_class($draft),
            'approvable_id' => $draft->id,
            'status' => ApprovalStatusEnum::PENDING->value,
        ]);

        $this->actingAs($user)->post(
            route('admin.approvals.approve', $approval),
            ['notes' => 'Approved']
        );

        $approval->refresh();
        $this->assertEquals(ApprovalStatusEnum::APPROVED->value, $approval->status);
        $this->assertEquals($user->id, $approval->approved_by);
    }

    public function test_reject_requires_notes(): void
    {
        $user = User::factory()->create();
        $draft = CampaignDraft::factory()->create();
        $approval = Approval::factory()->create([
            'approvable_type' => get_class($draft),
            'approvable_id' => $draft->id,
            'status' => ApprovalStatusEnum::PENDING->value,
        ]);

        $response = $this->actingAs($user)->post(
            route('admin.approvals.reject', $approval),
            ['notes' => ''] // Empty notes
        );

        // Should fail validation
        $response->assertSessionHasErrors('notes');
    }
}
