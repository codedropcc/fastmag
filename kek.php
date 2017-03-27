<?php

require_once('vendor/autoload.php');

$fastmag = new Fastmag\Fastmag();

var_dump($fastmag->getModel('Fastmag\AttributeHelper'));
