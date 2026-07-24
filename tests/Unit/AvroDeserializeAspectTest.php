<?php

declare(strict_types=1);

namespace HyperfTest\Unit;

use Ananiaslitz\HyperfAvro\Annotation\AvroDeserialize;
use Ananiaslitz\HyperfAvro\Aspect\AvroDeserializeAspect;
use Ananiaslitz\HyperfAvro\AvroSerializer;
use Ananiaslitz\HyperfAvro\Contract\SchemaRegistryInterface;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Ananiaslitz\HyperfAvro\SchemaManager;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Aop\AnnotationMetadata;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use PHPUnit\Framework\TestCase;

class TestDto
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
    ) {
    }

    public static function createCustom(array $data): static
    {
        return new static((int) $data['id'], strtoupper($data['username']), $data['email']);
    }
}

class AvroDeserializeAspectTest extends TestCase
{
    private KafkaAvroSerializer $kafkaSerializer;

    protected function setUp(): void
    {
        $schemaJson = json_encode([
            'type' => 'record',
            'name' => 'User',
            'namespace' => 'com.example',
            'fields' => [
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'username', 'type' => 'string'],
                ['name' => 'email', 'type' => 'string'],
            ],
        ]);

        $config = new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'avro.schema_path' ? __DIR__ . '/../fixtures' : $default;
            }
            public function has(string $key): bool { return true; }
            public function set(string $key, mixed $value): void {}
        };

        $registry = new class ($schemaJson) implements SchemaRegistryInterface {
            public function __construct(private string $schemaJson) {}
            public function getLatestSchema(string $subject): array { return ['id' => 1, 'schema' => $this->schemaJson]; }
            public function getSchemaById(int $id): string { return $this->schemaJson; }
            public function registerSchema(string $subject, string $schemaJson): int { return 1; }
        };

        $this->kafkaSerializer = new KafkaAvroSerializer(
            new AvroSerializer(new SchemaManager($config)),
            $registry
        );
    }

    public function testProcessWithCustomArgIndexAndFactoryMethod(): void
    {
        $binary = $this->kafkaSerializer->encode(['id' => 10, 'username' => 'john', 'email' => 'john@test.com'], 'user');

        $annotation = new AvroDeserialize(
            schema: 'user',
            targetClass: TestDto::class,
            argIndex: 1,
            factoryMethod: 'createCustom'
        );

        $annotationMetadata = new AnnotationMetadata([], ['Ananiaslitz\HyperfAvro\Annotation\AvroDeserialize' => $annotation]);

        $proceedingJoinPoint = $this->createMock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->method('getArguments')->willReturn(['header_data', $binary]);
        $proceedingJoinPoint->method('getAnnotationMetadata')->willReturn($annotationMetadata);
        $proceedingJoinPoint->method('process')->willReturn('OK');

        $aspect = new AvroDeserializeAspect($this->kafkaSerializer);
        $result = $aspect->process($proceedingJoinPoint);

        $this->assertSame('OK', $result);
        $this->assertInstanceOf(TestDto::class, $proceedingJoinPoint->arguments['keys'][1]);
        $this->assertSame(10, $proceedingJoinPoint->arguments['keys'][1]->id);
        $this->assertSame('JOHN', $proceedingJoinPoint->arguments['keys'][1]->username);
    }
}
