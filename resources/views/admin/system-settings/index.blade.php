@extends('layouts.admin')

@section('title', 'System Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">System Settings</h1>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Configuration</h5>
    </div>
    <div class="card-body">
        @if($settings->isEmpty())
            <p class="text-muted">No system settings found.</p>
            <p class="text-muted">Settings can be configured via <code>config/</code> files or the <code>system_settings</code> database table.</p>
        @else
            @foreach($settings as $category => $categorySettings)
                <h6 class="mt-4 mb-3 text-primary">{{ ucwords(str_replace('_', ' ', $category)) }}</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%">Key</th>
                                <th style="width: 40%">Value</th>
                                <th style="width: 20%">Type</th>
                                <th style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categorySettings as $setting)
                                <tr>
                                    <td>
                                        <code>{{ $setting->key }}</code>
                                        @if($setting->description)
                                            <br><small class="text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.system-settings.update', $setting) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            @if($setting->type === 'boolean')
                                                <select name="value" class="form-select form-select-sm">
                                                    <option value="true" {{ $setting->value === 'true' ? 'selected' : '' }}>True</option>
                                                    <option value="false" {{ $setting->value === 'false' ? 'selected' : '' }}>False</option>
                                                </select>
                                            @elseif($setting->type === 'integer' || $setting->type === 'decimal')
                                                <input type="number" name="value" value="{{ $setting->value }}"
                                                       class="form-control form-control-sm"
                                                       step="{{ $setting->type === 'decimal' ? '0.01' : '1' }}">
                                            @else
                                                <input type="text" name="value" value="{{ $setting->value }}"
                                                       class="form-control form-control-sm">
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $setting->type }}</span>
                                        </td>
                                        <td>
                                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif

        <hr class="my-4">

        <h6 class="mb-3">Configuration Files</h6>
        <p class="text-muted mb-2">Key configuration files:</p>
        <ul class="list-unstyled">
            <li><code>config/meta.php</code> - Meta API settings</li>
            <li><code>config/recommendations.php</code> - Recommendation thresholds</li>
            <li><code>config/guardrails.php</code> - Guardrail rules configuration</li>
            <li><code>config/reporting.php</code> - Reporting settings</li>
            <li><code>.env</code> - Environment variables (API keys, database, etc.)</li>
        </ul>
    </div>
</div>
@endsection
