<?php

namespace Tests\Unit;

use App\Enums\ApprovalStatusEnum;
use App\Enums\ApprovalTypeEnum;
use App\Models\Approval;
use App\Models\CampaignDraft;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->approvalService = app(ApprovalService::class);
    }

    public function test_request_approval_creates_record(): void
    {
        $user = User::factory()->create();
        $draft = CampaignDraft::factory()->create();

        $approval = $this->approvalService->requestApproval(
            $draft,
            ApprovalTypeEnum::CAMPAIGN_DRAFT_PUBLISH,
            $user,
            ['test' => 'payload'],
            'Test notes'
        );

        $this->assertInstanceOf(Approval::class, $approval);
        $this->assertEquals(ApprovalStatusEnum::PENDING->value, $approval->status);
        $this->assertEquals($user->id, $approval->requested_by);
        $this->assertEquals('Test notes', $approval->notes);
    }

    public function test_approve_only_works_on_pending(): void
    {
        $user = User::factory()->create();
        $draft = CampaignDraft::factory()->create();

        $approval = Approval::factory()->create([
            'approvable_type' => get_class($draft),
            'approvable_id' => $draft->id,
            'status' => ApprovalStatusEnum::APPROVED->value,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending approvals can be approved');

        $this->approvalService->approve($approval, $user);
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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Notes are required when rejecting');

        $this->approvalService->reject($approval, $user, '');
    }
}
