<?php

namespace Psalm\Tests\Traits;

use Psalm\Config;
use Psalm\Context;

use function array_key_exists;
use function str_replace;
use function strpos;
use function strtoupper;
use function substr;
use function version_compare;

use const PHP_OS;
use const PHP_VERSION;

/**
 * Support for testing with the creation of a list of issues
 *
 *
 * #### Description of motivation
 *
 * We have two different behaviors for the code related to
 * "CodeException":
 *
 * ```
 * $codebase->config->throw_exception = true; // or false
 * ```
 *
 * When `throw_exception` is set to `true`, code execution stops once
 * the first issue is emitted, thus it may mask any problems after
 * that point.
 *
 * When `throw_exception` is set to `false`, the code will continue
 * to be executed and we can uncover some additional bugs.
 *
 * This is trait allows testing for the second case, when the value of
 * "throw_exception" will be "false".
 *
 * @psalm-type psalmConfigOptions = array{
 *      strict_binary_operands?: bool,
 *  }
 * @psalm-type DeprecatedDataProviderArrayNotation = array{
 *     code: string,
 *     error_message: string,
 *     ignored_issues?: list<string>,
 *     php_version?: string|null,
 *     config_options?: psalmConfigOptions,
 * }
 * @psalm-type NamedArgumentsDataProviderArrayNotation = array{
 *     code: string,
 *     error_message: string,
 *     error_levels?: list<string>,
 *     php_version?: string|null,
 *     config_options?: psalmConfigOptions,
 * }
 */
trait InvalidCodeAnalysisWithIssuesTestTrait
{
    /**
     * @return iterable<
     *     string,
     *     DeprecatedDataProviderArrayNotation|NamedArgumentsDataProviderArrayNotation
     * >
     */
    abstract public function providerInvalidCodeParse(): iterable;

    /**
     * @dataProvider providerInvalidCodeParse
     * @small
     * @param list<string> $error_levels
     * @param psalmConfigOptions $config_options
     */
    public function testInvalidCodeWithIssues(
        string $code,
        string $error_message,
        array  $error_levels = [],
        ?string $php_version = null,
        array $config_options = []
    ): void {
        $php_version ??= '7.4';
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP80-') !== false) {
            if (version_compare(PHP_VERSION, '8.0.0', '<')) {
                $this->markTestSkipped('Test case requires PHP 8.0.');
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        // sanity check - do we have a PHP tag?
        if (strpos($code, '<?php') === false) {
            $this->fail('Test case must have a <?php tag');
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $code = str_replace("\n", "\r\n", $code);
        }

        $config = Config::getInstance();
        foreach ($error_levels as $error_level) {
            $issue_name = $error_level;
            $error_level = Config::REPORT_SUPPRESS;

            $config->setCustomErrorLevel($issue_name, $error_level);
        }
        if (array_key_exists('strict_binary_operands', $config_options)) {
            $config->strict_binary_operands = $config_options['strict_binary_operands'];
        }

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->enterServerMode();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $codebase->config->throw_exception = false;

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());

        $this->assertHasIssue($error_message);
    }
}
