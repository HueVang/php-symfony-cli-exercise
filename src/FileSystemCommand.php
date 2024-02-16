<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Question\Question;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class FileSystemCommand extends Command
{

    protected static $defaultName = 'echo:filewrite';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //  $helper = $this->getHelper('question');

        //  $question = new Question('What would you like to do? ', 'Nothing');

        // $nameAnswer = $helper->ask($input, $output, $question);
        $filesystem = new Filesystem();
        //$answer = $filesystem->exists('messages3.json');
        //$output->writeln('This is the answer: ' . __DIR__.'/data/messages3.json');
        $filesystem->dumpFile(__DIR__.'/data/messages2.json', '[
  {
    "id": 4,
    "message": "Hello, World",
    "date": "Mon Jan 16 2023 17:39:43 GMT-0600 (CST)"
  }
]');



        // the third argument tells whether the file should be locked when writing to it

        $output->writeln("Success!");

        return Command::SUCCESS;
    }

}

