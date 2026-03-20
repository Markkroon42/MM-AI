<?php

namespace App\Services\Approval;

use App\Enums\ApprovalStatusEnum;
use App\Enums\ApprovalTypeEnum;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ApprovalService
{
    public function requestApproval(
        Model $approvable,
        ApprovalTypeEnum $type,
        ?User $requestedBy = null,
        array $payload = [],
        ?string $notes = null
    ): Approval {
        $requestedBy = $requestedBy ?? auth()->user();

        Log::info('[APPROVAL_SERVICE] Creating approval request', [
            'approvable_type' => get_class($approvable),
            'approvable_id' => $approvable->id,
            'approval_type' => $type->value,
            'requested_by' => $requestedBy?->id,
        ]);

        $approval = Approval::create([
            'approvable_type' => get_class($approvable),
            'approvable_id' => $approvable->id,
            'approval_type' => $type->value,
            'status' => ApprovalStatusEnum::PENDING->value,
            'requested_by' => $requestedBy?->id,
            'requested_at' => now(),
            'notes' => $notes,
            'payload_json' => $payload,
        ]);

        AuditLog::log(
            'approval_requested',
            $approval,
            null,
            [
                'approval_type' => $type->value,
                'approvable_type' => get_class($approvable),
                'approvable_id' => $approvable->id,
            ]
        );

        return $approval;
    }

    public function approve(Approval $approval, User $user, ?string $notes = null): Approval
    {
        // Guardrail: Only pending approvals can be approved
        if ($approval->status !== ApprovalStatusEnum::PENDING->value) {
            Log::warning('[APPROVAL_SERVICE] Attempted to approve non-pending approval', [
                'approval_id' => $approval->id,
                'current_status' => $approval->status,
                'user_id' => $user->id,
            ]);

            throw new \Exception("Cannot approve approval with status: {$approval->status}. Only pending approvals can be approved.");
        }

        Log::info('[APPROVAL_SERVICE] Approving approval', [
            'approval_id' => $approval->id,
            'approved_by' => $user->id,
        ]);

        $oldStatus = $approval->status;

        $approval->update([
            'status' => ApprovalStatusEnum::APPROVED->value,
            'approved_by' => $user->id,
            'decided_at' => now(),
            'notes' => $notes ? ($approval->notes ? $approval->notes . "\n\n" . $notes : $notes) : $approval->notes,
        ]);

        AuditLog::log(
            'approval_approved',
            $approval,
            ['status' => $oldStatus],
            ['status' => ApprovalStatusEnum::APPROVED->value],
            [
                'approved_by' => $user->id,
                'notes' => $notes,
            ]
        );

        Log::info('[APPROVAL_SERVICE] Approval approved successfully', [
            'approval_id' => $approval->id,
        ]);

        return $approval->fresh();
    }

    public function reject(Approval $approval, User $user, string $notes): Approval
    {
        // Guardrail: Only pending approvals can be rejected
        if ($approval->status !== ApprovalStatusEnum::PENDING->value) {
            Log::warning('[APPROVAL_SERVICE] Attempted to reject non-pending approval', [
                'approval_id' => $approval->id,
                'current_status' => $approval->status,
                'user_id' => $user->id,
            ]);

            throw new \Exception("Cannot reject approval with status: {$approval->status}. Only pending approvals can be rejected.");
        }

        // Guardrail: Notes are required for rejection
        if (empty($notes)) {
            Log::warning('[APPROVAL_SERVICE] Attempted to reject without notes', [
                'approval_id' => $approval->id,
                'user_id' => $user->id,
            ]);

            throw new \Exception("Notes are required when rejecting an approval.");
        }

        Log::info('[APPROVAL_SERVICE] Rejecting approval', [
            'approval_id' => $approval->id,
            'rejected_by' => $user->id,
        ]);

        $oldStatus = $approval->status;

        $approval->update([
            'status' => ApprovalStatusEnum::REJECTED->value,
            'rejected_by' => $user->id,
            'decided_at' => now(),
            'notes' => $approval->notes ? $approval->notes . "\n\n" . $notes : $notes,
        ]);

        AuditLog::log(
            'approval_rejected',
            $approval,
            ['status' => $oldStatus],
            ['status' => ApprovalStatusEnum::REJECTED->value],
            [
                'rejected_by' => $user->id,
                'notes' => $notes,
            ]
        );

        Log::info('[APPROVAL_SERVICE] Approval rejected successfully', [
            'approval_id' => $approval->id,
        ]);

        return $approval->fresh();
    }
}
