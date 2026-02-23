<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro\Factory;

use Ananiaslitz\HyperfAvro\AvroSerializer;
use Ananiaslitz\HyperfAvro\Contract\SchemaRegistryInterface;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class KafkaAvroSerializerFactory
{
    public function __invoke(ContainerInterface $container): KafkaAvroSerializer
    {
        $config = $container->get(ConfigInterface::class);

        return new KafkaAvroSerializer(
            $container->get(AvroSerializer::class),
            $container->get(SchemaRegistryInterface::class),
            $config->get('avro.registry.subject_cache_ttl', 300),
        );
    }
}
