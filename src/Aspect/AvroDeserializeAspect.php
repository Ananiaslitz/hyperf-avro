<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro\Aspect;

use Ananiaslitz\HyperfAvro\Annotation\AvroDeserialize;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

/**
 * Intercepts methods annotated with #[AvroDeserialize].
 * Decodes the Confluent wire-format binary in the first argument
 * before passing the structured data to the Kafka consumer handler.
 */
#[Aspect]
class AvroDeserializeAspect extends AbstractAspect
{
    public array $annotations = [
        AvroDeserialize::class,
    ];

    public function __construct(
        private KafkaAvroSerializer $serializer,
    ) {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $arguments = $proceedingJoinPoint->getArguments();

        /** @var AvroDeserialize $annotation */
        $annotation = $proceedingJoinPoint->getAnnotationMetadata()->method[AvroDeserialize::class];

        $argIndex = $annotation->argIndex;

        if (isset($arguments[$argIndex]) && is_string($arguments[$argIndex])) {
            $decoded = $this->serializer->decode($arguments[$argIndex]);

            if ($annotation->targetClass && class_exists($annotation->targetClass)) {
                $targetClass = $annotation->targetClass;
                if ($annotation->factoryMethod && method_exists($targetClass, $annotation->factoryMethod)) {
                    $factory = $annotation->factoryMethod;
                    $decoded = $targetClass::$factory($decoded);
                } elseif (method_exists($targetClass, 'fromArray')) {
                    $decoded = $targetClass::fromArray($decoded);
                } else {
                    $decoded = new $targetClass(...$decoded);
                }
            }

            $proceedingJoinPoint->arguments['keys'][$argIndex] = $decoded;
        }

        return $proceedingJoinPoint->process();
    }
}
