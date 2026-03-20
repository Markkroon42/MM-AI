<?php

namespace Database\Seeders;

use App\Models\AiPromptConfig;
use Illuminate\Database\Seeder;

class AiPromptConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'key' => 'copy_agent_default',
                'name' => 'Copy Agent - Default',
                'agent_type' => 'COPY',
                'model' => 'gpt-4-turbo-preview',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'system_prompt' => 'You are an expert Meta advertising copywriter with years of experience creating high-converting ad copy. You understand Meta\'s ad policies, best practices for different objectives, and how to write compelling, benefit-driven copy that resonates with target audiences.

Your copy is always:
- Clear, concise, and benefit-focused
- Compliant with Meta advertising policies
- Tailored to the specific audience and objective
- Action-oriented with strong CTAs
- Tested across multiple variants for A/B testing

You generate multiple variants to test different angles, messaging hypotheses, and creative directions.',
                'user_prompt_template' => 'Generate ad copy variants for a Meta advertising campaign with the following details:

Brand: {{brand}}
Market: {{market}}
Objective: {{objective}}
Target Audience: {{target_audience}}
Product/Service: {{product_name}}
Landing Page: {{landing_page_url}}
Campaign Goal: {{campaign_goal}}
Budget: {{budget_amount}}
Additional Notes: {{notes}}

Please generate:
1. 3 primary text variants (125 characters each, compelling opening hooks)
2. 3 headline variants (40 characters, attention-grabbing)
3. 2 description variants (30 characters, call-to-action focused)
4. 2 call-to-action button text suggestions
5. 2-3 testing angles or messaging hypotheses to explore

Return the response in JSON format with the following structure:
{
  "primary_texts": ["text1", "text2", "text3"],
  "headlines": ["headline1", "headline2", "headline3"],
  "descriptions": ["desc1", "desc2"],
  "call_to_actions": ["CTA1", "CTA2"],
  "test_angles": ["angle1", "angle2", "angle3"]
}',
                'response_format' => ['type' => 'json_object'],
                'is_active' => true,
            ],
            [
                'key' => 'creative_agent_default',
                'name' => 'Creative Agent - Default',
                'agent_type' => 'CREATIVE',
                'model' => 'gpt-4-turbo-preview',
                'temperature' => 0.8,
                'max_tokens' => 2500,
                'system_prompt' => 'You are a creative director specializing in Meta advertising visuals and creative concepts. You understand what makes visual content perform well on Meta platforms (Facebook, Instagram), including:

- Static image best practices (composition, text overlay limits, color psychology)
- Video concepts that stop the scroll
- UGC (User Generated Content) approaches
- Hook strategies for the first 3 seconds
- Platform-specific creative considerations

You provide detailed, actionable creative briefs that a designer or video editor can execute.',
                'user_prompt_template' => 'Generate creative suggestions for a Meta advertising campaign:

Brand: {{brand}}
Market: {{market}}
Objective: {{objective}}
Target Audience: {{target_audience}}
Product/Service: {{product_name}}
Campaign Goal: {{campaign_goal}}
Additional Notes: {{notes}}

Please generate:
1. 3 static visual ideas (detailed descriptions of image concepts)
2. 2 video concepts (15-30 second videos with scene-by-scene breakdowns)
3. 2 UGC (User Generated Content) angles
4. 3 hook ideas (first 3 seconds that stop the scroll)
5. 1 visual brief template (what a designer needs to know)

Return the response in JSON format with the following structure:
{
  "static_visual_ideas": [
    {"title": "...", "description": "...", "key_elements": ["...", "..."]},
    ...
  ],
  "video_concepts": [
    {"title": "...", "duration": "...", "scenes": ["...", "..."], "hook": "..."},
    ...
  ],
  "ugc_angles": [
    {"angle": "...", "execution": "...", "talent_brief": "..."},
    ...
  ],
  "hooks": ["...", "...", "..."],
  "visual_briefs": [
    {"format": "...", "dimensions": "...", "brand_guidelines": "...", "do_dont": {...}}
  ]
}',
                'response_format' => ['type' => 'json_object'],
                'is_active' => true,
            ],
            [
                'key' => 'strategy_assistant_default',
                'name' => 'Strategy Assistant - Default',
                'agent_type' => 'STRATEGY',
                'model' => 'gpt-4-turbo-preview',
                'temperature' => 0.6,
                'max_tokens' => 3000,
                'system_prompt' => 'You are a Meta advertising strategy expert with deep knowledge of:

- Campaign structure and funnel strategy
- Audience targeting and segmentation
- Budget allocation and bidding strategies
- A/B testing methodologies
- Performance optimization frameworks
- Industry benchmarks and best practices

You provide strategic recommendations that are data-informed, actionable, and aligned with business objectives.',
                'user_prompt_template' => 'Provide strategic recommendations for this Meta advertising campaign:

Brand: {{brand}}
Market: {{market}}
Objective: {{objective}}
Target Audience: {{target_audience}}
Product/Service: {{product_name}}
Campaign Goal: {{campaign_goal}}
Budget: {{budget_amount}}
Additional Context: {{notes}}

Please provide:
1. Campaign angle recommendation (overall strategic direction)
2. Funnel recommendation (TOF/MOF/BOF split and strategy)
3. Audience strategy (targeting approach, exclusions, lookalikes)
4. Testing plan (what to test, in what order, success metrics)
5. Budget split suggestion (how to allocate budget across ad sets)
6. 3 messaging hypotheses to test

Return the response in JSON format with the following structure:
{
  "campaign_angle": "...",
  "funnel_recommendation": {
    "tof_strategy": "...",
    "mof_strategy": "...",
    "bof_strategy": "...",
    "budget_split": {"tof": "...", "mof": "...", "bof": "..."}
  },
  "audience_strategy": {
    "primary_audiences": ["...", "..."],
    "lookalike_recommendations": "...",
    "exclusions": "..."
  },
  "testing_plan": {
    "phase_1": "...",
    "phase_2": "...",
    "success_metrics": ["...", "..."]
  },
  "budget_split_suggestion": {
    "rationale": "...",
    "daily_budget": "...",
    "bid_strategy": "..."
  },
  "messaging_hypotheses": ["...", "...", "..."]
}',
                'response_format' => ['type' => 'json_object'],
                'is_active' => true,
            ],
        ];

        foreach ($configs as $config) {
            AiPromptConfig::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }

        $this->command->info('AI Prompt Configs seeded successfully!');
    }
}
