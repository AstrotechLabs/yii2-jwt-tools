<?php

declare(strict_types=1);

namespace Dersonsena\JWTTools;

use DateInterval;
use DateTime;
use ErrorException;

final class JWTPayload
{
    /**
     * @var int
     */
    private $exp;

    /**
     * @var string
     */
    private $iss;

    /**
     * @var string
     */
    private $aud;

    /**
     * @var string
     */
    private $sub;

    /**
     * @var int
     */
    private $iat;

    /**
     * @var string
     */
    private $jti;

    /**
     * @var array
     */
    private $extraAttributes = [];

    private function __construct(array $payloadAttrs)
    {
        $now = new DateTime();

        $this->iat = $payloadAttrs['iat'] ?? $now->getTimestamp();
        $this->exp = $payloadAttrs['exp'] ?? $now->add(new DateInterval("PT3600S"))->getTimestamp();
        $this->iss = $payloadAttrs['iss'] ?? '';
        $this->aud = $payloadAttrs['aud'] ?? '';
        $this->sub = $payloadAttrs['sub'] ?? $this->generateHash();
        $this->jti = $payloadAttrs['jti'] ?? $this->generateHash();
    }

    /**
     * @param array $payloadAttrs
     * @return static
     */
    public static function build(array $payloadAttrs): self
    {
        return new self($payloadAttrs);
    }

    /**
     * @param string $attribute
     * @return string
     * @throws ErrorException
     */
    public function get(string $attribute): string
    {
        if (!property_exists($this, $attribute)) {
            throw new ErrorException("Payload attribute '{$attribute}' doesn't exists.");
        }

        return $this->{$attribute};
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function addExtraAttribute(string $name, $value): self
    {
        $this->extraAttributes[$name] = $value;
        return $this;
    }

    /**
     * @param string $sub
     * @return JWTPayload
     */
    public function setSub(string $sub): JWTPayload
    {
        $this->sub = $sub;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return array_merge([
            'sub' => $this->sub,
            'iss' => $this->iss,
            'aud' => $this->aud,
            'iat' => $this->iat,
            'exp' => $this->exp,
            'jti' => $this->jti
        ], $this->extraAttributes);
    }

    /**
     * @return string
     */
    private function generateHash(): string
    {
        return md5(uniqid(rand() . "", true));
    }
}
