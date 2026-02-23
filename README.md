# hyperf-avro

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Integração do [Apache Avro](https://avro.apache.org/) com o framework [Hyperf](https://hyperf.io/) focada em **Kafka** e **Confluent Schema Registry**.

## Recursos

- **Confluent Wire Format**: Suporte nativo ao formato `0x00` (magic byte) + 4 bytes (Schema ID).
- **Schema Registry**: Cliente Guzzle para integração com Confluent Schema Registry.
- **Cache de Performance**: Cache em memória para Schema IDs (permanente) e Subjects (com TTL configurável).
- **AOP Support**: Atributos `#[AvroSerialize]` e `#[AvroDeserialize]` para facilitar a integração.
- **Fail-fast**: Erros de validação e comunicação encapsulados em `AvroSerializationException`.

## Instalação

```bash
composer require ananiaslitz/hyperf-avro
```

Publique o arquivo de configuração:

```bash
php bin/hyperf.php vendor:publish ananiaslitz/hyperf-avro
```

## Configuração

O arquivo `config/autoload/avro.php` permite configurar o path local e a conexão com o Registry:

```php
return [
    'schema_path' => BASE_PATH . '/storage/avro',
    'registry' => [
        'base_url' => env('SCHEMA_REGISTRY_URL', 'http://localhost:8081'),
        'auth' => [
            'key'    => env('SCHEMA_REGISTRY_KEY'),
            'secret' => env('SCHEMA_REGISTRY_SECRET'),
            'token'  => env('SCHEMA_REGISTRY_TOKEN'),
        ],
        'subject_cache_ttl' => (int) env('SCHEMA_REGISTRY_SUBJECT_CACHE_TTL', 300),
        'ssl_verify' => (bool) env('SCHEMA_REGISTRY_SSL_VERIFY', true),
    ],
];
```

## Uso com Kafka

### 1. Produzir Mensagem (Producer)

Use o `KafkaAvroSerializer` para converter seus dados no formato compatível com o ecossistema Confluent:

```php
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;

class UserProducer
{
    public function __construct(private KafkaAvroSerializer $avro) {}

    public function send(array $userData)
    {
        // encode() registra/busca o schema, resolve o ID e retorna o binário (wire format)
        $payload = $this->avro->encode($userData, 'user-events-value');
        
        // Agora envie $payload via hyperf/kafka ou similar
    }
}
```

### 2. Consumir Mensagem (Consumer)

O deserializer identifica automaticamente o schema pelo ID embutido nos primeiros bytes da mensagem:

```php
use Ananiaslitz\HyperfAvro\KafkaAvroSerializer;

class UserConsumer
{
    public function __construct(private KafkaAvroSerializer $avro) {}

    public function onMessage(string $value)
    {
        // decode() lê o ID, busca o schema no registry (cached) e deserializa os dados
        $data = $this->avro->decode($value);
        
        // $data['username']...
    }
}
```

### 3. Usando Atributos (AOP)

Para consumidores que recebem a string binária como primeiro argumento:

```php
use Ananiaslitz\HyperfAvro\Annotation\AvroDeserialize;

class EventConsumer
{
    #[AvroDeserialize(schema: 'user-events-value')]
    public function handle(string $payload): void
    {
        // $payload já chega como array associativo
    }
}
```

## Exceções

Trate erros de schema ou registry de forma granular:

```php
use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;

try {
    $payload = $avro->encode($data, 'subject');
} catch (AvroSerializationException $e) {
    // Erro no registry, schema incompatível ou payload inválido
}
```

## Licença

MIT
