<?php

declare(strict_types=1);

namespace App\Consumer;

use Ananiaslitz\HyperfAvro\Annotation\AvroDeserialize;
use App\DTO\UserDTO;
use Hyperf\Kafka\AbstractConsumer;

/**
 * Example 2: Consuming Kafka messages with AOP Avro decoding and DTO mapping.
 */
class UserEventConsumer extends AbstractConsumer
{
    public string $topic = 'user-events';

    /**
     * #[AvroDeserialize] automatically intercepts the binary message payload ($payload),
     * decodes the Schema ID from Confluent wire format, fetches the schema from Schema Registry,
     * and maps the decoded array into UserDTO using UserDTO::fromArray() or constructor.
     */
    #[AvroDeserialize(schema: 'user-events-value', targetClass: UserDTO::class, argIndex: 0, factoryMethod: 'fromArray')]
    public function consume(UserDTO $user): void
    {
        // $user is now a typed UserDTO instance
        echo "Received user event for: " . $user->getUsername() . " (" . $user->getEmail() . ")\n";
    }
}
