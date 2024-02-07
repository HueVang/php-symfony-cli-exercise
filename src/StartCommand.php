<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;


class StartCommand extends Command
{
    static $defaultName = 'echo:start';
   // public function __construct(string $name = null)
   // {
   //     parent::__construct($name);
   // }


    function configure(): void
    {
        $this->setDescription("Opens up the menu of options");
    }

    function sendMessage(InputInterface $input, OutputInterface $output): int
    {
        // $output->writeln("What template?");
        listTemplates($input, $output);

        $helper = $this->getHelper('question');

        $question = new Question('What Template?');
        $selection = $helper->ask($input, $output, $question);
        $output->writeln("You chose template: " . $selection);

        return Command::SUCCESS;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $question = new Question('SLACK MESSAGE SENDER

        What would you like to do?
        1. Send a message
        2. List templates
        3. Add a template
        4. Update a template
        5. Delete a template
        6. List users
        7. Add a user
        8. Show sent messages
        9. Exit
        ', '1');

        $selection = $helper->ask($input, $output, $question);
        function listMessages(InputInterface $input, OutputInterface $output): int
            {
                $finder = new Finder();
    // find all files in the current directory
                $finder->files()->in(__DIR__.'/data');

    // check if there are any search results
                if ($finder->hasResults()) {
                    // $output->writeln("We found some files!");

                    foreach ($finder as $file) {
                        $absoluteFilePath = $file->getRealPath();
                        $fileNameWithExtension = $file->getRelativePathname();

                        if ($fileNameWithExtension == "messages.json") {
                            $contents = $file->getContents();
                            $messagesArray = json_decode($contents, true);

                            foreach ($messagesArray as $message) {
                                $output->writeln($message["id"] . ": " . $message["message"] . " " . $message["date"]);
                            }

                        }
                    }
                }


                return Command::SUCCESS;
            }

        function listUsers(InputInterface $input, OutputInterface $output): int
            {
                $finder = new Finder();
        // find all files in the current directory
                $finder->files()->in(__DIR__.'/data');

        // check if there are any search results
                if ($finder->hasResults()) {
                    // $output->writeln("We found some files!");

                    foreach ($finder as $file) {
                        $absoluteFilePath = $file->getRealPath();
                        $fileNameWithExtension = $file->getRelativePathname();

                        if ($fileNameWithExtension == "users.json") {
                            $contents = $file->getContents();
                            $usersArray = json_decode($contents, true);

                            foreach ($usersArray as $user) {
                                $output->writeln($user["name"]);
                            }
                        }
                    }
                }


                return Command::SUCCESS;
            }

        function listTemplates(InputInterface $input, OutputInterface $output): int
            {
                $finder = new Finder();
        // find all files in the current directory
                $finder->files()->in(__DIR__.'/data');

        // check if there are any search results
                if ($finder->hasResults()) {
                    // $output->writeln("We found some files!");

                    foreach ($finder as $file) {
                        $absoluteFilePath = $file->getRealPath();
                        $fileNameWithExtension = $file->getRelativePathname();

                        if ($fileNameWithExtension == "templates.json") {
                            $contents = $file->getContents();
                            $templatesArray = json_decode($contents, true);

                            foreach ($templatesArray as $template) {
                                $output->writeln($template["id"] . ": " . $template["message"]);
                            }
                        }
                    }
                }


                return Command::SUCCESS;
            }



        switch ($selection) {
            case '1':
               // $testObj = new StartCommand();
                //$testObj
                $this->sendMessage($input, $output);
            break;
            case '2':
                listTemplates($input, $output);
            break;
            case '3':
            break;
            case '4':
            break;
            case '5':
            break;
            case '6':
                listUsers($input, $output);
            break;
            case '7':
            break;
            case '8':
                listMessages($input, $output);
            break;
            case '9':
            break;
        };

        return Command::SUCCESS;
    }
}

