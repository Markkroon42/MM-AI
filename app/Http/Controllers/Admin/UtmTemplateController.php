<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UtmTemplate;
use Illuminate\Http\Request;

class UtmTemplateController extends Controller
{
    public function index()
    {
        $templates = UtmTemplate::orderBy('name')->paginate(15);

        return view('admin.utm-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.utm-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'medium' => 'required|string|max:255',
            'campaign_pattern' => 'required|string|max:255',
            'content_pattern' => 'nullable|string|max:255',
            'term_pattern' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $template = UtmTemplate::create($validated);

        return redirect()
            ->route('admin.utm-templates.index')
            ->with('success', 'UTM template created successfully.');
    }
}
