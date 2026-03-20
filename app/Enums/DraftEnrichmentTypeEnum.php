<?php

namespace App\Enums;

enum DraftEnrichmentTypeEnum: string
{
    case COPY_VARIANTS = 'COPY_VARIANTS';
    case CREATIVE_SUGGESTIONS = 'CREATIVE_SUGGESTIONS';
    case STRATEGY_NOTES = 'STRATEGY_NOTES';
    case FULL_ENRICHMENT = 'FULL_ENRICHMENT';

    public function label(): string
    {
        return match($this) {
            self::COPY_VARIANTS => 'Copy Variants',
            self::CREATIVE_SUGGESTIONS => 'Creative Suggestions',
            self::STRATEGY_NOTES => 'Strategy Notes',
            self::FULL_ENRICHMENT => 'Full Enrichment',
        };
    }
}
