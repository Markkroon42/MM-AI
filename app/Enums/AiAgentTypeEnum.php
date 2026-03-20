<?php

namespace App\Enums;

enum AiAgentTypeEnum: string
{
    case COPY = 'COPY';
    case CREATIVE = 'CREATIVE';
    case STRATEGY = 'STRATEGY';
    case ENRICHMENT = 'ENRICHMENT';

    public function label(): string
    {
        return match($this) {
            self::COPY => 'Copy Agent',
            self::CREATIVE => 'Creative Suggestions',
            self::STRATEGY => 'Strategy Assistant',
            self::ENRICHMENT => 'Draft Enrichment',
        };
    }
}
