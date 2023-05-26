<?php
declare(strict_types=1);
require_once("vendor/autoload.php");
use PHPUnit\Framework\TestCase;

class DocumentModelTest extends TestCase
{
    public function testRawValue()
    {
        // Create a mock of the PersistenceContext
        $context = $this->createMock(\YeAPF\Connection\PersistenceContext::class);

        // Create a new DocumentModel instance
        $documentModel = new \YeAPF\ORM\DocumentModel($context, "translations");

        // Set the value of the text property
        $documentModel->text = "Don't have an Account";

        // Assert that the raw value is "Don&#039;t have an Account"
        $this->assertEquals("Don&#039;t have an Account", $documentModel->__get_raw_value('text'));
    }

    public function testProcessedValue()
    {
        // Create a mock of the PersistenceContext
        $context = $this->createMock(\YeAPF\Connection\PersistenceContext::class);

        // Create a new DocumentModel instance
        $documentModel = new \YeAPF\ORM\DocumentModel($context, "translations");

        // Set the value of the text property
        $documentModel->text = "Don't have an Account";

        // Assert that the processed value is "Don't have an Account"
        $this->assertEquals("Don't have an Account", $documentModel->text);
    }
}
