<?php
/**
 *                                _ __  __
 *    ____ ___  ____ _____ ______(_) /_/ /____
 *   / __ `__ \/ __ `/ __ `/ ___/ / __/ __/ _ \
 *  / / / / / / /_/ / /_/ / /  / / /_/ /_/  __/
 * /_/ /_/ /_/\__,_/\__, /_/  /_/\__/\__/\___/
 *                 /____/
 *
 * (c) Claudio Procida 2026
 *
 * @format
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.conf.php';

use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

use Emeraldion\EmeRails\Db;
use Emeraldion\EmeRails\DbAdapters\MysqliAdapter;
use Emeraldion\EmeRails\DbAdapters\MysqlAdapter;
use Emeraldion\EmeRails\Helpers\ANSIColorWriter;
use Emeraldion\EmeRails\Helpers\Localization;
use Emeraldion\EmeRails\Helpers\QueryString;

Db::register_adapter(new MysqliAdapter(), MysqliAdapter::NAME);
Db::register_adapter(new MysqlAdapter(), MysqlAdapter::NAME);

date_default_timezone_set('Europe/Rome');

ini_set('max_execution_time', 3000);

Localization::set_base_dir(__DIR__ . '/../');
// Load global English strings
$_COOKIE[Config::get('LANGUAGE_COOKIE')] = 'en';
Localization::add_strings_table(Localization::GLOBAL_TABLE);

abstract class ScriptCommand extends CLI
{
    const OPTION_DIAGNOSTICS = 'diagnostics';
    const OPTION_DRY_RUN = 'dry-run';
    const OPTION_ID = 'id';
    const OPTION_MEASURE = 'measure';
    const OPTION_TERSE = 'terse';
    const OPTION_VERBOSE = 'verbose';

    protected $name = '<COMMAND NAME>';
    protected $version = '<VERSION>';

    private function hello()
    {
        ANSIColorWriter::print(
            <<<EOT
                                            _ __  __
                ____ ___  ____ _____ ______(_) /_/ /____
               / __ `__ \/ __ `/ __ `/ ___/ / __/ __/ _ \
              / / / / / / /_/ / /_/ / /  / / /_/ /_/  __/
             /_/ /_/ /_/\__,_/\__, /_/  /_/\__/\__/\___/
                             /____/


            EOT
            ,
            'bright-green'
        );
        printf(
            <<<EOT
            (c) Claudio Procida 2026

            %s %s


            EOT
            ,
            $this->name,
            $this->version
        );
    }

    protected function execute()
    {
        if (!$this->options->getOpt(get_called_class()::OPTION_TERSE)) {
            $this->hello();
        }
        $elapsed = -hrtime(true);
        parent::execute();
        $elapsed += hrtime(true);
        if ($this->options->getOpt(get_called_class()::OPTION_DIAGNOSTICS)) {
            $this->print_diagnostics($elapsed);
        }
    }

    protected function register_common_options(Options $options)
    {
        $options->registerOption(get_called_class()::OPTION_TERSE, 'Skip any pleasantries, cut to the chase.');
        $options->registerOption(
            get_called_class()::OPTION_ID,
            'Provide a single or a comma-separated list of item identifiers.',
            'I',
            get_called_class()::OPTION_ID
        );
        $options->registerOption(get_called_class()::OPTION_VERBOSE, 'Print out detailed information messages.', 'v');
        $options->registerOption(
            get_called_class()::OPTION_DRY_RUN,
            'Run this command without committing any changes.',
            'd'
        );
        $options->registerOption(
            get_called_class()::OPTION_DIAGNOSTICS,
            'Print performance diagnostics of the execution of this command.'
        );
        $options->registerOption(
            get_called_class()::OPTION_MEASURE,
            'Publish metrics of the performance measurements of the execution of this command.'
        );
    }

    protected function get_php_argv()
    {
        global $argv;
        if (!is_array($argv)) {
            if (!@is_array($_SERVER['argv'])) {
                if (!@is_array($GLOBALS['HTTP_SERVER_VARS']['argv'])) {
                    throw new \Exception('Could not read cmd args (register_argc_argv=Off?)', Exception::E_ARG_READ);
                }
                return $GLOBALS['HTTP_SERVER_VARS']['argv'];
            }
            return $_SERVER['argv'];
        }
        return $argv;
    }

    protected function print_diagnostics($elapsed)
    {
        ANSIColorWriter::printf("\nExecution time: %.3f ms\n", 'bright-black', $elapsed / 1e6);
    }

    protected function log_error($fmt, ...$args)
    {
        printf('[%s] ' . $fmt, ANSIColorWriter::colorize('Error', 'red'), ...$args);
    }

    protected function validate_date($input)
    {
        if ($input === false) {
            return false;
        }
        $valid = true;
        $parts = explode('-', $input);
        if (count($parts) === 3) {
            if (!checkdate($parts[1], $parts[2], $parts[0])) {
                $valid = false;
            }
        } else {
            $valid = false;
        }
        if (!$valid) {
            throw new \Exception(sprintf('Not a valid date: %s', $input));
        }
        return $input;
    }

    protected function done()
    {
        if (!$this->options->getOpt(get_called_class()::OPTION_TERSE)) {
            ANSIColorWriter::printf("\n%s\n", 'green', ANSIColorWriter::bold('Done'));
        }
    }
}
