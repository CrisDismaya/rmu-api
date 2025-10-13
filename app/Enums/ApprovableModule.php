<?php

namespace App\Enums;

class ApprovableModule
{
    public const REPO_TAGGING               = 25;
    public const STOCK_TRANSFER             = 5;
    public const REQUEST_PRICE_APPRAISAL    = 6;
    public const REQUEST_REFURBISHMENT      = 23;
    public const SETTLE_REFERBISHMENT       = 26;
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

    public static function labels(): array
    {
        return [
            self::REPO_TAGGING            => 'Repo Tagging',
            self::STOCK_TRANSFER          => 'Stock Transfer',
            self::REQUEST_PRICE_APPRAISAL => 'Request Price Appraisal',
            self::REQUEST_REFURBISHMENT   => 'Request Refurbishment',
            self::SETTLE_REFERBISHMENT    => 'Settle Refurbishment',
            self::SALES_TAGGING           => 'Sales Tagging',
            self::PHYSICAL_INVENTORY      => 'Physical Inventory',
        ];
    }
}
