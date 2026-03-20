<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AiAgentTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\AiPromptConfig;
use Illuminate\Http\Request;

class AiPromptConfigController extends Controller
{
    public function index(Request $request)
    {
        $query = AiPromptConfig::query();

        // Filters
        if ($request->filled('agent_type')) {
            $query->where('agent_type', $request->agent_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $configs = $query->orderBy('created_at', 'desc')->paginate(15);

        $agentTypes = AiAgentTypeEnum::cases();

        return view('admin.ai-prompt-configs.index', compact('configs', 'agentTypes'));
    }

    public function show(AiPromptConfig $config)
    {
        return view('admin.ai-prompt-configs.show', compact('config'));
    }

    public function create()
    {
        $agentTypes = AiAgentTypeEnum::cases();
        return view('admin.ai-prompt-configs.create', compact('agentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:ai_prompt_configs,key',
            'name' => 'required|string|max:255',
            'agent_type' => 'required|string',
            'model' => 'required|string',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:1',
            'system_prompt' => 'required|string',
            'user_prompt_template' => 'required|string',
            'response_format' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('response_format')) {
            $validated['response_format'] = json_decode($validated['response_format'], true);
        }

        $config = AiPromptConfig::create($validated);

        return redirect()
            ->route('admin.ai-prompt-configs.show', $config)
            ->with('success', 'AI Prompt Config created successfully.');
    }

    public function edit(AiPromptConfig $config)
    {
        $agentTypes = AiAgentTypeEnum::cases();
        return view('admin.ai-prompt-configs.edit', compact('config', 'agentTypes'));
    }

    public function update(Request $request, AiPromptConfig $config)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:ai_prompt_configs,key,' . $config->id,
            'name' => 'required|string|max:255',
            'agent_type' => 'required|string',
            'model' => 'required|string',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:1',
            'system_prompt' => 'required|string',
            'user_prompt_template' => 'required|string',
            'response_format' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('response_format')) {
            $validated['response_format'] = json_decode($validated['response_format'], true);
        }

        $config->update($validated);

        return redirect()
            ->route('admin.ai-prompt-configs.show', $config)
            ->with('success', 'AI Prompt Config updated successfully.');
    }
}
