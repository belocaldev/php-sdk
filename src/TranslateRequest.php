<?php

declare(strict_types=1);

namespace BeLocal;

final class TranslateRequest
{
    // Context keys
    public const CTX_KEY_USER_TYPE = 'user_type';
    public const CTX_KEY_USER_CONTEXT = 'user_ctx';
    public const CTX_KEY_CACHE_TYPE = 'cache_type';
    public const CTX_KEY_ENTITY_KEY = 'entity_key';
    public const CTX_KEY_ENTITY_ID = 'entity_id';

    /** @var array<string> All allowed context keys */
    private const ALLOWED_CTX_KEYS = [
        self::CTX_KEY_USER_TYPE,
        self::CTX_KEY_USER_CONTEXT,
        self::CTX_KEY_CACHE_TYPE,
        self::CTX_KEY_ENTITY_KEY,
        self::CTX_KEY_ENTITY_ID,
    ];

    // Values for user_type
    public const USER_TYPE_PRODUCT = 'product';
    public const USER_TYPE_CHAT = 'chat';

    // Values for cache_type
    public const CACHE_TYPE_MANAGED = 'managed';

    /** @var array<string> */
    private array $texts;

    /** @var string */
    private string $lang;

    /** @var string|null */
    private ?string $sourceLang;

    /** @var array<string, string> */
    private array $context;

    /** @var string */
    private string $requestId;

    private ?TranslateManyResult $result;

    public function __construct(array $texts, string $lang, ?string $sourceLang, array $context)
    {
        $this->validateTextsArray($texts);
        $this->validateContextArray($context);

        $this->texts = $texts;
        $this->lang = $lang;
        $this->sourceLang = $sourceLang;
        $this->context = $context;
        $this->result = null;

        $this->requestId = $this->buildRequestId();
    }

    public function isCompleted(): bool
    {
        return $this->result !== null;
    }

    public function isSuccessful(): bool
    {
        return $this->result !== null && $this->result->isOk();
    }

    public function getResult(): ?TranslateManyResult
    {
        return $this->result;
    }

    public function setResult(TranslateManyResult $result): void
    {
        $this->result = $result;
    }

    /** @return array<string> */
    public function getTexts(): array
    {
        return $this->texts;
    }

    /** @return string */
    public function getLang(): string
    {
        return $this->lang;
    }

    /** @return string|null */
    public function getSourceLang()
    {
        return $this->sourceLang;
    }

    /** @return array<string, string> */
    public function getContext(): array
    {
        return $this->context;
    }

    /** @return string */
    public function getRequestId(): string
    {
        return $this->requestId;
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
     * Validates that context array has string keys and values, and only allowed keys
     *
     * Allowed keys: user_type, user_ctx, cache_type, entity_key, entity_id
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
            if (!in_array($key, self::ALLOWED_CTX_KEYS, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Unknown context key "%s". Allowed keys: %s', $key, implode(', ', self::ALLOWED_CTX_KEYS))
                );
            }
        }
    }

    /**
     * Builds a request ID from texts array, lang, sourceLang, and context
     * Texts are sorted alphabetically before hashing
     * Includes sourceLang to properly group requests with different source languages
     *
     * @return string
     */
    public function buildRequestId(): string
    {
        $sortedTexts = array_merge([], $this->texts); // Create a copy compatible with PHP 7.4
        sort($sortedTexts);

        $context = $this->context;
        if (!empty($context)) {
            $context = array_merge([], $context); // Create a copy compatible with PHP 7.4
            ksort($context);
        }

        $json = json_encode([$sortedTexts, $this->lang, $this->sourceLang, $context], JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            // Fallback to simple concatenation if json_encode fails
            return md5(implode('', $sortedTexts) . $this->lang . ($this->sourceLang ?? '') . serialize($context));
        }
        return md5($json);
    }

    /**
     * Converts TranslateRequest to array format for API request
     *
     * @return array
     */
    public function toRequestArray(): array
    {
        $requestData = [
            'request_id' => $this->requestId,
            'texts' => $this->texts,
            'lang' => $this->lang,
        ];

        if ($this->sourceLang !== null && $this->sourceLang !== '') {
            $requestData['source_lang'] = $this->sourceLang;
        }

        if (!empty($this->context)) {
            $requestData['ctx'] = $this->context;
        }

        return $requestData;
    }
}
