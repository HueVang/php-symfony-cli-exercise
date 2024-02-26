<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;


// variables in use
$selectedTemplate = null;
$selectedUser = null;
$templatesContents = null;
$usersContents = null;
$messagesContents = null;

// unused variables
$name = null;
$userName = null;
$displayName = null;
class StartCommand extends Command
{
    static $defaultName = 'echo:start';



    function configure(): void
    {
        $this->setDescription("Opens up the menu of options");
    }

    function createMessage($user, $template, InputInterface $input, OutputInterface $output)
    {
        $finalMessage = str_replace(array("{username}", "{name}", "{displayName}"), array($user['username'],$user['name'], $user['displayName']), $template['message']);
        $webhook = getenv('SLACK_WEBHOOK_URL');
        $jsonPayload = '{"channel": "#accelerated-engineer-program", "username": "'. $user['username'] .'", "text": "'. $finalMessage .'", "icon_emoji": ":ghost:"}';
        $command = ['curl', '-X', 'POST', '-H', '\'Content-Type: application/json\'', '-d', $jsonPayload, $webhook];

        writeFile($input, $output, 'messages', $finalMessage);

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

    }


    function sendMessage(InputInterface $input, OutputInterface $output): int
    {
        global $selectedTemplate;
        global $selectedUser;
        global $name;
        global $userName;
        global $displayName;
        listTemplates($input, $output);

        $helper = $this->getHelper('question');

        $question = new Question('What Template?');
        $selection = $helper->ask($input, $output, $question);
        selectTemplate($input, $output, $selection);

        $helper = $this->getHelper('question');
        listUsers($input, $output);
        $question = new Question('Which User? (Please enter full name)');
        $userSelection = $helper->ask($input, $output, $question);

        selectUser($input, $output, $userSelection);

        $this->createMessage($selectedUser, $selectedTemplate, $input, $output);
        return Command::SUCCESS;
    }

    function updateTemplate(InputInterface $input, OutputInterface $output): int
    {
        listTemplates($input, $output);
        $helper = $this->getHelper('question');
        $question = new Question('What template do you want to update? ');
        $selection = $helper->ask($input, $output, $question);


        $helper = $this->getHelper('question');
        $question = new Question('Enter your updated template and press enter to save:');
        $updatedTemplateMessage = $helper->ask($input, $output, $question);


        $finder = new Finder();
        $finder->files()->in(__DIR__.'/data');
        $newArray = array(); // this is update template test stuff

        if ($finder->hasResults()) {

            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                $fileNameWithExtension = $file->getRelativePathname();

                if ($fileNameWithExtension == "templates.json") {
                    $contents = $file->getContents();
                    $templatesArray = json_decode($contents, true);

                    foreach ($templatesArray as $template) {
                        if ($template['id'] == $selection) {
                            $newTemplate = (object) [
                                'id' => $selection,
                                'message' => $updatedTemplateMessage
                            ];
                            array_push($newArray, $newTemplate);
                        } else {
                            array_push($newArray, $template);
                        }
                    }
                }
            }
            $filesystem = new Filesystem();
            $filesystem->dumpFile(__DIR__.'/data/templates.json', json_encode($newArray));
        }


        return Command::SUCCESS;
    }

    function addTemplate(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');
        $question = new Question("Add a template \n \n 
        Available variables \n 
        * {name} \n
        * {username}\n
        * {displayName}\n
        Enter your new template and press enter to save: \n");

        $newTemplateMessage = $helper->ask($input, $output, $question);


        $finder = new Finder();
        $finder->files()->in(__DIR__.'/data');

        if ($finder->hasResults()) {

            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                $fileNameWithExtension = $file->getRelativePathname();

                if ($fileNameWithExtension == "templates.json") {
                    $contents = $file->getContents();
                    $templatesArray = json_decode($contents, true);
                    $lastElement = end($templatesArray);
                    $newID = $lastElement['id'] + 1;
                    $newTemplate = (object) [
                        'id' => $newID,
                        'message' => $newTemplateMessage
                    ];
                    array_push($templatesArray, $newTemplate);
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile(__DIR__.'/data/templates.json', json_encode($templatesArray));
                }
            }
        }



        return Command::SUCCESS;
    }

    function deleteTemplate(InputInterface $input, OutputInterface $output): int
    {
        listTemplates($input, $output);
        $helper = $this->getHelper('question');
        $question = new Question('What template do you want to delete? ');
        $selection = $helper->ask($input, $output, $question);


        $helper = $this->getHelper('question');
        $question = new Question('Are you sure? (y/n)');
        $confirmation = $helper->ask($input, $output, $question);

        if ($confirmation == strtolower('y')) {
            $finder = new Finder();
            $finder->files()->in(__DIR__.'/data');
            $newArray = array(); // this is update template test stuff
            if ($finder->hasResults()) {

                foreach ($finder as $file) {
                    $absoluteFilePath = $file->getRealPath();
                    $fileNameWithExtension = $file->getRelativePathname();

                    if ($fileNameWithExtension == "templates.json") {
                        $contents = $file->getContents();
                        $templatesArray = json_decode($contents, true);

                        foreach ($templatesArray as $template) {
                            if ($template['id'] == $selection) {
                            } else {
                                array_push($newArray, $template);
                            }
                        }
                    }
                }
                $filesystem = new Filesystem();
                $filesystem->dumpFile(__DIR__.'/data/templates.json', json_encode($newArray));
            }
        }

        return Command::SUCCESS;
    }

    function addUser(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');
        $question = new Question("Add a user \n
        Enter the user's name: ");

        $userName = $helper->ask($input, $output, $question);

        $helper = $this->getHelper('question');
        $question = new Question("\n 
        Enter the user's ID: ");

        $userID = $helper->ask($input, $output, $question);

        $helper = $this->getHelper('question');
        $question = new Question("\n 
        Enter the user's username: ");

        $userUsername = $helper->ask($input, $output, $question);

        $helper = $this->getHelper('question');
        $question = new Question("\n 
        Enter the user's display name: ");

        $userDisplayName = $helper->ask($input, $output, $question);



        $finder = new Finder();
        $finder->files()->in(__DIR__.'/data');

        if ($finder->hasResults()) {

            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                $fileNameWithExtension = $file->getRelativePathname();

                if ($fileNameWithExtension == "users.json") {
                    $contents = $file->getContents();
                    $usersArray = json_decode($contents, true);
                    $newUser = (object) [
                        'name' => $userName,
                        'userId' => $userID,
                        'username' => $userUsername,
                        'displayName' => $userDisplayName
                    ];
                    array_push($usersArray, $newUser);
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile(__DIR__.'/data/users.json', json_encode($usersArray));
                }
            }
        }



        return Command::SUCCESS;
    }


    function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Hue\'s PHP CLI Project');
        $continue = true;


        function listMessages(InputInterface $input, OutputInterface $output): int
            {
                $finder = new Finder();
                $finder->files()->in(__DIR__.'/data');
                $table = new Table ($output);
                $table->setHeaders(['Date', 'Message']);
                if ($finder->hasResults()) {

                    foreach ($finder as $file) {
                        $absoluteFilePath = $file->getRealPath();
                        $fileNameWithExtension = $file->getRelativePathname();

                        if ($fileNameWithExtension == "messages.json") {
                            $contents = $file->getContents();
                            $messagesArray = json_decode($contents, true);

                            foreach ($messagesArray as $message) {
                                $table->setRows([[$message["date"], $message["message"]]]);
                            }

                        }
                    }
                }

                $table->render();
                return Command::SUCCESS;
            }

        function listUsers(InputInterface $input, OutputInterface $output): int
            {
                $finder = new Finder();
                $finder->files()->in(__DIR__.'/data');

                if ($finder->hasResults()) {
                    $table = new Table($output);
                    $table->setHeaders(['Name', 'User ID', 'Username', 'Display Name']);
                    foreach ($finder as $file) {
                        $absoluteFilePath = $file->getRealPath();
                        $fileNameWithExtension = $file->getRelativePathname();

                        if ($fileNameWithExtension == "users.json") {
                            $contents = $file->getContents();
                            $usersArray = json_decode($contents, true);

                            foreach ($usersArray as $user) {
                                $table->addRows([[$user["name"], $user["userId"], $user["username"], $user["displayName"]]]);
                            }
                        }
                    }
                    $table->render();
                }

                return Command::SUCCESS;
            }

        function listTemplates(InputInterface $input, OutputInterface $output): int
            {
                $finder = new Finder();
                $finder->files()->in(__DIR__.'/data');
                $newArray = array();

                if ($finder->hasResults()) {
                    $table = new Table($output);
                    $table->setHeaders(['ID', 'Message']);

                    foreach ($finder as $file) {
                        $absoluteFilePath = $file->getRealPath();
                        $fileNameWithExtension = $file->getRelativePathname();

                        if ($fileNameWithExtension == "templates.json") {
                            $contents = $file->getContents();
                            $templatesArray = json_decode($contents, true);

                            foreach ($templatesArray as $template) {
                                $table->addRows([[$template["id"], $template["message"]]]);
                            }
                        }
                    }
                    $table->render();
                }


                return Command::SUCCESS;
            }


        function grabFileContents(InputInterface $input, OutputInterface $output, $fileName): int
        {
            global $templatesContents;
            global $usersContents;
            global $messagesContents;

            $finder = new Finder();
            $finder->files()->in(__DIR__.'/data');

            if ($finder->hasResults()) {

                foreach ($finder as $file) {
                    $absoluteFilePath = $file->getRealPath();
                    $fileNameWithExtension = $file->getRelativePathname();

                    if ($fileNameWithExtension == "{$fileName}.json") {
                        $contents = $file->getContents();
                        switch ($fileName) {
                            case 'templates':
                                $templatesContents = json_decode($contents, true);
                                break;
                            case 'users':
                                $usersContents = json_decode($contents, true);
                                break;
                            case 'messages2':
                                $messagesContents = json_decode($contents, true);
                                break;
                            default:
                                break;
                        }
                    }
                }
            }


            return Command::SUCCESS;
        }

        function writeFile(InputInterface $input, OutputInterface $output, $fileName, $message) {

            global $templatesContents;
            global $usersContents;
            global $messagesContents;
            grabFileContents($input, $output, $fileName);
            $jsonFormattedContents = null;
            $filesystem = new Filesystem();

            switch ($fileName) {
                case 'templates':
                    $jsonFormattedContents = json_encode($usersContents);
                    $filesystem->dumpFile(__DIR__.'/data/messages2.json', $jsonFormattedContents);
                    break;
                case 'users':
                    $jsonFormattedContents = json_encode($templatesContents);
                    $filesystem->dumpFile(__DIR__.'/data/messages2.json', $jsonFormattedContents);
                    break;
                case 'messages':
                    addMessage($input, $output, $messagesContents, $message);
                    break;
                default:
                    break;
            }

        }

        function addMessage(InputInterface $input, OutputInterface $output, $arr, $message) {
            global $messagesContents;
            grabFileContents($input, $output, 'messages2');
            $messagesArray = $messagesContents;
            $currentDate = date_create("now", new \DateTimeZone("America/Chicago"));
            $datePlaceholder = date_format($currentDate, "D M d Y H:i:s TO (T)");
            $dateStringArray = explode(" ", $datePlaceholder);
            $dateStringArray[5] = "GMT-0600";
            $dateInCorrectFormat = implode(" " ,$dateStringArray);

            $lastElement = end($messagesArray);
            $newID = $lastElement['id'] + 1;
            $newMessage = (object) [
                'id' => $newID,
                'message' => $message,
                'date' => $dateInCorrectFormat
            ];


            array_push($messagesArray, $newMessage);
            $filesystem = new Filesystem();
            $filesystem->dumpFile(__DIR__.'/data/messages2.json', json_encode($messagesArray));


        }

        function selectTemplate(InputInterface $input, OutputInterface $output, $choice): int
        {
            $finder = new Finder();
            $finder->files()->in(__DIR__.'/data');
            global $selectedTemplate;
            if ($finder->hasResults()) {

                foreach ($finder as $file) {
                    $absoluteFilePath = $file->getRealPath();
                    $fileNameWithExtension = $file->getRelativePathname();

                    if ($fileNameWithExtension == "templates.json") {
                        $contents = $file->getContents();
                        $templatesArray = json_decode($contents, true);

                        foreach ($templatesArray as $template) {
                            if ($template['id'] == $choice) {
                                $selectedTemplate = $template;
                            }
                        }
                    }
                }
            }


            return Command::SUCCESS;
        }

        function selectUser(InputInterface $input, OutputInterface $output, $choice): int
        {
            $finder = new Finder();
            $finder->files()->in(__DIR__.'/data');
            global $selectedUser;
            if ($finder->hasResults()) {

                foreach ($finder as $file) {
                    $absoluteFilePath = $file->getRealPath();
                    $fileNameWithExtension = $file->getRelativePathname();

                    if ($fileNameWithExtension == "users.json") {
                        $contents = $file->getContents();
                        $usersArray = json_decode($contents, true);

                        foreach ($usersArray as $user) {
                            if ($user['name'] == $choice) {
                                $selectedUser = $user;
                            }
                        }
                    }
                }
            }


            return Command::SUCCESS;
        }

        while ($continue) {
            $helper = $this->getHelper('question');

            $question = new Question('<info>SLACK MESSAGE SENDER

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
        </info>', '1');

            $selection = $helper->ask($input, $output, $question);


            switch ($selection) {
                case '1':
                    $this->sendMessage($input, $output);
                    break;
                case '2':
                    listTemplates($input, $output);
                    break;
                case '3':
                    $this->addTemplate($input, $output);
                    break;
                case '4':
                    $this->updateTemplate($input, $output);
                    break;
                case '5':
                    $this->deleteTemplate($input, $output);
                    break;
                case '6':
                    listUsers($input, $output);
                    break;
                case '7':
                    $this->addUser($input, $output);
                    break;
                case '8':
                    listMessages($input, $output);
                    break;
                case '9':
                    $continue = false;
                    break;
            };
        }


        return Command::SUCCESS;
    }
}

