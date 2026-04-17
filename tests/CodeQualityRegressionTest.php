<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CodeQualityRegressionTest extends TestCase
{
    private function readSource(string $relativePath): string
    {
        $path = __DIR__ . '/../' . ltrim($relativePath, '/');
        $this->assertFileExists($path, "Expected source file not found: $relativePath");
        $content = file_get_contents($path);
        $this->assertNotFalse($content, "Could not read source file: $relativePath");
        return (string) $content;
    }

    public function testJwtFileHasNoSensitiveTraceLeaks(): void
    {
        $content = $this->readSource('src/security/yeapf-jwt.php');

        $this->assertStringNotContainsString('SECRET KEY:', $content);
        $this->assertStringNotContainsString('Token payload:', $content);
        $this->assertStringNotContainsString('TOKEN: ', $content);
        $this->assertStringNotContainsString('_trace(', $content);
    }

    public function testSanitizedKeyDataDoesNotForceDebugTrueOnHotPaths(): void
    {
        $content = $this->readSource('src/classes/class.key-data.php');

        $this->assertDoesNotMatchRegularExpression(
            '/public function checkConstraint\\s*\\(.*?\\)\\s*\\{.*?\\$debug\\s*=\\s*true\\s*;/s',
            $content
        );

        $this->assertDoesNotMatchRegularExpression(
            '/public function __set\\s*\\(string\\s+\\$name\\s*,\\s*mixed\\s+\\$value\\s*\\)\\s*\\{.*?\\$debug\\s*=\\s*true\\s*;/s',
            $content
        );
    }

    public function testCollectionsFileHasNoTracePrintOrDebugMarkers(): void
    {
        $content = $this->readSource('src/database/yeapf-collections.php');

        $this->assertDoesNotMatchRegularExpression('/\\b_trace\\s*\\(/', $content);
        $this->assertDoesNotMatchRegularExpression('/\\bprint_r\\s*\\(/', $content);
        $this->assertDoesNotMatchRegularExpression('/\\bvar_dump\\s*\\(/', $content);
        $this->assertDoesNotMatchRegularExpression('/\\$debug\\b/', $content);
    }

    public function testI18nTranslateStopGapDebugFlagIsDisabled(): void
    {
        $content = $this->readSource('src/plugins/i18n.php');

        $this->assertDoesNotMatchRegularExpression(
            '/public function translate\\s*\\(.*?\\)\\s*\\{.*?\\$debug\\s*=\\s*true\\s*;/s',
            $content
        );
        $this->assertStringNotContainsString('_trace(', $content);
    }

    public function testPdoConnectionNoLongerUsesGlobalAnalyzerOrMainConnectionGlobal(): void
    {
        $content = $this->readSource('src/database/yeapf-pdo-connection.php');

        $this->assertStringNotContainsString('global $yAnalyzer', $content);
        $this->assertStringNotContainsString('global $yeapfMainPDOConnection', $content);
        $this->assertStringNotContainsString('information_schema', $content);
        $this->assertStringNotContainsString('from pg_tables', strtolower($content));
    }

    public function testPdoConnectionConnectFlowIsSplitAndNoDeadLockCommentsRemain(): void
    {
        $content = $this->readSource('src/database/yeapf-pdo-connection.php');

        $this->assertStringContainsString('private function connectSingle(', $content);
        $this->assertStringContainsString('private function buildPool(', $content);
        $this->assertStringNotContainsString('// if (PDOConnectionLock::lock())', $content);
    }

    public function testWebAppHasNoGlobalAnalyzerOrLiveDebugBlockInRouteLookup(): void
    {
        $content = $this->readSource('src/webapp/yeapf-webapp.php');

        $this->assertStringNotContainsString('global $yAnalyzer', $content);
        $this->assertDoesNotMatchRegularExpression(
            '/function getRouteHandlerDefinition\\s*\\(.*?\\)\\s*\\{.*?\\$debug\\s*=\\s*false\\s*;.*?\\bvar_dump\\s*\\(.*?\\)\\s*;.*?\\bdie\\s*\\(\\s*\\)\\s*;/s',
            $content
        );
    }

    public function testWebAppRouteRegistrationWasSplitIntoSmallerMethods(): void
    {
        $content = $this->readSource('src/webapp/yeapf-webapp.php');

        $this->assertStringContainsString('private static function registerTypedRoute(', $content);
        $this->assertStringContainsString('private static function registerSimpleRoute(', $content);
        $this->assertStringContainsString('private static function normalizeRoutePath(', $content);
    }

    public function testSseServiceHasNoEchoInProductionPaths(): void
    {
        $content = $this->readSource('src/service/yeapf-sse-service.php');
        $this->assertDoesNotMatchRegularExpression('/^[ \t]*echo\\b/m', $content);
    }

    public function testSseServiceRequestFlowIsExtractedIntoNamedMethods(): void
    {
        $content = $this->readSource('src/service/yeapf-sse-service.php');

        $this->assertStringContainsString('private function handleRequest(', $content);
        $this->assertStringContainsString('private function runClientEventLoop(', $content);
        $this->assertStringContainsString('private function registerServerCallbacks(', $content);
    }

    public function testYParserGetUsesDedicatedReadersAndHtmlScriptHelper(): void
    {
        $content = $this->readSource('src/misc/yParser.php');

        $this->assertStringContainsString('private function readNumber(', $content);
        $this->assertStringContainsString('private function readString(', $content);
        $this->assertStringContainsString('private function readLineComment(', $content);
        $this->assertStringContainsString('private function readBlockComment(', $content);
        $this->assertStringContainsString('private function readOperator(', $content);
        $this->assertStringContainsString('private function readMacro(', $content);
        $this->assertStringContainsString('private function readHtmlScriptToken(', $content);
    }

    public function testCollectionsExportDocumentModelIsSplitByOutputFormat(): void
    {
        $content = $this->readSource('src/database/yeapf-collections.php');

        $this->assertStringContainsString('private function exportAsJson(', $content);
        $this->assertStringContainsString('private function exportAsSql(', $content);
        $this->assertStringContainsString('private function exportAsProtobuf(', $content);
        $this->assertStringContainsString('private function yeapfTypeToProtobufType(', $content);
        $this->assertStringContainsString('return $this->exportAsJson();', $content);
        $this->assertStringContainsString('return $this->exportAsSql();', $content);
        $this->assertStringContainsString('return $this->exportAsProtobuf();', $content);
    }
}
