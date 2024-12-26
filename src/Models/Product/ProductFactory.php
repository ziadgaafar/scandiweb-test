<?php

namespace App\Models\Product;

use App\Models\Abstract\AbstractProduct;

class ProductFactory
{
    public static function create(array $data): AbstractProduct
    {
        return empty($data['attributes'])
            ? new SimpleProduct($data)
            : new ConfigurableProduct($data);
    }
}
