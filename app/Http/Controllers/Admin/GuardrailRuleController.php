<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuardrailRule;
use Illuminate\Http\Request;

class GuardrailRuleController extends Controller
{
    public function index()
    {
        $rules = GuardrailRule::orderBy('priority', 'asc')
            ->orderBy('applies_to_action_type', 'asc')
            ->paginate(50);

        return view('admin.guardrail-rules.index', compact('rules'));
    }

    public function show(GuardrailRule $rule)
    {
        return view('admin.guardrail-rules.show', compact('rule'));
    }

    public function create()
    {
        return view('admin.guardrail-rules.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'applies_to_action_type' => 'required|string|max:255',
            'condition_expression' => 'required|string',
            'effect' => 'required|in:allow,block,require_approval,warn',
            'severity' => 'required|in:low,medium,high,critical',
            'message_template' => 'required|string',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:1',
        ]);

        $rule = GuardrailRule::create($validated);

        return redirect()->route('admin.guardrail-rules.show', $rule)
            ->with('success', 'Guardrail rule created successfully.');
    }

    public function edit(GuardrailRule $rule)
    {
        return view('admin.guardrail-rules.edit', compact('rule'));
    }

    public function update(Request $request, GuardrailRule $rule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'applies_to_action_type' => 'required|string|max:255',
            'condition_expression' => 'required|string',
            'effect' => 'required|in:allow,block,require_approval,warn',
            'severity' => 'required|in:low,medium,high,critical',
            'message_template' => 'required|string',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:1',
        ]);

        $rule->update($validated);

        return redirect()->route('admin.guardrail-rules.show', $rule)
            ->with('success', 'Guardrail rule updated successfully.');
    }

    public function destroy(GuardrailRule $rule)
    {
        $rule->delete();

        return redirect()->route('admin.guardrail-rules.index')
            ->with('success', 'Guardrail rule deleted successfully.');
    }
}
