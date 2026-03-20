<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::orderBy('key')->get()->groupBy('category');

        return view('admin.system-settings.index', compact('settings'));
    }

    public function update(Request $request, SystemSetting $systemSetting)
    {
        $validated = $request->validate([
            'value' => 'required|string',
        ]);

        $systemSetting->update([
            'value' => $validated['value'],
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.system-settings.index')
            ->with('success', 'Setting updated successfully.');
    }
}
