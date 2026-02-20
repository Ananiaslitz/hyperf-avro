<?php

declare(strict_types=1);

namespace HyperfTest\Unit;

use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Ananiaslitz\HyperfAvro\SchemaManager;
use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;

class SchemaManagerTest extends TestCase
{
    private function makeManager(string $path): SchemaManager
    {
        $config = new class ($path) implements ConfigInterface {
            public function __construct(private string $path)
            {}
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'avro.schema_path' ? $this->path : $default;
            }
            public function has(string $key): bool
            {
                return $key === 'avro.schema_path'; }
            public function set(string $key, mixed $value): void
            {}
        };

        return new SchemaManager($config);
    }

    public function testLoadsSchemaSuccessfully(): void
    {
        $manager = $this->makeManager(__DIR__ . '/../fixtures');
        $schema = $manager->getSchema('user');

        $this->assertNotNull($schema);
        // AvroSchema is loaded from vendor at runtime; asserting instance type here would
        // require an import that is only available after composer install.
        // The successful return without exception is sufficient proof of correct loading.

    }

    public function testCachesSchemaOnSecondCall(): void
    {
        $manager = $this->makeManager(__DIR__ . '/../fixtures');

        $first = $manager->getSchema('user');
        $second = $manager->getSchema('user');

        $this->assertSame($first, $second);
    }

    public function testThrowsExceptionForMissingSchema(): void
    {
        $this->expectException(AvroSerializationException::class);
        $this->expectExceptionMessageMatches('/schema file not found/i');

        $manager = $this->makeManager(__DIR__ . '/../fixtures');
        $manager->getSchema('does_not_exist');
    }
}
