<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro;

use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Apache\Avro\Schema\AvroSchema;
use Apache\Avro\Schema\AvroSchemaParseException;
use Hyperf\Contract\ConfigInterface;
use Symfony\Component\Finder\Finder;

class SchemaManager
{
    protected array $schemas = [];
    protected string $schemaPath;

    public function __construct(ConfigInterface $config)
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
        $this->schemaPath = $config->get('avro.schema_path', $basePath . '/storage/avro');
    }

    public function getSchema(string $name): AvroSchema
    {
        if (isset($this->schemas[$name])) {
            return $this->schemas[$name];
        }

        $schemaFile = $this->schemaPath . '/' . $name . '.avsc';

        if (!file_exists($schemaFile)) {
            $finder = new Finder();
            $finder->files()->in($this->schemaPath)->name($name . '.avsc');

            if ($finder->hasResults()) {
                foreach ($finder as $file) {
                    $schemaFile = $file->getRealPath();
                    break;
                }
            } else {
                throw new AvroSerializationException("Avro schema file not found: {$name}.avsc in {$this->schemaPath}");
            }
        }

        try {
            $json = file_get_contents($schemaFile);
            $schema = AvroSchema::parse($json);
            $this->schemas[$name] = $schema;
            return $schema;
        } catch (AvroSchemaParseException $e) {
            throw new AvroSerializationException("Failed to parse Avro schema: {$name}. Error: " . $e->getMessage(), 0, $e);
        }
    }

    public function preloadSchemas(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->schemaPath)->name('*.avsc');

        foreach ($finder as $file) {
            $name = $file->getBasename('.avsc');
            try {
                $this->getSchema($name);
            } catch (AvroSerializationException $e) {
            }
        }
    }
}
