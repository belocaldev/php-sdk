<?php

declare(strict_types=1);

namespace BeLocal;

final class BeLocalError
{
    const INVALID_API_KEY = 'INVALID_API_KEY';
    const PAYMENT_REQUIRED = 'PAYMENT_REQUIRED';
    const NETWORK       = 'NETWORK';
    const HTTP_NON_200  = 'HTTP_NON_200';
    const DECODE        = 'DECODE';
    const API_SCHEMA    = 'API_SCHEMA';
    const JSON_UTF8     = 'INVALID_UTF8';
    const JSON_ENCODE   = 'JSON_ENCODE_FAILED';
    const UNCAUGHT      = 'UNCAUGHT';
    const UNKNOWN       = 'UNKNOWN';

    /** @var string */
    private string $code;

    /** @var string */
    private string $message;

    /**
     * @param string $code
     * @param string $message
     */
    public function __construct($code, $message)
    {
        $this->code = (string)$code;
        $this->message = (string)$message;
    }

    /** @return string */
    public function getCode()
    {
        return $this->code;
    }

    /** @return string */
    public function getMessage()
    {
        return $this->message;
    }
}