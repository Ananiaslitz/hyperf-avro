<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
class AvroDeserialize extends AbstractAnnotation
{
    public function __construct(
        public string $schema,
        public ?string $targetClass = null
    ) {
    }
}
