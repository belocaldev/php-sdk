<?php

/**
 * Advanced Usage Examples for BeLocal PHP SDK
 * 
 * This file demonstrates advanced usage of TranslateRequest and translateMultiRequest
 * methods with real-world scenarios including entity context and cache usage.
 * 
 * Usage: php advanced_usage.php <api-key>
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
    echo "Usage: php advanced_usage.php <api-key>\n";
    echo "Or set API_KEY environment variable\n";
    exit(1);
}

$engine = BeLocalEngine::withApiKey($apiKey);

// ============================================================================
// Example 1: translateRequest() - Translating news article and comments
// ============================================================================

echo "=== Example 1: Translating News Article and Comments ===\n";
echo "Using translateRequest() to translate a news article and its comments\n\n";

// News article content
$newsArticle = 'Breaking: New technology breakthrough announced today. Scientists have developed a revolutionary method for sustainable energy production.';

// Comments to the article
$comments = [
    'This is amazing news! Can\'t wait to see this in action.',
    'I have some concerns about the environmental impact.',
    'Great progress! When will this be available to the public?',
    'This could change everything. Very exciting!'
];

// Combine article and comments into one request
$allTexts = array_merge([$newsArticle], $comments);

// Create translation request for news article and comments
$request = new TranslateRequest(
    $allTexts,
    'ru', // Target language: Russian
    'en', // Source language: English
    [
        TranslateRequest::CTX_KEY_USER_CONTEXT => 'News article published on technology blog with user comments section displayed below the article'
    ]
);

// Translate the request
$request = $engine->translateRequest($request);

if ($request->isSuccessful()) {
    $result = $request->getResult();
    $translatedTexts = $result->getTexts();
    
    echo "Original article (EN):\n";
    echo "  $newsArticle\n\n";
    
    echo "Translated article (RU):\n";
    echo "  " . ($translatedTexts[0] ?? 'N/A') . "\n\n";
    
    echo "Original comments (EN):\n";
    foreach ($comments as $index => $comment) {
        echo "  Comment " . ($index + 1) . ": $comment\n";
    }
    
    echo "\nTranslated comments (RU):\n";
    foreach ($comments as $index => $comment) {
        $translatedComment = $translatedTexts[$index + 1] ?? $comment;
        echo "  Comment " . ($index + 1) . ": $translatedComment\n";
    }
} else {
    $result = $request->getResult();
    $error = $result->getError();
    echo "Translation failed: " . ($error ? $error->getMessage() : 'Unknown error') . "\n";
}

echo "\n";

// ============================================================================
// Example 2: translateMultiRequest() - Translating product cards
// ============================================================================

echo "=== Example 2: Translating Product Cards ===\n";
echo "Using translateMultiRequest() to translate three product cards with entity context\n\n";

// Product 1: Electronics category
$product1 = [
    'id' => 1001,
    'name' => 'Wireless Bluetooth Headphones',
    'description' => 'Premium noise-cancelling headphones with 30-hour battery life and superior sound quality.',
    'specs' => [
        'Battery Life: 30 hours',
        'Connectivity: Bluetooth 5.0',
        'Weight: 250g',
        'Color: Black'
    ]
];

// Product 2: Clothing category
$product2 = [
    'id' => 2002,
    'name' => 'Organic Cotton T-Shirt',
    'description' => 'Comfortable and sustainable t-shirt made from 100% organic cotton. Perfect for everyday wear.',
    'specs' => [
        'Material: 100% Organic Cotton',
        'Sizes: S, M, L, XL',
        'Care: Machine washable',
        'Color: Navy Blue'
    ]
];

// Product 3: Home & Garden category
$product3 = [
    'id' => 3003,
    'name' => 'Smart LED Light Bulb',
    'description' => 'Energy-efficient smart bulb with color changing capabilities. Compatible with voice assistants.',
    'specs' => [
        'Wattage: 9W',
        'Lumens: 800',
        'Color Temperature: 2700K-6500K',
        'Compatibility: Alexa, Google Home'
    ]
];

// Create translation requests for each product
// Each product includes name, description, and all specifications
$requests = [];

// Product 1: Electronics
$product1Texts = array_merge(
    [$product1['name']],
    [$product1['description']],
    $product1['specs']
);
$requests[] = new TranslateRequest(
    $product1Texts,
    'es', // Spanish
    'en',
    [
        TranslateRequest::CTX_KEY_USER_CONTEXT => 'Product card displayed on e-commerce website product listing page',
        'entity_key' => 'product',
        'entity_id' => (string)$product1['id']
    ]
);

// Product 2: Clothing
$product2Texts = array_merge(
    [$product2['name']],
    [$product2['description']],
    $product2['specs']
);
$requests[] = new TranslateRequest(
    $product2Texts,
    'es', // Spanish
    'en',
    [
        TranslateRequest::CTX_KEY_USER_CONTEXT => 'Product card displayed on e-commerce website product listing page',
        'entity_key' => 'product',
        'entity_id' => (string)$product2['id']
    ]
);

// Product 3: Home & Garden
$product3Texts = array_merge(
    [$product3['name']],
    [$product3['description']],
    $product3['specs']
);
$requests[] = new TranslateRequest(
    $product3Texts,
    'es', // Spanish
    'en',
    [
        TranslateRequest::CTX_KEY_USER_CONTEXT => 'Product card displayed on e-commerce website product listing page',
        'entity_key' => 'product',
        'entity_id' => (string)$product3['id']
    ]
);

// Translate all product cards in a single API call
$requests = $engine->translateMultiRequest($requests);

// Display results
$products = [$product1, $product2, $product3];
foreach ($requests as $index => $request) {
    $productNum = $index + 1;
    $product = $products[$index];
    
    echo "Product $productNum (ID: {$product['id']}):\n";
    
    if ($request->isSuccessful()) {
        $result = $request->getResult();
        $translatedTexts = $result->getTexts();
        
        echo "  Name (EN): {$product['name']}\n";
        echo "  Name (ES): " . ($translatedTexts[0] ?? 'N/A') . "\n";
        
        echo "  Description (EN): {$product['description']}\n";
        echo "  Description (ES): " . ($translatedTexts[1] ?? 'N/A') . "\n";
        
        echo "  Specifications (ES):\n";
        foreach ($product['specs'] as $specIndex => $spec) {
            $translatedSpec = $translatedTexts[$specIndex + 2] ?? $spec;
            echo "    - $translatedSpec\n";
        }
    } else {
        $result = $request->getResult();
        $error = $result->getError();
        echo "  Translation failed: " . ($error ? $error->getMessage() : 'Unknown error') . "\n";
    }
    echo "\n";
}

// ============================================================================
// Example 3: translateMultiRequest() - Cached translation (names only)
// ============================================================================

echo "=== Example 3: Cached Translation (Product Names Only) ===\n";
echo "Requesting only product names with the same context - should return from cache\n\n";

// Create requests for only product names with the same entity context
$nameRequests = [];

// Product 1 name only
$nameRequests[] = new TranslateRequest(
    [$product1['name']],
    'es',
    'en',
    [
        TranslateRequest::CTX_KEY_USER_CONTEXT => 'Product card displayed on e-commerce website product listing page',
        'entity_key' => 'product',
        'entity_id' => (string)$product1['id']
    ]
);

// Product 2 name only
$nameRequests[] = new TranslateRequest(
    [$product2['name']],
    'es',
    'en',
    [
        TranslateRequest::CTX_KEY_USER_CONTEXT => 'Product card displayed on e-commerce website product listing page',
        'entity_key' => 'product',
        'entity_id' => (string)$product2['id']
    ]
);

// Product 3 name only
$nameRequests[] = new TranslateRequest(
    [$product3['name']],
    'es',
    'en',
    [
        TranslateRequest::CTX_KEY_USER_CONTEXT => 'Product card displayed on e-commerce website product listing page',
        'entity_key' => 'product',
        'entity_id' => (string)$product3['id']
    ]
);

// Translate product names (should use cache from Example 2)
$nameRequests = $engine->translateMultiRequest($nameRequests);

echo "Product names retrieved (should match Example 2):\n";
$products = [$product1, $product2, $product3];
foreach ($nameRequests as $index => $request) {
    $productNum = $index + 1;
    $product = $products[$index];
    
    if ($request->isSuccessful()) {
        $result = $request->getResult();
        $translatedTexts = $result->getTexts();
        $translatedName = $translatedTexts[0] ?? $product['name'];
        
        echo "  Product $productNum (ID: {$product['id']}):\n";
        echo "    Original: {$product['name']}\n";
        echo "    Translated: $translatedName\n";
        
        // Verify it matches the previous translation
        $previousRequest = $requests[$index];
        if ($previousRequest->isSuccessful()) {
            $previousResult = $previousRequest->getResult();
            $previousTexts = $previousResult->getTexts();
            $previousName = $previousTexts[0] ?? '';
            
            if ($translatedName === $previousName) {
                echo "    ✓ Cache hit - translation matches previous result\n";
            } else {
                echo "    ⚠ Translation differs from previous result\n";
            }
        }
    } else {
        $result = $request->getResult();
        $error = $result->getError();
        echo "  Product $productNum: Translation failed - " . ($error ? $error->getMessage() : 'Unknown error') . "\n";
    }
    echo "\n";
}

echo "Note: The translations should be identical to Example 2 because the same texts\n";
echo "with the same context (entity_key and entity_id) are being requested again.\n";
echo "This demonstrates how the caching system works.\n";
