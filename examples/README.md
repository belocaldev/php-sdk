# Examples

## Quick Start

1. Install dependencies:
```bash
composer install
```

2. Run examples with your API key:

   **Option A** — copy `.env.example` to `.env` and put your key there:
   ```bash
   cp examples/.env.example examples/.env
   # Edit examples/.env and set API_KEY=your-key
   php examples/basic_usage.php
   ```

   **Option B** — pass key as argument or set env var:
   ```bash
   php examples/basic_usage.php <your-api-key>
   # or
   API_KEY=your-key php examples/basic_usage.php
   ```

## basic_usage.php

Sugar methods `t()` and `tMany()` for quick translations.

| # | Scenario | Method | Managed | Languages |
|---|----------|--------|---------|-----------|
| 1 | Store categories list | `tMany()` | yes | EN → RU |
| 2 | User search query | `t()` | no | EN → ES |
| 3 | Product reviews | `tMany()` | no | EN → FR |
| 4 | Country name | `t()` | yes | EN → DE |

## advanced_usage.php

Advanced methods `translateRequest()` and `translateMultiRequest()` with full control over requests, error handling, and entity context.

| # | Scenario | Method | Features |
|---|----------|--------|----------|
| 1 | News article + comments | `translateRequest()` | Single request with multiple texts, error handling |
| 2 | Product cards (3 products) | `translateMultiRequest()` | Batch request, entity context (`entity_key`, `entity_id`) |
| 3 | Product names (repeat) | `translateMultiRequest()` | Cache verification — same entity context returns cached results |
