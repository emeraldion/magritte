#!/usr/bin/env php
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

require_once __DIR__ . '/base.php';
require_once __DIR__ . '/../models/pipeline_item.php';

use splitbrain\phpcli\Options;

use Emeraldion\Magritte\Helpers\PipelineRunner;
use Emeraldion\Magritte\Models\BasePipeline;
use Emeraldion\Magritte\Models\BasePipelineStage;

class RunPipeline extends ScriptCommand
{
    protected $name = 'Pipeline Runner';
    protected $version = 'v1.0';

    const COMMAND_RUN = 'run';
    const COMMAND_VIEW = 'view';
    const COMMAND_INJECT = 'inject';

    const OPTION_EMPTY_STAGES = 'empty-stages';
    const OPTION_EXPUNGE = 'expunge';
    const OPTION_LIMIT = 'limit';
    const OPTION_STAGE = 'stage';
    const OPTION_START = 'start';

    const ARGUMENT_SHORT_NAME = 'short-name';

    protected function setup(Options $options)
    {
        $this->register_common_options($options);

        // Command: run
        $options->registerCommand(self::COMMAND_RUN, 'Runs a pipeline');
        $options->registerOption(
            get_called_class()::OPTION_LIMIT,
            'Process only up to this many items.',
            'l',
            get_called_class()::OPTION_LIMIT,
            self::COMMAND_RUN
        );
        $options->registerOption(
            get_called_class()::OPTION_START,
            'Start from an item other than the first.',
            's',
            get_called_class()::OPTION_START,
            self::COMMAND_RUN
        );
        $options->registerOption(
            get_called_class()::OPTION_STAGE,
            'Run this stage only.',
            't',
            get_called_class()::OPTION_STAGE,
            self::COMMAND_RUN
        );
        $options->registerOption(
            get_called_class()::OPTION_EXPUNGE,
            'Expunge processed items.',
            'E',
            false,
            self::COMMAND_RUN
        );
        $options->registerArgument(
            self::ARGUMENT_SHORT_NAME,
            'Provide the short name of a pipeline to run.',
            true,
            self::COMMAND_RUN
        );

        // Command: view
        $options->registerCommand(get_called_class()::COMMAND_VIEW, 'Views a pipeline');
        $options->registerOption(
            get_called_class()::OPTION_STAGE,
            'View this stage only.',
            't',
            get_called_class()::OPTION_STAGE,
            get_called_class()::COMMAND_VIEW
        );
        $options->registerOption(
            get_called_class()::OPTION_EMPTY_STAGES,
            'Show empty stages.',
            'E',
            false,
            get_called_class()::COMMAND_VIEW
        );
        $options->registerArgument(
            get_called_class()::ARGUMENT_SHORT_NAME,
            'Provide the short name of a pipeline to view.',
            true,
            get_called_class()::COMMAND_VIEW
        );

        // Command: inject
        $options->registerCommand(get_called_class()::COMMAND_INJECT, 'Injects items in a pipeline stage');
        $options->registerOption(
            get_called_class()::OPTION_STAGE,
            'Stage to inject.',
            't',
            get_called_class()::OPTION_STAGE,
            get_called_class()::COMMAND_INJECT
        );
        $options->registerOption(
            get_called_class()::OPTION_ID,
            'Comma separated list of item identifiers.',
            'I',
            get_called_class()::OPTION_ID,
            get_called_class()::COMMAND_INJECT
        );
        $options->registerArgument(
            get_called_class()::ARGUMENT_SHORT_NAME,
            'Provide the short name of a pipeline to run.',
            true,
            get_called_class()::COMMAND_INJECT
        );

        $options->setHelp('Magritte - Pipeline runner');
    }

    protected function main(Options $options)
    {
        switch ($options->getCmd()) {
            case get_called_class()::COMMAND_RUN:
                $this->do_run($options);
                break;
            case get_called_class()::COMMAND_VIEW:
                $this->view($options);
                break;
            case get_called_class()::COMMAND_INJECT:
                $this->inject($options);
                break;
            default:
                $options->useCompactHelp();
                break;
        }

        ANSIColorWriter::printf("\n%s\n", 'green', ANSIColorWriter::bold('Done'));
    }

    protected function do_run(Options $options)
    {
        $verbose = $options->getOpt(get_called_class()::OPTION_VERBOSE);
        $dry_run = $options->getOpt(get_called_class()::OPTION_DRY_RUN);
        $expunge = $options->getOpt(get_called_class()::OPTION_EXPUNGE);
        $limit = $options->getOpt(get_called_class()::OPTION_LIMIT) ?: 10;
        $start = $options->getOpt(get_called_class()::OPTION_START) ?: 0;
        $stage_name = $options->getOpt(get_called_class()::OPTION_STAGE);
        $short_name = first($options->getArgs());

        $runner = PipelineRunner::for(PipelineItem::class);
        try {
            if (!$runner->run($short_name, $stage_name, $verbose, $dry_run, $limit, $start, $expunge)) {
                ANSIColorWriter::printf("\n%s\n", 'bright-black', AnsiColorWriter::bold('Nothing to do'));
            }
        } catch (Throwable $t) {
            printf("[%s] %s\n", ANSIColorWriter::colorize('Error', 'red'), $t->getMessage());
            exit(1);
        } catch (Error $e) {
            printf("[%s] %s\n", ANSIColorWriter::colorize('Error', 'red'), $e->getMessage());
            exit(1);
        }
    }

    protected function view(Options $options)
    {
        $verbose = $options->getOpt(get_called_class()::OPTION_VERBOSE);
        $stage_name = $options->getOpt(get_called_class()::OPTION_STAGE);
        $show_empty = $options->getOpt(get_called_class()::OPTION_EMPTY_STAGES);
        $short_name = first($options->getArgs());

        $runner = PipelineRunner::for(PipelineItem::class);
        try {
            $runner->inspect($short_name, $stage_name, $verbose, $show_empty);
        } catch (Throwable $t) {
            printf("[%s] %s\n", ANSIColorWriter::colorize('Error', 'red'), $t->getMessage());
            exit(1);
        } catch (Error $e) {
            printf("[%s] %s\n", ANSIColorWriter::colorize('Error', 'red'), $e->getMessage());
            exit(1);
        }
    }

    protected function inject(Options $options)
    {
        $verbose = $options->getOpt(get_called_class()::OPTION_VERBOSE);
        $stage_name = $options->getOpt(get_called_class()::OPTION_STAGE);
        $identifiers = $options->getOpt(get_called_class()::OPTION_ID);
        $short_name = first($options->getArgs());

        $runner = PipelineRunner::for(PipelineItem::class);
        try {
            $runner->inject($short_name, $stage_name, explode(',', $identifiers), $verbose);
        } catch (Throwable $t) {
            printf("[%s] %s\n", ANSIColorWriter::colorize('Error', 'red'), $t->getMessage());
            exit(1);
        } catch (Error $e) {
            printf("[%s] %s\n", ANSIColorWriter::colorize('Error', 'red'), $e->getMessage());
            exit(1);
        }
    }
}

$command = new RunPipeline();
$command->run();

