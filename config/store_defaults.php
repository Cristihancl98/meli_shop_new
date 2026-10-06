<?php

/*
| Configuración inicial de cada cuenta de Mercado Libre.
| Valores tomados de la tabla `configuraciones` de la BD original (dbMeli.sql),
| normalizados a la estructura actual (porcentajes en 0-100, ganancia por rangos).
| Se insertan automáticamente al vincular una cuenta y se usan como respaldo
| cuando una clave no tiene valor guardado.
*/

return [
    'listing_type'         => 'gold_special',
    'logistics_price'      => 5,
    'shipping_price'       => 5,
    'dollar_price'         => 5000,
    'meli_commission'      => 16,
    'iva'                  => 0,
    'manufacturing_days'   => 5,
    'profit_ranges'        => [['from' => 0, 'to' => 1000000, 'percentage' => 10]],
    'amazon_commission'    => 0,
    'weight_ranges'        => [],
    'national_tax_ranges'  => [],
    'sale_message'         => null,
    'description_template' => null,
    'warranty_days'        => 30,
    'default_quantity'     => 1,
    'scraping_url'         => null,
];
