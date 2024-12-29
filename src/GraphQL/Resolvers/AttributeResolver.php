<?php

namespace App\GraphQL\Resolvers;

use App\Models\Attribute\AttributeFactory;
use App\Repositories\AttributeRepository;
use App\GraphQL\Exception\InvalidAttributeException;
use App\GraphQL\Exception\MissingAttributesException;

class AttributeResolver
{
    private AttributeRepository $repository;

    public function __construct()
    {
        $this->repository = new AttributeRepository();
    }

    public function getAttributesByProduct(string $productId): array
    {
        try {
            // Get raw attribute data from repository
            $attributeSets = $this->repository->getAttributesByProduct($productId);

            // Transform each attribute set using appropriate model
            return array_map(function ($setData) {
                // Create attribute instance with all data at once
                $attribute = AttributeFactory::create($setData);
                return $attribute->toGraphQL();
            }, $attributeSets);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching attributes for product {$productId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getAttributeSet(string $attributeId): ?array
    {
        try {
            $setData = $this->repository->getAttributeSetById($attributeId);
            if (!$setData) {
                return null;
            }

            $attribute = AttributeFactory::create($setData);
            return $attribute->toGraphQL();
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching attribute set {$attributeId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function validateProductAttributes(string $productId, ?array $selectedAttributes = null): void
    {
        try {
            $productAttributes = $this->repository->getAttributesByProduct($productId);

            if (empty($productAttributes)) {
                return;
            }

            if (empty($selectedAttributes)) {
                $missingAttributes = array_map(fn($attr) => $attr['name'], $productAttributes);
                throw new MissingAttributesException($productId, $missingAttributes);
            }

            $selectedAttributeIds = array_map(fn($attr) => $attr['id'], $selectedAttributes);
            $missingAttributes = array_filter($productAttributes, fn($attr) => !in_array($attr['id'], $selectedAttributeIds));

            if (!empty($missingAttributes)) {
                $missingAttributeNames = array_map(fn($attr) => $attr['name'], $missingAttributes);
                throw new MissingAttributesException($productId, $missingAttributeNames);
            }

            foreach ($selectedAttributes as $selected) {
                $attributeSet = null;
                foreach ($productAttributes as $attr) {
                    if ($attr['id'] === $selected['id']) {
                        $attributeSet = $attr;
                        break;
                    }
                }

                if (!$attributeSet) {
                    throw new InvalidAttributeException(
                        "Invalid attribute ID: {$selected['id']}"
                    );
                }

                $attribute = AttributeFactory::create($attributeSet);
                if (!$attribute->validateValue($selected['value'])) {
                    throw new InvalidAttributeException(
                        "Invalid value for attribute {$attributeSet['name']}"
                    );
                }
            }
        } catch (InvalidAttributeException | MissingAttributesException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error validating attributes for product {$productId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
