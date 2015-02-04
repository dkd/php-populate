<?php

require realpath(__DIR__ . '/../../vendor/autoload.php');

$array = array();
$data = array(
    'property1' => 'test1',
    'property2' => 'test2',
    'boolean' => true
);

$start = microtime(true);

for ($i=0; $i<100000; $i++) {
    $populatable = new \Dkd\Populate\Tests\Fixtures\PopulateDummy();
    $populatable->setProperty1($data['property1']);
    $populatable->setProperty2($data['property2']);
    $populatable->setBoolean($data['boolean']);
}

$usingSetters = microtime(true) - $start;

echo '100,000 iterations with a three-property data set, duration in seconds:' . PHP_EOL;

echo '--> With standard setters:            ' . number_format($usingSetters, 4) . PHP_EOL;


$start = microtime(true);

for ($i=0; $i<100000; $i++) {
    $populatable = new \Dkd\Populate\Tests\Fixtures\PopulateDummy();
    $populatable->populate($data);
}

$usingPopulate = microtime(true) - $start;

echo '--> With PopulateTrait->populate():   ' . number_format($usingPopulate, 4) . PHP_EOL;

echo '--> Difference factor:                ' . number_format($usingPopulate / $usingSetters, 1) . 'x' . PHP_EOL;
