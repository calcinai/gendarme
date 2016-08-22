<?php

/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */
namespace Calcinai\Gendarme\Command;

use Calcinai\Gendarme\Parser;
use JsonSchema\SchemaStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command {

    protected function configure() {
        $this
            ->setName('generate')
            ->setDescription('Generate classes representing the JSON schema')
            ->addArgument(
                'schema',
                InputArgument::REQUIRED,
                'JSON schema file'
            )
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Directory to put the generated classes'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL,
                'The Base namespace for generated classes',
                ''
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $schema_file = $input->getArgument('schema');
        $output_dir = $input->getArgument('output');

        if(!file_exists($schema_file)){
            throw new InvalidArgumentException('Invalid schema provided');
        }

        if(!file_exists($output_dir)){
            throw new InvalidArgumentException('Invalid output directory provided');
        }

        $parser = new Parser($schema_file);

    }
}


