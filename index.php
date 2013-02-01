<?php
// use \Sixpack\Sixpack;
include('lib/Sixpack.php');

// Next, make sure Sixpack can load internal classes
// Not necessary if you have a PSR-0 compatible autoloader
Sixpack::register_autoloader();

$sp = new Sixpack();
$sp->setExperimentName('show-bieber');
//$sp->setAlternatives(array('yes', 'no'));
// only if we're forcing it. Otherwise, just rock and roll.
$sp->setClientId('704D99DD-BF42-E4C8-53D1');

$response = $sp->convert();
var_dump($response);
// $alt = Sixpack::simple_participate('show-bieber', array('trolled', 'nottrolled'));
