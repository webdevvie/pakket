<?php

$fn = __DIR__.'/currentversion';
$version = str_replace("\n","",file_get_contents($fn));
$parts = explode(".",$version);

if(!isset($argv[1]))
{
    $argv[1]='maintenance';
}

$pos = 1;

switch($argv[1])
{
    case 'build':
        $pos = 3;

        break;
    default:
    case 'maintenance':
        $pos = 2;

        break;
    case 'minor':
        $pos= 1;
        break;
    case 'major';
        $pos = 0;

}
$parts[$pos]++;
for($p=$pos;$p<count($parts);$p++)
{
    if($p!=$pos)
    {
        $parts[$p]=0;
    }
}



file_put_contents($fn,implode(".",$parts));