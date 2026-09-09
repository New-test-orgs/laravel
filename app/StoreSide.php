<?php

namespace App;

enum StoreSide: string
{
    case Source = 'source';
    case Target = 'target';

    public function label(): string
    {
        return match ($this) {
            self::Source => 'Source',
            self::Target => 'Target',
        };
    }
}
