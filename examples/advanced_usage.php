<?php

/**
 * Advanced Usage Examples for BeLocal PHP SDK
 * 
 * This file demonstrates advanced patterns, error handling, and best practices
 * 
 * Usage: php advanced_usage.php <api-key>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BeLocal\BeLocalEngine;
use BeLocal\TranslateRequest;
use BeLocal\TranslateResult;
use BeLocal\TranslateManyResult;

// Get API key from command line argument or environment variable
$apiKey = null;
if ($argc >= 2) {
    $apiKey = $argv[1];
} elseif (getenv('API_KEY')) {
    $apiKey = getenv('API_KEY');
}

if (empty($apiKey)) {
    echo "Usage: php advanced_usage.php <api-key>\n";
    echo "Or set API_KEY environment variable\n";
    exit(1);
}

$engine = BeLocalEngine::withApiKey($apiKey);

// ============================================================================
// 1. Comprehensive Error Handling
// ============================================================================

echo "=== Comprehensive Error Handling ===\n";

function handleTranslationResult(TranslateResult $result, string $originalText): string
{
    if ($result->isOk() && $result->getText()) {
        return $result->getText();
    }
    
    // Log error details
    if ($result->getError()) {
        $error = $result->getError();
        error_log(sprintf(
            'Translation error: %s (Code: %s, HTTP: %s, cURL: %s)',
            $error->getMessage(),
            $error->getCode(),
            $result->getHttpCode() ?? 'N/A',
            $result->getCurlErrno() ?? 'N/A'
        ));
    }
    
    // Return original text as fallback
    return $originalText;
}

function handleManyTranslationResult(TranslateManyResult $result, array $originalTexts): array
{
    if ($result->isOk() && $result->getTexts()) {
        $translatedTexts = $result->getTexts();
        
        // Ensure all texts are translated, fallback to original if null
        foreach ($originalTexts as $index => $original) {
            if (!isset($translatedTexts[$index]) || $translatedTexts[$index] === null) {
                $translatedTexts[$index] = $original;
            }
        }
        
        return $translatedTexts;
    }
    
    // Log error
    if ($result->getError()) {
        $error = $result->getError();
        error_log(sprintf(
            'Batch translation error: %s (Code: %s)',
            $error->getMessage(),
            $error->getCode()
        ));
    }
    
    // Return original texts as fallback
    return $originalTexts;
}

// Example usage
$result = $engine->translate('Hello, world!', 'es');
$translated = handleTranslationResult($result, 'Hello, world!');
echo "Translated with error handling: $translated\n\n";

// ============================================================================
// 2. Batch Translation with Retry Logic
// ============================================================================

echo "=== Batch Translation with Retry Logic ===\n";

function translateWithRetry(
    BeLocalEngine $engine,
    array $texts,
    string $lang,
    string $sourceLang = '',
    array $context = [],
    int $maxRetries = 3
): TranslateManyResult {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            $result = $engine->translateMany($texts, $lang, $sourceLang, $context);
            
            if ($result->isOk()) {
                return $result;
            }
            
            // Check if error is retryable (e.g., network error)
            $error = $result->getError();
            if ($error && $error->getCode() === 'NETWORK') {
                $attempt++;
                if ($attempt < $maxRetries) {
                    sleep(1 * $attempt); // Exponential backoff
                    continue;
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw $e;
            }
            sleep(1 * $attempt);
        }
    }
    
    throw new \RuntimeException('Max retries exceeded');
}

$texts = ['Hello', 'Goodbye', 'Thank you'];
$result = translateWithRetry($engine, $texts, 'fr');
if ($result->isOk()) {
    echo "Translated with retry: " . implode(', ', array_filter($result->getTexts() ?? [])) . "\n";
}
echo "\n";

// ============================================================================
// 3. Processing Multiple Requests Efficiently
// ============================================================================

echo "=== Processing Multiple Requests Efficiently ===\n";

// Group requests by language for better caching
function groupRequestsByLanguage(array $requests): array
{
    $grouped = [];
    
    foreach ($requests as $request) {
        $lang = $request->getLang();
        if (!isset($grouped[$lang])) {
            $grouped[$lang] = [];
        }
        $grouped[$lang][] = $request;
    }
    
    return $grouped;
}

// Create requests for different languages
$requests = [
    new TranslateRequest(['Hello', 'World'], 'es', 'en', ['entity_key' => 'product']),
    new TranslateRequest(['Goodbye'], 'es', 'en', ['entity_key' => 'product']),
    new TranslateRequest(['Hello', 'World'], 'fr', 'en', ['entity_key' => 'product']),
    new TranslateRequest(['Thank you'], 'fr', 'en', ['entity_key' => 'product']),
];

// Group by language
$grouped = groupRequestsByLanguage($requests);

// Process each language group
foreach ($grouped as $lang => $langRequests) {
    echo "Processing $lang requests...\n";
    $langRequests = $engine->translateMultiRequest($langRequests);
    
    foreach ($langRequests as $request) {
        if ($request->isSuccessful()) {
            $translatedTexts = $request->getResult()->getTexts();
            echo "  Translated: " . implode(', ', array_filter($translatedTexts ?? [])) . "\n";
        }
    }
}
echo "\n";

// ============================================================================
// 4. Translation Cache Management
// ============================================================================

echo "=== Translation Cache Management ===\n";

// Use editable cache for content that might be edited later
function translateEditableContent(
    BeLocalEngine $engine,
    string $text,
    string $lang,
    string $sourceLang = ''
): string {
    return $engine->tEditable($text, $lang, $sourceLang, 'editable-content');
}

// Use regular cache for static content
function translateStaticContent(
    BeLocalEngine $engine,
    string $text,
    string $lang,
    string $sourceLang = ''
): string {
    return $engine->t($text, $lang, $sourceLang, 'static-content');
}

$editableText = translateEditableContent($engine, 'Product description', 'es');
echo "Editable content: $editableText\n";

$staticText = translateStaticContent($engine, 'Copyright notice', 'es');
echo "Static content: $staticText\n";
echo "\n";

// ============================================================================
// 5. Context-Aware Translation
// ============================================================================

echo "=== Context-Aware Translation ===\n";

class ProductTranslator
{
    private BeLocalEngine $engine;
    
    public function __construct(BeLocalEngine $engine)
    {
        $this->engine = $engine;
    }
    
    public function translateProduct(array $productData, string $targetLang): array
    {
        $context = [
            'entity_key' => 'product',
            'entity_id' => (string)$productData['id'],
        ];
        
        $translated = [];
        
        // Translate product name
        if (isset($productData['name'])) {
            $result = $this->engine->translate(
                $productData['name'],
                $targetLang,
                $productData['source_lang'] ?? '',
                $context
            );
            $translated['name'] = $result->isOk() ? $result->getText() : $productData['name'];
        }
        
        // Translate product description
        if (isset($productData['description'])) {
            $result = $this->engine->translate(
                $productData['description'],
                $targetLang,
                $productData['source_lang'] ?? '',
                $context
            );
            $translated['description'] = $result->isOk() ? $result->getText() : $productData['description'];
        }
        
        return $translated;
    }
}

$productData = [
    'id' => 123,
    'name' => 'Wireless Mouse',
    'description' => 'Ergonomic wireless mouse with long battery life',
    'source_lang' => 'en',
];

$translator = new ProductTranslator($engine);
$translated = $translator->translateProduct($productData, 'es');

echo "Product translation:\n";
echo "  Name: " . ($translated['name'] ?? 'N/A') . "\n";
echo "  Description: " . ($translated['description'] ?? 'N/A') . "\n";
echo "\n";

// ============================================================================
// 6. Bulk Translation with Progress Tracking
// ============================================================================

echo "=== Bulk Translation with Progress Tracking ===\n";

function translateBulk(
    BeLocalEngine $engine,
    array $items,
    string $targetLang,
    ?callable $onProgress = null
): array {
    $total = count($items);
    $translated = [];
    $batchSize = 10; // Process in batches
    
    for ($i = 0; $i < $total; $i += $batchSize) {
        $batch = array_slice($items, $i, $batchSize);
        $texts = array_column($batch, 'text');
        
        $result = $engine->translateMany($texts, $targetLang);
        
        if ($result->isOk()) {
            $translatedTexts = $result->getTexts();
            foreach ($batch as $index => $item) {
                $item['translated'] = $translatedTexts[$index] ?? $item['text'];
                $translated[] = $item;
            }
        } else {
            // Fallback to original texts
            foreach ($batch as $item) {
                $item['translated'] = $item['text'];
                $translated[] = $item;
            }
        }
        
        // Report progress
        if ($onProgress) {
            $onProgress(count($translated), $total);
        }
    }
    
    return $translated;
}

$items = [
    ['id' => 1, 'text' => 'Hello'],
    ['id' => 2, 'text' => 'Goodbye'],
    ['id' => 3, 'text' => 'Thank you'],
];

$translated = translateBulk(
    $engine,
    $items,
    'fr',
    fn($processed, $total) => print("Progress: $processed/$total\n")
);

echo "Bulk translation completed: " . count($translated) . " items\n";
echo "\n";

// ============================================================================
// 7. Multi-Language Translation Pipeline
// ============================================================================

echo "=== Multi-Language Translation Pipeline ===\n";

function translateToMultipleLanguages(
    BeLocalEngine $engine,
    string $text,
    array $targetLanguages,
    string $sourceLang = ''
): array {
    $results = [];
    
    foreach ($targetLanguages as $lang) {
        $result = $engine->translate($text, $lang, $sourceLang);
        $results[$lang] = $result->isOk() ? $result->getText() : $text;
    }
    
    return $results;
}

$text = 'Welcome to our store';
$languages = ['es', 'fr', 'de', 'it'];
$translations = translateToMultipleLanguages($engine, $text, $languages, 'en');

echo "Multi-language translation:\n";
foreach ($translations as $lang => $translated) {
    echo "  $lang: $translated\n";
}
echo "\n";

// ============================================================================
// 8. Working with TranslateRequest Results
// ============================================================================

echo "=== Working with TranslateRequest Results ===\n";

$request = new TranslateRequest(
    ['Hello world', 'How are you?'],
    'es',
    'en',
    ['entity_key' => 'product', 'entity_id' => '123']
);

// Translate the request
$requests = $engine->translateMultiRequest([$request]);
$request = $requests[0];

// Check result
if ($request->isSuccessful()) {
    $result = $request->getResult();
    $translatedTexts = $result->getTexts();
    
    echo "Successfully translated:\n";
    foreach ($translatedTexts ?? [] as $index => $translated) {
        echo "  " . ($index + 1) . ". $translated\n";
    }
    
    // Access result details
    echo "  HTTP Code: " . ($result->getHttpCode() ?? 'N/A') . "\n";
    echo "  Is OK: " . ($result->isOk() ? 'Yes' : 'No') . "\n";
} else {
    $result = $request->getResult();
    $error = $result->getError();
    echo "Translation failed: " . ($error ? $error->getMessage() : 'Unknown error') . "\n";
}
echo "\n";

