<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro\Aspect;

use Ananiaslitz\HyperfAvro\Annotation\AvroSerialize;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

/**
 * Intercepts methods annotated with #[AvroSerialize] and serializes
 * the return value to Confluent wire-format Avro bytes for Kafka publishing.
 */
#[Aspect]
class AvroSerializeAspect extends AbstractAspect
{
    public array $annotations = [
        AvroSerialize::class,
    ];

    public function __construct(
        private KafkaAvroSerializer $serializer,
    ) {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $result = $proceedingJoinPoint->process();

        /** @var AvroSerialize $annotation */
        $annotation = $proceedingJoinPoint->getAnnotationMetadata()->method[AvroSerialize::class];

        return $this->serializer->encode((array) $result, $annotation->schema);
    }
}
