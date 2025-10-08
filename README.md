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
- [Examples](#examples)
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

// Initialize the translation engine using the factory method
$translator = BeLocalEngine::withApiKey(
    'your-api-key-here',                                   // API key
    'https://dynamic.belocal.dev',                         // API base URL (optional)
    30                                                     // Timeout in seconds (optional)
);

// Translate text
$translatedText = $translator->t('Hello, world!', 'fr');

echo $translatedText; // Output: Bonjour, monde!

// You can also provide a custom fallback value (defaults to original text)
$translatedText = $translator->t('Hello, world!', 'fr', [], 'Translation failed');

// For more control, use the translate method which returns a TranslateResult object
$result = $translator->translate('Hello, world!', 'fr');
if ($result->isOk()) {
    echo $result->getText();
} else {
    echo "Error: " . $result->getError()->getMessage();
}

// Translate multiple texts at once
$texts = ['Hello', 'World'];
$results = $translator->translateMany($texts, 'fr');
if ($results->isOk()) {
    foreach ($results->getTexts() as $text) {
        echo $text . ' ';
    }
}
```

### Error Handling

By default, the library returns the original text if an error occurs during translation. This ensures your application continues to function even if the translation service is unavailable.

```php
<?php
// Default behavior - returns original text on error
$translatedText = $translator->t('Hello, world!', 'fr');

// If you want to handle errors explicitly, you can modify the t() method
// or wrap it in your own try/catch block
try {
    $translatedText = $translator->t('Hello, world!', 'fr');
    echo $translatedText;
} catch (\Exception $e) {
    echo "Translation error: " . $e->getMessage();
}
```

## API Documentation

### BeLocalEngine Class

#### Constructor

```php
public function __construct(Transport $transport)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $transport | Transport | Transport layer for API communication |

#### Factory Method

```php
public static function withApiKey(
    string $apiKey,
    string $baseUrl = 'https://dynamic.belocal.dev',
    int $timeout = 30
): BeLocalEngine
```

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $apiKey | string | Your API authentication key | Required |
| $baseUrl | string | Base URL for the translation API | 'https://dynamic.belocal.dev' |
| $timeout | int | Timeout in seconds for API requests | 30 |

#### Methods

##### t(string $text, string $lang, array $context = [], string $fallback = null)

Translates text to the specified language. This is a convenience method that returns the translated text directly.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $text | string | Text to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $context | array | Optional context parameters to improve translation accuracy | [] |
| $fallback | string | Value to return if translation fails | $text (original text) |

**Returns:** string - Translated text or fallback value on error

##### translate(string $text, string $lang, array $context = [])

Translates text to the specified language and returns a TranslateResult object.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $text | string | Text to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $context | array | Optional context parameters to improve translation accuracy | [] |

**Returns:** TranslateResult - Object containing the translation result and status information

##### translateMany(array $texts, string $lang, array $context = [])

Translates multiple texts to the specified language in a single API call.

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $texts | array | Array of texts to translate | Required |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') | Required |
| $context | array | Optional context parameters to improve translation accuracy | [] |

**Returns:** TranslateManyResult - Object containing the translation results and status information

### TranslateResult Class

Contains the result of a single text translation.

#### Methods

##### getText(): ?string

Returns the translated text or null if translation failed.

##### isOk(): bool

Returns whether the translation was successful.

##### getError(): ?BeLocalError

Returns the error object if translation failed, or null if successful.

### TranslateManyResult Class

Contains the results of a batch translation.

#### Methods

##### getTexts(): ?array

Returns an array of translated texts or null if translation failed.

##### isOk(): bool

Returns whether the batch translation was successful.

##### getError(): ?BeLocalError

Returns the error object if translation failed, or null if successful.

## Features

- PHP 7.0 compatible
- Uses cURL with HTTP/1.1 keep-alive connections for better performance
- Simple API with support for context parameters
- Graceful error handling (returns original text on error)
- PSR-4 autoloading
- Minimal dependencies

## License

MIT
