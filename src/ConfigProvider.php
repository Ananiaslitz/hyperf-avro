<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro;

use Ananiaslitz\HyperfAvro\Aspect\AvroDeserializeAspect;
use Ananiaslitz\HyperfAvro\Aspect\AvroSerializeAspect;
use Ananiaslitz\HyperfAvro\Contract\SchemaRegistryInterface;
use Ananiaslitz\HyperfAvro\Factory\ConfluentSchemaRegistryFactory;
use Ananiaslitz\HyperfAvro\Factory\KafkaAvroSerializerFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                SchemaManager::class => SchemaManager::class,
                AvroSerializer::class => AvroSerializer::class,
                KafkaAvroSerializer::class => KafkaAvroSerializerFactory::class,
                SchemaRegistryInterface::class => ConfluentSchemaRegistryFactory::class,
            ],
            'aspects' => [
                AvroSerializeAspect::class,
                AvroDeserializeAspect::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for hyperf-avro.',
                    'source' => __DIR__ . '/../publish/avro.php',
                    'destination' => (defined('BASE_PATH') ? BASE_PATH : '') . '/config/autoload/avro.php',
                ],
            ],
        ];
    }
}
