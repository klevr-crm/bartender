# Bartender

Channel simulator for Klevr CRM messaging pipeline.

## License

MIT

## What it does

Bartender simulates inbound messages from Meta (WhatsApp Cloud, Instagram Direct, Messenger) and Evolution API (WhatsApp via Baileys) to test the CRM end-to-end without live accounts. It drives AI personas via Prism to hold autonomous conversations, captures outbound messages from RabbitMQ, and sends delivery/read receipts back.

## Quick start

```bash
docker compose up -d
```

Open http://localhost:8088 and use HTTP basic auth (set in `.env`).

## Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `BARTENDER_GATEWAY_URL` | `http://omni-gateway:3001` | Gateway webhook base URL |
| `BARTENDER_INBOUND_MODE` | `http` | `http` (POST to Gateway) or `mq` (direct RabbitMQ) |
| `BARTENDER_META_APP_SECRET` | — | Meta app secret for HMAC |
| `BARTENDER_EVOLUTION_API_KEY` | — | Evolution API key |
| `BARTENDER_RABBITMQ_DSN` | — | RabbitMQ connection string |
| `BARTENDER_TARGET_ORG_ID` | — | Target CRM org |
| `BARTENDER_AI_PROVIDER` | `openai` | AI provider (openai/anthropic) |
| `OPENAI_API_KEY` | — | OpenAI API key |
| `ANTHROPIC_API_KEY` | — | Anthropic API key |

## Architecture

```
+----------+     +------------------+     +------------------+
| Persona  | --> | ConversationEngine| --> | InboundDispatcher|
+----------+     +------------------+     +------------------+
                                                | http / mq
+----------+     +------------------+          v
|  RabbitMQ| <-- | OutboundConsumer | <-- +----------+
+----------+     +------------------+     |  Gateway |
                                          +----------+
```

## Channels supported

| Channel | Provider | Inbound | Outbound | ACK |
|---------|----------|---------|----------|-----|
| WhatsApp Cloud | Meta | ✅ | ✅ | ✅ |
| Instagram Direct | Meta | ✅ | ✅ | ✅ |
| Messenger | Meta | ✅ | ✅ | ✅ |
| WhatsApp (Baileys) | Evolution | ✅ | ✅ | ✅ |

## Contributing

1. Fork the repository
2. Create a feature branch
3. Run tests: `vendor/bin/pest`
4. Run static analysis: `vendor/bin/phpstan analyse`
5. Run linter: `vendor/bin/pint`
6. Submit a pull request
