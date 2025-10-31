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
    private $transport;

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
     * @param string $baseUrl Base URL for the translation API
     * @param int $timeout Timeout in seconds for API requests
     */
    public static function withApiKey(
        string $apiKey,
        string $baseUrl = 'https://dynamic.belocal.dev',
        int $timeout = 30
    ): self {
        return new self(new Transport($apiKey, $baseUrl, $timeout));
    }


    /**
     * @param array<string> $texts
     * @param string $lang
     * @param array $context
     * @return TranslateManyResult
     */
    public function translateMany(array $texts, string $lang, string $sourceLang = '', array $context = []): TranslateManyResult
    {
        if (count($texts) === 0 || $lang === '') {
            return new TranslateManyResult($texts, false);
        }

        $requestIds = [];
        $batchRequestData = [];
        foreach ($texts as $text) {
            $requestId = $this->buildRequestId($text, $lang, $context);
            $requestIds []= $requestId;

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

        return TranslateManyResult::fromResponseAndRequestIds($requestIds, $response);
    }

    /**
     * @param string $text
     * @param string $lang
     * @param array  $context
     */
    public function translate(string $text, string $lang, string $sourceLang = '', array $context = []): TranslateResult
    {
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
     * @param string|null $fallback
     * @return string
     */
    public function t(string $text, string $lang, string $sourceLang = '', string $context = '', $fallback = null): string
    {
        $result = $this->translate($text, $lang, $sourceLang, ['user_ctx' => $context]);

        if ($result->isOk() && $result->getText()) {
            return $result->getText();
        }

        return $fallback !== null ? $fallback : $text;
    }

    /**
     * Sugar for translateMany method
     *
     * @param array<string> $texts
     * @param string $lang
     * @param string $sourceLang
     * @param string $context
     * @return array<string>
     */
    public function tMany(array $texts, string $lang, string $sourceLang = '', string $context = ''): array
    {
        $result = $this->translateMany($texts, $lang, $sourceLang, ['user_ctx' => $context]);

        if ($result->isOk() && $result->getTexts()) {
            $translatedTexts = $result->getTexts();

            foreach ($texts as $index => $originalText) {
                if (!isset($translatedTexts[$index]) || $translatedTexts[$index] === null) {
                    $translatedTexts[$index] = $originalText;
                }
            }
            return $translatedTexts;
        }

        return $texts;
    }

    private function buildRequestId(string $text, string $lang, array $context): string
    {
        return md5(json_encode([$text, $lang, $context]));
    }
}
