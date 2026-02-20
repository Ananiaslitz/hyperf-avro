<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro\Registry;

use Ananiaslitz\HyperfAvro\Contract\SchemaRegistryInterface;
use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ConfluentSchemaRegistry implements SchemaRegistryInterface
{
    private Client $http;

    public function __construct(
        string $baseUrl,
        array $options = [],
    ) {
        $this->http = new Client(array_merge([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'headers' => ['Content-Type' => 'application/vnd.schemaregistry.v1+json'],
        ], $options));
    }

    public function getSchemaById(int $id): string
    {
        try {
            $response = $this->http->get("schemas/ids/{$id}");
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $body['schema'];
        } catch (GuzzleException $e) {
            throw new AvroSerializationException(
                "Schema Registry: failed to fetch schema ID {$id}: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    public function registerSchema(string $subject, string $schemaJson): int
    {
        try {
            $response = $this->http->post("subjects/{$subject}/versions", [
                'json' => ['schema' => $schemaJson],
            ]);
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $body['id'];
        } catch (GuzzleException $e) {
            throw new AvroSerializationException(
                "Schema Registry: failed to register schema for subject '{$subject}': " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    public function getLatestSchema(string $subject): array
    {
        try {
            $response = $this->http->get("subjects/{$subject}/versions/latest");
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return [
                'id' => $body['id'],
                'schema' => $body['schema'],
            ];
        } catch (GuzzleException $e) {
            throw new AvroSerializationException(
                "Schema Registry: failed to fetch latest schema for subject '{$subject}': " . $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
