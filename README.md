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

// Initialize the translation engine
$translator = new BeLocalEngine(
    'your-api-key-here',                                   // API key
    'https://dynamic.belocal.dev/v1/translate',            // API base URL (optional)
    30                                                     // Timeout in seconds (optional)
);

// Translate text
$translatedText = $translator->t('Hello, world!', 'fr');

echo $translatedText; // Output: Bonjour, monde!
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
public function __construct(
    string $apiKey,
    string $baseUrl = 'https://dynamic.belocal.dev/v1/translate',
    int $timeout = 30
)
```

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| $apiKey | string | Your API authentication key | Required |
| $baseUrl | string | Base URL for the translation API | 'https://dynamic.belocal.dev/v1/translate' |
| $timeout | int | Timeout in seconds for API requests | 30 |

#### Methods

##### t(string $text, string $lang, array $context = [])

Translates text to the specified language.

| Parameter | Type | Description |
|-----------|------|-------------|
| $text | string | Text to translate |
| $lang | string | Target language code (e.g., 'fr', 'es', 'de') |
| $context | array | Optional context parameters to improve translation accuracy |

**Returns:** string - Translated text or original text on error

## Features

- PHP 7.0 compatible
- Uses cURL with HTTP/1.1 keep-alive connections for better performance
- Simple API with support for context parameters
- Graceful error handling (returns original text on error)
- PSR-4 autoloading
- Minimal dependencies

## License

MIT
