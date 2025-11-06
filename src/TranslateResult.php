<?php

declare(strict_types=1);

namespace BeLocal;

final class TranslateResult
{
    /** @var string|null */
    private $text;

    /** @var bool */
    private $ok;

    /** @var BeLocalError|null */
    private $error;

    /** @var int|null */
    private $httpCode;

    /** @var int|null */
    private $curlErrno;

    /** @var string|null */
    private $raw;

    /**
     * @param string|null     $text
     * @param bool            $ok
     * @param BeLocalError|null $error
     * @param int|null        $httpCode
     * @param int|null        $curlErrno
     * @param string|null     $raw
     */
    public function __construct($text, bool $ok, BeLocalError $error = null, $httpCode = null, $curlErrno = null, $raw = null)
    {
        $this->text = $text;
        $this->ok = $ok;
        $this->error = $error;
        $this->httpCode = $httpCode;
        $this->curlErrno = $curlErrno;
        $this->raw = $raw;
    }

    /** @return string|null */
    public function getText()
    {
        return $this->text;
    }

    /** @return bool */
    public function isOk()
    {
        return $this->ok;
    }

    /** @return BeLocalError|null */
    public function getError()
    {
        return $this->error;
    }

    /** @return int|null */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /** @return int|null */
    public function getCurlErrno()
    {
        return $this->curlErrno;
    }

    /** @return string|null */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * Creates a TranslationResult from a TranslationResponse
     *
     * @param TranslateResponse $response
     * @return TranslateResult
     */
    public static function fromResponse(TranslateResponse $response): TranslateResult
    {
        $responseBody = $response->getResponseBody();

        $isOk = $response->isOk();

        $text = (
            is_array($responseBody)
            && isset($responseBody['text'])
            && is_string($responseBody['text'])
            && isset($responseBody['status'])
            && $responseBody['status'] !== 'error'
        )
            ? $responseBody['text'] 
            : null;

        if (isset($responseBody['status']) && $responseBody['status'] === 'error') {
            $isOk = false;
        }

        return new self(
            $text,
            $isOk,
            $response->getError(),
            $response->getHttpCode(),
            $response->getCurlErrno(),
            $response->getRaw()
        );
    }
}
