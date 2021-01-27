<?php

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

die;


/*****************************************
 * Hole BTC-Währungen
 *****************************************/
$ticker = $api->prices();
$count = 0;
foreach($ticker as $symbol=>$value)
{
    if(
        (substr($symbol, -3) == "BTC")
        && $count<2
    )
    {
        $btcSymbols[$symbol]["name"] = $symbol;
        $btcSymbols[$symbol]["kurs"] = $value;
        $count++;
    }
}


/*****************************************
 * Change BTC-Währungen
 *****************************************/

foreach($btcSymbols as $value)
{
    $btcSymbol = $value["name"];

    // 24h
    $prevDay = $api->prevDay($btcSymbol);
    $btcSymbols[$btcSymbol]["24hChange"] = $prevDay['priceChangePercent'];

    #print_r($prevDay);
    #echo $btcSymbol." price change since yesterday: ".$prevDay['priceChangePercent']."%".PHP_EOL;

    // 1m 3m 5m 15m 30m 1h 2h 4h 6h 8h 12h 1d 3d 1w 1M
    $timekapsel = array("4h", "3d");
    
    foreach($timekapsel as $tDauer)
    {
        // customTime        
        $anzahl = 1;

        $ticks = $api->candlesticks($btcSymbol, $tDauer, $anzahl);
        #print_r(($ticks));    
        
        // Anfangswert = openPrice
        // Endwert = lastPrice
        // Change = Endwert - Anfangswert / Anfangswert * 100

        $change = ($btcSymbols[$btcSymbol]["kurs"] - current($ticks)["open"]) / current($ticks)["open"] * 100;
        #echo $btcSymbol." price change since ".$tDauer.": ".$change."%".PHP_EOL;

        $btcSymbols[$btcSymbol][$tDauer."Change"] = $change;
    }
    
    
    
}


/*****************************************
 * Wähle BTC-Währung
 *****************************************/
array_multisort(array_column($btcSymbols, '3dChange'), SORT_DESC, $btcSymbols);
foreach($btcSymbols as $symbol=>$values)
{
    if(
        ($values["3dChange"] > 0)
        && ($values["4hChange"] > 0)
        && ($values["24hChange"] > 0)
    ) {
        echo "Beste: ".$symbol; print_r($values);
        $buy = $symbol;
        break;
    }
}




 
#print_r($btcSymbols);


//array_multisort(array_column($btcSymbols, '24hChange'), SORT_DESC, array_column($btcSymbols, '1hChange'), SORT_DESC, $btcSymbols);


/*****************************************
 * Datenbank
 *****************************************/
require "config.php";

try {
    
    $connection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, $options);
    $sql = "SELECT * FROM orders WHERE buy IS NOT NULL AND sell IS NULL";
    $erg = $connection->prepare($sql);
    $erg->execute();

    echo "DB:".$erg->rowCount();
    $orderstatus = $erg->rowCount();

    if($orderstatus = 0)
    {
        $sql = "INSERT INTO orders (symbol, buy, buydate) VALUES (?,?,?)";
        $stmt = $connection->prepare($sql);
        $stmt->execute( [$btcSymbols[$symbol]["name"], $btcSymbols[$symbol]["kurs"], date('Y-m-d H:i:s')]); 
    } else 
    {
        echo "DB drin!";
    }

        
} catch(PDOException $error) {
    echo $sql . "<br>" . $error->getMessage();
    echo "Fehler";
}

print_r($btcSymbols);
echo "</pre>";