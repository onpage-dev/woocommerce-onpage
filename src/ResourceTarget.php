<?php

namespace OpSupport;

if (!defined('OP_PLUGIN')) {
    die(400);
}

final class ResourceTarget
{
    public const STORAGE_POST = 'post';
    public const STORAGE_TERM = 'term';
    public const STORAGE_THING = 'thing';

    public const WC_MODE_NONE = 'none';
    public const WC_MODE_SIMPLE = 'simple';

    private string $storage;
    private ?string $postType;
    private ?string $taxonomy;
    private string $wcMode;
    private ?string $wpmlElementType;

    public function __construct(
        string $storage,
        ?string $postType = null,
        ?string $taxonomy = null,
        string $wcMode = self::WC_MODE_NONE,
        ?string $wpmlElementType = null
    ) {
        if (!in_array($storage, [self::STORAGE_POST, self::STORAGE_TERM, self::STORAGE_THING], true)) {
            throw new \InvalidArgumentException("Invalid resource target storage: {$storage}");
        }
        if (!in_array($wcMode, [self::WC_MODE_NONE, self::WC_MODE_SIMPLE], true)) {
            throw new \InvalidArgumentException("Invalid WooCommerce mode: {$wcMode}");
        }

        $this->storage = $storage;
        $this->postType = $postType;
        $this->taxonomy = $taxonomy;
        $this->wcMode = $wcMode;
        $this->wpmlElementType = $wpmlElementType;
    }

    public static function fromLegacyType(string $type): self
    {
        if ($type === self::STORAGE_POST) {
            return new self(self::STORAGE_POST, 'product', null, self::WC_MODE_SIMPLE, 'post_product');
        }
        if ($type === self::STORAGE_TERM) {
            return new self(self::STORAGE_TERM, null, 'product_cat', self::WC_MODE_NONE, 'tax_product_cat');
        }
        if ($type === self::STORAGE_THING) {
            return new self(self::STORAGE_THING);
        }

        throw new \InvalidArgumentException("Invalid legacy resource type: {$type}");
    }

    public static function fromConfig(string $storage, array $config = []): self
    {
        $legacy = self::fromLegacyType($storage);
        if ($legacy->isThing()) {
            return $legacy;
        }

        if ($legacy->isPost()) {
            $postType = self::validSlug($config['post_type'] ?? null) ?: $legacy->postType();
            $wcMode = $postType === 'product' ? self::WC_MODE_SIMPLE : self::WC_MODE_NONE;
            if (($config['wc_mode'] ?? null) === self::WC_MODE_NONE) {
                $wcMode = self::WC_MODE_NONE;
            }
            return new self(self::STORAGE_POST, $postType, null, $wcMode, "post_{$postType}");
        }

        $taxonomy = self::validSlug($config['taxonomy'] ?? null) ?: $legacy->taxonomy();
        return new self(self::STORAGE_TERM, null, $taxonomy, self::WC_MODE_NONE, "tax_{$taxonomy}");
    }

    private static function validSlug($slug): ?string
    {
        if (!is_string($slug) || $slug === '') {
            return null;
        }

        $clean = sanitize_key($slug);
        return $clean === $slug ? $slug : null;
    }

    public function storage(): string
    {
        return $this->storage;
    }

    public function isPost(): bool
    {
        return $this->storage === self::STORAGE_POST;
    }

    public function isTerm(): bool
    {
        return $this->storage === self::STORAGE_TERM;
    }

    public function isThing(): bool
    {
        return $this->storage === self::STORAGE_THING;
    }

    public function postType(): ?string
    {
        return $this->postType;
    }

    public function taxonomy(): ?string
    {
        return $this->taxonomy;
    }

    public function wcMode(): string
    {
        return $this->wcMode;
    }

    public function hasWooCommerce(): bool
    {
        return $this->wcMode !== self::WC_MODE_NONE;
    }

    public function wpmlElementType(): ?string
    {
        return $this->wpmlElementType;
    }

    public function thumbnailMetaKey(): ?string
    {
        if ($this->isThing()) {
            return null;
        }

        return $this->isPost() ? '_thumbnail_id' : 'thumbnail_id';
    }

    public function toArray(): array
    {
        return [
            'storage' => $this->storage,
            'post_type' => $this->postType,
            'taxonomy' => $this->taxonomy,
            'wc_mode' => $this->wcMode,
            'wpml_element_type' => $this->wpmlElementType,
        ];
    }
}
