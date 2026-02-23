<?php

/**
 * Sandbox Consumer
 *
 * Polls 'user-events', decodes each Confluent wire-format message,
 * and prints the structured data. Press Ctrl+C to stop.
 *
 * Run:
 *   docker-compose run --rm consumer
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Ananiaslitz\HyperfAvro\AvroSerializer;
use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Ananiaslitz\HyperfAvro\Registry\ConfluentSchemaRegistry;
use Ananiaslitz\HyperfAvro\SchemaManager;
use longlang\phpkafka\Consumer\Consumer;
use longlang\phpkafka\Consumer\ConsumerConfig;

$schemaRegistryUrl = getenv('SCHEMA_REGISTRY_URL') ?: 'http://localhost:18081';
$kafkaBrokers = getenv('KAFKA_BROKERS') ?: 'localhost:19092';

$config = new class implements \Hyperf\Contract\ConfigInterface {
    public function get(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'avro.schema_path' => __DIR__ . '/storage/avro',
            default => $default,
        };
    }
    public function has(string $key): bool
    {
        return true;
    }
    public function set(string $key, mixed $value): void
    {
    }
};

$registry = new ConfluentSchemaRegistry($schemaRegistryUrl);
$avro = new KafkaAvroSerializer(new AvroSerializer(new SchemaManager($config)), $registry);

$consumerConfig = new ConsumerConfig();
$consumerConfig->setBootstrapServers($kafkaBrokers);
$consumerConfig->setTopic('user-events');
$consumerConfig->setGroupId('sandbox-group');
$consumerConfig->setGroupInstanceId('sandbox-consumer-1');
$consumerConfig->setAutoCommit(false);

$consumer = new Consumer($consumerConfig);

echo "⏳ Waiting for messages on 'user-events'... (Ctrl+C to stop)\n";

while (true) {
    $message = $consumer->consume();

    if ($message === null) {
        continue;
    }

    try {
        $data = $avro->decode($message->getValue());

        echo "✔ Message received:\n";
        echo "  id       : {$data['id']}\n";
        echo "  username : {$data['username']}\n";
        echo "  email    : {$data['email']}\n";

        $consumer->ack($message);
    } catch (AvroSerializationException $e) {
        echo "✘ Failed to decode message: {$e->getMessage()}\n";
        $consumer->ack($message);
    }
}
