<?php

declare(strict_types=1);

namespace Dersonsena\JWTTools;

use DateInterval;
use DateTime;
use ErrorException;
use Exception;
use Firebase\JWT\JWT;
use stdClass;
use yii\db\ActiveRecord;
use yii\helpers\BaseStringHelper;

final class JWTTools
{
    /**
     * @var ActiveRecord
     */
    private $model;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var array
     */
    private $payload = [];

    /**
     * @var string
     */
    private $algorithm = 'HS256';

    /**
     * @var int
     */
    private $expiration = 3600;

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

    private function __construct(string $secretKey, array $options = [])
    {
        $this->secretKey = $secretKey;

        if (isset($options['algorithm'])) {
            $this->algorithm = $options['algorithm'];
        }

        if (isset($options['expiration'])) {
            $this->expiration = $options['expiration'];
        }

        if (isset($options['iss'])) {
            $this->iss = $options['iss'];
        }

        if (isset($options['aud'])) {
            $this->aud = $options['aud'];
        }

        $now = ($now ?? new DateTime());
        $this->sub = md5(uniqid(rand() . "", true));

        $this->payload = [
            'sub' => $this->sub,
            'iss' => $this->iss,
            'aud' => $this->aud,
            'iat' => $now->getTimestamp(),
            'exp' => $now->add(new DateInterval("PT{$this->expiration}S"))->getTimestamp(),
            'jti' => md5(uniqid(rand() . "", true))
        ];
    }

    /**
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * @return int
     */
    public function getExpiration(): int
    {
        return $this->expiration;
    }

    /**
     * @return string
     */
    public function getIss(): string
    {
        return $this->iss;
    }

    /**
     * @return string
     */
    public function getAud(): string
    {
        return $this->aud;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param  string $secretKey
     * @param  array  $options
     * @return JWTTools
     */
    public static function build(string $secretKey, array $options = []): self
    {
        return new self($secretKey, $options);
    }

    /**
     * @param  ActiveRecord $model
     * @param  array        $attributes
     * @return $this
     * @throws ErrorException
     */
    public function withModel(ActiveRecord $model, array $attributes = []): self
    {
        $this->model = $model;
        $this->sub = $this->model->getPrimaryKey();
        $this->payload['sub'] = $this->sub;

        if (empty($attributes)) {
            return $this;
        }

        foreach ($attributes as $attr) {
            if (!$this->model->hasAttribute($attr)) {
                throw new ErrorException("Attribute '{$attr}' doesn't exists in model class '" . get_class($this->model) . "' .");
            }

            $this->payload[$attr] = $this->model->getAttribute($attr);
        }

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getJWT(): string
    {
        return JWT::encode($this->payload, $this->secretKey, $this->algorithm, $this->sub);
    }

    /**
     * @param  string $token
     * @return stdClass
     */
    public function decodeToken(string $token): stdClass
    {
        return JWT::decode($token, $this->secretKey, [$this->algorithm]);
    }

    /**
     * @param  string $token
     * @return bool
     */
    public function signatureIsValid(string $token): bool
    {
        list($header, $payload, $signatureProvided) = explode(".", $token);

        $signature = hash_hmac('sha256', "{$header}.{$payload}", $this->secretKey, true);
        $signature = str_replace("=", "", BaseStringHelper::base64UrlEncode($signature));

        if ($signatureProvided !== $signature) {
            return false;
        }

        return true;
    }

    /**
     * @param  string $token
     * @return bool
     * @throws Exception
     */
    public function tokenIsExpired(string $token): bool
    {
        $decodedToken = $this->decodeToken($token);
        $now = new DateTime();
        $expiration = new DateTime("@{$decodedToken->exp}");

        if ($now > $expiration) {
            return true;
        }

        return false;
    }
}
