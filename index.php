<?php

include_once 'NDFDXMLClient.php';

$ndfdClient = new \Weather\Library\NDFDXMLClient(19134, time());
$ndfdClient->setLatLon();

$res = $ndfdClient->getSinglePoint();

var_dump(json_decode($res));