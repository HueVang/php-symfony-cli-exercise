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
   // public function __construct(string $name = null)
   // {
   //     parent::__construct($name);
   // }


    function configure(): void
    {
        $this->setDescription("Opens up the menu of options");
    }

    function createMessage($user, $template, InputInterface $input, OutputInterface $output)
    {

        $stringArray = explode(" ", $template['message']);
        $modifiedStringArray = [];

        foreach ($stringArray as $string) {
            switch ($string) {
                case '{displayName}':
                    array_push($modifiedStringArray, $user['displayName']);
                    break;
                case '{name}':
                    array_push($modifiedStringArray, $user['name']);
                    break;
                case '{username}':
                    array_push($modifiedStringArray, $user['username']);
                    break;
                default:
                    array_push($modifiedStringArray, $string);
                    break;
            }
        }
        $finalMessage = implode(" ", $modifiedStringArray);

        //$output->writeln('This is the final message :' . $finalMessage);
        $webhook = getenv('SLACK_WEBHOOK_URL');
        $jsonPayload = '{"channel": "#accelerated-engineer-program", "username": "'. $user['username'] .'", "text": "'. $finalMessage .'", "icon_emoji": ":ghost:"}';
        //$command = ['curl', '-X', 'POST', '-H', '\'Content-Type: application/json\'', '-d', '{"channel": "#accelerated-engineer-program", "username": "Patrick Star", "text": ' . $finalMessage . ', "icon_emoji": ":ghost:"}', $webhook];
        $command = ['curl', '-X', 'POST', '-H', '\'Content-Type: application/json\'', '-d', $jsonPayload, $webhook];
        //$output->writeln('This is the jsonPayload :' . $jsonPayload);

        // grabFileContents($input, $output, 'messages');
        writeFile($input, $output, 'messages', $finalMessage);

        $process = new Process($command);

        //$process->run();



// executes after the command finishes
        //if (!$process->isSuccessful()) {
        //    throw new ProcessFailedException($process);
        //}

        //echo $process->getOutput();
        //$output->writeln('This is the process: ');
        //$output->writeln($process);
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
        $output->writeln("You chose template number: " . $selection);
        selectTemplate($input, $output, $selection);
        $output->writeln("You chose template: " . $selectedTemplate['message']);

        $helper = $this->getHelper('question');
        listUsers($input, $output);
        $question = new Question('Which User? (Please enter full name)');
        $userSelection = $helper->ask($input, $output, $question);
        $output->writeln("You chose user: " . $userSelection);

        selectUser($input, $output, $userSelection);
        $output->writeln("You chose user: " . $selectedUser['name']);

        $output->writeln("You chose user: " . $selectedUser['name']);
        $this->createMessage($selectedUser, $selectedTemplate, $input, $output);
        return Command::SUCCESS;
    }

    function updateTemplate(InputInterface $input, OutputInterface $output): int
    {
        listTemplates($input, $output);
        $helper = $this->getHelper('question');
        $question = new Question('What template do you want to update? ');
        $selection = $helper->ask($input, $output, $question);
        $output->writeln("You chose template number: " . $selection);

        $helper = $this->getHelper('question');
        $question = new Question('Enter your updated template and press enter to save:');
        $updatedTemplateMessage = $helper->ask($input, $output, $question);
        $output->writeln("This is the updated template message: " . $updatedTemplateMessage);


        $finder = new Finder();
        // find all files in the current directory
        $finder->files()->in(__DIR__.'/data');
        $newArray = array(); // this is update template test stuff

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
                        if ($template['id'] == $selection) { // this is update template test stuff
                            $newTemplate = (object) [ // this is update template test stuff
                                'id' => $selection, // this is update template test stuff
                                'message' => $updatedTemplateMessage // this is update template test stuff
                            ];
                            $output->writeln('This hit the if conditional: ' . $template["message"]); // this is update template test stuff
                            array_push($newArray, $newTemplate); // this is update template test stuff
                        } else {
                            array_push($newArray, $template); // this is update template test stuff
                            $output->writeln('This hit the else conditional'); // this is update template test stuff
                        }
                        // $output->writeln($template["id"] . ": " . $template["message"]);
                        $output->writeln('This is the newArray: ' . json_encode($newArray));
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
        $output->writeln("This is your new template message: " . $newTemplateMessage);


        $finder = new Finder();
        // find all files in the current directory
        $finder->files()->in(__DIR__.'/data');
        $newArray = array(); // this is update template test stuff

        // check if there are any search results
        if ($finder->hasResults()) {
            // $output->writeln("We found some files!");

            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                $fileNameWithExtension = $file->getRelativePathname();

                if ($fileNameWithExtension == "templates.json") {
                    $contents = $file->getContents();
                    $templatesArray = json_decode($contents, true);
                    $lastElement = end($templatesArray);
                    $newID = $lastElement['id'] + 1;
                    echo 'This is the new id value ' . $newID . " \n";
                    $newTemplate = (object) [
                        'id' => $newID,
                        'message' => $newTemplateMessage
                    ];
                    array_push($templatesArray, $newTemplate);
                    $output->writeln("This is the new templates array: " . json_encode($templatesArray));
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
        $output->writeln("You chose template number: " . $selection);


        $helper = $this->getHelper('question');
        $question = new Question('Are you sure? (y/n)');
        $confirmation = $helper->ask($input, $output, $question);
        $output->writeln("This is the confirmation: " . $confirmation);

        if ($confirmation == 'y' || 'Y') {
            $finder = new Finder();
            // find all files in the current directory
            $finder->files()->in(__DIR__.'/data');
            $newArray = array(); // this is update template test stuff

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
                            if ($template['id'] == $selection) { // this is update template test stuff
                                $output->writeln('This hit the if conditional for DELETE - we\'re deleting...'); // this is update template test stuff
                            } else {
                                array_push($newArray, $template); // this is update template test stuff
                                $output->writeln('This hit the else conditional for DELETE - we\'re pushing...'); // this is update template test stuff
                            }
                            // $output->writeln($template["id"] . ": " . $template["message"]);
                            $output->writeln('This is the newArray: ' . json_encode($newArray));
                        }
                    }
                }
                $filesystem = new Filesystem();
                $filesystem->dumpFile(__DIR__.'/data/templates.json', json_encode($newArray));
            }
        } else {
            echo 'Okay, we won\'t delete any templates';
        }

        return Command::SUCCESS;
    }

    function addUser(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');
        $question = new Question("Add a user \n
        Enter the user's name: ");

        $userName = $helper->ask($input, $output, $question);
        $output->writeln("This is your user's name: " . $userName);

        $helper = $this->getHelper('question');
        $question = new Question("\n 
        Enter the user's ID: ");

        $userID = $helper->ask($input, $output, $question);
        $output->writeln("This is your user's IS: " . $userID);

        $helper = $this->getHelper('question');
        $question = new Question("\n 
        Enter the user's username: ");

        $userUsername = $helper->ask($input, $output, $question);
        $output->writeln("This is your user's username: " . $userUsername);

        $helper = $this->getHelper('question');
        $question = new Question("\n 
        Enter the user's display name: ");

        $userDisplayName = $helper->ask($input, $output, $question);
        $output->writeln("This is your user's display name: " . $userDisplayName);



        $finder = new Finder();
        // find all files in the current directory
        $finder->files()->in(__DIR__.'/data');
        $newArray = array(); // this is update template test stuff

        // check if there are any search results
        if ($finder->hasResults()) {
            // $output->writeln("We found some files!");

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
                    $output->writeln("This is the new templates array: " . json_encode($usersArray));
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile(__DIR__.'/data/users.json', json_encode($usersArray));
                }
            }
        }



        return Command::SUCCESS;
    }


    function execute(InputInterface $input, OutputInterface $output): int
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
                $newArray = array(); // this is update template test stuff

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
                                if ($template['id'] == 2) { // this is update template test stuff
                                    $newTemplate = (object) [ // this is update template test stuff
                                        $template['id'] => 2, // this is update template test stuff
                                        'message' => 'This is the final test' // this is update template test stuff
                                    ];
                                    $output->writeln('This hit the if conditional: ' . $template["message"]); // this is update template test stuff
                                    array_push($newArray, $newTemplate); // this is update template test stuff
                                } else {
                                    array_push($newArray, $template); // this is update template test stuff
                                    $output->writeln('This hit the else conditional'); // this is update template test stuff
                                }
                                // $output->writeln($template["id"] . ": " . $template["message"]);
                                $output->writeln('This is the newArray: ' . json_encode($newArray));
                            }
                        }
                    }
                }


                return Command::SUCCESS;
            }


        function grabFileContents(InputInterface $input, OutputInterface $output, $fileName): int
        {
            global $templatesContents;
            global $usersContents;
            global $messagesContents;

            $finder = new Finder();
            // find all files in the current directory
            $finder->files()->in(__DIR__.'/data');

            // check if there are any search results
            if ($finder->hasResults()) {
                // $output->writeln("We found some files!");

                foreach ($finder as $file) {
                    $absoluteFilePath = $file->getRealPath();
                    $fileNameWithExtension = $file->getRelativePathname();

                    if ($fileNameWithExtension == "{$fileName}.json") {
                        $contents = $file->getContents();
                        switch ($fileName) {
                            case 'templates':
                                $templatesContents = json_decode($contents, true);
                                $output->writeln('This is the template contents: ' . json_encode($templatesContents));
                                break;
                            case 'users':
                                $usersContents = json_decode($contents, true);
                                $output->writeln('This is the users contents: ' . json_encode($usersContents));
                                break;
                            case 'messages2':
                                //addMessage();
                                $messagesContents = json_decode($contents, true);
                                $output->writeln('This is the messages contents: ' . json_encode($messagesContents));
                                break;
                            default:
                                $output->writeln('Switch case did not match condition');
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
            //$answer = $filesystem->exists('messages3.json');
            //$output->writeln('This is the answer: ' . __DIR__.'/data/messages3.json')

            switch ($fileName) {
                case 'templates':
                    $jsonFormattedContents = json_encode($usersContents);
                    $filesystem->dumpFile(__DIR__.'/data/messages2.json', $jsonFormattedContents);
                    $output->writeln('This is the template contents: ' . $jsonFormattedContents);
                    break;
                case 'users':
                    $jsonFormattedContents = json_encode($templatesContents);
                    $filesystem->dumpFile(__DIR__.'/data/messages2.json', $jsonFormattedContents);
                    $output->writeln('This is the users contents: ' . $jsonFormattedContents);
                    break;
                case 'messages':
                    addMessage($messagesContents, $message);
                    $jsonFormattedContents = json_encode($messagesContents);
                    $filesystem->dumpFile(__DIR__.'/data/messages2.json', $jsonFormattedContents);
                    //array_push($messagesContents, );
                    $output->writeln('This is the messages contents: ' . $jsonFormattedContents);
                    break;
                default:
                    $output->writeln('Switch case did not match condition');
                    break;
            }

        }

        function addMessage(InputInterface $input, OutputInterface $output, $arr, $message) {
            $newIdValue = null;
            $testArray = grabFileContents($input, $output, 'messages2');
            echo "this is the testArray: -> " . json_encode($testArray) . "\n";
            array_push($arr, $message);
            $newArray = $arr;
            echo "this is the array after the push: -> " . json_encode($newArray) . "\n";
            echo "this is the message value: -> " . $message . "\n";
            $currentDate = date(DATE_RFC2822);
            echo "this is the current time: ->" . json_encode($currentDate) . "\n";
            //echo "this is the new array value: -> " . $newArray . "\n";
            //echo "this is the newArray: -> " . json_encode($newArray);
        }

        function selectTemplate(InputInterface $input, OutputInterface $output, $choice): int
        {
            $finder = new Finder();
            // find all files in the current directory
            $finder->files()->in(__DIR__.'/data');
            global $selectedTemplate;
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
            // find all files in the current directory
            $finder->files()->in(__DIR__.'/data');
            global $selectedUser;
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
                            if ($user['name'] == $choice) {
                                $selectedUser = $user;
                            }
                        }
                    }
                }
            }


            return Command::SUCCESS;
        }



        switch ($selection) {
            case '1':
                $this->sendMessage($input, $output); // partially finished
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
            break;
        };

        return Command::SUCCESS;
    }
}

