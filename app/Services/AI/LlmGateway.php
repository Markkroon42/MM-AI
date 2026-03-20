<?php

namespace App\Services\AI;

use App\Models\AiPromptConfig;
use Illuminate\Support\Facades\Log;
use OpenAI;

class LlmGateway
{
    protected $client;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('OPENAI_API_KEY not configured');
        }

        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Generate content using AI model
     *
     * @param AiPromptConfig $config
     * @param string $userPrompt
     * @param array $context
     * @return array
     */
    public function generate(AiPromptConfig $config, string $userPrompt, array $context = []): array
    {
        try {
            Log::info('[LLM_GATEWAY] Starting generation', [
                'config_key' => $config->key,
                'model' => $config->model,
                'temperature' => $config->temperature,
            ]);

            // Replace variables in user prompt
            $processedUserPrompt = $this->replaceVariables($userPrompt, $context);

            // Build messages
            $messages = [
                ['role' => 'system', 'content' => $config->system_prompt],
                ['role' => 'user', 'content' => $processedUserPrompt],
            ];

            // Prepare request parameters
            $params = [
                'model' => $config->model,
                'messages' => $messages,
                'temperature' => (float) $config->temperature,
                'max_tokens' => $config->max_tokens,
            ];

            // Add JSON response format if specified
            if ($config->response_format && isset($config->response_format['type'])) {
                $params['response_format'] = $config->response_format;
            }

            Log::info('[LLM_GATEWAY] Calling OpenAI API', [
                'model' => $config->model,
                'message_count' => count($messages),
            ]);

            // Call OpenAI API
            $response = $this->client->chat()->create($params);

            $content = $response->choices[0]->message->content ?? '';

            // Parse JSON response if response format is JSON
            $data = $content;
            if ($config->response_format && isset($config->response_format['type']) && $config->response_format['type'] === 'json_object') {
                $data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON response from LLM: ' . json_last_error_msg());
                }
            }

            $tokensInput = $response->usage->promptTokens ?? 0;
            $tokensOutput = $response->usage->completionTokens ?? 0;

            Log::info('[LLM_GATEWAY] Generation successful', [
                'tokens_input' => $tokensInput,
                'tokens_output' => $tokensOutput,
            ]);

            return [
                'success' => true,
                'data' => $data,
                'usage' => [
                    'prompt_tokens' => $tokensInput,
                    'completion_tokens' => $tokensOutput,
                    'total_tokens' => $response->usage->totalTokens ?? 0,
                ],
                'model' => $config->model,
            ];

        } catch (\Exception $e) {
            Log::error('[LLM_GATEWAY] Generation failed', [
                'error' => $e->getMessage(),
                'config_key' => $config->key,
            ]);

            throw $e;
        }
    }

    /**
     * Replace variables in prompt template
     *
     * @param string $template
     * @param array $variables
     * @return string
     */
    protected function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Convert arrays/objects to JSON string
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
            }

            // Replace {{key}} with value
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }
}
