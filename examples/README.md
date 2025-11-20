# BeLocal PHP SDK Examples

This directory contains example code demonstrating how to use the BeLocal PHP SDK.

## Files

- **basic_usage.php** - Basic examples showing how to use all methods of BeLocalEngine
- **advanced_usage.php** - Advanced examples with error handling and best practices

## Quick Start

1. Install dependencies:
```bash
composer install
```

2. Run examples with your API key as a command line argument:
```bash
php examples/basic_usage.php <your-api-key>
php examples/advanced_usage.php <your-api-key>
```

## Available Methods

### Basic Translation Methods

- `translate($text, $lang, $sourceLang = '', $context = [])` - Translate a single text
- `translateMany($texts, $lang, $sourceLang = '', $context = [])` - Translate multiple texts
- `translateMultiRequest($requests)` - Translate multiple TranslateRequest objects in a single API call

### Sugar Methods (convenience shortcuts)

- `t($text, $lang, $sourceLang = '', $context = '')` - Quick translation, returns string directly
- `tEditable($text, $lang, $sourceLang = '', $context = '')` - Translation with editable cache type
- `tMany($texts, $lang, $sourceLang = '', $context = '')` - Quick translation of multiple texts
- `tManyEditable($texts, $lang, $sourceLang = '', $context = '')` - Multiple translations with editable cache type

### Factory Method

- `BeLocalEngine::withApiKey($apiKey, $timeout = 30)` - Create engine instance with API key

## Examples Overview

### Basic Translation
```php
$engine = BeLocalEngine::withApiKey('your-api-key');
$result = $engine->translate('Hello, world!', 'es');
if ($result->isOk()) {
    echo $result->getText();
}
```

### Batch Translation
```php
$texts = ['Hello', 'Goodbye', 'Thank you'];
$result = $engine->translateMany($texts, 'fr');
if ($result->isOk()) {
    $translatedTexts = $result->getTexts();
}
```

### Multi-Request Translation
```php
$requests = [
    new TranslateRequest(['Hello', 'World'], 'es', 'en', ['entity_key' => 'product']),
    new TranslateRequest(['Goodbye'], 'fr', null, []),
];
$requests = $engine->translateMultiRequest($requests);
foreach ($requests as $request) {
    if ($request->isSuccessful()) {
        $translatedTexts = $request->getResult()->getTexts();
    }
}
```

### Quick Translation (Sugar Methods)
```php
// Returns translated text directly
$translated = $engine->t('Hello, world!', 'es');

// Multiple texts
$translatedTexts = $engine->tMany(['Hello', 'Goodbye'], 'fr');
```

## Error Handling

All methods return result objects that you should check:

```php
$result = $engine->translate('Hello', 'es');
if ($result->isOk()) {
    echo $result->getText();
} else {
    $error = $result->getError();
    echo "Error: " . $error->getMessage();
}
```

## Context Usage

Context helps the translation engine provide better translations:

```php
$result = $engine->translate(
    'Product name',
    'es',
    'en',
    ['entity_key' => 'product', 'entity_id' => '123']
);
```

## See Also

- [Main README](../README.md)
- [API Documentation](../README.md)

