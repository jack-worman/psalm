<?php

namespace Psalm\Internal\Cli;

use LanguageServerProtocol\MessageType;
use Psalm\Config;
use Psalm\Internal\CliUtils;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\Fork\PsalmRestarter;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\LanguageServer\ClientConfiguration;
use Psalm\Internal\LanguageServer\LanguageServer as LanguageServerLanguageServer;
use Psalm\Report;

use function array_key_exists;
use function array_map;
use function array_search;
use function array_slice;
use function chdir;
use function error_log;
use function fwrite;
use function gc_disable;
use function getcwd;
use function getopt;
use function implode;
use function in_array;
use function ini_set;
use function is_array;
use function is_string;
use function preg_replace;
use function realpath;
use function setlocale;
use function strpos;
use function strtolower;
use function substr;

use const DIRECTORY_SEPARATOR;
use const LC_CTYPE;
use const PHP_EOL;
use const STDERR;

// phpcs:disable PSR1.Files.SideEffects

require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../CliUtils.php';
require_once __DIR__ . '/../Composer.php';
require_once __DIR__ . '/../IncludeCollector.php';
require_once __DIR__ . '/../LanguageServer/ClientConfiguration.php';

final class LanguageServer
{
    /**
     * @param array<int,string> $argv
     * @psalm-suppress ComplexMethod
     */
    public static function run(array $argv): void
    {
        $clientConfiguration = new ClientConfiguration();

        gc_disable();
        ErrorHandler::install();
        $valid_short_options = [
            'h',
            'v',
            'c:',
            'r:',
        ];

        $valid_long_options = [
            'clear-cache',
            'clear-cache-on-boot',
            'config:',
            'find-dead-code',
            'help',
            'root:',
            'use-ini-defaults',
            'version',
            'tcp:',
            'tcp-server',
            'disable-on-change::',
            'enable-autocomplete::',
            'enable-code-actions::',
            'enable-provide-diagnostics::',
            'enable-provide-hover::',
            'enable-provide-signature-help::',
            'enable-provide-definition::',
            'show-diagnostic-warnings::',
            'on-change-debounce-ms::',
            'use-extended-diagnostic-codes',
            'verbose'
        ];

        $args = array_slice($argv, 1);

        $psalm_proxy = array_search('--language-server', $args, true);

        if ($psalm_proxy !== false) {
            unset($args[$psalm_proxy]);
        }

        array_map(
            function (string $arg) use ($valid_long_options): void {
                if (strpos($arg, '--') === 0 && $arg !== '--') {
                    $arg_name = preg_replace('/=.*$/', '', substr($arg, 2));

                    if (!in_array($arg_name, $valid_long_options, true)
                        && !in_array($arg_name . ':', $valid_long_options, true)
                        && !in_array($arg_name . '::', $valid_long_options, true)
                    ) {
                        fwrite(
                            STDERR,
                            'Unrecognised argument "--' . $arg_name . '"' . PHP_EOL
                            . 'Type --help to see a list of supported arguments' . PHP_EOL
                        );
                        error_log('Bad argument');
                        exit(1);
                    }
                }
            },
            $args
        );

        // get options from command line
        $options = getopt(implode('', $valid_short_options), $valid_long_options);

        if (!array_key_exists('use-ini-defaults', $options)) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            ini_set('memory_limit', (string) (8 * 1024 * 1024 * 1024));
        }

        if (array_key_exists('help', $options)) {
            $options['h'] = false;
        }

        if (array_key_exists('version', $options)) {
            $options['v'] = false;
        }

        if (isset($options['config'])) {
            $options['c'] = $options['config'];
        }

        if (isset($options['c']) && is_array($options['c'])) {
            fwrite(STDERR, 'Too many config files provided' . PHP_EOL);
            exit(1);
        }

        if (array_key_exists('h', $options)) {
            echo <<<HELP
Usage:
    psalm-language-server [options]

Options:
    -h, --help
        Display this help message

    -v, --version
        Display the Psalm version

    -c, --config=psalm.xml
        Path to a psalm.xml configuration file. Run psalm --init to create one.

    -r, --root
        If running Psalm globally you'll need to specify a project root. Defaults to cwd

    --find-dead-code
        Look for dead code

    --clear-cache
        Clears all cache files that the language server uses for this specific project (exits after)

    --clear-cache-on-boot
        Clears all cache files that the language server uses for this specific project on boot (does not exit)

    --use-ini-defaults
        Use PHP-provided ini defaults for memory and error display

    --tcp=url
        Use TCP mode (by default Psalm uses STDIO)

    --tcp-server
        Use TCP in server mode (default is client)

    --disable-on-change[=line-number-threshold]
        If added, the language server will not respond to onChange events.
        You can also specify a line count over which Psalm will not run on-change events.

    --enable-code-actions[=BOOL]
        Enables or disables code actions. Default is true.

    --enable-provide-diagnostics[=BOOL]
        Enables or disables providing diagnostics. Default is true.

    --enable-autocomplete[=BOOL]
        Enables or disables autocomplete on methods and properties. Default is true.

    --enable-provide-hover[=BOOL]
        Enables or disables providing hover. Default is true.

    --enable-provide-signature-help[=BOOL]
        Enables or disables providing signature help. Default is true.

    --enable-provide-definition[=BOOL]
        Enables or disables providing definition. Default is true.

    --show-diagnostic-warnings[=BOOL]
        Enables or disables showing diagnostic warnings. Default is true.

    --use-extended-diagnostic-codes (DEPRECATED)
        Enables sending help uri links with the code in diagnostic messages.

    --on-change-debounce-ms=[INT]
        The number of milliseconds to debounce onChange events.

    --verbose
        Will send log messages to the client with information.

HELP;

            exit;
        }

        if (getcwd() === false) {
            fwrite(STDERR, 'Cannot get current working directory' . PHP_EOL);
            exit(1);
        }

        if (isset($options['root'])) {
            $options['r'] = $options['root'];
        }

        $current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;

        if (isset($options['r']) && is_string($options['r'])) {
            $root_path = realpath($options['r']);

            if (!$root_path) {
                fwrite(
                    STDERR,
                    'Could not locate root directory ' . $current_dir . DIRECTORY_SEPARATOR . $options['r'] . PHP_EOL
                );
                exit(1);
            }

            $current_dir = $root_path . DIRECTORY_SEPARATOR;
        }

        $vendor_dir = CliUtils::getVendorDir($current_dir);

        $include_collector = new IncludeCollector();

        $first_autoloader = $include_collector->runAndCollect(
            // we ignore the FQN because of a hack in scoper.inc that needs full path
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
            function () use ($current_dir, $options, $vendor_dir): ?\Composer\Autoload\ClassLoader {
                return CliUtils::requireAutoloaders($current_dir, isset($options['r']), $vendor_dir);
            }
        );

        if (array_key_exists('v', $options)) {
            echo 'Psalm ' . PSALM_VERSION . PHP_EOL;
            exit;
        }

        $ini_handler = new PsalmRestarter('PSALM');

        $ini_handler->disableExtension('grpc');

        // If Xdebug is enabled, restart without it
        $ini_handler->check();

        setlocale(LC_CTYPE, 'C');

        $path_to_config = CliUtils::getPathToConfig($options);

        if (isset($options['tcp'])) {
            if (!is_string($options['tcp'])) {
                fwrite(STDERR, 'tcp url should be a string' . PHP_EOL);
                exit(1);
            }
        }

        $config = CliUtils::initializeConfig(
            $path_to_config,
            $current_dir,
            Report::TYPE_CONSOLE,
            $first_autoloader
        );
        $config->setIncludeCollector($include_collector);

        if ($config->resolve_from_config_file) {
            $current_dir = $config->base_dir;
            chdir($current_dir);
        }

        $config->setServerMode();

        if (isset($options['clear-cache']) || isset($options['clear-cache-on-boot'])) {
            $cache_directory = $config->getCacheDirectory();

            if ($cache_directory !== null) {
                Config::removeCacheDirectory($cache_directory);
            }
            if (!isset($options['clear-cache-on-boot'])) {
                echo 'Cache directory deleted' . PHP_EOL;
                exit;
            }
        }

        if (isset($options['disable-on-change'])) {
            $clientConfiguration->onchangeLineLimit = (int) $options['disable-on-change'];
        }

        if (isset($options['on-change-debounce-ms'])) {
            $clientConfiguration->onChangeDebounceMs = (int) $options['on-change-debounce-ms'];
        }

        $clientConfiguration->provideDefinition = !isset($options['enable-provide-definition'])
            || !is_string($options['enable-provide-definition'])
            || strtolower($options['enable-provide-definition']) !== 'false';

        $clientConfiguration->provideSignatureHelp = !isset($options['enable-provide-signature-help'])
            || !is_string($options['enable-provide-signature-help'])
            || strtolower($options['enable-provide-signature-help']) !== 'false';

        $clientConfiguration->provideHover = !isset($options['enable-provide-hover'])
            || !is_string($options['enable-provide-hover'])
            || strtolower($options['enable-provide-hover']) !== 'false';

        $clientConfiguration->provideDiagnostics = !isset($options['enable-provide-diagnostics'])
            || !is_string($options['enable-provide-diagnostics'])
            || strtolower($options['enable-provide-diagnostics']) !== 'false';

        $clientConfiguration->provideCodeActions = !isset($options['enable-code-actions'])
            || !is_string($options['enable-code-actions'])
            || strtolower($options['enable-code-actions']) !== 'false';

        $clientConfiguration->provideCompletion = !isset($options['enable-autocomplete'])
            || !is_string($options['enable-autocomplete'])
            || strtolower($options['enable-autocomplete']) !== 'false';

        $clientConfiguration->hideWarnings = !(
            !isset($options['show-diagnostic-warnings'])
            || !is_string($options['show-diagnostic-warnings'])
            || strtolower($options['show-diagnostic-warnings']) !== 'false'
        );

        $find_unused_code = isset($options['find-dead-code']) ? 'auto' : null;
        if ($config->find_unused_code) {
            $find_unused_code = 'auto';
        }
        if ($find_unused_code) {
            $clientConfiguration->findUnusedCode = $find_unused_code;
        }

        if (isset($options['verbose'])) {
            $clientConfiguration->logLevel = $options['verbose'] ? MessageType::LOG : MessageType::INFO;
        }

        $clientConfiguration->TCPServerAddress = $options['tcp'] ?? null;
        $clientConfiguration->TCPServerMode = isset($options['tcp-server']);

        LanguageServerLanguageServer::run($config, $clientConfiguration, $current_dir);
    }
}
