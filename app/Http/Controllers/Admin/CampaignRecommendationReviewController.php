<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignRecommendation;
use Illuminate\Http\Request;

class CampaignRecommendationReviewController extends Controller
{
    /**
     * Mark recommendation as reviewing.
     */
    public function markReviewing(CampaignRecommendation $recommendation)
    {
        $recommendation->update([
            'status' => 'reviewing',
        ]);

        return redirect()->back()->with('success', 'Recommendation marked as reviewing.');
    }

    /**
     * Approve the recommendation.
     */
    public function approve(Request $request, CampaignRecommendation $recommendation)
    {
        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:2000',
        ]);

        $recommendation->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return redirect()->route('admin.recommendations.show', $recommendation)
            ->with('success', 'Recommendation approved successfully.');
    }

    /**
     * Reject the recommendation.
     */
    public function reject(Request $request, CampaignRecommendation $recommendation)
    {
        $validated = $request->validate([
            'review_notes' => 'required|string|max:2000',
        ]);

        $recommendation->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        return redirect()->route('admin.recommendations.show', $recommendation)
            ->with('success', 'Recommendation rejected.');
    }
}
