<?php

declare(strict_types=1);

namespace App\Producer;

use Ananiaslitz\HyperfAvro\Annotation\AvroSerialize;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Hyperf\Kafka\Producer;

/**
 * Example 1: Producing Kafka messages using KafkaAvroSerializer manually or via #[AvroSerialize] AOP annotation.
 */
class UserEventProducer
{
    public function __construct(
        private Producer $producer,
        private KafkaAvroSerializer $avro,
    ) {
    }

    /**
     * Option A: Manual encoding with KafkaAvroSerializer
     */
    public function sendUserRegistered(int $userId, string $name, string $email): void
    {
        $payload = [
            'id' => $userId,
            'username' => $name,
            'email' => $email,
        ];

        // Encodes payload with Confluent Wire Format (Magic Byte 0x00 + Schema ID)
        $binary = $this->avro->encode($payload, 'user-events-value');

        $this->producer->send('user-events', $binary, (string) $userId);
    }

    /**
     * Option B: Automatic serialization via #[AvroSerialize] annotation.
     * The return value of this method will automatically be converted to Confluent Avro wire format.
     */
    #[AvroSerialize(schema: 'user-events-value')]
    public function buildUserCreatedPayload(int $userId, string $name, string $email): array
    {
        return [
            'id' => $userId,
            'username' => $name,
            'email' => $email,
        ];
    }
}
