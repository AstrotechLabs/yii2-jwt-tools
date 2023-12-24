<?php

use AstrotechLabs\JWTTools\JWTPayload;
use PHPUnit\Framework\TestCase;

class JWTPayloadTest extends TestCase
{
    public function testCreatePayloadWithDefaultAttributes()
    {
        $payload = JWTPayload::build();

        $this->assertEquals(6, count($payload->getData()));

        $this->assertEmpty($payload->get('iss'));
        $this->assertEmpty($payload->get('aud'));
        $this->assertEquals(15, strlen($payload->get('sub')));
        $this->assertEquals(15, strlen($payload->get('jti')));
    }

    public function testCreatePayloadWithCustomAttributes()
    {
        $now = new DateTime();
        $nowTimestamp = $now->getTimestamp();
        $expirationTimestamp = $now->add(new DateInterval("PT3600S"))->getTimestamp();

        $payload = JWTPayload::build([
            'iat' => $nowTimestamp,
            'exp' => $expirationTimestamp,
            'iss' => 'localhost-iss.com',
            'aud' => 'localhost-aud.com',
            'sub' => 20,
            'jti' => 'd4b678686'
        ]);

        $this->assertEquals(6, count($payload->getData()));

        $this->assertSame($nowTimestamp, $payload->get('iat'));
        $this->assertSame($expirationTimestamp, $payload->get('exp'));
        $this->assertSame('localhost-iss.com', $payload->get('iss'));
        $this->assertSame('localhost-aud.com', $payload->get('aud'));
        $this->assertSame(20, $payload->get('sub'));
        $this->assertSame('d4b678686', $payload->get('jti'));
    }

    public function testGetAInvalidAttributeFromPayload()
    {
        $this->expectException(InvalidArgumentException::class);
        JWTPayload::build()->get('xpto');
    }

    public function testChangeSub()
    {
        $payload = JWTPayload::build();
        $payload->setSub(500);

        $this->assertEquals(500, $payload->get('sub'));
    }

    public function testAddExtraAttributesToPayload()
    {
        $payload = JWTPayload::build();
        $payload->addExtraAttribute('name', 'Kilderson Sena');
        $payload->addExtraAttribute('github', 'dersonsena');
        $payload->addExtraAttribute('twitter', 'derson_sena');

        $data = $payload->getData();

        $this->assertSame(9, count($data));

        $this->assertTrue(isset($data['name']));
        $this->assertTrue(isset($data['github']));
        $this->assertTrue(isset($data['twitter']));

        $this->assertSame('Kilderson Sena', $data['name']);
        $this->assertSame('dersonsena', $data['github']);
        $this->assertSame('derson_sena', $data['twitter']);
    }

    public function testPayloadDataWithoutExtraAttributes()
    {
        $payload = JWTPayload::build();
        $data = $payload->getData();

        $this->assertSame(6, count($data));

        $this->assertTrue(isset($data['sub']));
        $this->assertTrue(isset($data['iss']));
        $this->assertTrue(isset($data['aud']));
        $this->assertTrue(isset($data['iat']));
        $this->assertTrue(isset($data['exp']));
        $this->assertTrue(isset($data['jti']));
    }

    public function testPayloadDataWithExtraAttributes()
    {
        $payload = JWTPayload::build();
        $payload->addExtraAttribute('name', 'Kilderson Sena');
        $payload->addExtraAttribute('github', 'dersonsena');
        $payload->addExtraAttribute('twitter', 'derson_sena');

        $data = $payload->getData();

        $this->assertSame(9, count($data));

        $this->assertTrue(isset($data['sub']));
        $this->assertTrue(isset($data['iss']));
        $this->assertTrue(isset($data['aud']));
        $this->assertTrue(isset($data['iat']));
        $this->assertTrue(isset($data['exp']));
        $this->assertTrue(isset($data['jti']));
        $this->assertTrue(isset($data['name']));
        $this->assertTrue(isset($data['github']));
        $this->assertTrue(isset($data['twitter']));
    }

    public function testIfCreatesPayloadWithExtraParamsInConstructor()
    {
        $payload = JWTPayload::build(['extraParams' => [
            'any_key_1' => 'any_value1',
            'any_key_2' => 'any_value2',
        ]])->getData();

        $this->assertSame(8, count($payload));
        $this->assertTrue(isset($payload['any_key_1']));
        $this->assertTrue(isset($payload['any_key_2']));
        $this->assertSame('any_value1', $payload['any_key_1']);
        $this->assertSame('any_value2', $payload['any_key_2']);
    }
}
