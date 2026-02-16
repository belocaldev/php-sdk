<?php

/**
 * Basic Usage Examples for BeLocal PHP SDK
 * 
 * This file demonstrates how to use sugar methods (t, tMany)
 * for quick and easy translations in real-world scenarios.
 * 
 * Usage: php basic_usage.php <api-key>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BeLocal\BeLocalEngine;

// Load API_KEY from .env (so getenv('API_KEY') works)
$envFile = __DIR__ . '/.env';
if (is_readable($envFile) && preg_match('/^API_KEY=(.+)$/m', file_get_contents($envFile), $m)) {
    putenv('API_KEY=' . trim($m[1]));
}

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
// Creating BeLocalEngine instance
// ============================================================================

// Using factory method (recommended)
$engine = BeLocalEngine::withApiKey($apiKey);

// ============================================================================
// Example 1: tManyManaged() - Translation of store categories list
// ============================================================================

echo "=== Example 1: Translating Store Categories ===\n";
echo "Translating store categories from English to Russian\n\n";

// Store categories that might be edited later (e.g., admin can change category names)
$storeCategories = [
    'Electronics',
    'Clothing',
    'Home & Garden',
    'Sports & Outdoors',
    'Books & Media'
];

// Translate categories with managed translations cache (allows future edits)
$translatedCategories = $engine->tMany($storeCategories, 'ru', 'en', '', true);

echo "Original categories (EN):\n";
foreach ($storeCategories as $index => $category) {
    echo "  " . ($index + 1) . ". $category\n";
}

echo "\nTranslated categories (RU):\n";
foreach ($translatedCategories as $index => $translated) {
    echo "  " . ($index + 1) . ". $translated\n";
}

echo "\n";

// ============================================================================
// Example 2: t() - Homonym translation with user_context
// ============================================================================

echo "=== Example 2: Translating Homonym with user_context ===\n";
echo "The word 'crane' has multiple meanings. user_context clarifies which translation to use.\n\n";

// Homonym: crane = bird OR construction equipment
$homonym = 'crane';

// Without context, translation could be ambiguous. user_context specifies the site topic
$translatedAsEquipment = $engine->t(
    $homonym,
    'ru',
    'en',
    'Website for construction services'
);

// Same word, different context: wildlife site
$translatedAsBird = $engine->t(
    $homonym,
    'ru',
    'en',
    'Website about rare and endangered animal species'
);

echo "Original (EN): $homonym\n";
echo "As construction equipment (RU): $translatedAsEquipment\n";
echo "As bird (RU): $translatedAsBird\n";
echo "\nuser_context helps the translator choose the correct meaning for ambiguous words.\n";

echo "\n";

// ============================================================================
// Example 3: tMany() - Translation of product reviews
// ============================================================================

echo "=== Example 3: Translating Product Reviews ===\n";
echo "Translating customer reviews on the website\n\n";

// Customer reviews that need to be displayed in different languages
$reviews = [
    'Great product! Very satisfied with the quality.',
    'Fast shipping and excellent customer service.',
    'The product exceeded my expectations. Highly recommend!',
    'Good value for money. Will buy again.',
    'Not what I expected. Returned the item.'
];

// Translate reviews to French
$translatedReviews = $engine->tMany($reviews, 'fr', 'en');

echo "Original reviews (EN):\n";
foreach ($reviews as $index => $review) {
    echo "  Review " . ($index + 1) . ": $review\n";
}

echo "\nTranslated reviews (FR):\n";
foreach ($translatedReviews as $index => $translated) {
    echo "  Review " . ($index + 1) . ": $translated\n";
}

echo "\n";

// ============================================================================
// Example 4: tManaged() - Translation of country name
// ============================================================================

echo "=== Example 4: Translating Country Name ===\n";
echo "Translating country name (managed translations cache allows corrections)\n\n";

// Country name that might need manual correction later
$countryName = 'United States';

// Translate country name to German with managed translations cache (managed=true)
$translatedCountry = $engine->t($countryName, 'de', 'en', '', true);

echo "Original country name (EN): $countryName\n";
echo "Translated country name (DE): $translatedCountry\n";
echo "\nNote: Using managed translations cache allows you to manually correct the translation if needed\n";

echo "\n";
