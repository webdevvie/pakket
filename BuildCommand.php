<?php

namespace Webdevvie\Pakket;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BuildCommand
 * @package Webdevvie\Pakket
 */
class BuildCommand extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds a phar package')
            ->setHelp('With this command you can create a phar package');
        $this->addArgument("directory", InputArgument::REQUIRED, "the directory to work in");
        $this->addArgument("target", InputArgument::OPTIONAL, "the target phar file", null);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(file_get_contents(__DIR__ . '/logo.txt'));
        $targetFile = $input->getArgument('target', null);
        $directory = $input->getArgument('directory');
        $realpath = realpath($directory);
        $output->writeln("<comment>Looking at " . $directory . "</comment>");
        if (!is_dir($realpath)) {
            $output->writeln("<error>Directory $directory does not exist</error>");
            return;
        }
        $config = [
            "targetFile" => $targetFile,
        ];
        $configFile = [];
        if (file_exists($realpath . '/pakket.json')) {
            $output->writeln("<comment>Using pakket.json</comment>");
            $configFile = json_decode(file_get_contents($realpath . '/pakket.json'), true);
            if (is_null($configFile)) {
                $output->writeln("<error>Invalid pakket.json</error>");
                return;
            }
        }
        $config = array_merge($config, $configFile);
        $builder = new Builder($output);
        $builder->build($realpath, $targetFile, $config);
    }
}
