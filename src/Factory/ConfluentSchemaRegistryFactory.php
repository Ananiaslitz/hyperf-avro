<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro\Factory;

use Ananiaslitz\HyperfAvro\Registry\ConfluentSchemaRegistry;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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

        $maxRetries = (int) $config->get('avro.registry.max_retries', 3);
        if ($maxRetries > 0) {
            $handlerStack = HandlerStack::create();
            $handlerStack->push(Middleware::retry(
                function (int $retries, Request $request, ?Response $response = null, ?\Throwable $exception = null) use ($maxRetries): bool {
                    if ($retries >= $maxRetries) {
                        return false;
                    }
                    if ($exception instanceof ConnectException) {
                        return true;
                    }
                    if ($response && $response->getStatusCode() >= 500) {
                        return true;
                    }
                    return false;
                },
                function (int $retries): int {
                    return 100 * (2 ** ($retries - 1));
                }
            ));
            $guzzleOptions['handler'] = $handlerStack;
        }

        return new ConfluentSchemaRegistry($baseUrl, $guzzleOptions);
    }
}
