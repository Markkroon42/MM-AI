<div class="dropdown">
    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-translate"></i> {{ strtoupper(app()->getLocale()) }}
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
        @foreach(config('app.available_locales') as $locale)
            <li>
                <a class="dropdown-item {{ app()->getLocale() === $locale ? 'active' : '' }}"
                   href="{{ route('language.switch', $locale) }}">
                    {{ $locale === 'nl' ? '🇳🇱 Nederlands' : '🇬🇧 English' }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
