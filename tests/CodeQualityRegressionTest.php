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
}
