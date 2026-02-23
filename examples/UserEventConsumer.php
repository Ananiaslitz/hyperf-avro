<?php

/**
 * Kafka Consumer Example — hyperf-avro
 *
 * This example shows how to consume Avro-serialized Kafka messages using
 * either the #[AvroDeserialize] annotation (AOP) or direct deserialization.
 *
 * Assumes:
 *   - Hyperf app with hyperf/kafka and hyperf/di
 *   - KafkaAvroSerializer injected via constructor
 */

declare(strict_types=1);

namespace App\Kafka\Consumer;

use Ananiaslitz\HyperfAvro\Annotation\AvroDeserialize;
use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Hyperf\Kafka\AbstractConsumer;
use Hyperf\Kafka\Annotation\Consumer;
use longlang\phpkafka\Consumer\ConsumeMessage;

#[Consumer(topic: 'user-events', groupId: 'user-service', name: 'UserEventConsumer', nums: 1)]
class UserEventConsumerWithAnnotation extends AbstractConsumer
{
    #[AvroDeserialize(schema: 'user-events-value')]
    public function consume(ConsumeMessage $message): string
    {
        return self::ACK;
    }
}

#[Consumer(topic: 'user-events', groupId: 'user-service', name: 'UserEventConsumerDirect', nums: 1)]
class UserEventConsumerDirect extends AbstractConsumer
{
    public function __construct(
        private KafkaAvroSerializer $avro,
    ) {
    }

    public function consume(ConsumeMessage $message): string
    {
        try {
            $data = $this->avro->decode($message->getValue());

            $this->handleUserCreated(
                id: $data['id'],
                username: $data['username'],
                email: $data['email'],
            );

            return self::ACK;
        } catch (AvroSerializationException $e) {
            return self::DROP;
        }
    }

    private function handleUserCreated(int $id, string $username, string $email): void
    {
    }
}
