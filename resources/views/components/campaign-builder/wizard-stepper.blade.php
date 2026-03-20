@props(['currentStep' => 1, 'steps' => []])

<div class="wizard-stepper mb-4">
    <div class="row g-3">
        @foreach($steps as $index => $step)
            @php
                $stepNumber = $index + 1;
                $isActive = $stepNumber === $currentStep;
                $isCompleted = $stepNumber < $currentStep;
                $isFuture = $stepNumber > $currentStep;
            @endphp
            <div class="col">
                <div class="step-item {{ $isActive ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }} {{ $isFuture ? 'future' : '' }}">
                    <div class="step-marker">
                        @if($isCompleted)
                            <i class="bi bi-check-circle-fill text-success"></i>
                        @else
                            <span class="step-number">{{ $stepNumber }}</span>
                        @endif
                    </div>
                    <div class="step-label">
                        <div class="step-title">{{ $step['title'] }}</div>
                        @if(!empty($step['subtitle']))
                            <div class="step-subtitle text-muted small">{{ $step['subtitle'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
.wizard-stepper {
    margin-bottom: 2rem;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    padding: 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.step-item.active {
    background-color: #f8f9fa;
}

.step-marker {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #e9ecef;
    margin-bottom: 0.75rem;
    font-size: 1.5rem;
}

.step-item.active .step-marker {
    background-color: #0d6efd;
    color: white;
}

.step-item.completed .step-marker {
    background-color: transparent;
}

.step-item.future .step-marker {
    background-color: #e9ecef;
    color: #6c757d;
}

.step-number {
    font-weight: 600;
    font-size: 1.25rem;
}

.step-label {
    font-size: 0.9rem;
}

.step-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.step-subtitle {
    font-size: 0.85rem;
}

.step-item.active .step-title {
    color: #0d6efd;
}
</style>
