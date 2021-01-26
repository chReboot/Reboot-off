<?php

require 'vendor/autoload.php';

$api = new Binance\API( "binance.json" );

echo "<pre>";

$btcSymbols = array();

/*****************************************
 * Hole BTC-Währungen
 *****************************************/
$ticker = $api->prices();
$count = 0;
foreach($ticker as $symbol=>$value)
{
    if(
        (substr($symbol, -3) == "BTC")
        && $count<15
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
    echo $btcSymbol." price change since yesterday: ".$prevDay['priceChangePercent']."%".PHP_EOL;

    // 1m 3m 5m 15m 30m 1h 2h 4h 6h 8h 12h 1d 3d 1w 1M
    // customTime
    $dauer = "4h";
    $anzahl = 1;

    $ticks = $api->candlesticks($btcSymbol, $dauer, $anzahl);
    #print_r(($ticks));    

    
    // Anfangswert = openPrice
    // Endwert = lastPrice
    // Change = Endwert - Anfangswert / Anfangswert * 100

    $change = ($btcSymbols[$btcSymbol]["kurs"] - current($ticks)["open"]) / current($ticks)["open"] * 100;
    echo $btcSymbol." price change since ".$dauer.": ".$change."%".PHP_EOL;

    $btcSymbols[$btcSymbol][$dauer."Change"] = $change;
    
    
}


/*****************************************
 * Wähle BTC-Währung
 *****************************************/


 
#print_r($btcSymbols);

array_multisort(array_column($btcSymbols, '24hChange'), SORT_DESC, $btcSymbols);
//array_multisort(array_column($btcSymbols, '24hChange'), SORT_DESC, array_column($btcSymbols, '1hChange'), SORT_DESC, $btcSymbols);


print_r($btcSymbols);
echo "</pre>";