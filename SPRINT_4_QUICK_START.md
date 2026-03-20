# Sprint 4 Quick Start Guide
## AI Content Creation - Get Up and Running in 5 Minutes

---

## Prerequisites

1. All previous migrations run successfully
2. OpenAI API key obtained from https://platform.openai.com/api-keys

---

## Setup Steps

### 1. Add OpenAI API Key to .env

```bash
# Add this line to your .env file
OPENAI_API_KEY=sk-proj-your-actual-key-here
```

### 2. Run Migrations (Already Complete)

```bash
php artisan migrate
```

### 3. Seed AI Data (Already Complete)

```bash
php artisan db:seed --class=AiPromptConfigSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## Test the AI Features

### Option 1: Via UI

1. **Navigate to AI Prompt Configs:**
   - Go to `/admin/ai-prompt-configs`
   - You should see 3 default configs:
     - copy_agent_default
     - creative_agent_default
     - strategy_assistant_default

2. **Generate AI Content for a Briefing:**
   - Go to any Campaign Briefing detail page
   - Look for AI action buttons (Note: Views may need completion)
   - Click "Generate Copy" or "Generate Strategy"

3. **View AI Usage Logs:**
   - Go to `/admin/ai-usage-logs`
   - See all AI calls with costs and timing

### Option 2: Via Tinker (For Testing)

```bash
php artisan tinker
```

```php
// 1. Test Copy Agent
$briefing = \App\Models\CampaignBriefing::first();
$copyAgent = app(\App\Services\AI\CopyAgentService::class);
$enrichment = $copyAgent->generateForBriefing($briefing);

// Result: DraftEnrichment with copy variants
dd($enrichment->payload_json);

// 2. Check AI Usage Log
$log = \App\Models\AiUsageLog::latest()->first();
dd([
    'status' => $log->status,
    'tokens' => $log->tokens_input + $log->tokens_output,
    'cost' => $log->cost_estimate,
    'output' => $log->output_payload_json
]);

// 3. Test Strategy Agent
$strategyAgent = app(\App\Services\AI\CampaignStrategyAssistantService::class);
$strategy = $strategyAgent->generateForBriefing($briefing);
dd($strategy->strategy_payload_json);
```

---

## Key URLs

Once your Laravel app is running:

```
AI Prompt Configs:      /admin/ai-prompt-configs
AI Usage Logs:          /admin/ai-usage-logs
Campaign Briefings:     /admin/campaign-briefings
Campaign Drafts:        /admin/campaign-drafts
Dashboard:              /admin/
```

---

## API Endpoints (for AI generation)

### Campaign Briefing AI

```http
POST /admin/campaign-briefings/{id}/ai/generate-strategy
POST /admin/campaign-briefings/{id}/ai/generate-copy
POST /admin/campaign-briefings/{id}/ai/generate-creative
```

### Campaign Draft AI

```http
POST /admin/campaign-drafts/{id}/ai/generate-copy
POST /admin/campaign-drafts/{id}/ai/generate-creative
POST /admin/campaign-drafts/{id}/ai/generate-full
```

### Enrichment Actions

```http
POST /admin/draft-enrichments/{id}/approve
POST /admin/draft-enrichments/{id}/reject
POST /admin/draft-enrichments/{id}/apply
```

---

## Default Prompt Configs

### 1. copy_agent_default
- **Model:** gpt-4-turbo-preview
- **Temperature:** 0.7
- **Generates:** Primary texts, headlines, descriptions, CTAs, test angles
- **Output:** JSON with 5 categories of copy variants

### 2. creative_agent_default
- **Model:** gpt-4-turbo-preview
- **Temperature:** 0.8 (more creative)
- **Generates:** Static visuals, video concepts, UGC angles, hooks, visual briefs
- **Output:** JSON with detailed creative suggestions

### 3. strategy_assistant_default
- **Model:** gpt-4-turbo-preview
- **Temperature:** 0.6 (more focused)
- **Generates:** Campaign angle, funnel strategy, audience strategy, testing plan
- **Output:** JSON with comprehensive strategy recommendations

---

## Understanding the Flow

### Copy Generation Flow

```
1. User clicks "Generate Copy"
   ↓
2. CampaignBriefingAiController@generateCopy
   ↓
3. CopyAgentService@generateForBriefing
   ↓
4. PromptConfigResolver resolves "copy_agent_default"
   ↓
5. CopyPromptBuilder builds context from briefing
   ↓
6. AiUsageLogger starts log (status: RUNNING)
   ↓
7. LlmGateway calls OpenAI API
   ↓
8. Response parsed (JSON)
   ↓
9. Cost calculated
   ↓
10. AiUsageLogger marks success (tokens, cost)
    ↓
11. DraftEnrichmentService stores enrichment (status: DRAFT)
    ↓
12. User sees enrichment (can approve/reject/apply)
```

### Enrichment Application Flow

```
1. User clicks "Apply Enrichment"
   ↓
2. DraftEnrichmentController@apply
   ↓
3. DraftEnrichmentService@applyEnrichment
   ↓
4. Reads current draft_payload_json
   ↓
5. Adds to draft_payload_json['ai_enrichments']['{type}']
   ↓
6. NEVER overwrites existing data
   ↓
7. Updates enrichment status to APPLIED
   ↓
8. Redirects back to draft detail
```

---

## Cost Expectations

Based on typical usage:

| Operation | Tokens | Est. Cost |
|-----------|--------|-----------|
| Copy Generation | 1,500-3,000 | $0.02-$0.05 |
| Creative Suggestions | 2,000-4,000 | $0.03-$0.07 |
| Strategy Generation | 3,000-6,000 | $0.05-$0.10 |

**Monthly Budget Estimate:**
- 100 copy generations: $3-$5
- 50 creative suggestions: $2-$4
- 50 strategy generations: $3-$5
- **Total:** ~$10-$15/month for moderate usage

---

## Troubleshooting

### Error: "OPENAI_API_KEY not configured"
**Solution:** Add `OPENAI_API_KEY=sk-...` to your .env file

### Error: "Prompt config not found or inactive"
**Solution:** Run `php artisan db:seed --class=AiPromptConfigSeeder`

### Error: "Route [login] not defined"
**Solution:** Already fixed in routes/web.php (login routes exist)

### No results showing in UI
**Solution:**
1. Check ai_usage_logs table for error messages
2. Review Laravel logs: `storage/logs/laravel.log`
3. Verify API key is valid

### Cost seems high
**Solution:**
1. Check temperature settings (higher = more tokens)
2. Reduce max_tokens in prompt config
3. Use gpt-3.5-turbo for non-critical tasks

---

## Customizing Prompts

### Edit via UI:
1. Go to `/admin/ai-prompt-configs`
2. Click "Edit" on any config
3. Modify system_prompt or user_prompt_template
4. Save

### Variables Available in Templates:

```
{{brand}}              - Brand name
{{market}}             - Target market
{{objective}}          - Campaign objective
{{target_audience}}    - Audience description
{{product_name}}       - Product/service name
{{landing_page_url}}   - Landing page URL
{{campaign_goal}}      - Campaign goal
{{budget_amount}}      - Budget (formatted)
{{notes}}              - Additional notes
{{current_copy}}       - Existing copy (for drafts)
{{current_creative}}   - Existing creative (for drafts)
```

---

## Creating a New Agent Type

1. **Add to AiAgentTypeEnum:**
   ```php
   case AUDIENCE_ANALYZER = 'AUDIENCE_ANALYZER';
   ```

2. **Create Agent Service:**
   ```php
   class AudienceAnalyzerAgentService { ... }
   ```

3. **Create Prompt Config:**
   ```php
   AiPromptConfig::create([
       'key' => 'audience_analyzer_default',
       'agent_type' => 'AUDIENCE_ANALYZER',
       ...
   ]);
   ```

4. **Add Controller Method:**
   ```php
   public function analyzeAudience(CampaignBriefing $briefing) { ... }
   ```

5. **Add Route:**
   ```php
   Route::post('...', 'analyzeAudience')->name('...');
   ```

---

## Performance Tips

1. **Use Queues (Future Sprint):**
   - Move AI calls to background jobs
   - Return immediately, process async

2. **Cache Prompt Configs:**
   - Already implemented (1-hour TTL)
   - Clear cache: `Cache::forget('ai_prompt_config_...')`

3. **Optimize Token Usage:**
   - Reduce max_tokens for simple tasks
   - Use more focused system prompts
   - Consider gpt-3.5-turbo for drafts

4. **Monitor Costs:**
   - Check ai_usage_logs regularly
   - Filter by date range
   - Sum cost_estimate column

---

## Security Checklist

- [x] API key in .env (not committed to git)
- [x] Permissions system implemented
- [x] User tracking for all actions
- [x] No hardcoded prompts (all in database)
- [x] Input validation on all endpoints
- [x] Auth middleware on all routes
- [ ] Rate limiting (future enhancement)
- [ ] API key rotation policy (future enhancement)

---

## Next Steps After Setup

1. **Complete Views:**
   - Create form views for prompt configs
   - Update briefing/draft show views with AI sections
   - Add dashboard AI widgets

2. **Test Thoroughly:**
   - Generate copy for multiple briefings
   - Test approve/reject/apply workflow
   - Verify non-destructive merging

3. **Monitor Usage:**
   - Check ai_usage_logs table
   - Track costs
   - Monitor for failures

4. **Customize Prompts:**
   - Adjust prompts for your brand voice
   - Test different temperatures
   - Optimize for token usage

5. **Plan Sprint 5:**
   - Queue-based processing
   - More agent types
   - Analytics dashboard

---

## Support

For issues or questions:
1. Check `SPRINT_4_IMPLEMENTATION.md` for detailed docs
2. Review ai_usage_logs for error messages
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify all migrations ran successfully
5. Ensure seeders completed without errors

---

**You're ready to use AI-powered content creation!**

Generate your first AI copy:
```bash
# In browser
Visit: /admin/campaign-briefings
Click on any briefing
(Add AI action buttons to the view)
Click "Generate Copy"
```

**Happy AI-powered marketing!**
