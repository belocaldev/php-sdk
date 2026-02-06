# BeLocal Translation SDK for PHP

A PHP library for text translation via API with HTTP/1.1 keep-alive support.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Error Handling](#error-handling)
- [API Documentation](#api-documentation)
- [Features](#features)
- [License](#license)

## Requirements

- PHP 7.0 or higher
- cURL extension
- JSON extension

## Installation

Install via Composer:

```bash
composer require belocal/sdk
```

## Usage

### Basic Usage

```php
<?php
// Include Composer autoloader
require_once 'vendor/autoload.php';

// Import the BeLocalEngine class
use BeLocal\BeLocalEngine;

// Initialize the translation engine
$translator = BeLocalEngine::withApiKey(
    'your-api-key-here', // API key
    30                   // Timeout in seconds (optional)
);

// Translate text
$translatedText = $translator->t('Hello, world!', 'fr');

echo $translatedText; // Output: Bonjour, monde!

// For explicit error handling, use translate() which returns a TranslateResult object
$result = $translator->translate('Hello, world!', 'fr');
if ($result->isOk()) {
    echo $result->getText();
} else {
    echo "Error: " . $result->getError()->getMessage();
}

// Translate with context (array format for advanced usage)
$result = $translator->translate(
    'Product name',
    'es',
    'en',
    ['entity_key' => 'product', 'entity_id' => '123']
);
if ($result->isOk()) {
    echo $result->getText();
}

// Translate multiple texts at once
[$aTranslated, $bTranslated] = $translator->tMany(['Hello', 'World'], 'fr');
// $aTranslated -> 'Bonjour', $bTranslated -> 'Monde'

// Translate multiple TranslateRequest objects in a single API call
use BeLocal\TranslateRequest;

$requests = [
    new TranslateRequest(['Hello world', 'How are you?'], 'es', 'en', ['entity_key' => 'product']),
    new TranslateRequest(['Good morning'], 'fr', null, ['entity_key' => 'product']),
];

$requests = $translator->translateMultiRequest($requests);
foreach ($requests as $request) {
    if ($request->isSuccessful()) {
        $translatedTexts = $request->getResult()->getTexts();
        // Process translated texts...
    }
}
```

### Error Handling

Methods `t()`, `tManaged()`, `tMany()`, and `tManyManaged()` return the original text if an error occurs. This ensures your application continues to function even if the translation service is unavailable.

```php
<?php
// Returns original text on error
$translatedText = $translator->t('Hello, world!', 'fr');

// For explicit error handling, use translate() or translateMany()
$result = $translator->translate('Hello, world!', 'fr');
if ($result->isOk()) {
    echo $result->getText();
} else {
    echo "Error: " . $result->getError()->getMessage();
}

// Batch translation with error handling
$result = $translator->translateMany(['Hello', 'Goodbye'], 'fr');
if ($result->isOk()) {
    $translatedTexts = $result->getTexts();
    foreach ($translatedTexts as $translated) {
        echo $translated . "\n";
    }
} else {
    echo "Error: " . $result->getError()->getMessage();
}
```

## API Documentation

### BeLocalEngine Class

#### Factory Method

```php
public static function withApiKey(
    string $apiKey,
    int $timeout = 30
): BeLocalEngine
```

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $apiKey | string | Your API authentication key | Required |
| $timeout | int | Timeout in seconds for API requests | 30 |

#### Methods

##### t(string $text, string $lang, string $sourceLang = '', string $context = '')

Convenience method that translates text and returns the translated string directly.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $text | string | Text to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $sourceLang | string | Source language code (auto-detected when empty) | '' |
| $context | string | Optional user context string to improve translation accuracy | '' |

**Returns:** string - Translated text (or original text on error)

##### tManaged(string $text, string $lang, string $sourceLang = '', string $context = '')

Same as `t()`, but forces cache type = `managed` (managed translations cache) on the server side.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $text | string | Text to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $sourceLang | string | Source language code (auto-detected when empty) | '' |
| $context | string | Optional user context string to improve translation accuracy | '' |

**Returns:** string - Translated text (or original text on error)

##### tMany(array $texts, string $lang, string $sourceLang = '', string $context = '')

Batch variant of `t()`. Returns array of translated strings. Original text is returned for failed items.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $texts | array | Array of texts to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $sourceLang | string | Source language code (auto-detected when empty) | '' |
| $context | string | Optional user context string to improve translation accuracy | '' |

**Returns:** array<string> - Translated texts (originals preserved on error)

##### tManyManaged(array $texts, string $lang, string $sourceLang = '', string $context = '')

Batch variant of `tManaged()`. Returns array of translated strings. Original text is returned for failed items.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $texts | array | Array of texts to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $sourceLang | string | Source language code (auto-detected when empty) | '' |
| $context | string | Optional user context string to improve translation accuracy | '' |

**Returns:** array<string> - Translated texts (originals preserved on error)

##### translate(string $text, string $lang, string $sourceLang = '', array $context = [])

Translates text and returns a TranslateResult object for explicit error handling.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $text | string | Text to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $sourceLang | string | Source language code (auto-detected when empty) | '' |
| $context | array | Optional context parameters to improve translation accuracy | [] |

**Returns:** TranslateResult - Object containing the translation result and status information

##### translateMany(array $texts, string $lang, string $sourceLang = '', array $context = [])

Translates multiple texts in a single API call. Returns TranslateManyResult for explicit error handling.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $texts | array | Array of texts to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $sourceLang | string | Source language code (auto-detected when empty) | '' |
| $context | array | Optional context parameters to improve translation accuracy | [] |

**Returns:** TranslateManyResult - Object containing the translation results and status information

##### translateMultiRequest(array $requests)

Translates multiple TranslateRequest objects in a single API call. This allows you to translate different sets of texts to different languages in one request.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $requests | array | Array of TranslateRequest objects | Required |

**Returns:** array<TranslateRequest> - The same array of TranslateRequest objects with filled result property

**Example:**
```php
use BeLocal\TranslateRequest;

$requests = [
    new TranslateRequest(['Hello world', 'How are you?'], 'es', 'en', ['entity_key' => 'product']),
    new TranslateRequest(['Good morning', 'Thank you'], 'fr', null, ['entity_key' => 'product']),
];

$requests = $translator->translateMultiRequest($requests);

foreach ($requests as $request) {
    if ($request->isSuccessful()) {
        $translatedTexts = $request->getResult()->getTexts();
        echo "Translated: " . implode(', ', $translatedTexts) . "\n";
    } else {
        echo "Error: " . $request->getResult()->getError()->getMessage() . "\n";
    }
}
```

**Throws:** `\InvalidArgumentException` if requests array is empty or contains non-TranslateRequest elements

### TranslateResult Class

Result of a single translation.

**Methods:**
- `getText(): ?string` - Translated text or null on failure
- `isOk(): bool` - Whether translation succeeded
- `getError(): ?BeLocalError` - Error object or null on success
- `getHttpCode(): ?int` - HTTP response code or null
- `getCurlErrno(): ?int` - cURL error number or null
- `getRaw(): ?string` - Raw response body or null

### TranslateManyResult Class

Result of a batch translation.

**Methods:**
- `getTexts(): ?array` - Array of translated texts or null on failure
- `isOk(): bool` - Whether batch translation succeeded
- `getError(): ?BeLocalError` - Error object or null on success
- `getHttpCode(): ?int` - HTTP response code or null
- `getCurlErrno(): ?int` - cURL error number or null
- `getRaw(): ?string` - Raw response body or null

### TranslateRequest Class

Represents a translation request with multiple texts, target language, optional source language, and context.

**Constructor:**
```php
public function __construct(
    array $texts,
    string $lang,
    ?string $sourceLang,
    array $context
)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $texts | array<string> | Array of texts to translate |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') |
| $sourceLang | string\|null | Source language code (null for auto-detection) |
| $context | array<string, string> | Context parameters (key-value pairs) |

**Methods:**
- `getTexts(): array<string>` - Returns the array of texts to translate
- `getLang(): string` - Returns the target language code
- `getSourceLang(): ?string` - Returns the source language code or null
- `getContext(): array<string, string>` - Returns the context parameters
- `getRequestId(): string` - Returns the unique request ID
- `setRequestId(string $requestId): void` - Sets the request ID
- `isCompleted(): bool` - Returns whether the request has been processed
- `isSuccessful(): bool` - Returns whether the translation was successful
- `getResult(): ?TranslateManyResult` - Returns the translation result or null
- `setResult(TranslateManyResult $result): void` - Sets the translation result
- `toRequestArray(): array` - Converts the request to array format for API calls

**Example:**
```php
use BeLocal\TranslateRequest;

$request = new TranslateRequest(
    ['Hello world', 'How are you?'],
    'es',
    'en',
    ['entity_key' => 'product', 'entity_id' => '123']
);

// Translate the request
$requests = $translator->translateMultiRequest([$request]);
$request = $requests[0];

if ($request->isSuccessful()) {
    $translatedTexts = $request->getResult()->getTexts();
    // Process translated texts...
}
```

## Features

- PHP 7.0 compatible
- Uses cURL with HTTP/1.1 keep-alive connections for better performance
- Simple API with support for context parameters
- Graceful error handling (returns original text on error)
- PSR-4 autoloading
- Minimal dependencies

## License

MIT
