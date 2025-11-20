<?php

declare(strict_types=1);

namespace BeLocal;

/**
 * BeLocalEngine - A PHP library for text translation via API
 * 
 * This library provides functionality to translate text using a translation API.
 */
class BeLocalEngine
{
    /**
     * @var Transport The transport layer for API communication
     */
    private Transport $transport;

    /**
     * Constructor
     *
     * @param Transport $transport
     */
    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Factory for creating without creating transport
     *
     * @param string $apiKey API key for authentication
     * @param int $timeout Timeout in seconds for API requests
     */
    public static function withApiKey(
        string $apiKey,
        int $timeout = 30
    ): self {
        return new self(new Transport($apiKey, Transport::BASE_URL, $timeout));
    }

    /**
     * @param array<string> $texts Array of strings to translate
     * @param string $lang Target language code
     * @param string $sourceLang Source language code (empty for auto-detection)
     * @param array<string, mixed> $context Context parameters (key-value pairs)
     * @return TranslateManyResult
     * @throws \InvalidArgumentException If texts array contains non-string elements or context has non-string keys
     */
    public function translateMany(array $texts, string $lang, string $sourceLang = '', array $context = []): TranslateManyResult
    {
        $this->validateTextsArray($texts);
        $this->validateContextArray($context);

        if (count($texts) === 0 || $lang === '') {
            return new TranslateManyResult($texts, false);
        }

        $requestIds = [];
        $batchRequestData = [];
        foreach ($texts as $text) {
            $requestId = $this->buildRequestId($text, $lang, $context);
            $requestIds[] = $requestId;

            if ($text === '') {
                continue;
            }

            $payload = [
                'text' => $text,
                'lang' => $lang,
            ];

            if (!empty($sourceLang)) {
                $payload['source_lang'] = $sourceLang;
            }

            if (!empty($context)) {
                $payload['ctx'] = $context;
            }

            $batchRequestData[] = [
                'requestId' => $requestId,
                'payload' => $payload,
            ];
        }

        if (count($batchRequestData) === 0) {
            return new TranslateManyResult($texts, false);
        }

        $response = $this->transport->sendBatch(['batch' => $batchRequestData]);

        return TranslateManyResultFactory::fromResponseAndRequestIds($requestIds, $response);
    }

    /**
     * @param string $text Text to translate
     * @param string $lang Target language code
     * @param string $sourceLang Source language code (empty for auto-detection)
     * @param array<string, mixed> $context Context parameters (key-value pairs)
     * @return TranslateResult
     * @throws \InvalidArgumentException If context has non-string keys
     */
    public function translate(string $text, string $lang, string $sourceLang = '', array $context = []): TranslateResult
    {
        $this->validateContextArray($context);

        if ($text === '' || $lang === '') {
            return new TranslateResult($text, false);
        }

        $data = ['text' => $text, 'lang' => $lang];

        if (!empty($sourceLang)) {
            $data['source_lang'] = $sourceLang;
        }

        if (!empty($context)) {
            $data['ctx'] = $context;
        }

        $response = $this->transport->send($data);

        return TranslateResult::fromResponse($response);
    }

    /**
     * Sugar for translate method
     *
     * @param string $text
     * @param string $lang
     * @param string $sourceLang
     * @param string $context
     * @return string
     */
    public function t(string $text, string $lang, string $sourceLang = '', string $context = ''): string
    {
        $result = $this->translate($text, $lang, $sourceLang, ['user_ctx' => $context]);

        return $this->getSafeTranslatedTextFromResult($text, $result);
    }

    /**
     * Sugar for translate method with cache type = editable
     *
     * @param string $text
     * @param string $lang
     * @param string $sourceLang
     * @param string $context
     * @return string
     */
    public function tEditable(string $text, string $lang, string $sourceLang = '', string $context = ''): string
    {
        $result = $this->translate($text, $lang, $sourceLang, ['user_ctx' => $context, 'cache_type' => 'editable']);

        return $this->getSafeTranslatedTextFromResult($text, $result);
    }

    private function getSafeTranslatedTextFromResult(string $text, TranslateResult $result): string
    {
        if ($result->isOk() && $result->getText()) {
            return $result->getText();
        }

        return $text;
    }

    /**
     * Sugar for translateMany method
     *
     * @param array<string> $texts Array of strings to translate
     * @param string $lang Target language code
     * @param string $sourceLang Source language code (empty for auto-detection)
     * @param string $context Optional user context string
     * @return array<string> Translated texts (originals preserved on error)
     * @throws \InvalidArgumentException If texts array contains non-string elements
     */
    public function tMany(array $texts, string $lang, string $sourceLang = '', string $context = ''): array
    {
        $result = $this->translateMany($texts, $lang, $sourceLang, ['user_ctx' => $context]);

        return $this->getSafeTranslatedTextsFromResult($texts, $result);
    }

    /**
     * Sugar for translateMany method with cache type = editable
     *
     * @param array<string> $texts Array of strings to translate
     * @param string $lang Target language code
     * @param string $sourceLang Source language code (empty for auto-detection)
     * @param string $context Optional user context string
     * @return array<string> Translated texts (originals preserved on error)
     * @throws \InvalidArgumentException If texts array contains non-string elements
     */
    public function tManyEditable(array $texts, string $lang, string $sourceLang = '', string $context = ''): array
    {
        $result = $this->translateMany($texts, $lang, $sourceLang, ['user_ctx' => $context, 'cache_type' => 'editable']);

        return $this->getSafeTranslatedTextsFromResult($texts, $result);
    }

    private function getSafeTranslatedTextsFromResult(array $texts, TranslateManyResult $result): array
    {
        if ($result->isOk() && $result->getTexts()) {
            $translatedTexts = $result->getTexts();

            foreach ($texts as $index => $originalText) {
                if (!array_key_exists($index, $translatedTexts) || $translatedTexts[$index] === null) {
                    $translatedTexts[$index] = $originalText;
                }
            }
            return $translatedTexts;
        }

        return $texts;
    }

    /**
     * Validates that array contains only string elements
     *
     * @param array $texts
     * @throws \InvalidArgumentException
     */
    private function validateTextsArray(array $texts)
    {
        foreach ($texts as $index => $text) {
            if (!is_string($text)) {
                throw new \InvalidArgumentException(
                    sprintf('Expected array<string>, but element at index %d is %s', $index, gettype($text))
                );
            }
        }
    }

    /**
     * Validates that context array has string keys
     *
     * @param array $context
     * @throws \InvalidArgumentException
     */
    private function validateContextArray(array $context)
    {
        foreach ($context as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new \InvalidArgumentException(
                    sprintf('Context keys and values must be strings, but key "%s" is %s and value "%s" is %s', $key, gettype($key), $value, gettype($value))
                );
            }
        }
    }

    private function buildRequestId(string $text, string $lang, array $context): string
    {
        if (!empty($context)) {
            $context = array_merge([], $context); // Create a copy compatible with PHP 7.4
            ksort($context);
        }
        $json = json_encode([$text, $lang, $context], JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            // Fallback to simple concatenation if json_encode fails
            return md5($text . $lang . serialize($context));
        }
        return md5($json);
    }

    /**
     * Translate multiple TranslateRequest objects in a single API call
     *
     * @param array<TranslateRequest> $requests Array of TranslateRequest objects
     * @return array<TranslateRequest> The same array of TranslateRequest objects with filled result property
     * @throws \InvalidArgumentException If requests array is empty or contains non-TranslateRequest elements
     */
    public function translateMultiRequest(array $requests): array
    {
        if (count($requests) === 0) {
            throw new \InvalidArgumentException('Requests array cannot be empty');
        }

        // Validate that all elements are TranslateRequest instances
        foreach ($requests as $index => $request) {
            if (!($request instanceof TranslateRequest)) {
                throw new \InvalidArgumentException(
                    sprintf('Expected array<TranslateRequest>, but element at index %d is %s', $index, gettype($request))
                );
            }
        }

        // Generate requestId for each request and build request body
        $requestBody = ['requests' => []];
        foreach ($requests as $request) {
            $requestBody['requests'][] = $request->toRequestArray();
        }

        // Send request to API
        $response = $this->transport->sendMulti($requestBody);

        // Parse response and map results back to TranslateRequest objects
        $resultMap = TranslateManyResultFactory::fromMultiResponse($response);

        // Set result on each TranslateRequest
        foreach ($requests as $request) {
            $requestId = $request->getRequestId();
            if (isset($resultMap[$requestId])) {
                $request->setResult($resultMap[$requestId]);
            } else {
                // If no result found, create an error result
                $error = $response->getError() ?? new BeLocalError(BeLocalErrorCode::UNCAUGHT, 'No result found for requestId: ' . $requestId);
                $request->setResult(new TranslateManyResult(
                    null,
                    false,
                    $error,
                    $response->getHttpCode(),
                    $response->getCurlErrno(),
                    $response->getRaw()
                ));
            }
        }

        return $requests;
    }
}
