<?php

namespace App\Services\AI;

use App\Enums\AiAgentTypeEnum;
use App\Models\AiPromptConfig;
use Illuminate\Support\Facades\Cache;

class PromptConfigResolver
{
    /**
     * Resolve config by key
     *
     * @param string $key
     * @return AiPromptConfig
     * @throws \Exception
     */
    public function resolveByKey(string $key): AiPromptConfig
    {
        $cacheKey = "ai_prompt_config_{$key}";

        $config = Cache::remember($cacheKey, 3600, function () use ($key) {
            return AiPromptConfig::where('key', $key)
                ->where('is_active', true)
                ->first();
        });

        if (!$config) {
            throw new \Exception("Prompt config not found or inactive: {$key}");
        }

        return $config;
    }

    /**
     * Resolve default config for agent type
     *
     * @param AiAgentTypeEnum $agentType
     * @return AiPromptConfig
     * @throws \Exception
     */
    public function resolveDefaultForAgent(AiAgentTypeEnum $agentType): AiPromptConfig
    {
        $cacheKey = "ai_prompt_config_agent_{$agentType->value}";

        $config = Cache::remember($cacheKey, 3600, function () use ($agentType) {
            return AiPromptConfig::where('agent_type', $agentType->value)
                ->where('is_active', true)
                ->where('key', 'like', '%_default')
                ->first();
        });

        if (!$config) {
            throw new \Exception("No default prompt config found for agent type: {$agentType->value}");
        }

        return $config;
    }

    /**
     * Clear cache for a specific config
     *
     * @param string $key
     * @return void
     */
    public function clearCache(string $key): void
    {
        Cache::forget("ai_prompt_config_{$key}");
    }
}
