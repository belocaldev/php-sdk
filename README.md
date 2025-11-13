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

// Translate multiple texts at once
[$aTranslated, $bTranslated] = $translator->tMany(['Hello', 'World'], 'fr');
// $aTranslated -> 'Bonjour', $bTranslated -> 'Monde'
```

### Error Handling

Methods `t()`, `tEditable()`, `tMany()`, and `tManyEditable()` return the original text if an error occurs. This ensures your application continues to function even if the translation service is unavailable.

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

##### tEditable(string $text, string $lang, string $sourceLang = '', string $context = '')

Same as `t()`, but forces cache type = `editable` on the server side.

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

##### tManyEditable(array $texts, string $lang, string $sourceLang = '', string $context = '')

Batch variant of `tEditable()`. Returns array of translated strings. Original text is returned for failed items.

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

### TranslateResult Class

Result of a single translation.

**Methods:**
- `getText(): ?string` - Translated text or null on failure
- `isOk(): bool` - Whether translation succeeded
- `getError(): ?BeLocalError` - Error object or null on success

### TranslateManyResult Class

Result of a batch translation.

**Methods:**
- `getTexts(): ?array` - Array of translated texts or null on failure
- `isOk(): bool` - Whether batch translation succeeded
- `getError(): ?BeLocalError` - Error object or null on success

## Features

- PHP 7.0 compatible
- Uses cURL with HTTP/1.1 keep-alive connections for better performance
- Simple API with support for context parameters
- Graceful error handling (returns original text on error)
- PSR-4 autoloading
- Minimal dependencies

## License

MIT


## Pre-push tests

To run unit tests automatically before every `git push` run:

```bash
printf '#!/usr/bin/env bash\n./vendor/bin/phpunit --configuration phpunit.xml\n' > .git/hooks/pre-push && chmod +x .git/hooks/pre-push
```
