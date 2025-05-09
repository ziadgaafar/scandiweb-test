<?php

namespace App\Models\Product;

use App\Models\Abstract\AbstractProduct;

class ProductFactory
{
    /**
     * Create appropriate product instance based on data
     *
     * @param array $data Product data
     * @return AbstractProduct
     */
    public static function create(array $data): AbstractProduct
    {
        return empty($data['attributes'])
            ? new SimpleProduct($data)
            : new ConfigurableProduct($data);
    }
}
