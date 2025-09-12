<?php

namespace App\Enums;

class ApprovableModule
{
    public const REPO_TAGGING               = 25; // done
    public const STOCK_TRANSFER             = 5;  // done
    public const REQUEST_PRICE_APPRAISAL    = 6;  // done
    public const REQUEST_REFURBISHMENT      = 23; // done
    public const SETTLE_REFERBISHMENT       = 26; // done
    public const SALES_TAGGING              = 19; 
    public const PHYSICAL_INVENTORY         = 38;

    public static function values(): array
    {
        return [
            self::REPO_TAGGING,
            self::STOCK_TRANSFER,
            self::REQUEST_PRICE_APPRAISAL,
            self::REQUEST_REFURBISHMENT,
            self::SETTLE_REFERBISHMENT,
            self::SALES_TAGGING,
            self::PHYSICAL_INVENTORY,
        ];
    }
}
