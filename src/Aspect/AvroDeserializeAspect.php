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

        if (isset($arguments[0]) && is_string($arguments[0])) {
            $decoded = $this->serializer->decode($arguments[0]);

            if ($annotation->targetClass && class_exists($annotation->targetClass)) {
                $decoded = new $annotation->targetClass(...$decoded);
            }

            $proceedingJoinPoint->arguments['keys'][0] = $decoded;
        }

        return $proceedingJoinPoint->process();
    }
}
