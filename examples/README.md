# BeLocal PHP SDK Examples

This directory contains example code demonstrating how to use the BeLocal PHP SDK.

## Files

- **basic_usage.php** - Basic examples using `t()` and `tMany()` sugar methods
- **advanced_usage.php** - Advanced examples using `translateRequest()` and `translateMultiRequest()`

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

### Sugar Methods (convenience shortcuts)

- `t($text, $lang, $sourceLang, $userContext, $managed = false)` - Translate single text, returns string
- `tMany($texts, $lang, $sourceLang, $userContext, $managed = false)` - Translate multiple texts, returns array

### Advanced Methods

- `translateRequest($request)` - Translate a single TranslateRequest object
- `translateMultiRequest($requests)` - Translate multiple TranslateRequest objects in a single API call

### Factory

- `BeLocalEngine::withApiKey($apiKey, $timeout = 30)` - Create engine instance with API key

## Examples

### Quick Translation
```php
$engine = BeLocalEngine::withApiKey('your-api-key');

// Single text
$translated = $engine->t('Hello, world!', 'es', null, 'website greeting');

// Multiple texts
$translated = $engine->tMany(['Hello', 'Goodbye'], 'fr', null, 'website UI');

// With managed cache (editable translations)
$translated = $engine->t('Hello', 'es', null, 'greeting', true);
```

### Multi-Request Translation
```php
$requests = [
    new TranslateRequest(['Hello', 'World'], 'es', 'en', ['user_ctx' => 'product']),
    new TranslateRequest(['Goodbye'], 'fr', null, ['user_ctx' => 'email']),
];
$requests = $engine->translateMultiRequest($requests);
foreach ($requests as $request) {
    if ($request->isSuccessful()) {
        $texts = $request->getResult()->getTexts();
    }
}
```

## See Also

- [Main README](../README.md)
