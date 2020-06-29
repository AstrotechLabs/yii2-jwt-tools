<?php

use Dersonsena\JWTTools\JWTTools;
use Firebase\JWT\ExpiredException;
use PHPUnit\Framework\TestCase;
use yii\db\ActiveRecord;

class JWTToolsTest extends TestCase
{
    /**
     * @var string
     */
    private const SECRET = 'e469d667b15f48808e7595529cf152a0cfb641bd';

    /**
     * @var JWTTools
     */
    private $jwtTools;

    protected function setUp(): void
    {
        $this->jwtTools = JWTTools::build(static::SECRET);
    }

    public function testBuildASingleInstance()
    {
        $this->assertInstanceOf(JWTTools::class, $this->jwtTools);
    }

    public function testConfiguringOptionalOptions()
    {
        $jwtTools = JWTTools::build(static::SECRET, [
            'algorithm' => 'ES256',
            'expiration' => 1589069866,
            'iss' => 'localhost-iss.com.br',
            'aud' => 'localhost-aud.com.br',
        ]);

        $this->assertSame(static::SECRET, $jwtTools->getSecretKey());
        $this->assertSame('ES256', $jwtTools->getAlgorithm());
        $this->assertSame(1589069866, $jwtTools->getExpiration());
        $this->assertSame('localhost-iss.com.br', $jwtTools->getPayload()->get('iss'));
        $this->assertSame('localhost-aud.com.br', $jwtTools->getPayload()->get('aud'));
    }

    public function testGenerationTokenWithoutActiveRecord()
    {
        $token = $this->jwtTools->getJWT();
        $payload = $this->jwtTools->getPayload()->getData();

        $this->assertEquals(6, count($payload));

        $this->assertTrue(array_key_exists('sub', $payload));
        $this->assertTrue(array_key_exists('iss', $payload));
        $this->assertTrue(array_key_exists('aud', $payload));
        $this->assertTrue(array_key_exists('iat', $payload));
        $this->assertTrue(array_key_exists('exp', $payload));
        $this->assertTrue(array_key_exists('jti', $payload));
    }

    public function testGenerationOfTheTokenWithActiveRecord()
    {
        $model = $this->createPersonMock();

        $payload = $this->jwtTools
            ->withModel($model, ['name', 'github'])
            ->getPayload()
            ->getData();

        $this->assertEquals(8, count($payload));

        $this->assertSame('100', $payload['sub']);
        $this->assertSame('Kilderson Sena', $payload['name']);
        $this->assertSame('dersonsena', $payload['github']);
    }

    public function testDecodeJWTTokenWithoutModel()
    {
        $token = $this->jwtTools->getJWT();
        $decodedToken = $this->jwtTools->decodeToken($token);

        $this->assertEquals(stdClass::class, get_class($decodedToken));

        $this->assertTrue(property_exists($decodedToken, 'sub'));
        $this->assertTrue(property_exists($decodedToken, 'iss'));
        $this->assertTrue(property_exists($decodedToken, 'aud'));
        $this->assertTrue(property_exists($decodedToken, 'iat'));
        $this->assertTrue(property_exists($decodedToken, 'exp'));
        $this->assertTrue(property_exists($decodedToken, 'jti'));
    }

    public function testIfSignatureIsValid()
    {
        $token = $this->jwtTools->getJWT();
        $signatureIsValid = $this->jwtTools->signatureIsValid($token);

        $this->assertTrue($signatureIsValid);
    }

    public function testIfDateTimeTokenIsExpired()
    {
        $this->expectException(ExpiredException::class);

        $token = JWTTools::build(static::SECRET, [
            'expiration' => 1, // expires in 0.5 second
        ])->getJWT();

        sleep(1.5);

        $expired = $this->jwtTools->tokenIsExpired($token);

        $this->assertTrue($expired);
    }

    public function testIfDateTimeTokenIsNotExpired()
    {
        $token = JWTTools::build(static::SECRET, [
            'expiration' => 10, // expires in 10 seconds
        ])->getJWT();

        $expired = $this->jwtTools->tokenIsExpired($token);

        $this->assertFalse($expired);
    }

    private function createPersonMock()
    {
        $model = $this->createMock(ActiveRecord::class);

        $model->method('attributes')
            ->willReturn(['name', 'github']);

        $model->method('getPrimaryKey')
            ->willReturn('100');

        $model->expects($this->exactly(2))
            ->method('hasAttribute')
            ->with($this->logicalOr(
                $this->equalTo('name'),
                $this->equalTo('github')
            ))
            ->willReturn(true);

        $model->expects($this->exactly(2))
            ->method('getAttribute')
            ->withConsecutive(
                [$this->equalTo('name')],
                [$this->equalTo('github')]
            )
            ->willReturnOnConsecutiveCalls('Kilderson Sena', 'dersonsena');

        return $model;
    }
}
