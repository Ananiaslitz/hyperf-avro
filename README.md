# hyperf-avro

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Integração do [Apache Avro](https://avro.apache.org/) com o framework [Hyperf](https://hyperf.io/) usando AOP (Aspect-Oriented Programming).

## Instalação

```bash
composer require ananiaslitz/hyperf-avro
```

Publique o arquivo de configuração:

```bash
php bin/hyperf.php vendor:publish ananiaslitz/hyperf-avro
```

## Configuração

O arquivo publicado em `config/autoload/avro.php`:

```php
return [
    'schema_path' => BASE_PATH . '/storage/avro',
];
```

Coloque seus arquivos `.avsc` no diretório configurado. A organização de versões fica a critério do projeto:

```
storage/
  avro/
    user.avsc
    order.avsc
    v2/
      user.avsc   # se precisar versionar, use subpastas
```

## Uso

### Serializar a resposta de um método

```php
use Ananiaslitz\HyperfAvro\Annotation\AvroSerialize;

class UserController
{
    #[AvroSerialize(schema: 'user')]
    public function show(int $id): array
    {
        return ['id' => $id, 'username' => 'ananias', 'email' => 'ananias@example.com'];
    }
}
```

O aspect intercepta o retorno, serializa para Avro binário e define o header `Content-Type: avro/binary`.

### Deserializar o payload de entrada

```php
use Ananiaslitz\HyperfAvro\Annotation\AvroDeserialize;

class EventConsumer
{
    #[AvroDeserialize(schema: 'user')]
    public function handle(string $payload): void
    {
        // $payload já foi deserializado para array
    }
}
```

### Uso direto (sem AOP)

```php
use Ananiaslitz\HyperfAvro\AvroSerializer;
use Ananiaslitz\HyperfAvro\SchemaManager;

$binary  = $serializer->encode($data, 'user');
$decoded = $serializer->decode($binary, 'user');
```

## Exceções

Todos os erros da lib lançam `Ananiaslitz\HyperfAvro\Exception\AvroSerializationException`, permitindo tratamento específico:

```php
use Ananiaslitz\HyperfAvro\Exception\AvroSerializationException;

try {
    $binary = $serializer->encode($data, 'user');
} catch (AvroSerializationException $e) {
    // schema não encontrado ou inválido
}
```

## Desenvolvimento

```bash
# Rodar os testes via Docker
docker-compose run --rm hyperf-avro-test vendor/bin/phpunit

# Análise estática
docker-compose run --rm hyperf-avro-test vendor/bin/phpstan analyse src --level=5
```

## Licença

MIT
