<?php

namespace OpSupport;

if (!defined('OP_PLUGIN')) {
    die(400);
}

final class WooCommerceFieldMap
{
    public static function adminFields(): array
    {
        return [
            ['name' => 'sku', 'label' => 'Sku', 'default' => 'none', 'types' => ['string', 'int']],
            ['name' => 'excerpt', 'label' => 'Short Description', 'default' => 'auto', 'types' => ['string', 'text', 'html', 'int', 'real']],
            ['name' => 'weight', 'label' => 'Weight', 'default' => 'none', 'types' => ['weight', 'int', 'real']],
            ['name' => 'length', 'label' => 'Length', 'default' => 'none', 'types' => ['int', 'real']],
            ['name' => 'width', 'label' => 'Width', 'default' => 'none', 'types' => ['int', 'real']],
            ['name' => 'height', 'label' => 'Height', 'default' => 'none', 'types' => ['int', 'real']],
            ['name' => 'price', 'label' => 'Price', 'default' => 'none', 'types' => ['int', 'real', 'price'], 'can_be_empty' => true],
            ['name' => 'discounted-price', 'label' => 'Discounted Price', 'default' => 'none', 'types' => ['int', 'real', 'price'], 'can_be_empty' => true],
            ['name' => 'discounted-start-date', 'label' => 'Discount start date', 'default' => 'none', 'types' => ['date'], 'can_be_empty' => true],
            ['name' => 'discounted-end-date', 'label' => 'Discount end date', 'default' => 'none', 'types' => ['date'], 'can_be_empty' => true],
            ['name' => 'downloadable', 'label' => 'Downloadable', 'default' => 'none', 'types' => ['bool'], 'can_be_empty' => true],
            ['name' => 'manage_stock', 'label' => 'Manage stock', 'default' => 'off', 'types' => ['bool']],
            ['name' => 'stock', 'label' => 'Stock (available pieces)', 'default' => 'infinity', 'types' => ['int']],
            ['name' => 'low_stock_amount', 'label' => 'Low stock threshold', 'default' => 'none', 'types' => ['int']],
            ['name' => 'stock_status', 'label' => 'In stock (true/false)', 'default' => 'if stock > 0', 'types' => ['bool']],
            ['name' => 'virtual', 'label' => 'Virtual product', 'default' => 'none', 'types' => ['bool']],
            ['name' => 'image', 'label' => 'Image', 'default' => 'none', 'note' => 'WARNING: importing images in the Wordpress Gallery will greatly slow down the import process and is generally not needed', 'types' => ['image']],
            ['name' => 'sorting', 'label' => 'Sorting', 'default' => 'none', 'none_label' => 'Same as On Page (default)', 'note' => 'By default, sorting will reflect the On Page ordering, but you can use any numeric field to set the order or maintain the wordpress custom sorting', 'types' => ['int'], 'custom_fields' => [['label' => 'Maintain wordpress sorting', 'value' => '_wp_sorting']]],
        ];
    }

    public static function variationAdminFields(): array
    {
        $excluded = ['excerpt', 'image', 'sorting'];
        return array_values(array_filter(static::adminFields(), static function (array $field) use ($excluded): bool {
            return !in_array($field['name'], $excluded, true);
        }));
    }

    public static function metaDefinitions(): array
    {
        $yesNo = static function ($value): string {
            return $value ? 'yes' : 'no';
        };

        return [
            '_regular_price' => ['option' => 'price'],
            '_sale_price' => ['option' => 'discounted-price'],
            '_sale_price_dates_from' => ['option' => 'discounted-start-date'],
            '_sale_price_dates_to' => ['option' => 'discounted-end-date'],
            '_sku' => ['option' => 'sku'],
            '_weight' => ['option' => 'weight'],
            '_width' => ['option' => 'width'],
            '_length' => ['option' => 'length'],
            '_height' => ['option' => 'height'],
            '_downloadable' => ['option' => 'downloadable', 'mapper' => $yesNo],
            '_low_stock_amount' => ['option' => 'low_stock_amount'],
            '_manage_stock' => ['option' => 'manage_stock', 'mapper' => $yesNo],
            '_stock' => ['option' => 'stock'],
            '_stock_status' => [
                'option' => 'stock_status',
                'mapper' => static function ($value): string {
                    return $value ? 'instock' : 'outofstock';
                },
            ],
            '_virtual' => ['option' => 'virtual', 'mapper' => $yesNo],
        ];
    }

    public static function commerceMetaKeys(): array
    {
        return array_values(array_unique(array_merge(array_keys(static::metaDefinitions()), ['_price'])));
    }
}
