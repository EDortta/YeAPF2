<?php declare(strict_types=1);

// require_once("vendor/autoload.php");
require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class DocumentModelTest extends TestCase
{
    public function testRawValue()
    {
        $context = $this->createMock(\YeAPF\Connection\PersistenceContext::class);

        $documentModel = new \YeAPF\ORM\DocumentModel($context, 'translations');

        $documentModel->text = "Don't have an Account";

        $this->assertEquals('Don&#039;t have an Account', $documentModel->__get_raw_value('text'));
    }

    public function testProcessedValue()
    {
        $context = $this->createMock(\YeAPF\Connection\PersistenceContext::class);

        $documentModel = new \YeAPF\ORM\DocumentModel($context, 'translations');

        $documentModel->text = "Don't have an Account";

        $this->assertEquals("Don't have an Account", $documentModel->text);
    }
}
