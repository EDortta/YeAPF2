<?php declare(strict_types=1);

// require_once("vendor/autoload.php");
require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;
use YeAPF\BaseBulletin;

class YeapfBulletinTest extends TestCase
{
    public function testSetBinaryFile()
    {
        $bulletin = new BaseBulletin();

        $binaryFile = __DIR__ . '/test-files/New_Zealand_-_Rural_landscape_-_9795.jpg';
        $bulletin->setBinaryFile($binaryFile);

        $this->assertEquals($binaryFile, $bulletin->getBinaryFile());

        $this->assertEquals(YeAPF_BULLETIN_OUTPUT_TYPE_BINARYFILE, $bulletin->getDesiredOutputFormat());

        $this->assertEquals('image/jpeg', $bulletin->getContentType());
    }

    public function testSetNonExistantBinaryFile()
    {
        $bulletin = new BaseBulletin();

        $this->expectException(YeAPF\YeAPFException::class);
        $bulletin->setBinaryFile('not-existant-file.test');
    }

    public function testSetNotAFileBinaryFile()
    {
        $bulletin = new BaseBulletin();

        $this->expectException(YeAPF\YeAPFException::class);
        $bulletin->setBinaryFile('test-files');
    }

    public function testSetCharset()
    {
        $bulletin = new BaseBulletin();
        $charset  = 'UTF-8';
        $bulletin->setCharset($charset);
        $this->assertEquals($charset, $bulletin->getCharset());
    }

    public function testSetContentType()
    {
        $bulletin    = new BaseBulletin();
        $contentType = 'application/json';
        $bulletin->setContentType($contentType);
        $this->assertEquals($contentType, $bulletin->getContentType());
    }

    public function testSetFilename()
    {
        $bulletin = new BaseBulletin();
        $filename = 'example.json';
        $bulletin->setFilename($filename);
        $this->assertEquals($filename, $bulletin->getFilename());
        $this->assertNull($bulletin->getDesiredOutputFormat());
    }

    public function testSetJsonFile()
    {
        $bulletin = new BaseBulletin();
        $jsonFile = 'test-files/test.json';
        $bulletin->setJsonFile($jsonFile);

        $this->assertEquals($jsonFile, $bulletin->getJsonFile());

        $this->assertEquals(YeAPF_BULLETIN_OUTPUT_TYPE_JSONFILE, $bulletin->getDesiredOutputFormat());
    }

    public function testSetJsonString()
    {
        $bulletin   = new BaseBulletin();
        $jsonString = '{"key": "value"}';
        $bulletin->setJsonString($jsonString);

        $this->assertEquals($jsonString, $bulletin->getJsonString());

        $this->assertEquals(YeAPF_BULLETIN_OUTPUT_TYPE_JSONSTRING, $bulletin->getDesiredOutputFormat());
    }

    public function testSetReason()
    {
        $bulletin = new BaseBulletin();
        $reason   = 'Test reason';
        $bulletin->setReason($reason);
        $this->assertEquals($reason, $bulletin->getReason());
    }
}
