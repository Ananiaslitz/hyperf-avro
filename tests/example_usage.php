<?php

require __DIR__ . '/../vendor/autoload.php';

use Ananiaslitz\HyperfAvro\SchemaManager;
use Ananiaslitz\HyperfAvro\AvroSerializer;
use Hyperf\Contract\ConfigInterface;

// Simple inline implementation of ConfigInterface - no Mockery needed
$config = new class implements ConfigInterface {
    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === 'avro.schema_path') {
            return __DIR__;
        }
        return $default;
    }
    public function has(string $key): bool
    {
        return $key === 'avro.schema_path';
    }
    public function set(string $key, mixed $value): void
    {
    }
};

// Instantiate SchemaManager
$schemaManager = new SchemaManager($config);

// Test Schema Loading
echo "Loading schema 'user'...\n";
try {
    $schema = $schemaManager->getSchema('user');
    echo "Schema loaded successfully: " . $schema->fullname() . "\n";
} catch (Exception $e) {
    echo "Failed to load schema: " . $e->getMessage() . "\n";
    exit(1);
}

// Instantiate AvroSerializer
$serializer = new AvroSerializer($schemaManager);

// Test Encoding
$data = [
    'id' => 1,
    'username' => 'ananias',
    'email' => 'ananias@example.com'
];

echo "Encoding data...\n";
try {
    $binary = $serializer->encode($data, 'user');
    echo "Data encoded successfully. Length: " . strlen($binary) . "\n";
} catch (Exception $e) {
    echo "Failed to encode data: " . $e->getMessage() . "\n";
    exit(1);
}

// Test Decoding
echo "Decoding data...\n";
try {
    $decoded = $serializer->decode($binary, 'user');
    print_r($decoded);
} catch (Exception $e) {
    echo "Failed to decode data: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Verification complete.\n";
