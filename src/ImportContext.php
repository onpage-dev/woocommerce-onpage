<?php

namespace OpSupport;

if (!defined('OP_PLUGIN')) {
    die(400);
}

final class ImportContext
{
    public object $schema;
    public object $schemaJson;
    public array $langs;
    public string $importedAt;
    public array $idMap = [];
    public array $newItems = [];
    public array $defaultSlugItems = [];

    public function __construct(object $schema, object $schemaJson, array $langs, string $importedAt)
    {
        $this->schema = $schema;
        $this->schemaJson = $schemaJson;
        $this->langs = $langs;
        $this->importedAt = $importedAt;
    }

    public function addImportedItem(string $resourceId, $onPageId, $lang, int $wpId, bool $isNew, bool $hasDefaultSlug): void
    {
        $this->idMap[$resourceId][$onPageId][$lang] = $wpId;

        if ($isNew) {
            $this->newItems[$resourceId][$onPageId][$lang] = $wpId;
        }

        if ($hasDefaultSlug) {
            $this->defaultSlugItems[$resourceId][$onPageId][$lang] = $wpId;
        }
    }

    public function wpId(string $resourceId, $onPageId, $lang): ?int
    {
        return $this->idMap[$resourceId][$onPageId][$lang] ?? null;
    }

    public function languagesForItem(string $resourceId, $onPageId): array
    {
        return $this->idMap[$resourceId][$onPageId] ?? [];
    }
}
