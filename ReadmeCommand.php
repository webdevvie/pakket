<?php

namespace Webdevvie\Pakket;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReadmeCommand
 * @package Webdevvie\Pakket
 */
class ReadmeCommand extends Command
{
    /**
     * @return  void
     */
    protected function configure()
    {
        $this
            ->setName('readme')
            ->setDescription('displays the README.MD file');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $content = file_get_contents(__DIR__ . '/README.md');
        //todo replace md markup to console markup at some point
        $output->writeln("<info>" . $content . "</info>");
        return self::SUCCESS;
    }
}
