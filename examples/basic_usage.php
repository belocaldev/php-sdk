<?php

/**
 * Basic Usage Examples for BeLocal PHP SDK
 * 
 * This file demonstrates how to use all methods of BeLocalEngine
 * 
 * Usage: php basic_usage.php <api-key>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BeLocal\BeLocalEngine;
use BeLocal\TranslateRequest;

// Get API key from command line argument or environment variable
$apiKey = null;
if ($argc >= 2) {
    $apiKey = $argv[1];
} elseif (getenv('API_KEY')) {
    $apiKey = getenv('API_KEY');
}

if (empty($apiKey)) {
    echo "Usage: php basic_usage.php <api-key>\n";
    echo "Or set API_KEY environment variable\n";
    exit(1);
}

// ============================================================================
// 1. Creating BeLocalEngine instance
// ============================================================================

// Method 1: Using factory method (recommended)
$engine = BeLocalEngine::withApiKey($apiKey);

// Method 2: Using constructor with custom timeout
$engine = BeLocalEngine::withApiKey($apiKey, 60); // 60 seconds timeout

// ============================================================================
// 2. translate() - Translate a single text
// ============================================================================

echo "=== translate() method ===\n";

// Basic translation
$result = $engine->translate('Hello, world!', 'es');
if ($result->isOk()) {
    echo "Translated: " . $result->getText() . "\n";
} else {
    echo "Error: " . ($result->getError() ? $result->getError()->getMessage() : 'Unknown error') . "\n";
}

// Translation with source language
$result = $engine->translate('Hello, world!', 'es', 'en');
if ($result->isOk()) {
    echo "Translated (en->es): " . $result->getText() . "\n";
}

// Translation with context
$result = $engine->translate(
    'Product name',
    'es',
    'en',
    ['entity_key' => 'product', 'entity_id' => '123']
);
if ($result->isOk()) {
    echo "Translated with context: " . $result->getText() . "\n";
}

echo "\n";

// ============================================================================
// 3. translateMany() - Translate multiple texts
// ============================================================================

echo "=== translateMany() method ===\n";

$texts = ['Hello', 'Goodbye', 'Thank you'];
$result = $engine->translateMany($texts, 'fr');

if ($result->isOk()) {
    $translatedTexts = $result->getTexts();
    foreach ($translatedTexts as $index => $translated) {
        echo "Original: {$texts[$index]} -> Translated: " . ($translated ?? 'N/A') . "\n";
    }
} else {
    echo "Error: " . ($result->getError() ? $result->getError()->getMessage() : 'Unknown error') . "\n";
}

// With source language and context
$result = $engine->translateMany(
    ['Hello', 'Goodbye'],
    'de',
    'en',
    ['entity_key' => 'product', 'entity_id' => '456']
);

if ($result->isOk()) {
    $translatedTexts = $result->getTexts();
    echo "Translated texts with context: " . implode(', ', array_filter($translatedTexts)) . "\n";
}

echo "\n";

// ============================================================================
// 4. translateMultiRequest() - Translate multiple TranslateRequest objects
// ============================================================================

echo "=== translateMultiRequest() method ===\n";

// Create multiple TranslateRequest objects
$requests = [
    new TranslateRequest(
        ['Hello world', 'How are you?'],
        'es',
        'en',
        ['entity_key' => 'product', 'entity_id' => '123']
    ),
    new TranslateRequest(
        ['Good morning', 'Thank you'],
        'fr',
        null, // auto-detect source language
        ['entity_key' => 'product', 'entity_id' => '456']
    ),
    new TranslateRequest(
        ['Welcome'],
        'de',
        'en',
        [] // empty context
    ),
];

// Translate all requests in a single API call
$requests = $engine->translateMultiRequest($requests);

// Check results
foreach ($requests as $index => $request) {
    echo "Request " . ($index + 1) . ":\n";
    echo "  Texts: " . implode(', ', $request->getTexts()) . "\n";
    echo "  Lang: " . $request->getLang() . "\n";
    echo "  RequestId: " . $request->getRequestId() . "\n";
    
    if ($request->isSuccessful()) {
        $result = $request->getResult();
        $translatedTexts = $result->getTexts();
        echo "  Translated: " . implode(', ', array_filter($translatedTexts ?? [])) . "\n";
    } else {
        $result = $request->getResult();
        echo "  Error: " . ($result && $result->getError() ? $result->getError()->getMessage() : 'Unknown error') . "\n";
    }
    echo "\n";
}

// ============================================================================
// 5. t() - Sugar method for quick translation (returns string directly)
// ============================================================================

echo "=== t() method (sugar for translate) ===\n";

// Simple translation - returns translated text or original on error
$translated = $engine->t('Hello, world!', 'es');
echo "Translated: $translated\n";

// With source language
$translated = $engine->t('Hello, world!', 'fr', 'en');
echo "Translated (en->fr): $translated\n";

// With context
$translated = $engine->t('Product name', 'de', 'en', 'product-context');
echo "Translated with context: $translated\n";

echo "\n";

// ============================================================================
// 6. tEditable() - Sugar method with editable cache type
// ============================================================================

echo "=== tEditable() method ===\n";

// Translation with editable cache type
$translated = $engine->tEditable('Hello, world!', 'es');
echo "Translated (editable): $translated\n";

// With context
$translated = $engine->tEditable('Product description', 'fr', 'en', 'product-123');
echo "Translated (editable with context): $translated\n";

echo "\n";

// ============================================================================
// 7. tMany() - Sugar method for translating multiple texts
// ============================================================================

echo "=== tMany() method (sugar for translateMany) ===\n";

$texts = ['Hello', 'Goodbye', 'Thank you'];
$translatedTexts = $engine->tMany($texts, 'fr');

// Returns array of translated texts (originals preserved on error)
foreach ($translatedTexts as $index => $translated) {
    echo "{$texts[$index]} -> $translated\n";
}

// With source language and context
$translatedTexts = $engine->tMany(['Hello', 'Goodbye'], 'de', 'en', 'product-context');
echo "Translated with context: " . implode(', ', $translatedTexts) . "\n";

echo "\n";

// ============================================================================
// 8. tManyEditable() - Sugar method with editable cache type
// ============================================================================

echo "=== tManyEditable() method ===\n";

$texts = ['Hello', 'Goodbye', 'Thank you'];
$translatedTexts = $engine->tManyEditable($texts, 'es');

foreach ($translatedTexts as $index => $translated) {
    echo "{$texts[$index]} -> $translated\n";
}

// With context
$translatedTexts = $engine->tManyEditable(['Welcome', 'Goodbye'], 'fr', 'en', 'product-456');
echo "Translated (editable with context): " . implode(', ', $translatedTexts) . "\n";

echo "\n";

// ============================================================================
// 9. Error Handling Examples
// ============================================================================

echo "=== Error Handling ===\n";

// Empty text
$result = $engine->translate('', 'es');
if (!$result->isOk()) {
    echo "Empty text handled correctly\n";
}

// Empty language
$result = $engine->translate('Hello', '');
if (!$result->isOk()) {
    echo "Empty language handled correctly\n";
}

// Empty texts array
$result = $engine->translateMany([], 'es');
if (!$result->isOk()) {
    echo "Empty texts array handled correctly\n";
}

// Invalid context (non-string keys) - will throw InvalidArgumentException
try {
    $engine->translate('Hello', 'es', '', [123 => 'value']);
    echo "Should not reach here\n";
} catch (\InvalidArgumentException $e) {
    echo "Invalid context caught: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================================
// 10. Working with TranslateRequest
// ============================================================================

echo "=== Working with TranslateRequest ===\n";

$request = new TranslateRequest(
    ['Hello world', 'How are you?'],
    'es',
    'en',
    ['entity_key' => 'product', 'entity_id' => '123']
);

echo "Request details:\n";
echo "  Texts: " . implode(', ', $request->getTexts()) . "\n";
echo "  Lang: " . $request->getLang() . "\n";
echo "  Source Lang: " . ($request->getSourceLang() ?? 'auto') . "\n";
echo "  Context: " . json_encode($request->getContext()) . "\n";
echo "  RequestId: " . $request->getRequestId() . "\n";

// Convert to request array format
$requestArray = $request->toRequestArray();
echo "  Request Array: " . json_encode($requestArray, JSON_PRETTY_PRINT) . "\n";

echo "\n";

