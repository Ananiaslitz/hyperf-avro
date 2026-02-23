<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro;

use Ananiaslitz\HyperfAvro\Contract\SchemaRegistryInterface;
use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Apache\Avro\Schema\AvroSchema;

/**
 * Kafka-aware Avro serializer using the Confluent Wire Format.
 *
 * Wire format:
 *   - Byte 0:    Magic byte (0x00)
 *   - Bytes 1-4: Schema ID (big-endian int32)
 *   - Bytes 5+:  Avro binary payload
 *
 * Schema caching:
 *   - Decode: schemas are cached permanently by ID (Confluent IDs are immutable).
 *   - Encode: the latest schema per subject is cached with a configurable TTL
 *     to avoid a registry round-trip on every message while still picking up
 *     new schema versions within a reasonable window.
 */
class KafkaAvroSerializer
{
    private const MAGIC_BYTE = 0x00;
    private const PREFIX_LENGTH = 5;

    /** @var array<int, AvroSchema> */
    private array $schemaById = [];

    /** @var array<string, array{id: int, schema: AvroSchema, fetchedAt: int}> */
    private array $schemaBySubject = [];

    public function __construct(
        private AvroSerializer $serializer,
        private SchemaRegistryInterface $registry,
        private int $subjectCacheTtl = 300,
    ) {
    }

    /**
     * Encode $data to Confluent wire format using the latest schema for $subject.
     */
    public function encode(array $data, string $subject): string
    {
        ['id' => $schemaId, 'schema' => $schema] = $this->resolveLatestSchema($subject);

        $binary = $this->serializer->encodeWithSchema($data, $schema);

        return $this->prependWireFormat($schemaId, $binary);
    }

    /**
     * Decode a Confluent wire-format payload using the embedded schema ID.
     */
    public function decode(string $payload): array
    {
        [$schemaId, $binary] = $this->parseWireFormat($payload);

        $schema = $this->resolveSchemaById($schemaId);

        return $this->serializer->decodeWithSchema($binary, $schema);
    }

    /**
     * Register a schema under $subject and return the assigned schema ID.
     */
    public function registerSchema(string $subject, string $schemaJson): int
    {
        return $this->registry->registerSchema($subject, $schemaJson);
    }

    /**
     * Resolve the latest schema for a subject, using the TTL-based cache.
     *
     * @return array{id: int, schema: AvroSchema}
     */
    private function resolveLatestSchema(string $subject): array
    {
        $now = time();
        $cached = $this->schemaBySubject[$subject] ?? null;

        if ($cached !== null && ($now - $cached['fetchedAt']) < $this->subjectCacheTtl) {
            return ['id' => $cached['id'], 'schema' => $cached['schema']];
        }

        ['id' => $schemaId, 'schema' => $schemaJson] = $this->registry->getLatestSchema($subject);

        $schema = $this->parseSchema($schemaJson);

        $this->schemaBySubject[$subject] = [
            'id' => $schemaId,
            'schema' => $schema,
            'fetchedAt' => $now,
        ];

        $this->schemaById[$schemaId] = $schema;

        return ['id' => $schemaId, 'schema' => $schema];
    }

    /**
     * Resolve a schema by its registry ID, using the permanent ID cache.
     * Schema IDs in Confluent are immutable — a given ID always maps to the same schema.
     */
    private function resolveSchemaById(int $schemaId): AvroSchema
    {
        if (isset($this->schemaById[$schemaId])) {
            return $this->schemaById[$schemaId];
        }

        $schemaJson = $this->registry->getSchemaById($schemaId);
        $schema = $this->parseSchema($schemaJson);

        $this->schemaById[$schemaId] = $schema;

        return $schema;
    }

    private function prependWireFormat(int $schemaId, string $binary): string
    {
        return pack('CN', self::MAGIC_BYTE, $schemaId) . $binary;
    }

    private function parseWireFormat(string $payload): array
    {
        if (strlen($payload) < self::PREFIX_LENGTH) {
            throw new AvroSerializationException(
                'Invalid Confluent wire format: payload too short (' . strlen($payload) . ' bytes)',
            );
        }

        /** @var array{magic: int, schemaId: int} $prefix */
        $prefix = unpack('Cmagic/NschemaId', substr($payload, 0, self::PREFIX_LENGTH));

        if ($prefix['magic'] !== self::MAGIC_BYTE) {
            throw new AvroSerializationException(
                sprintf('Invalid Confluent wire format: expected magic byte 0x00, got 0x%02X', $prefix['magic']),
            );
        }

        return [$prefix['schemaId'], substr($payload, self::PREFIX_LENGTH)];
    }

    private function parseSchema(string $schemaJson): AvroSchema
    {
        try {
            return AvroSchema::parse($schemaJson);
        } catch (\Throwable $e) {
            throw new AvroSerializationException('Failed to parse schema from registry: ' . $e->getMessage(), 0, $e);
        }
    }
}
