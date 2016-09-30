<?php

/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */
namespace Calcinai\Gendarme\Command;

use Calcinai\Gendarme\Generator;
use Calcinai\Gendarme\Parser;
use Calcinai\Gendarme\Schema;
use PhpParser\Error;
use PhpParser\ParserFactory;
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
                'The base namespace for generated classes',
                ''
            )
            ->addOption(
                'root-class',
                null,
                InputOption::VALUE_OPTIONAL,
                'The name of the root generated schema',
                'Schema'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){

//        $code = '<?php static ';
//        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
//
//        try {
//            $stmts = $parser->parse($code);
//            print_r($stmts);
//        } catch (Error $e) {
//            echo 'Parse Error: ', $e->getMessage();
//        }
//
//
//        exit;

        $schema_file = $input->getArgument('schema');
        $output_dir = $input->getArgument('output');

        if(!file_exists($schema_file)){
            throw new InvalidArgumentException('Invalid schema provided');
        }

        if(!file_exists($output_dir)){
            throw new InvalidArgumentException('Invalid output directory provided');
        }

        $parser = new Parser($schema_file);
        $parser->parse();


        $generator = new Generator($input->getOption('namespace'), $input->getOption('root-class'), $parser->getSchemas());


    }
}


