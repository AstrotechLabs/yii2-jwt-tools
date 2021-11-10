<?php

declare(strict_types=1);

namespace Dersonsena\JWTTools;

use DateInterval;
use DateTime;
use InvalidArgumentException;

final class JWTPayload
{
    private int $exp;
    private string $iss;
    private string $aud;
    private string|int $sub;
    private int $iat;
    private string $jti;
    private array $extraAttributes = [];

    private function __construct(array $payloadAttrs = [])
    {
        $now = new DateTime();

        $this->iat = $payloadAttrs['iat'] ?? $now->getTimestamp();
        $this->exp = $payloadAttrs['exp'] ?? $now->add(new DateInterval("PT3600S"))->getTimestamp();
        $this->iss = $payloadAttrs['iss'] ?? '';
        $this->aud = $payloadAttrs['aud'] ?? '';
        $this->sub = $payloadAttrs['sub'] ?? $this->generateHash();
        $this->jti = $payloadAttrs['jti'] ?? $this->generateHash();

        if (!isset($payloadAttrs['extraParams'])) {
            return;
        }

        foreach ($payloadAttrs['extraParams'] as $name => $value) {
            $this->addExtraAttribute($name, $value);
        }
    }

    /**
     * @param array $payloadAttrs
     * @return static
     */
    public static function build(array $payloadAttrs = []): self
    {
        return new self($payloadAttrs);
    }

    /**
     * @param string $attribute
     * @throws InvalidArgumentException
     * @return string | int
     */
    public function get(string $attribute)
    {
        if (!property_exists($this, $attribute)) {
            throw new InvalidArgumentException("Payload attribute '{$attribute}' doesn't exists.");
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
     * @param string | int $sub
     * @return JWTPayload
     */
    public function setSub($sub): JWTPayload
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
        $hash = md5(uniqid(rand() . "", true));
        return substr($hash, 0, 15);
    }
}
