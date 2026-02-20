<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro\Contract;

interface SchemaRegistryInterface
{
    /**
     * Fetch a schema JSON string by its registry ID.
     */
    public function getSchemaById(int $id): string;

    /**
     * Register a schema under a subject. Returns the assigned schema ID.
     */
    public function registerSchema(string $subject, string $schemaJson): int;

    /**
     * Get the latest schema version for a subject.
     *
     * @return array{id: int, schema: string}
     */
    public function getLatestSchema(string $subject): array;
}
