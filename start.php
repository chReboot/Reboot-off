<?php

require 'vendor/autoload.php';

$api = new Binance\API( "binance.json" );

echo "<pre>";


$ticker = $api->prices();
print_r($ticker);






echo "</pre>";