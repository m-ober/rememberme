<?php

use mober\Rememberme\Triplet;
use PHPUnit\Framework\TestCase;

class TripletTest extends TestCase
{
    public const VALID_CREDENTIAL = 'user@example.org';
    public const VALID_TOKEN = '6021839c4c083b02d90c05b992ef6a509145f084';
    public const VALID_PERSISTENT_TOKEN = 'd7ebd0fc1557c6fa14c8c83d8f7764ea91bcb7d0';

    public function testTripletFromStringSimple()
    {
        $parts = [self::VALID_CREDENTIAL, self::VALID_TOKEN, self::VALID_PERSISTENT_TOKEN];
        $triplet = Triplet::fromString(implode(Triplet::SEPARATOR, $parts));

        $this->assertTrue($triplet->isValid(), "Triplet should be valid");
        $this->assertEquals(self::VALID_CREDENTIAL, $triplet->getCredential());
        $this->assertEquals(self::VALID_TOKEN, $triplet->getOneTimeToken());
        $this->assertEquals(self::VALID_PERSISTENT_TOKEN, $triplet->getPersistentToken());
    }

    public function testTripletFromStringComplex()
    {
        $complex_credential = self::VALID_CREDENTIAL . Triplet::SEPARATOR . 'complex';

        $parts = [$complex_credential, self::VALID_TOKEN, self::VALID_PERSISTENT_TOKEN];
        $triplet = Triplet::fromString(implode(Triplet::SEPARATOR, $parts));

        $this->assertTrue($triplet->isValid(), "Triplet should be valid");
        $this->assertEquals($complex_credential, $triplet->getCredential());
        $this->assertEquals(self::VALID_TOKEN, $triplet->getOneTimeToken());
        $this->assertEquals(self::VALID_PERSISTENT_TOKEN, $triplet->getPersistentToken());
    }

    public function testTripletFromStringPartsMissing()
    {
        $parts = [self::VALID_CREDENTIAL, self::VALID_TOKEN];
        $triplet = Triplet::fromString(implode(Triplet::SEPARATOR, $parts));

        $this->assertFalse($triplet->isValid(), "Triplet should be invalid");
        $this->assertEquals('', $triplet->getCredential());
        $this->assertEquals('', $triplet->getOneTimeToken());
        $this->assertEquals('', $triplet->getPersistentToken());
    }

    public function testTripletSerializationAndDeserialization()
    {
        $triplet = new Triplet(self::VALID_CREDENTIAL, self::VALID_TOKEN, self::VALID_PERSISTENT_TOKEN);

        $tripletString = (string) $triplet;
        $tripletDeserialized = Triplet::fromString($tripletString);

        $this->assertTrue($tripletDeserialized->isValid(), "Triplet should be valid");
        $this->assertEquals(self::VALID_CREDENTIAL, $tripletDeserialized->getCredential());
        $this->assertEquals(self::VALID_TOKEN, $tripletDeserialized->getOneTimeToken());
        $this->assertEquals(self::VALID_PERSISTENT_TOKEN, $tripletDeserialized->getPersistentToken());
    }
}
