# Sandbox — hyperf-avro integration test

## What this sandbox does

1. **Redpanda** starts (Kafka + Schema Registry in one container)
2. **redpanda-init** creates the `user-events` topic
3. **producer** registers the Avro schema, encodes a message to the Confluent wire format, and sends it
4. **consumer** polls the topic in a loop, decodes each message using the schema ID embedded in the wire format, and prints the result

## Usage

```bash
# Start everything (producer + consumer in parallel)
docker-compose up --build

# Or run them separately:
docker-compose up redpanda redpanda-init
docker-compose run --rm producer    # send one message
docker-compose run --rm consumer    # listen (Ctrl+C to stop)
```

## Confirming the schema in the registry (from host)

```bash
# List subjects
curl http://localhost:18081/subjects

# Get latest schema for user-events-value
curl http://localhost:18081/subjects/user-events-value/versions/latest | jq .
```

## What to look for in the producer output

```
✔ Schema registered — ID: 1
✔ Payload encoded (24 bytes)
  Magic byte : 0x00        ← Confluent magic byte
  Schema ID  : 1           ← 4-byte big-endian ID
  Avro binary: 020e616e61... ← raw Avro bytes
✔ Message sent to topic 'user-events'
  Data: {"id":1,"username":"ananias","email":"ananias@example.com"}
```

## What to look for in the consumer output

```
⏳ Waiting for messages on 'user-events'... (Ctrl+C to stop)
✔ Message received:
  id       : 1
  username : ananias
  email    : ananias@example.com
```
