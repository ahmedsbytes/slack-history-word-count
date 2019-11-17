#!/usr/bin/env php
<?php
// application.php
require __DIR__.'/vendor/autoload.php';

use SlackHistoryWordCount\MainCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveEnvPlaceholdersPass;
use Symfony\Component\DependencyInjection\Compiler\ValidateEnvPlaceholdersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
$container = new ContainerBuilder();
$yamlFileLoader = new YamlFileLoader(
    $container,
    new FileLocator(__DIR__ . DIRECTORY_SEPARATOR . 'config')
);
$yamlFileLoader->load('services.yaml');
$container
    ->addCompilerPass(new AddConsoleCommandPass())
    ->addCompilerPass(new ValidateEnvPlaceholdersPass())
    ->addCompilerPass(new ResolveEnvPlaceholdersPass())
    ->compile()
;
$app = new Application();
$app->setDispatcher($container->get('event_dispatcher'));
$app->setCommandLoader($container->get('console.command_loader'));
$app->setDefaultCommand(MainCommand::getDefaultName(), true);
$app->run();
