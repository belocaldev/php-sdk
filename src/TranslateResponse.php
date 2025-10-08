<?php

declare(strict_types=1);

namespace BeLocal;

final class TranslateResponse
{
    /** @var array|null */
    private $responseBody;

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
     * @param array|null      $responseBody
     * @param bool            $ok
     * @param BeLocalError|null $error
     * @param int|null        $httpCode
     * @param int|null        $curlErrno
     * @param string|null     $raw
     */
    public function __construct($responseBody, bool $ok, BeLocalError $error = null, $httpCode = null, $curlErrno = null, $raw = null)
    {
        $this->responseBody = $responseBody;
        $this->ok = $ok;
        $this->error = $error;
        $this->httpCode = $httpCode;
        $this->curlErrno = $curlErrno;
        $this->raw = $raw;
    }

    /** @return array|null */
    public function getResponseBody()
    {
        return $this->responseBody;
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
}
