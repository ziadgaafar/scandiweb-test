<?php

namespace App\Models\Abstract;

abstract class AbstractModel
{
    protected array $attributes = [];
    protected array $fillable = [];

    public function __construct(array $data = [])
    {
        $this->fill($data);
    }

    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    protected function fill(array $data): void
    {
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $this->attributes[$field] = $data[$field];
            }
        }
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
