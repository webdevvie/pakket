<?php

namespace Webdevvie\Pakket;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Builder
 * @package Webdevvie\Pakket
 */
class Builder
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Builder constructor.
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param string $type
     * @param string $path
     * @param string $targetPath
     * @param array $config
     * @return void
     */
    private function handleRunCommands($type, $path, $targetPath, array $config = [])
    {
        $var = $type . 'Run';
        if (isset($config[$var]) && is_array($config[$var])) {
            $runs = $config[$var];
            foreach ($runs as $cmd) {
                if (!is_string($cmd)) {
                    continue;
                }
                $cmd = str_replace("{{PHPBIN}}", PHP_BINARY, $cmd);
                $cmd = str_replace("{{TARGETPATH}}", $targetPath, $cmd);
                $cmd = "cd $path; " . $cmd;
                $this->output->writeln("executing $cmd");
                $this->output->writeln(shell_exec($cmd));
            }
        }
    }

    /**
     * @param string $path
     * @param string $targetPath
     * @param null|array $config
     * @return boolean
     */
    public function build($path, $targetPath = '', $config = null)
    {


        $d = DIRECTORY_SEPARATOR;
        if (!is_dir($path)) {
            $this->output->writeln("<error>No valid path defined defined!</error>");
        }
        $path = realpath($path);
        $defaultConfig = [
            "gzip" => false,
            "buffer" => false,
            "stubFile" => __DIR__ . '/defaultStub',
            "stub" => "",
            "sources" => ["" => ""],
            "sourcePath" => $path,
            "exclude" => [],
            "parse" => [],
            "vars" => [
                "PHARFILE" => ""
            ]
        ];
        if (is_null($config) || !is_array($config)) {
            $config = [
                'targetPath' => $targetPath
            ];
        } elseif ($targetPath != '') {
            $config['targetPath'] = $targetPath;
        }
        $config = array_merge($defaultConfig, $config);
        $targetPath = $config['targetPath'];
        $phpversion = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
        $targetPath = str_replace("{{PHPVERSION}}", $phpversion, $targetPath);

        $parts = explode($d, $targetPath);
        if ($config['targetPath'] == '') {
            $this->output->writeln("<error>No target file defined!</error>");
            return false;
        }
        if (file_exists($config['targetPath'])) {
            unlink($config['targetPath']);
        }
        $this->handleRunCommands('pre', $path, $targetPath, $config);
        $filename = array_pop($parts);

        $config['vars']['PHARFILE'] = $filename;
        $files = [];
        foreach ($config['sources'] as $source => $targetinfo) {
            $this->output->writeln("Looking at source '$source'");
            if (is_dir($path . $d . $source)) { //source below path
                $this->getFiles($path . $d . $source, $targetinfo, $files);
            } elseif (is_dir($source)) { //hard coded path
                $this->getFiles($source, $targetinfo, $files);
            }
        }
        $pharFile = new \Phar($targetPath, 0, $filename);
        $pharFile->setSignatureAlgorithm(\Phar::SHA512);
        if (isset($config['index'])) {
            if (isset($config['indexWeb'])) {
                $config['stub'] = $pharFile->createDefaultStub($config['index'], $config['indexWeb']);
            } else {
                $config['stub'] = $pharFile->createDefaultStub($config['index']);
            }
            $stub = "#!/usr/bin/env php\n" . $config['stub'];
        } else {
            if ($config['stub'] == '' && $config['stubFile'] != '') {
                if (file_exists($path . $d . $config['stubFile'])) {
                    $this->output->writeln("Using stub file: " . $path . $d . $config['stubFile']);
                    $config['stub'] = file_get_contents($path . $d . $config['stubFile']);
                } elseif (file_exists($config['stubFile'])) {
                    $this->output->writeln("Using stub file: " . $config['stubFile']);
                    $config['stub'] = file_get_contents($config['stubFile']);
                } else {
                    $this->output->writeln("<error>No stub or stubFile defined</error>");
                    return false;
                }
            } elseif ($config['stub'] == '') {
                $this->output->writeln("<error>No stub or stubFile defined</error>");
                return false;
            } else {
                $this->output->writeln("<error>No stub or stubFile defined</error>");
            }

            $stub = $config['stub'];
            $stub = str_replace("{{PHARFILE}}", $filename, $stub);
        }


        if ($config['gzip']) {
            $this->output->writeln("<info>Compressing files with Gzip</info>");
            $pharFile->compressFiles(\Phar::GZ);
        }
        if ($config['buffer']) {
            $this->output->writeln("<info>Buffering</info>");
            $pharFile->startBuffering();
        }
        ksort($files);
        $queue = [];
        foreach ($files as $file => $fileData) {
            if (!$this->shouldKeep($file, $config)) {
                continue;
            }
            if ($fileData['type'] == 'dir') {
                $this->output->writeln("<info>Adding dir $file</info>");
                $pharFile->addEmptyDir($file);
            } elseif ($fileData['type'] == 'file') {
                if ($this->shouldParse($file, $config)) {
                    $this->output->writeln("<info>Adding parsed file $file</info>");
                    $pharFile->addFromString(
                        $file,
                        $this->parse(file_get_contents($fileData['source']), $config['vars'])
                    );
                } else {
                    $this->output->writeln("<info>Queueing file $file</info>");
                    $queue[$file] = $fileData['source'];
                }
            }
            if (count($queue) >= 500) {
                $this->output->writeln("<info>Adding Queued files(" . count($queue) . ")</info>");
                $pharFile->buildFromIterator(
                    new \ArrayIterator($queue)
                );
                $queue = [];
            }
        }
        if (count($queue) >= 0) {
            $this->output->writeln("<info>Adding Queued files(" . count($queue) . ")</info>");
            $pharFile->buildFromIterator(
                new \ArrayIterator($queue)
            );
            $queue = [];
        }
        $pharFile->addFile($fileData['source'], $file);
        $pharFile->addFromString('packageInfo', "Packaged with Pakket!");
        $this->output->writeln("<info>Writing stub</info>");
        $pharFile->setStub($stub);
        if ($config['buffer']) {
            $this->output->writeln("<info>Writing file</info>");
            $pharFile->stopBuffering();
        }
        $this->handleRunCommands($path, $targetPath, $config);
        return true;
    }

    /**
     * @param string $dir
     * @return boolean|null
     */
    private function isDirEmpty($dir)
    {
        return (count(scandir($dir)) == 2);
    }

    /**
     * @param string $content
     * @param array $vars
     * @return mixed
     */
    public function parse($content, array &$vars)
    {
        foreach ($vars as $var => $val) {
            $content = str_replace("{{" . $var . "}}", $val, $content);
        }
        return $content;
    }

    /**
     * @param string $file
     * @param array $config
     * @return boolean
     */
    public function shouldKeep($file, array &$config)
    {
        foreach ($config['exclude'] as $exclude) {
            if (preg_match($exclude, $file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $file
     * @param array $config
     * @return boolean
     */
    public function shouldParse($file, array &$config)
    {
        foreach ($config['parse'] as $parse) {
            if (preg_match($parse, $file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $source
     * @param string|array $targetInfo
     * @param array $files
     * @return void
     */
    public function getFiles($source, $targetInfo, array &$files)
    {
        $d = DIRECTORY_SEPARATOR;
        $directory = new \RecursiveDirectoryIterator($source);
        $ittr = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($ittr as $name => $obj) {
            $parts = explode($d, $name);
            $lp = array_pop($parts);
            if ($lp == '.' || $lp == '..') {
                continue;
            }
            if (is_dir($name)) {
                if ($this->isDirEmpty($name)) {
                    $info = ['type' => 'dir'];
                } else {
                    continue;
                }
            } else {
                $info = ["type" => 'file'];
            }
            $target = str_replace($source, $targetInfo, $name);
            $info['source'] = $name;
            $files[$target] = $info;
        }
    }
}
