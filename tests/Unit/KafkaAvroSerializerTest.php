<?php

declare(strict_types=1);

namespace HyperfTest\Unit;

use Ananiaslitz\HyperfAvro\AvroSerializer;
use Ananiaslitz\HyperfAvro\Contract\SchemaRegistryInterface;
use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;
use Ananiaslitz\HyperfAvro\SchemaManager;
use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;

class KafkaAvroSerializerTest extends TestCase
{
    private KafkaAvroSerializer $kafka;
    private string $schemaJson;

    protected function setUp(): void
    {
        $this->schemaJson = json_encode([
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
            public function has(string $key): bool
            {
                return true;
            }
            public function set(string $key, mixed $value): void
            {
            }
        };

        $schemaManager = new SchemaManager($config);
        $avroSerializer = new AvroSerializer($schemaManager);

        $schemaJson = $this->schemaJson;

        $registry = new class ($schemaJson) implements SchemaRegistryInterface {
            private int $fakeId = 42;
            public function __construct(private string $schemaJson)
            {}

            public function getLatestSchema(string $subject): array
            {
                return ['id' => $this->fakeId, 'schema' => $this->schemaJson];
            }

            public function getSchemaById(int $id): string
            {
                return $this->schemaJson;
            }

            public function registerSchema(string $subject, string $schemaJson): int
            {
                return $this->fakeId;
            }
        };

        $this->kafka = new KafkaAvroSerializer($avroSerializer, $registry);
    }

    public function testEncodeProducesConfluentWireFormat(): void
    {
        $binary = $this->kafka->encode(['id' => 1, 'username' => 'ananias', 'email' => 'a@a.com'], 'user');

        $this->assertSame(0x00, ord($binary[0]));
        $schemaId = unpack('N', substr($binary, 1, 4))[1];
        $this->assertSame(42, $schemaId);
        $this->assertGreaterThan(5, strlen($binary));
    }

    public function testDecodeRoundtrip(): void
    {
        $original = ['id' => 7, 'username' => 'kafka_user', 'email' => 'kafka@example.com'];

        $binary = $this->kafka->encode($original, 'user');
        $decoded = $this->kafka->decode($binary);

        $this->assertSame($original['id'], $decoded['id']);
        $this->assertSame($original['username'], $decoded['username']);
        $this->assertSame($original['email'], $decoded['email']);
    }

    public function testDecodeThrowsOnInvalidMagicByte(): void
    {
        $this->expectException(AvroSerializationException::class);
        $this->expectExceptionMessageMatches('/magic byte/i');

        $invalid = pack('CN', 0x01, 42) . 'garbage';
        $this->kafka->decode($invalid);
    }

    public function testDecodeThrowsOnPayloadTooShort(): void
    {
        $this->expectException(AvroSerializationException::class);
        $this->expectExceptionMessageMatches('/too short/i');

        $this->kafka->decode('abc');
    }

    public function testSchemaIdIsCachedOnDecode(): void
    {
        $callCount = 0;
        $schemaJson = $this->schemaJson;

        $countingRegistry = new class ($schemaJson, $callCount) implements SchemaRegistryInterface {
            public function __construct(
            private string $schemaJson,
            public int &$callCount,
            ) {}

            public function getLatestSchema(string $subject): array
            {
                return ['id' => 1, 'schema' => $this->schemaJson];
            }

            public function getSchemaById(int $id): string
            {
                $this->callCount++;
                return $this->schemaJson;
            }

            public function registerSchema(string $subject, string $schemaJson): int
            {
                return 1;
            }
        };

        $config = new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'avro.schema_path' ? __DIR__ . '/../fixtures' : $default;
            }
            public function has(string $key): bool
            {
                return true;
            }
            public function set(string $key, mixed $value): void
            {
            }
        };

        $kafka = new KafkaAvroSerializer(
            new AvroSerializer(new SchemaManager($config)),
            $countingRegistry,
        );

        $binary = $kafka->encode(['id' => 1, 'username' => 'x', 'email' => 'x@x.com'], 'user');

        $kafka->decode($binary);
        $kafka->decode($binary);

        $this->assertSame(0, $callCount, 'getSchemaById should not be called — schema was seeded by encode');
    }
}
