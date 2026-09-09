<?php

namespace App\Cart2Cart;

use App\StoreSide;

final readonly class Cart2CartStoreAccess
{
    public function __construct(
        public Cart2CartStoreCredentials $source,
        public Cart2CartStoreCredentials $target,
    ) {}

    public function for(StoreSide $side): Cart2CartStoreCredentials
    {
        return match ($side) {
            StoreSide::Source => $this->source,
            StoreSide::Target => $this->target,
        };
    }
}
