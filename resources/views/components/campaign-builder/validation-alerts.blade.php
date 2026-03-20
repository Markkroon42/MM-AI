@props(['validation'])

@php
    $blockers = $validation['blockers'] ?? [];
    $warnings = $validation['warnings'] ?? [];
    $infos = $validation['infos'] ?? [];
@endphp

@if(count($blockers) > 0)
    <div class="alert alert-danger d-flex align-items-start" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
        <div class="flex-grow-1">
            <strong>Publish Blockers</strong>
            <ul class="mb-0 mt-2">
                @foreach($blockers as $blocker)
                    <li>{{ $blocker['message'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(count($warnings) > 0)
    <div class="alert alert-warning d-flex align-items-start" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2 mt-1"></i>
        <div class="flex-grow-1">
            <strong>Warnings</strong>
            <ul class="mb-0 mt-2">
                @foreach($warnings as $warning)
                    <li>{{ $warning['message'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(count($infos) > 0)
    <div class="alert alert-info d-flex align-items-start" role="alert">
        <i class="bi bi-info-circle-fill me-2 mt-1"></i>
        <div class="flex-grow-1">
            <strong>Information</strong>
            <ul class="mb-0 mt-2">
                @foreach($infos as $info)
                    <li>{{ $info['message'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
