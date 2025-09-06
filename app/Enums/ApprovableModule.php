<?php

namespace App\Enums;

class ApprovableModule
{
    public const STOCK_TRANSFER             = 5;
    public const REQUEST_PRICE_APPRAISAL    = 6;
    public const SALES_TAGGING              = 19;
    public const REQUEST_REFURBISHMENT      = 23;
    public const REPO_TAGGING               = 25;
    public const PHYSICAL_INVENTORY         = 38;


    public static function values(): array
    {
        return [
            self::STOCK_TRANSFER,
            self::REQUEST_PRICE_APPRAISAL,
            self::SALES_TAGGING,
            self::REQUEST_REFURBISHMENT,
            self::REPO_TAGGING,
            self::PHYSICAL_INVENTORY,
        ];
    }
}
