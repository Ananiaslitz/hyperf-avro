<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro\Factory;

use Ananiaslitz\HyperfAvro\Registry\ConfluentSchemaRegistry;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class ConfluentSchemaRegistryFactory
{
    public function __invoke(ContainerInterface $container): ConfluentSchemaRegistry
    {
        $config = $container->get(ConfigInterface::class);
        $baseUrl = $config->get('avro.registry.base_url', 'http://localhost:8081');

        $guzzleOptions = [];

        $authKey = $config->get('avro.registry.auth.key');
        $authSecret = $config->get('avro.registry.auth.secret');
        if ($authKey && $authSecret) {
            $guzzleOptions['auth'] = [$authKey, $authSecret];
        }

        $token = $config->get('avro.registry.auth.token');
        if ($token) {
            $guzzleOptions['headers']['Authorization'] = "Bearer {$token}";
        }

        $guzzleOptions['verify'] = $config->get('avro.registry.ssl_verify', true);
        $guzzleOptions['connect_timeout'] = $config->get('avro.registry.connect_timeout', 5);
        $guzzleOptions['timeout'] = $config->get('avro.registry.timeout', 10);

        return new ConfluentSchemaRegistry($baseUrl, $guzzleOptions);
    }
}
