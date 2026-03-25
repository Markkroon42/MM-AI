<?php

namespace App\Services\Meta;

use App\Exceptions\NonRetryablePublishException;
use App\Models\AuditLog;
use App\Models\CampaignDraft;
use App\Models\MetaCampaign;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class MetaCampaignWriteService
{
    public function __construct(
        protected MetaWriteClient $metaWriteClient
    ) {}

    /**
     * Pause a campaign
     */
    public function pauseCampaign(MetaCampaign $campaign): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Pausing campaign', [
            'campaign_id' => $campaign->id,
            'meta_campaign_id' => $campaign->meta_campaign_id,
        ]);

        try {
            $response = $this->metaWriteClient->updateCampaignStatus(
                $campaign->meta_campaign_id,
                'PAUSED'
            );

            // Update local database
            $campaign->update([
                'status' => 'PAUSED',
            ]);

            AuditLog::log(
                'campaign_paused',
                $campaign,
                ['status' => $campaign->getOriginal('status')],
                ['status' => 'PAUSED'],
                ['meta_response' => $response]
            );

            Log::info('[META_CAMPAIGN_WRITE] Campaign paused successfully', [
                'campaign_id' => $campaign->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('[META_CAMPAIGN_WRITE] Failed to pause campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resume a campaign
     */
    public function resumeCampaign(MetaCampaign $campaign): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Resuming campaign', [
            'campaign_id' => $campaign->id,
            'meta_campaign_id' => $campaign->meta_campaign_id,
        ]);

        try {
            $response = $this->metaWriteClient->updateCampaignStatus(
                $campaign->meta_campaign_id,
                'ACTIVE'
            );

            // Update local database
            $campaign->update([
                'status' => 'ACTIVE',
            ]);

            AuditLog::log(
                'campaign_resumed',
                $campaign,
                ['status' => $campaign->getOriginal('status')],
                ['status' => 'ACTIVE'],
                ['meta_response' => $response]
            );

            Log::info('[META_CAMPAIGN_WRITE] Campaign resumed successfully', [
                'campaign_id' => $campaign->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('[META_CAMPAIGN_WRITE] Failed to resume campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update campaign budget
     */
    public function updateBudget(MetaCampaign $campaign, float $newBudget): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Updating campaign budget', [
            'campaign_id' => $campaign->id,
            'meta_campaign_id' => $campaign->meta_campaign_id,
            'old_budget' => $campaign->daily_budget,
            'new_budget' => $newBudget,
        ]);

        try {
            $response = $this->metaWriteClient->updateCampaignBudget(
                $campaign->meta_campaign_id,
                $newBudget
            );

            // Update local database
            $oldBudget = $campaign->daily_budget;
            $campaign->update([
                'daily_budget' => $newBudget,
            ]);

            AuditLog::log(
                'campaign_budget_updated',
                $campaign,
                ['daily_budget' => $oldBudget],
                ['daily_budget' => $newBudget],
                ['meta_response' => $response]
            );

            Log::info('[META_CAMPAIGN_WRITE] Campaign budget updated successfully', [
                'campaign_id' => $campaign->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('[META_CAMPAIGN_WRITE] Failed to update campaign budget', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Publish a campaign draft
     * Fix: Proper account ID resolution and payload mapping
     * Extended: Full hierarchy publish (campaign → ad sets → creatives → ads)
     * Fix: Idempotency guard to prevent duplicate campaign creation
     */
    public function publishDraft(CampaignDraft $draft): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Publishing campaign draft', [
            'draft_id' => $draft->id,
            'draft_name' => $draft->generated_name,
        ]);

        try {
            $payload = $draft->draft_payload_json;

            // Fix #1: Resolve account ID with proper fallback chain (check first)
            $accountId = $this->resolveAccountId($draft, $payload);

            // Fix #1 & #4: Validate account ID before proceeding (non-retryable error)
            if (empty($accountId)) {
                $errorMessage = 'No valid Meta ad account ID resolved for draft publish';
                Log::error('[META_CAMPAIGN_WRITE] ' . $errorMessage, [
                    'draft_id' => $draft->id,
                    'briefing_id' => $draft->briefing_id,
                    'template_id' => $draft->template_id,
                ]);
                throw new NonRetryablePublishException($errorMessage);
            }

            // Validate draft before publish
            $this->validateDraftForPublish($draft, $payload);

            Log::info('[META_CAMPAIGN_WRITE] Resolved account ID for publish', [
                'account_id' => $accountId,
                'draft_id' => $draft->id,
            ]);

            // Fix #2 & #3: Translate draft payload to Meta API format using correct sources
            $metaPayload = $this->translateDraftToMetaFormat($draft, $payload);

            Log::info('[META_CAMPAIGN_WRITE] Prepared Meta campaign payload', [
                'draft_id' => $draft->id,
                'campaign_name' => $metaPayload['name'],
                'objective' => $metaPayload['objective'],
                'daily_budget' => $metaPayload['daily_budget'],
            ]);

            // Fix: Idempotency guard - check if campaign already created for this draft
            $existingCampaign = $this->checkExistingCampaign($draft);
            if ($existingCampaign) {
                Log::warning('[META_CAMPAIGN_WRITE] Campaign already exists for this draft, reusing existing campaign', [
                    'draft_id' => $draft->id,
                    'meta_campaign_id' => $existingCampaign['campaign']['id'],
                ]);
                return $existingCampaign;
            }

            // Step 1: Create campaign
            $campaignResponse = $this->metaWriteClient->createCampaign($accountId, $metaPayload);
            $metaCampaignId = $campaignResponse['id'] ?? null;

            if (empty($metaCampaignId)) {
                throw new \Exception('Campaign created but no ID returned from Meta');
            }

            Log::info('[META_CAMPAIGN_WRITE] Campaign created successfully', [
                'draft_id' => $draft->id,
                'meta_campaign_id' => $metaCampaignId,
            ]);

            $publishResult = [
                'campaign' => $campaignResponse,
                'ad_sets' => [],
                'creatives' => [],
                'ads' => [],
            ];

            // Step 2: Create ad sets
            $adSets = $payload['ad_sets'] ?? [];
            $adSetIdMap = [];

            foreach ($adSets as $index => $adSetData) {
                Log::info('[META_CAMPAIGN_WRITE] Creating ad set', [
                    'draft_id' => $draft->id,
                    'ad_set_index' => $index,
                    'ad_set_name' => $adSetData['name'] ?? "AdSet_{$index}",
                ]);

                $adSetPayload = $this->buildAdSetPayload($metaCampaignId, $adSetData, $draft, $payload);
                $adSetResponse = $this->metaWriteClient->createAdSet($accountId, $adSetPayload);
                $adSetId = $adSetResponse['id'] ?? null;

                if ($adSetId) {
                    $adSetIdMap[$index] = $adSetId;
                    $publishResult['ad_sets'][] = $adSetResponse;

                    Log::info('[META_ADSET_WRITE] Ad set created', [
                        'draft_id' => $draft->id,
                        'ad_set_id' => $adSetId,
                        'ad_set_name' => $adSetData['name'] ?? "AdSet_{$index}",
                    ]);
                }
            }

            // Step 3 & 4: Create creatives and ads for each ad set
            $ads = $payload['ads'] ?? [];

            foreach ($adSetIdMap as $adSetIndex => $adSetId) {
                foreach ($ads as $adIndex => $adData) {
                    Log::info('[META_CAMPAIGN_WRITE] Creating ad', [
                        'draft_id' => $draft->id,
                        'ad_set_id' => $adSetId,
                        'ad_index' => $adIndex,
                        'ad_name' => $adData['name'] ?? "Ad_{$adIndex}",
                    ]);

                    // Build creative payload with UTM parameters
                    $creativePayload = $this->buildCreativePayload($adData, $draft, $payload);
                    $creativeResponse = $this->metaWriteClient->createAdCreative($accountId, $creativePayload);
                    $creativeId = $creativeResponse['id'] ?? null;

                    if ($creativeId) {
                        $publishResult['creatives'][] = $creativeResponse;

                        Log::info('[META_CREATIVE_WRITE] Creative created', [
                            'draft_id' => $draft->id,
                            'creative_id' => $creativeId,
                            'ad_name' => $adData['name'] ?? "Ad_{$adIndex}",
                        ]);

                        // Create ad with creative
                        $adPayload = $this->buildAdPayload($adSetId, $creativeId, $adData);
                        $adResponse = $this->metaWriteClient->createAd($accountId, $adPayload);
                        $adId = $adResponse['id'] ?? null;

                        if ($adId) {
                            $publishResult['ads'][] = $adResponse;

                            Log::info('[META_AD_WRITE] Ad created', [
                                'draft_id' => $draft->id,
                                'ad_id' => $adId,
                                'ad_set_id' => $adSetId,
                                'creative_id' => $creativeId,
                                'ad_name' => $adData['name'] ?? "Ad_{$adIndex}",
                            ]);
                        }
                    }
                }
            }

            AuditLog::log(
                'draft_published',
                $draft,
                null,
                ['meta_campaign_id' => $metaCampaignId],
                [
                    'meta_response' => $publishResult,
                    'payload' => $metaPayload,
                    'account_id' => $accountId,
                    'ad_sets_count' => count($publishResult['ad_sets']),
                    'creatives_count' => count($publishResult['creatives']),
                    'ads_count' => count($publishResult['ads']),
                ]
            );

            Log::info('[META_CAMPAIGN_WRITE] Campaign draft published successfully', [
                'draft_id' => $draft->id,
                'meta_campaign_id' => $metaCampaignId,
                'ad_sets_created' => count($publishResult['ad_sets']),
                'creatives_created' => count($publishResult['creatives']),
                'ads_created' => count($publishResult['ads']),
            ]);

            return $publishResult;
        } catch (\Exception $e) {
            Log::error('[META_CAMPAIGN_WRITE] Failed to publish campaign draft', [
                'draft_id' => $draft->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve Meta ad account ID with fallback chain
     * Fix #1: Account ID source of truth
     */
    protected function resolveAccountId(CampaignDraft $draft, array $payload): ?string
    {
        // Try multiple sources in order of priority
        $sources = [
            'draft_payload' => $payload['meta_account_id'] ?? null,
            'briefing_meta_account' => $draft->briefing?->meta_account_id ?? null,
            'template_meta_account' => $draft->template?->meta_account_id ?? null,
            'system_setting' => SystemSetting::get('meta', 'default_account_id'),
            'config_default' => config('meta.default_account_id'),
        ];

        Log::info('[META_CAMPAIGN_WRITE] Starting account ID resolution', [
            'draft_id' => $draft->id,
            'sources_to_check' => array_keys($sources),
        ]);

        foreach ($sources as $source => $accountId) {
            // Validate that account ID is not empty and is a valid string
            if (!empty($accountId) && is_string($accountId) && trim($accountId) !== '') {
                Log::info('[META_CAMPAIGN_WRITE] Resolved account ID from source', [
                    'source' => $source,
                    'account_id' => $accountId,
                    'draft_id' => $draft->id,
                ]);
                return trim($accountId);
            }
        }

        Log::warning('[META_CAMPAIGN_WRITE] No valid account ID found in any source', [
            'draft_id' => $draft->id,
            'sources_checked' => array_keys($sources),
            'checked_values' => array_map(fn($v) => is_string($v) ? $v : gettype($v), $sources),
        ]);

        return null;
    }

    /**
     * Translate draft payload to Meta API format
     * Fix #2 & #3: Use correct payload sources (campaign.* structure)
     */
    protected function translateDraftToMetaFormat(CampaignDraft $draft, array $draftPayload): array
    {
        $campaign = $draftPayload['campaign'] ?? [];

        // Fix #3: Campaign name from correct source with proper fallback
        $name = $campaign['name'] ?? $draft->generated_name ?? 'Unnamed Campaign';

        // Fix #3: Objective from correct source with proper mapping
        $objective = $this->mapObjectiveToMeta($campaign['objective'] ?? $draft->briefing?->objective ?? 'OUTCOME_TRAFFIC');

        // Fix #3: Budget from correct source with fallbacks
        $dailyBudget = $campaign['daily_budget']
            ?? $draft->briefing?->budget_amount
            ?? $draft->template?->default_budget
            ?? null;

        // Convert to cents if present
        $dailyBudgetCents = $dailyBudget ? (int) ($dailyBudget * 100) : null;

        // Fix: Map internal status to valid Meta status
        $internalStatus = $campaign['status'] ?? $draft->status ?? 'draft';
        $metaStatus = $this->mapInternalStatusToMeta($internalStatus);

        $metaPayload = [
            'name' => $name,
            'objective' => $objective,
            'status' => $metaStatus,
            'special_ad_categories' => $campaign['special_ad_categories'] ?? [],
            'buying_type' => $campaign['buying_type'] ?? 'AUCTION',
            'daily_budget' => $dailyBudgetCents,
        ];

        Log::info('[META_CAMPAIGN_WRITE] Translated draft to Meta format', [
            'draft_id' => $draft->id,
            'source_campaign_name' => $campaign['name'] ?? null,
            'fallback_generated_name' => $draft->generated_name,
            'final_name' => $name,
            'source_objective' => $campaign['objective'] ?? null,
            'mapped_objective' => $objective,
            'internal_status' => $internalStatus,
            'mapped_meta_status' => $metaStatus,
            'daily_budget_euros' => $dailyBudget,
            'daily_budget_cents' => $dailyBudgetCents,
        ]);

        return $metaPayload;
    }

    /**
     * Map internal status to Meta campaign status
     * Fix: Convert internal draft statuses to valid Meta statuses
     *
     * Meta only accepts: ACTIVE, PAUSED, DELETED, ARCHIVED
     * Internal statuses like 'draft', 'ready_for_review', 'approved', etc. must be mapped
     */
    protected function mapInternalStatusToMeta(string $internalStatus): string
    {
        // Normalize to uppercase for comparison
        $normalized = strtoupper($internalStatus);

        // If already a valid Meta status, use it
        $validMetaStatuses = ['ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED'];
        if (in_array($normalized, $validMetaStatuses)) {
            Log::info('[META_CAMPAIGN_WRITE] Status already valid for Meta', [
                'internal_status' => $internalStatus,
                'meta_status' => $normalized,
            ]);
            return $normalized;
        }

        // Map internal statuses to Meta statuses
        // Default to PAUSED for safety - campaign is created but not active
        $mapping = [
            'draft' => 'PAUSED',
            'ready_for_review' => 'PAUSED',
            'approved' => 'PAUSED',
            'publishing' => 'PAUSED',
            'published' => 'PAUSED',
            'active' => 'ACTIVE',
            'paused' => 'PAUSED',
        ];

        $metaStatus = $mapping[strtolower($internalStatus)] ?? 'PAUSED';

        Log::info('[META_CAMPAIGN_WRITE] Mapped internal status to Meta status', [
            'internal_status' => $internalStatus,
            'meta_status' => $metaStatus,
        ]);

        return $metaStatus;
    }

    /**
     * Map internal objective to Meta objective format (campaign level)
     * Fix #3: Proper objective mapping
     */
    protected function mapObjectiveToMeta(string $objective): string
    {
        $mapping = [
            'leads' => 'OUTCOME_LEADS',
            'LEADS' => 'OUTCOME_LEADS',
            'traffic' => 'OUTCOME_TRAFFIC',
            'TRAFFIC' => 'OUTCOME_TRAFFIC',
            'awareness' => 'OUTCOME_AWARENESS',
            'AWARENESS' => 'OUTCOME_AWARENESS',
            'engagement' => 'OUTCOME_ENGAGEMENT',
            'ENGAGEMENT' => 'OUTCOME_ENGAGEMENT',
            'app_promotion' => 'OUTCOME_APP_PROMOTION',
            'APP_PROMOTION' => 'OUTCOME_APP_PROMOTION',
            'sales' => 'OUTCOME_SALES',
            'SALES' => 'OUTCOME_SALES',
        ];

        $mapped = $mapping[$objective] ?? null;

        if (!$mapped) {
            // If already in Meta format (starts with OUTCOME_), use as-is
            if (str_starts_with(strtoupper($objective), 'OUTCOME_')) {
                $mapped = strtoupper($objective);
            } else {
                // Default fallback
                $mapped = 'OUTCOME_TRAFFIC';
                Log::warning('[META_CAMPAIGN_WRITE] Unknown objective, using default', [
                    'original_objective' => $objective,
                    'mapped_objective' => $mapped,
                ]);
            }
        }

        return $mapped;
    }

    /**
     * Map internal optimization goal to Meta ad set optimization_goal
     * Fix: Ad set optimization_goal is different from campaign objective
     */
    protected function mapOptimizationGoalToMeta(string $optimizationGoal): string
    {
        // Map internal optimization goals to valid Meta ad set optimization goals
        $mapping = [
            'LEADS' => 'LEAD_GENERATION',
            'LEAD_GENERATION' => 'LEAD_GENERATION',
            'TRAFFIC' => 'LINK_CLICKS',
            'LINK_CLICKS' => 'LINK_CLICKS',
            'LANDING_PAGE_VIEWS' => 'LANDING_PAGE_VIEWS',
            'AWARENESS' => 'REACH',
            'REACH' => 'REACH',
            'IMPRESSIONS' => 'IMPRESSIONS',
            'ENGAGEMENT' => 'POST_ENGAGEMENT',
            'POST_ENGAGEMENT' => 'POST_ENGAGEMENT',
            'VIDEO_VIEWS' => 'VIDEO_VIEWS',
            'CONVERSIONS' => 'OFFSITE_CONVERSIONS',
            'OFFSITE_CONVERSIONS' => 'OFFSITE_CONVERSIONS',
            'APP_INSTALLS' => 'APP_INSTALLS',
            'SALES' => 'OFFSITE_CONVERSIONS',
        ];

        $mapped = $mapping[$optimizationGoal] ?? null;

        if (!$mapped) {
            // If not found, check if it's already a valid Meta optimization goal
            $validMetaGoals = array_values($mapping);
            if (in_array($optimizationGoal, $validMetaGoals)) {
                Log::info('[META_ADSET_WRITE] Optimization goal already in Meta format', [
                    'optimization_goal' => $optimizationGoal,
                ]);
                return $optimizationGoal;
            }

            // Controlled failure for unknown optimization goals
            $errorMessage = "Unknown optimization goal '{$optimizationGoal}' cannot be mapped to valid Meta ad set optimization_goal";
            Log::error('[META_ADSET_WRITE] Invalid optimization goal', [
                'optimization_goal' => $optimizationGoal,
                'valid_values' => array_keys($mapping),
            ]);
            throw new NonRetryablePublishException($errorMessage);
        }

        return $mapped;
    }

    /**
     * Validate draft before publish
     */
    protected function validateDraftForPublish(CampaignDraft $draft, array $payload): void
    {
        $campaign = $payload['campaign'] ?? [];
        $adSets = $payload['ad_sets'] ?? [];
        $ads = $payload['ads'] ?? [];

        // Campaign name validation
        $campaignName = $campaign['name'] ?? $draft->generated_name ?? null;
        if (empty($campaignName)) {
            Log::error('[META_CAMPAIGN_WRITE] Validation failed: No campaign name', [
                'draft_id' => $draft->id,
            ]);
            throw new NonRetryablePublishException('No campaign name available for publish');
        }

        // Ad sets validation
        if (empty($adSets)) {
            Log::error('[META_CAMPAIGN_WRITE] Validation failed: No ad sets', [
                'draft_id' => $draft->id,
            ]);
            throw new NonRetryablePublishException('Cannot publish campaign without ad sets');
        }

        // Ads validation
        if (empty($ads)) {
            Log::error('[META_CAMPAIGN_WRITE] Validation failed: No ads', [
                'draft_id' => $draft->id,
            ]);
            throw new NonRetryablePublishException('Cannot publish campaign without ads');
        }

        // Landing page validation
        $landingPage = $campaign['landing_page_url']
            ?? $draft->template?->landing_page_url
            ?? $draft->briefing?->landing_page_url
            ?? null;

        if (empty($landingPage)) {
            Log::error('[META_CAMPAIGN_WRITE] Validation failed: No landing page URL', [
                'draft_id' => $draft->id,
            ]);
            throw new NonRetryablePublishException('No landing page URL available for publish');
        }

        // Validate ads have copy
        foreach ($ads as $index => $adData) {
            $hasCopy = !empty($adData['creative']['object_story_spec']['link_data']['message'] ?? null)
                || !empty($adData['creative']['message'] ?? null)
                || !empty($adData['message'] ?? null);

            if (!$hasCopy) {
                Log::warning('[META_CAMPAIGN_WRITE] Ad missing copy, but continuing', [
                    'draft_id' => $draft->id,
                    'ad_index' => $index,
                    'ad_name' => $adData['name'] ?? "Ad_{$index}",
                ]);
            }
        }

        Log::info('[META_CAMPAIGN_WRITE] Draft validation passed', [
            'draft_id' => $draft->id,
            'campaign_name' => $campaignName,
            'ad_sets_count' => count($adSets),
            'ads_count' => count($ads),
        ]);
    }

    /**
     * Build ad set payload for Meta API
     * Fix: Explicit bid_strategy to avoid bid_amount requirement
     * Fix: Complete ad set payload logging
     */
    protected function buildAdSetPayload(string $campaignId, array $adSetData, CampaignDraft $draft, array $draftPayload): array
    {
        $campaign = $draftPayload['campaign'] ?? [];

        $name = $adSetData['name'] ?? 'AdSet_' . uniqid();
        $internalOptimizationGoal = strtoupper($adSetData['optimization_goal'] ?? 'LEADS');
        $billingEvent = $adSetData['billing_event'] ?? 'IMPRESSIONS';

        // Map internal optimization goal to Meta ad set optimization_goal
        $metaOptimizationGoal = $this->mapOptimizationGoalToMeta($internalOptimizationGoal);

        // Use campaign budget or ad set specific budget
        $dailyBudget = $adSetData['daily_budget']
            ?? $campaign['daily_budget']
            ?? $draft->briefing?->budget_amount
            ?? $draft->template?->default_budget
            ?? null;

        $dailyBudgetCents = $dailyBudget ? (int) ($dailyBudget * 100) : null;

        $payload = [
            'name' => $name,
            'campaign_id' => $campaignId,
            'status' => 'PAUSED',
            'optimization_goal' => $metaOptimizationGoal,
            'billing_event' => $billingEvent,
            'targeting' => $this->buildTargeting($adSetData),
        ];

        // Add budget if available
        if ($dailyBudgetCents) {
            $payload['daily_budget'] = $dailyBudgetCents;
        }

        // FIX 3: LEAD_GENERATION specific billing/bidding configuration
        // For LEAD_GENERATION, omit bid_strategy to let Meta use its automatic default
        // The combination of LEAD_GENERATION + IMPRESSIONS + explicit bid_strategy
        // can trigger "bid_amount required" errors. By omitting bid_strategy,
        // Meta automatically applies the correct bidding strategy for lead generation.
        if ($metaOptimizationGoal === 'LEAD_GENERATION') {
            Log::info('[META_ADSET_WRITE] LEAD_GENERATION: using Meta automatic bidding (omitting bid_strategy)', [
                'optimization_goal' => $metaOptimizationGoal,
                'billing_event' => $billingEvent,
                'reason' => 'LEAD_GENERATION with automatic bidding avoids bid_amount requirement',
            ]);

            // FIX 3.2/3.3/3.4: For LEAD_GENERATION, resolve and build complete lead ad context
            // Meta requires page_id for lead generation campaigns
            Log::info('[META_ADSET_WRITE] LEAD_GENERATION: resolving lead ad context');

            $pageId = $this->resolvePageIdForLeadGeneration($adSetData, $draft);

            if (!$pageId) {
                // FIX 3.3: Controlled failure - LEAD_GENERATION requires page_id
                Log::error('[META_ADSET_WRITE] LEAD_GENERATION: no valid page_id found', [
                    'reason' => 'LEAD_GENERATION publish requires a valid Meta page_id',
                    'checked_sources' => ['ad_set_data', 'draft_payload', 'briefing', 'template', 'system_settings', 'config'],
                ]);

                throw new NonRetryablePublishException(
                    'LEAD_GENERATION publish requires a valid page_id. ' .
                    'Please configure meta.page_id in system settings or provide page_id in campaign configuration.'
                );
            }

            // FIX 3.4: Build complete promoted_object for LEAD_GENERATION
            // For on-Facebook lead ads, Meta may require additional context beyond just page_id
            // The exact requirements depend on whether a lead form is configured
            $leadGenFormId = $this->resolveLeadGenFormId($adSetData, $draft);

            $promotedObject = ['page_id' => $pageId];

            if ($leadGenFormId) {
                // If a lead form is configured, include it
                $promotedObject['lead_gen_form_id'] = $leadGenFormId;

                Log::info('[META_ADSET_WRITE] LEAD_GENERATION: using configured lead form', [
                    'page_id' => $pageId,
                    'lead_gen_form_id' => $leadGenFormId,
                    'reason' => 'Lead form ID found in configuration',
                ]);
            } else {
                // No lead form configured - Meta will likely reject this
                // This is a common case for first-time LEAD_GENERATION setup
                Log::warning('[META_ADSET_WRITE] LEAD_GENERATION: no lead form configured', [
                    'page_id' => $pageId,
                    'reason' => 'LEAD_GENERATION ad sets typically require a lead form. Meta may reject this request.',
                    'recommendation' => 'Configure a lead form ID in campaign settings or create one in Meta Business Manager',
                ]);

                // FIX 3.4: Controlled failure for missing lead form
                throw new NonRetryablePublishException(
                    'LEAD_GENERATION ad sets require a lead form (instant form). ' .
                    'Please create a lead form in Meta Business Manager and configure the lead_gen_form_id in your campaign settings. ' .
                    'Page ID found: ' . $pageId
                );
            }

            $payload['promoted_object'] = $promotedObject;

            Log::info('[META_ADSET_WRITE] LEAD_GENERATION: final promoted_object configured', [
                'promoted_object' => $promotedObject,
                'has_lead_form' => isset($promotedObject['lead_gen_form_id']),
            ]);

            // Explicitly do not add bid_strategy for LEAD_GENERATION
            // Meta will use automatic bidding which is the correct default
        } else {
            // For other optimization goals, use LOWEST_COST_WITHOUT_CAP
            // This is a safe default bidding strategy for first publish
            $payload['bid_strategy'] = 'LOWEST_COST_WITHOUT_CAP';

            Log::info('[META_ADSET_WRITE] Using explicit bid_strategy for non-LEAD_GENERATION', [
                'optimization_goal' => $metaOptimizationGoal,
                'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
            ]);
        }

        Log::info('[META_ADSET_WRITE] Mapped internal objective to Meta optimization goal', [
            'internal_optimization_goal' => $internalOptimizationGoal,
            'meta_optimization_goal' => $metaOptimizationGoal,
        ]);

        // Fix: Full ad set payload logging with all relevant Meta fields
        Log::info('[META_ADSET_WRITE] Final ad set payload prepared', [
            'name' => $name,
            'campaign_id' => $campaignId,
            'optimization_goal' => $metaOptimizationGoal,
            'billing_event' => $billingEvent,
            'bid_strategy' => $payload['bid_strategy'] ?? '(auto)',
            'bid_amount' => $payload['bid_amount'] ?? null,
            'daily_budget_cents' => $dailyBudgetCents,
            'status' => $payload['status'],
            'has_promoted_object' => isset($payload['promoted_object']),
            'promoted_object' => $payload['promoted_object'] ?? null,
            'targeting_countries' => $payload['targeting']['geo_locations']['countries'] ?? null,
            'uses_automatic_bidding' => !isset($payload['bid_strategy']),
        ]);

        return $payload;
    }

    /**
     * Build targeting for ad set
     */
    protected function buildTargeting(array $adSetData): array
    {
        // Default targeting structure
        $targeting = [
            'geo_locations' => [
                'countries' => ['NL'], // Default to Netherlands
            ],
        ];

        // Add custom targeting if provided
        if (!empty($adSetData['targeting'])) {
            $targeting = array_merge($targeting, $adSetData['targeting']);
        }

        // Add audience if provided
        if (!empty($adSetData['audience'])) {
            Log::info('[META_ADSET_WRITE] Using audience targeting', [
                'audience' => $adSetData['audience'],
            ]);
        }

        return $targeting;
    }

    /**
     * Build creative payload for Meta API
     */
    protected function buildCreativePayload(array $adData, CampaignDraft $draft, array $draftPayload): array
    {
        $campaign = $draftPayload['campaign'] ?? [];

        $name = $adData['name'] ?? 'Creative_' . uniqid();

        // Get copy from various sources
        $creative = $adData['creative'] ?? [];
        $linkData = $creative['object_story_spec']['link_data'] ?? $creative['link_data'] ?? [];

        $message = $linkData['message']
            ?? $creative['message']
            ?? $adData['message']
            ?? $adData['primary_text']
            ?? '';

        $headline = $linkData['name']
            ?? $creative['headline']
            ?? $adData['headline']
            ?? '';

        $description = $linkData['description']
            ?? $creative['description']
            ?? $adData['description']
            ?? '';

        // Build landing page URL with UTM parameters
        $baseLandingPage = $adData['landing_page_url']
            ?? $campaign['landing_page_url']
            ?? $draft->template?->landing_page_url
            ?? '';

        $destinationUrl = $this->buildDestinationUrl($baseLandingPage, $adData, $draftPayload);

        Log::info('[META_CREATIVE_WRITE] Using destination URL', [
            'base_url' => $baseLandingPage,
            'final_url' => $destinationUrl,
        ]);

        $payload = [
            'name' => $name,
            'object_story_spec' => [
                'page_id' => config('meta.page_id'),
                'link_data' => [
                    'link' => $destinationUrl,
                    'message' => $message,
                    'name' => $headline,
                    'description' => $description,
                ],
            ],
        ];

        Log::info('[META_CREATIVE_WRITE] Built creative payload', [
            'name' => $name,
            'has_message' => !empty($message),
            'has_headline' => !empty($headline),
            'has_description' => !empty($description),
            'destination_url' => $destinationUrl,
        ]);

        return $payload;
    }

    /**
     * Build destination URL with UTM parameters
     */
    protected function buildDestinationUrl(string $baseUrl, array $adData, array $draftPayload): string
    {
        if (empty($baseUrl)) {
            Log::warning('[META_CREATIVE_WRITE] No base URL provided for destination');
            return '';
        }

        // Check if UTM parameters are already in the ad data
        $utmParams = $adData['utm_parameters'] ?? [];

        if (empty($utmParams)) {
            // Return base URL if no UTM parameters
            return $baseUrl;
        }

        // Build query string from UTM parameters
        $queryParams = [];
        foreach ($utmParams as $key => $value) {
            if (!empty($value)) {
                $queryParams[$key] = $value;
            }
        }

        if (empty($queryParams)) {
            return $baseUrl;
        }

        // Parse existing URL to check for existing query params
        $parsedUrl = parse_url($baseUrl);
        $existingQuery = $parsedUrl['query'] ?? '';

        // Build final query string
        $newQueryString = http_build_query($queryParams);

        if (!empty($existingQuery)) {
            $finalUrl = $baseUrl . '&' . $newQueryString;
        } else {
            $separator = strpos($baseUrl, '?') === false ? '?' : '&';
            $finalUrl = $baseUrl . $separator . $newQueryString;
        }

        Log::info('[META_CREATIVE_WRITE] Built destination URL with UTM parameters', [
            'base_url' => $baseUrl,
            'utm_params' => $queryParams,
            'final_url' => $finalUrl,
        ]);

        return $finalUrl;
    }

    /**
     * Build ad payload for Meta API
     */
    protected function buildAdPayload(string $adSetId, string $creativeId, array $adData): array
    {
        $name = $adData['name'] ?? 'Ad_' . uniqid();

        $payload = [
            'name' => $name,
            'adset_id' => $adSetId,
            'creative' => ['creative_id' => $creativeId],
            'status' => 'PAUSED',
        ];

        Log::info('[META_AD_WRITE] Built ad payload', [
            'name' => $name,
            'adset_id' => $adSetId,
            'creative_id' => $creativeId,
        ]);

        return $payload;
    }

    /**
     * Check if campaign already exists for this draft
     * Fix: Idempotency helper to prevent duplicate campaign creation
     */
    protected function checkExistingCampaign(CampaignDraft $draft): ?array
    {
        // Check if draft has any successful publish jobs with campaign results
        $successfulPublishJob = $draft->publishJobs()
            ->where('status', 'success')
            ->where('action_type', 'publish_campaign_draft')
            ->whereNotNull('response_json')
            ->latest()
            ->first();

        if (!$successfulPublishJob) {
            Log::info('[META_CAMPAIGN_WRITE] No existing successful publish job found', [
                'draft_id' => $draft->id,
            ]);
            return null;
        }

        $response = $successfulPublishJob->response_json;

        // Check if response contains a campaign ID
        $campaignId = $response['campaign']['id'] ?? null;

        if (!$campaignId) {
            Log::warning('[META_CAMPAIGN_WRITE] Successful publish job found but no campaign ID in response', [
                'draft_id' => $draft->id,
                'publish_job_id' => $successfulPublishJob->id,
            ]);
            return null;
        }

        Log::info('[META_CAMPAIGN_WRITE] Found existing campaign from previous successful publish', [
            'draft_id' => $draft->id,
            'publish_job_id' => $successfulPublishJob->id,
            'campaign_id' => $campaignId,
            'executed_at' => $successfulPublishJob->executed_at,
        ]);

        return $response;
    }

    /**
     * FIX 3.3: Resolve page_id for LEAD_GENERATION campaigns
     * Tries multiple sources in priority order:
     * 1. Ad set data
     * 2. Draft payload (campaign level)
     * 3. Briefing
     * 4. Template
     * 5. System settings
     * 6. Config/env
     */
    protected function resolvePageIdForLeadGeneration(array $adSetData, CampaignDraft $draft): ?string
    {
        $sources = [];

        // 1. Check ad set data
        if (!empty($adSetData['page_id'])) {
            Log::info('[META_ADSET_WRITE] Resolved page_id from ad_set_data', [
                'page_id' => $adSetData['page_id'],
                'source' => 'ad_set_data',
            ]);
            return $adSetData['page_id'];
        }
        $sources[] = 'ad_set_data (not found)';

        // 2. Check draft payload (campaign level)
        $draftPayload = $draft->draft_payload_json;
        if (!empty($draftPayload['campaign']['page_id'])) {
            Log::info('[META_ADSET_WRITE] Resolved page_id from draft_payload', [
                'page_id' => $draftPayload['campaign']['page_id'],
                'source' => 'draft_payload.campaign',
            ]);
            return $draftPayload['campaign']['page_id'];
        }
        $sources[] = 'draft_payload.campaign (not found)';

        // 3. Check briefing
        if ($draft->briefing && !empty($draft->briefing->meta_page_id)) {
            Log::info('[META_ADSET_WRITE] Resolved page_id from briefing', [
                'page_id' => $draft->briefing->meta_page_id,
                'source' => 'briefing.meta_page_id',
            ]);
            return $draft->briefing->meta_page_id;
        }
        $sources[] = 'briefing.meta_page_id (not found)';

        // 4. Check template
        if ($draft->template && !empty($draft->template->meta_page_id)) {
            Log::info('[META_ADSET_WRITE] Resolved page_id from template', [
                'page_id' => $draft->template->meta_page_id,
                'source' => 'template.meta_page_id',
            ]);
            return $draft->template->meta_page_id;
        }
        $sources[] = 'template.meta_page_id (not found)';

        // 5. Check system settings
        $systemPageId = SystemSetting::get('meta', 'default_page_id');
        if (!empty($systemPageId)) {
            Log::info('[META_ADSET_WRITE] Resolved page_id from system_settings', [
                'page_id' => $systemPageId,
                'source' => 'system_settings.meta.default_page_id',
            ]);
            return $systemPageId;
        }
        $sources[] = 'system_settings.meta.default_page_id (not found)';

        // 6. Check config/env
        $configPageId = config('meta.page_id');
        if (!empty($configPageId)) {
            Log::info('[META_ADSET_WRITE] Resolved page_id from config', [
                'page_id' => $configPageId,
                'source' => 'config.meta.page_id',
            ]);
            return $configPageId;
        }
        $sources[] = 'config.meta.page_id (not found)';

        // No valid page_id found
        Log::warning('[META_ADSET_WRITE] No page_id found in any source', [
            'checked_sources' => $sources,
        ]);

        return null;
    }

    /**
     * FIX 3.4: Resolve lead_gen_form_id for LEAD_GENERATION campaigns
     * Tries multiple sources in priority order:
     * 1. Ad set data
     * 2. Draft payload (campaign level)
     * 3. Briefing
     * 4. Template
     * 5. System settings
     * 6. Config/env
     */
    protected function resolveLeadGenFormId(array $adSetData, CampaignDraft $draft): ?string
    {
        $sources = [];

        // 1. Check ad set data
        if (!empty($adSetData['lead_gen_form_id'])) {
            Log::info('[META_ADSET_WRITE] Resolved lead_gen_form_id from ad_set_data', [
                'lead_gen_form_id' => $adSetData['lead_gen_form_id'],
                'source' => 'ad_set_data',
            ]);
            return $adSetData['lead_gen_form_id'];
        }
        $sources[] = 'ad_set_data (not found)';

        // 2. Check draft payload (campaign level)
        $draftPayload = $draft->draft_payload_json;
        if (!empty($draftPayload['campaign']['lead_gen_form_id'])) {
            Log::info('[META_ADSET_WRITE] Resolved lead_gen_form_id from draft_payload', [
                'lead_gen_form_id' => $draftPayload['campaign']['lead_gen_form_id'],
                'source' => 'draft_payload.campaign',
            ]);
            return $draftPayload['campaign']['lead_gen_form_id'];
        }
        $sources[] = 'draft_payload.campaign (not found)';

        // 3. Check briefing
        if ($draft->briefing && !empty($draft->briefing->meta_lead_gen_form_id)) {
            Log::info('[META_ADSET_WRITE] Resolved lead_gen_form_id from briefing', [
                'lead_gen_form_id' => $draft->briefing->meta_lead_gen_form_id,
                'source' => 'briefing.meta_lead_gen_form_id',
            ]);
            return $draft->briefing->meta_lead_gen_form_id;
        }
        $sources[] = 'briefing.meta_lead_gen_form_id (not found)';

        // 4. Check template
        if ($draft->template && !empty($draft->template->meta_lead_gen_form_id)) {
            Log::info('[META_ADSET_WRITE] Resolved lead_gen_form_id from template', [
                'lead_gen_form_id' => $draft->template->meta_lead_gen_form_id,
                'source' => 'template.meta_lead_gen_form_id',
            ]);
            return $draft->template->meta_lead_gen_form_id;
        }
        $sources[] = 'template.meta_lead_gen_form_id (not found)';

        // 5. Check system settings
        $systemLeadFormId = SystemSetting::get('meta', 'default_lead_gen_form_id');
        if (!empty($systemLeadFormId)) {
            Log::info('[META_ADSET_WRITE] Resolved lead_gen_form_id from system_settings', [
                'lead_gen_form_id' => $systemLeadFormId,
                'source' => 'system_settings.meta.default_lead_gen_form_id',
            ]);
            return $systemLeadFormId;
        }
        $sources[] = 'system_settings.meta.default_lead_gen_form_id (not found)';

        // 6. Check config/env
        $configLeadFormId = config('meta.lead_gen_form_id');
        if (!empty($configLeadFormId)) {
            Log::info('[META_ADSET_WRITE] Resolved lead_gen_form_id from config', [
                'lead_gen_form_id' => $configLeadFormId,
                'source' => 'config.meta.lead_gen_form_id',
            ]);
            return $configLeadFormId;
        }
        $sources[] = 'config.meta.lead_gen_form_id (not found)';

        // No valid lead_gen_form_id found
        Log::warning('[META_ADSET_WRITE] No lead_gen_form_id found in any source', [
            'checked_sources' => $sources,
        ]);

        return null;
    }
}

