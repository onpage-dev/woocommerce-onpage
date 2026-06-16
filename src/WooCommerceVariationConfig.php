<?php

namespace OpSupport;

if (!defined('OP_PLUGIN')) {
    die(400);
}

final class WooCommerceVariationAttributeConfig
{
    public string $slug;
    public string $label;
    public string $field;
    public ?string $field2;
    public bool $visible;

    public function __construct(string $slug, string $label, string $field, ?string $field2 = null, bool $visible = true)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->field = $field;
        $this->field2 = $field2;
        $this->visible = $visible;
    }

    public static function fromArray(array $data): ?self
    {
        $field = isset($data['field']) ? (string) $data['field'] : '';
        if ($field === '') {
            return null;
        }

        $label = trim((string) ($data['label'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($label === '') {
            $label = $slug;
        }
        if ($slug === '') {
            $slug = $label;
        }

        $slug = self::sanitizeSlug($slug);
        if ($slug === '') {
            return null;
        }

        return new self(
            $slug,
            $label !== '' ? $label : $slug,
            $field,
            isset($data['field2']) && $data['field2'] !== '' ? (string) $data['field2'] : null,
            !array_key_exists('visible', $data) || (bool) $data['visible']
        );
    }

    public static function sanitizeSlug(string $slug): string
    {
        if (function_exists('wc_sanitize_taxonomy_name')) {
            $slug = wc_sanitize_taxonomy_name($slug);
        } else {
            $slug = sanitize_title($slug);
        }
        $slug = preg_replace('/^pa_/', '', $slug);
        return substr($slug, 0, 28);
    }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'label' => $this->label,
            'field' => $this->field,
            'field2' => $this->field2,
            'visible' => $this->visible,
        ];
    }
}

final class WooCommerceVariationConfig
{
    public bool $enabled;
    public string $relation;
    /** @var array<string,string|null> */
    public array $fields;
    /** @var WooCommerceVariationAttributeConfig[] */
    public array $attributes;

    /**
     * @param array<string,string|null> $fields
     * @param WooCommerceVariationAttributeConfig[] $attributes
     */
    public function __construct(bool $enabled, string $relation, array $fields = [], array $attributes = [])
    {
        $this->enabled = $enabled;
        $this->relation = $relation;
        $this->fields = $fields;
        $this->attributes = $attributes;
    }

    public static function fromArray(array $data): ?self
    {
        $enabled = !empty($data['enabled']);
        $relation = isset($data['relation']) ? (string) $data['relation'] : '';
        if (!$enabled || $relation === '') {
            return null;
        }

        $fields = [];
        foreach ((array) ($data['fields'] ?? []) as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $fields[$key] = is_string($value) && $value !== '' ? $value : null;
        }

        $attributes = [];
        foreach ((array) ($data['attributes'] ?? []) as $attribute) {
            $attribute = is_object($attribute) ? (array) $attribute : (array) $attribute;
            $config = WooCommerceVariationAttributeConfig::fromArray($attribute);
            if ($config) {
                $attributes[] = $config;
            }
        }

        if (empty($attributes)) {
            return null;
        }

        return new self(true, $relation, $fields, $attributes);
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'relation' => $this->relation,
            'fields' => $this->fields,
            'attributes' => array_map(static function (WooCommerceVariationAttributeConfig $attribute): array {
                return $attribute->toArray();
            }, $this->attributes),
        ];
    }
}
