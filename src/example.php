<?php
use Lusitanian\GNUUnitsWrapper\UnitConverter;

spl_autoload_register(function ($classname) {
    $classname = ltrim($classname, "\\");
    preg_match('/^(.+)?([^\\\\]+)$/U', $classname, $match);
    $classname = str_replace("\\", "/", $match[1])
        . str_replace(["\\", "_"], "/", $match[2])
        . ".php";
    include_once $classname;
});

$unitConverter = new UnitConverter();
echo $unitConverter->convert('2 gigabytes', 'megabytes');
