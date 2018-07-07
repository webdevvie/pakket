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
     * @param string     $path
     * @param string     $targetPath
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
            "gzip" => true,
            "buffer" => true,
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
        $parts = explode($d, $targetPath);
        if ($config['targetPath'] == '') {
            $this->output->writeln("<error>No target file defined!</error>");
            return false;
        }
        if (file_exists($config['targetPath'])) {
            unlink($config['targetPath']);
        }
        $filename = array_pop($parts);
        if ($config['stub'] == '' && $config['stubFile'] != '') {
            if (file_exists($path . $d . $config['stubFile'])) {
                $config['stub'] = file_get_contents($path . $d . $config['stubFile']);
            } elseif (file_exists($config['stubFile'])) {
                $config['stub'] = file_get_contents($config['stubFile']);
            } else {
                $this->output->writeln("<error>No stub or stubFile defined</error>");
                return false;
            }
        } elseif ($config['stub'] == '') {
            $this->output->writeln("<error>No stub or stubFile defined</error>");
            return false;
        }
        $stub = $config['stub'];
        $stub = str_replace("{{PHARFILE}}", $filename, $stub);
        $config['vars']['PHARFILE'] = $filename;

        $files = [];
        foreach ($config['sources'] as $source => $targetinfo) {
            $this->output->writeln("Looking at source '$source'");
            if (is_dir($path . $d . $source)) { //source below path
                $this->output->writeln("Rewritten to '" . $path . $d . $source . "'");
                $this->getFiles($path . $d . $source, $targetinfo, $files);
            } elseif (is_dir($source)) { //hard coded path
                $this->output->writeln("Rewritten to '" . $source . "'");
                $this->getFiles($source, $targetinfo, $files);
            }
        }
        $pharFile = new \Phar($targetPath, 0, $filename);
        $pharFile->setSignatureAlgorithm(\Phar::SHA512);
        if ($config['gzip']) {
            $this->output->writeln("<info>Compressing files with Gzip</info>");
            $pharFile->compressFiles(\Phar::GZ);
        }
        if ($config['buffer']) {
            $this->output->writeln("<info>Buffering</info>");
            $pharFile->startBuffering();
        }
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
                    $this->output->writeln("<info>Adding file $file</info>");
                    $pharFile->addFile($fileData['source'], $file);
                }
            }
        }

        $pharFile->addFromString('packageInfo', "Packaged with Pakket!");
        $this->output->writeln("<info>Writing stub</info>");
        $pharFile->setStub($stub);
        if ($config['buffer']) {
            $this->output->writeln("<info>Writing file</info>");
            $pharFile->stopBuffering();
        }
        return true;
    }

    /**
     * @param string $content
     * @param array  $vars
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
     * @param array  $config
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
     * @param array  $config
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
     * @param string       $source
     * @param string|array $targetInfo
     * @param array        $files
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
                $info = ['type' => 'dir'];
            } else {
                $info = ["type" => 'file'];
            }
            $target = str_replace($source, $targetInfo, $name);
            $info['source'] = $name;
            $files[$target] = $info;
        }
    }
}
