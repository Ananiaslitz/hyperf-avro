<?php

/**
 * Kafka Producer Example — hyperf-avro
 *
 * This example shows how to produce an Avro-serialized message to a Kafka topic
 * using the Confluent Wire Format (magic byte + schema ID + Avro binary).
 *
 * Assumes:
 *   - Hyperf app with hyperf/kafka and hyperf/di
 *   - Schema already registered in the Schema Registry (or auto-registered below)
 *   - KafkaAvroSerializer injected via constructor
 */

declare(strict_types=1);

namespace App\Kafka\Producer;

use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Hyperf\Kafka\Producer;

class UserEventProducer
{
    private const TOPIC = 'user-events';
    private const SUBJECT = self::TOPIC . '-value';

    public function __construct(
        private KafkaAvroSerializer $avro,
        private Producer $producer,
    ) {
    }

    public function publishUserCreated(int $id, string $username, string $email): void
    {
        try {
            $payload = $this->avro->encode(
                ['id' => $id, 'username' => $username, 'email' => $email],
                self::SUBJECT,
            );

            $this->producer->send(self::TOPIC, $payload);
        } catch (AvroSerializationException $e) {
            throw $e;
        }
    }

    public function ensureSchemaRegistered(): int
    {
        $schemaJson = (string) file_get_contents(__DIR__ . '/../../storage/avro/user.avsc');

        return $this->avro->registerSchema(self::SUBJECT, $schemaJson);
    }
}
