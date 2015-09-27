<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require 'QueryStorage.php';
require 'EmailStorage.php';
require 'SettingsStorage.php';
require 'ResponseStorage.php';
require 'AllegroApi.php';
require 'AllegroFilters.php';
require 'RunQuery.php';

class Console extends Application {

    public function __construct() {
        parent::__construct();

        // register action
        $this->registerSet();
        $this->registerDel();
        $this->registerShow();
        $this->registerEmails();
        $this->registerSubscribe();
        $this->registerUnsubscribe();
        $this->registerRun();
        $this->registerConfig();
        $this->registerCodes();
        $this->registerCategories();
        $this->registerFilters();
    }

    private function registerSet() {
        $command = $this->register('set');

        // set arguments
        $command->setDefinition(array(
            new InputArgument('query', InputArgument::REQUIRED, 'Phrase to search'),
            new InputArgument('id', InputArgument::OPTIONAL, 'Query identifier'),
            new InputOption('only-new', null, InputOption::VALUE_NONE, 'Search only new items'),
            new InputOption('only-buy-now', null, InputOption::VALUE_NONE, 'Search only buy now items'),
            new InputOption('min-price', null, InputOption::VALUE_REQUIRED, 'Minimal price'),
            new InputOption('max-price', null, InputOption::VALUE_REQUIRED, 'Maximal price'),
        ));

        // set description
        $command->setDescription('Adds/updates query to search on allegro');

        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    // storage
                    $storage = QueryStorage::getInstance();

                    // create data
                    $data = QueryStorage::createQuery($input->getArgument('query'), $input->getOption('only-new'), $input->getOption('only-buy-now'), $input->getOption('min-price'), $input->getOption('max-price'));

                    // existing ids
                    $ids = $storage->getIds();

                    // set/update
                    $id = QueryStorage::getInstance()->set($data, $input->getArgument('id'));

                    // save
                    QueryStorage::getInstance()->save();

                    // write output
                    $output->writeln(sprintf('%s query: <info>%s</info> to database with ID: <info>%s</info>', in_array($id, $ids) ? 'Updated' : 'Added', QueryStorage::formatQuery($data), $id));
                });
    }

    private function registerDel() {
        $command = $this->register('del');

        // set arguments
        $command->setDefinition(array(
            new InputArgument('id', InputArgument::REQUIRED, 'Query identifier'),
        ));

        // set description
        $command->setDescription('Deletes query');

        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {
                    // storage
                    $storage = QueryStorage::getInstance();

                    // get and store ids
                    $ids = QueryStorage::getInstance()->getIds();

                    // get argument id
                    $id = $input->getArgument('id');

                    // check if query exists
                    if (in_array($id, $ids)) {

                        // delete (cascade with emails) & save
                        $storage->delete($id, true);
                        $storage->save();

                        // write output
                        $output->writeln(sprintf('Query with id <info>%s</info> deleted', $id));
                    } else {
                        // inform about missing id
                        $output->writeln(sprintf('Query with id <info>%s</info> not found', $id));
                    }
                });
    }

    private function registerShow() {
        $command = $this->register('show');

        // set description
        $command->setDescription('Shows all queries');


        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    // iterate through all queries
                    foreach (QueryStorage::getInstance()->getAll() as $id => $data) {
                        $output->writeln(sprintf('%s: <info>%s</info>', $id, QueryStorage::formatQuery($data)));
                    }
                });
    }

    private function registerEmails() {
        $command = $this->register('emails');

        // set description
        $command->setDescription('Shows emails related to query');

        // set arguments
        $command->setDefinition(array(
            new InputArgument('id', InputArgument::REQUIRED, 'Query identifier'),
        ));


        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    // get and store ids
                    $ids = QueryStorage::getInstance()->getIds();

                    // get argument id
                    $id = $input->getArgument('id');

                    // check if query exists
                    if (in_array($id, $ids)) {
                        foreach (EmailStorage::getInstance()->getAllWhichHasOne($id, QueryStorage::getInstance()) as $data) {
                            $output->writeln(sprintf('<info>%s</info>', EmailStorage::formatEmail($data)));
                        }
                    } else {
                        $output->writeln("Query with $id not found");
                    }
                });
    }

    private function registerSubscribe() {
        $command = $this->register('subscribe');

        // set description
        $command->setDescription('Adds/updates email related to query');

        // set arguments
        $command->setDefinition(array(
            new InputArgument('email', InputArgument::REQUIRED, 'Email address'),
            new InputArgument('id', InputArgument::REQUIRED, 'Query identifier'),
            new InputOption('message', null, InputOption::VALUE_REQUIRED, 'Email message (first line)'),
        ));


        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    // get and store ids
                    $ids = QueryStorage::getInstance()->getIds();

                    // get argument id
                    $id = $input->getArgument('id');

                    // get argument email
                    $email = $input->getArgument('email');

                    // email storage
                    $emailStorage = EmailStorage::getInstance();

                    // validate email
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $output->writeln("$email is not valid");
                        return;
                    }

                    // check if query exists
                    if (in_array($id, $ids)) {
                        $key = $id . ',' . $email;

                        // get email keys
                        $emailIds = $emailStorage->getIds();

                        // set data
                        $emailStorage->set($data = EmailStorage::createEmail($email, $input->getOption('message')), $key);

                        // set relations
                        $emailStorage->hasOne($key, QueryStorage::getInstance(), $id);

                        // save
                        $emailStorage->save();

                        // output
                        $output->writeln(sprintf('%s email: <info>%s</info> to database (Query: <info>%s</info>)', in_array($key, $emailIds) ? 'Updated' : 'Added', EmailStorage::formatEmail($data), $id));
                    } else {
                        $output->writeln("Query with $id not found");
                    }
                });
    }

    private function registerUnsubscribe() {
        $command = $this->register('unsubscribe');

        // set description
        $command->setDescription('Deletes email related to query');

        // set arguments
        $command->setDefinition(array(
            new InputArgument('email', InputArgument::REQUIRED, 'Email address'),
            new InputArgument('id', InputArgument::REQUIRED, 'Query identifier'),
        ));


        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    // get argument id
                    $id = $input->getArgument('id');

                    // get argument email
                    $email = $input->getArgument('email');

                    // email storage
                    $emailStorage = EmailStorage::getInstance();

                    // get key
                    $key = $id . ',' . $email;

                    // get email keys
                    $emailIds = $emailStorage->getIds();

                    // check if query exists
                    if (in_array($key, $emailIds)) {

                        // set data
                        $emailStorage->delete($key);

                        // save
                        $emailStorage->save();

                        // output
                        $output->writeln(sprintf('%s email unsubscribed (from query %s)', $email, $id));
                    } else {
                        $output->writeln("Email $email related to query $id not found");
                    }
                });
    }

    private function registerRun() {
        $command = $this->register('run');

        // set description
        $command->setDescription('Runs query');

        // set arguments
        $command->setDefinition(array(
            new InputArgument('id', InputArgument::OPTIONAL, 'Query identifier'),
        ));


        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    // get argument id
                    $id = $input->getArgument('id');
                    
                    $setting = SettingsStorage::getInstance();
                        $code = $setting->getById('code');
                        $key = $setting->getById('key');

                        if (empty($code)) {
                            $code = 1;
                        } else {
                            $code = (int) $code['value'];
                        }

                        if (empty($key)) {
                            $output->writeln("Allegro web api key not set, use 'config key XXXXXX' to configure");
                            return;
                        } else {
                            $key = $key['value'];
                        }
                        

                    if (empty($id)) {
                        foreach (QueryStorage::getInstance()->getAll() as $id => $data) {
                            $newItems = (new RunQuery($id))->execute(new AllegroApi($key, $code));

                            $output->writeln("$id: $newItems new item(s) found");
                        }
                    } else {

                        // check if query exists
                        if (!in_array($id, QueryStorage::getInstance()->getIds())) {
                            $output->writeln("Query with id $id not found");
                            return;
                        }

                        $newItems = (new RunQuery($id))->execute(new AllegroApi($key, $code));

                        $output->writeln("$newItems new item(s) found");
                    }
                });
    }

    
    private function registerFilters() {
        $command = $this->register('filters');

        // set description
        $command->setDescription('Returns possible filters for query');

        // set arguments
        $command->setDefinition(array(
            new InputOption('filters', null, InputOption::VALUE_REQUIRED, 'Formatted filters: filter1=value1,value2;filter2=value3,value4'),
            new InputArgument('query', InputArgument::REQUIRED, 'Query'),
        ));


        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    // get argument id
                    $query = $input->getArgument('query');
                    
                    $setting = SettingsStorage::getInstance();
                    $code = $setting->getById('code');
                    $key = $setting->getById('key');

                    if (empty($code)) {
                        $code = 1;
                    } else {
                        $code = (int) $code['value'];
                    }

                    if (empty($key)) {
                        $output->writeln("Allegro web api key not set, use 'config key XXXXXX' to configure");
                        return;
                    } else {
                        $key = $key['value'];
                    }
                    
                    $filters = $input->getOption('filters');
                    if (!empty($filters)) {
                        $filters = AllegroFilters::formatFilters($filters);
                        //var_dump($filters);die;
                    }

                    $api = new AllegroApi($key, $code);
                    
                    // returns filters
                    foreach (RunQuery::getFilters($api, $query, $filters) as $filter) {
                        $output->writeln(
                            sprintf("<info>%-30s</info>\t%-30s\n%s\n",
                                "<info>{$filter['name']} " . ($filter['range'] ? '=[min,max]' : '') . "</info>",
                                $filter['description'],
                                implode(', ', array_map(function($item) { return "{$item['value']} - '{$item['name']}'"; }, $filter['values']))
                        ));
                    }
                    
                    
                });
    }
    
    private function registerCodes() {
        $command = $this->register('codes');

        // set description
        $command->setDescription('Shows country codes');

        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    $setting = SettingsStorage::getInstance();
                    $code = $setting->getById('code');
                    $key = $setting->getById('key');

                    if (empty($code)) {
                        $code = 1;
                    } else {
                        $code = (int) $code['value'];
                    }

                    if (empty($key)) {
                        $output->writeln("Allegro web api key not set, use 'config key XXXXXX' to configure");
                        return;
                    } else {
                        $key = $key['value'];
                    }

                    $api = new AllegroApi($key, $code);
                    $countries = $api->getCountryCodes();

                    $output->writeln(implode("\n", array_map(function ($item) {
                                                return $item->{"country-id"} . "\t" . $item->{"country-name"};
                                            }, $countries)));
                });
    }
    
    
    private function registerCategories() {
        $command = $this->register('categories');

        // set description
        $command->setDescription('Shows categories');

        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    $setting = SettingsStorage::getInstance();
                    $code = $setting->getById('code');
                    $key = $setting->getById('key');

                    if (empty($code)) {
                        $code = 1;
                    } else {
                        $code = (int) $code['value'];
                    }

                    if (empty($key)) {
                        $output->writeln("Allegro web api key not set, use 'config key XXXXXX' to configure");
                        return;
                    } else {
                        $key = $key['value'];
                    }

                    $api = new AllegroApi($key, $code);
                    $categories = $api->getCategories();
                    
                    $tree = [];
                    $names = [];
                    
                    
                    foreach ($api->getCategories() as $category) {
                        if (empty($tree[$category->{"cat-parent"}])) {
                            $tree[$category->{"cat-parent"}] = [];
                        }
                        $tree[$category->{"cat-parent"}][] = $category->{"cat-id"};
                        $names[$category->{"cat-id"}] = $category->{"cat-name"};
                    }
                    
                    $this->printCategoryTree($tree, $names);
                    
                });
    }
    
    private function printCategoryTree($tree, $names, $key = 0, $level = 0) {
        foreach ($tree[$key] as $sub) {
            echo sprintf("%12d %s\n", $sub, str_repeat("\t", $level) . $names[$sub]);
            if (isset($tree[$sub])) {
                $this->printCategoryTree($tree, $names, $sub, $level + 1);
            }
        }
    }

    private function registerConfig() {
        $command = $this->register('config');

        // set description
        $command->setDescription('Sets configuration (api key, country code)');

        // set arguments
        $command->setDefinition(array(
            new InputArgument('name', InputArgument::REQUIRED, 'Setting name'),
            new InputArgument('value', InputArgument::REQUIRED, 'Setting value'),
        ));


        // set action
        $command->setCode(function (InputInterface $input, OutputInterface $output) {

                    $name = $input->getArgument('name');

                    if (!in_array($name, ['key', 'code'])) {
                        $output->writeln("Config $name not supported");
                        return;
                    }

                    // store key
                    $value = $input->getArgument('value');
                    SettingsStorage::getInstance()->set(['value' => $value], $name);

                    SettingsStorage::getInstance()->save();

                    $output->writeln("Config $name stored witch value $value");
                });
    }

}