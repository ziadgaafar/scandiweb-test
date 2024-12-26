<?php

namespace App\GraphQL\Resolvers;

class CategoryResolver extends AbstractResolver
{
    public function getCategories(): array
    {
        return $this->executeQuery("SELECT * FROM categories ORDER BY name");
    }

    public function getCategory(string $name): ?array
    {
        return $this->executeSingle(
            "SELECT * FROM categories WHERE name = :name",
            ['name' => $name]
        );
    }
}
