<?php

/**
 * Basic Usage Examples for BeLocal PHP SDK
 * 
 * This file demonstrates how to use sugar methods (t, tMany, tEditable, tManyEditable)
 * for quick and easy translations in real-world scenarios.
 * 
 * Usage: php basic_usage.php <api-key>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BeLocal\BeLocalEngine;

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
// Example 1: tManyEditable() - Translation of store categories list
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

// Translate categories with editable cache (allows future edits)
// User context explains that these are e-commerce store navigation categories
$translatedCategories = $engine->tManyEditable($storeCategories, 'ru', 'en', 'E-commerce store navigation menu categories displayed in the header');

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
// Example 2: t() - Translation of search query for product search
// ============================================================================

echo "=== Example 2: Translating Search Query ===\n";
echo "Translating user search query for product search on another language\n\n";

// User enters search query in their language
$userSearchQuery = 'wireless headphones';

// Translate search query to Spanish for searching products in Spanish catalog
// User context explains that this is a user-entered search query for product search
$translatedQuery = $engine->t($userSearchQuery, 'es', 'en', 'User search query entered in product search box on e-commerce website');

echo "Original search query (EN): $userSearchQuery\n";
echo "Translated query (ES): $translatedQuery\n";
echo "\nNow you can use '$translatedQuery' to search products in Spanish catalog\n";

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
// User context explains that these are customer product reviews displayed on product pages
$translatedReviews = $engine->tMany($reviews, 'fr', 'en', 'Customer product reviews displayed on product detail page');

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
// Example 4: tEditable() - Translation of country name
// ============================================================================

echo "=== Example 4: Translating Country Name ===\n";
echo "Translating country name (editable cache allows corrections)\n\n";

// Country name that might need manual correction later
$countryName = 'United States';

// Translate country name to German with editable cache
// User context explains that this is a country name displayed in shipping address form
$translatedCountry = $engine->tEditable($countryName, 'de', 'en', 'Country name displayed in shipping address form during checkout');

echo "Original country name (EN): $countryName\n";
echo "Translated country name (DE): $translatedCountry\n";
echo "\nNote: Using editable cache allows you to manually correct the translation if needed\n";

echo "\n";
