<?php
ini_set('max_execution_time', '300');
require 'vendor/autoload.php';
require 'Logik.php';

echo "<pre>";

$logik = new Logik();

if ($logik->getOrderDB() == 0)
{ 
    $logik->getBTCSymbols();
    $logik->berechneChange();
    if ($logik->selectSymbol() == true)    
    {
        $logik->buySymbol();
    }   

} else 
{
    if ($logik->sellCheck() == true)
    {
        $logik->sellSymbol();
    }        
}


echo "Ende".PHP_EOL;
echo "</pre>";