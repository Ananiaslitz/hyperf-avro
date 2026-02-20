<?php

declare(strict_types=1);

namespace Ananiaslitz\HyperfAvro;

use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;
use Apache\Avro\Datum\AvroIOBinaryDecoder;
use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\Datum\AvroIODatumReader;
use Apache\Avro\Datum\AvroIODatumWriter;
use Apache\Avro\IO\AvroIOException;
use Apache\Avro\IO\AvroStringIO;
use Apache\Avro\Schema\AvroSchema;

class AvroSerializer
{
    public function __construct(
        protected SchemaManager $schemaManager,
    ) {
    }

    /**
     * Encode $data using a schema resolved by name from the local schema path.
     */
    public function encode(mixed $data, string $schemaName): string
    {
        $schema = $this->schemaManager->getSchema($schemaName);
        return $this->encodeWithSchema($data, $schema);
    }

    /**
     * Decode $binary using a schema resolved by name from the local schema path.
     */
    public function decode(string $binary, string $schemaName): mixed
    {
        $schema = $this->schemaManager->getSchema($schemaName);
        return $this->decodeWithSchema($binary, $schema);
    }

    /**
     * Encode $data using a pre-resolved AvroSchema instance.
     */
    public function encodeWithSchema(mixed $data, AvroSchema $schema): string
    {
        try {
            $io = new AvroStringIO();
            $writer = new AvroIODatumWriter($schema);
            $encoder = new AvroIOBinaryEncoder($io);

            $writer->write($data, $encoder);

            return $io->string();
        } catch (AvroIOException $e) {
            throw new AvroSerializationException('Avro encode failed: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new AvroSerializationException('Avro encode failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Decode $binary using a pre-resolved AvroSchema instance.
     */
    public function decodeWithSchema(string $binary, AvroSchema $schema): mixed
    {
        try {
            $io = new AvroStringIO($binary);
            $reader = new AvroIODatumReader($schema);
            $decoder = new AvroIOBinaryDecoder($io);

            return $reader->read($decoder);
        } catch (AvroIOException $e) {
            throw new AvroSerializationException('Avro decode failed: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new AvroSerializationException('Avro decode failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
