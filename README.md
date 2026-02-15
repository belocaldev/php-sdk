# BeLocal Translation SDK for PHP

A PHP library for text translation via API with HTTP/1.1 keep-alive support.

## Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension

## Installation

Install via Composer:

```bash
composer require belocal/sdk
```

## Usage

### Initialization

```php
<?php
require_once 'vendor/autoload.php';

use BeLocal\BeLocalEngine;

$translator = BeLocalEngine::withApiKey('your-api-key-here');

$translated = $translator->t('Hello, world!', 'fr', null, 'website greeting');
// "Bonjour, monde !"
```

### t() — translate single text

```php
$translated = $translator->t('Hello, world!', 'fr', null, 'website greeting');
```

### tMany() — translate multiple texts

```php
$translated = $translator->tMany(['Hello', 'Goodbye'], 'fr', null, 'website UI');
// ['Bonjour', 'Au revoir']
```

### t() with managed cache

```php
$translated = $translator->t('Hello', 'fr', null, 'website greeting', true);
```

### tMany() with managed cache

```php
$translated = $translator->tMany(['Hello', 'Goodbye'], 'fr', null, 'website UI', true);
```

### translateRequest() — single request with full control

```php
use BeLocal\TranslateRequest;

$request = $translator->translateRequest(
    new TranslateRequest(['Hello'], 'fr', null, ['user_ctx' => 'greeting'])
);

if ($request->isSuccessful()) {
    $texts = $request->getResult()->getTexts();
}
```

### translateMultiRequest() — batch requests

```php
$requests = [
    new TranslateRequest(['Hello world', 'How are you?'], 'es', 'en', ['user_ctx' => 'product page']),
    new TranslateRequest(['Good morning'], 'fr', null, ['user_ctx' => 'email subject']),
];

$requests = $translator->translateMultiRequest($requests);

foreach ($requests as $request) {
    if ($request->isSuccessful()) {
        $texts = $request->getResult()->getTexts();
    }
}
```

### Error handling

`t()` and `tMany()` return the original text on error. For explicit error handling, use `translateRequest()`:

```php
$request = $translator->translateRequest(
    new TranslateRequest(['Hello'], 'fr', null, ['user_ctx' => 'greeting'])
);

$result = $request->getResult();
if ($result->isOk()) {
    echo $result->getTexts()[0];
} else {
    echo "Error: " . $result->getError()->getMessage();
}
```

## API Reference

### BeLocalEngine

#### Factory

```php
BeLocalEngine::withApiKey(string $apiKey, int $timeout = 30): BeLocalEngine
```

#### t() -- translate single text

```php
$engine->t(string $text, string $lang, ?string $sourceLang, string $userContext, bool $managed = false): string
```

| Parameter | Type | Description | Default  |
|-----------|------|-------------|----------|
| $text | string | Text to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es') | Required |
| $sourceLang | string\|null | Source language (null = auto-detect) | false    |
| $userContext | string | Context to improve translation accuracy | Required |
| $managed | bool | Use managed translations cache | false    |

Returns translated text, or original text on error.

#### tMany() -- translate multiple texts

```php
$engine->tMany(array $texts, string $lang, ?string $sourceLang, string $userContext, bool $managed = false): array
```

Same parameters as `t()`, but `$texts` is `array<string>`. Returns `array<string>`.

#### translateRequest() -- single request with full result

```php
$engine->translateRequest(TranslateRequest $request): TranslateRequest
```

Returns the same `TranslateRequest` object with its `result` property filled.

#### translateMultiRequest() -- batch requests in one API call

```php
$engine->translateMultiRequest(array $requests): array
```

Takes `array<TranslateRequest>`, returns the same array with results filled. Throws `\InvalidArgumentException` if array is empty.

### TranslateRequest

```php
new TranslateRequest(array $texts, string $lang, ?string $sourceLang, array $context)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $texts | array\<string\> | Texts to translate |
| $lang | string | Target language code |
| $sourceLang | string\|null | Source language (null = auto-detect) |
| $context | array\<string, string\> | Context key-value pairs |

Methods: `getTexts()`, `getLang()`, `getSourceLang()`, `getContext()`, `getRequestId()`, `isCompleted()`, `isSuccessful()`, `getResult()`.

### TranslateManyResult

Result of a translation request.

Methods:
- `getTexts(): ?array` -- translated texts or null on failure
- `isOk(): bool` -- whether translation succeeded
- `getError(): ?BeLocalError` -- error object or null
- `getHttpCode(): ?int` -- HTTP response code
- `getCurlErrno(): ?int` -- cURL error number
- `getRaw(): ?string` -- raw response body

### BeLocalError

Error value object with code constants and message.

Constants: `INVALID_API_KEY`, `PAYMENT_REQUIRED`, `NETWORK`, `HTTP_NON_200`, `DECODE`, `API_SCHEMA`, `JSON_UTF8`, `JSON_ENCODE`, `UNCAUGHT`, `UNKNOWN`.

Methods: `getCode(): string`, `getMessage(): string`.

## Features

- PHP 7.4+ compatible
- HTTP/1.1 keep-alive connections via cURL
- Graceful error handling (original text returned on error)
- Deterministic request IDs for deduplication
- PSR-4 autoloading
- Zero external dependencies

## License

MIT
