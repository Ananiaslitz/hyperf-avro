<?php

/**
 * Sandbox Producer
 *
 * Registers the schema in the Schema Registry, then sends one
 * Avro-encoded Kafka message using the Confluent Wire Format.
 *
 * Run:
 *   docker-compose run --rm producer
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Ananiaslitz\HyperfAvro\AvroSerializer;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Ananiaslitz\HyperfAvro\Registry\ConfluentSchemaRegistry;
use Ananiaslitz\HyperfAvro\SchemaManager;
use longlang\phpkafka\Producer\Producer;
use longlang\phpkafka\Producer\ProducerConfig;

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

$schemaJson = (string) file_get_contents(__DIR__ . '/storage/avro/user-events-value.avsc');
$schemaId = $avro->registerSchema('user-events-value', $schemaJson);

echo "✔ Schema registered — ID: {$schemaId}\n";

$data = ['id' => 1, 'username' => 'ananias', 'email' => 'ananias@example.com'];

$payload = $avro->encode($data, 'user-events-value');

echo "✔ Payload encoded (" . strlen($payload) . " bytes)\n";
echo "  Magic byte : 0x" . sprintf('%02X', ord($payload[0])) . "\n";
echo "  Schema ID  : " . unpack('N', substr($payload, 1, 4))[1] . "\n";
echo "  Avro binary: " . bin2hex(substr($payload, 5)) . "\n";

$producerConfig = new ProducerConfig();
$producerConfig->setBootstrapServers($kafkaBrokers);
$producerConfig->setUpdateBrokers(true);
$producerConfig->setAcks(-1);

$producer = new Producer($producerConfig);
$producer->send('user-events', $payload, (string) time());
$producer->close();

echo "✔ Message sent to topic 'user-events'\n";
echo "  Data: " . json_encode($data) . "\n";
