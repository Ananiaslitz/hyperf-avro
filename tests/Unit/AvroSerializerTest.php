<?php

declare(strict_types=1);

namespace HyperfTest\Unit;

use Ananiaslitz\HyperfAvro\AvroSerializer;
use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Ananiaslitz\HyperfAvro\SchemaManager;
use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;

class AvroSerializerTest extends TestCase
{
    private SchemaManager $schemaManager;
    private AvroSerializer $serializer;

    protected function setUp(): void
    {
        $config = new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'avro.schema_path' ? __DIR__ . '/../fixtures' : $default;
            }
            public function has(string $key): bool
            {
                return $key === 'avro.schema_path';
            }
            public function set(string $key, mixed $value): void
            {
            }
        };

        $this->schemaManager = new SchemaManager($config);
        $this->serializer = new AvroSerializer($this->schemaManager);
    }

    public function testEncodeReturnsNonEmptyBinary(): void
    {
        $data = ['id' => 1, 'username' => 'ananias', 'email' => 'ananias@example.com'];
        $binary = $this->serializer->encode($data, 'user');

        $this->assertIsString($binary);
        $this->assertNotEmpty($binary);
    }

    public function testDecodeRoundtrip(): void
    {
        $original = ['id' => 42, 'username' => 'test_user', 'email' => 'test@example.com'];

        $binary = $this->serializer->encode($original, 'user');
        $decoded = $this->serializer->decode($binary, 'user');

        $this->assertSame($original['id'], $decoded['id']);
        $this->assertSame($original['username'], $decoded['username']);
        $this->assertSame($original['email'], $decoded['email']);
    }

    public function testEncodeThrowsOnUnknownSchema(): void
    {
        $this->expectException(AvroSerializationException::class);
        $this->serializer->encode(['foo' => 'bar'], 'nonexistent_schema');
    }
}
