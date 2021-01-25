<?php

require 'vendor/autoload.php';

$api = new Binance\API( "binance.json" );

echo "<pre>";

/*****************************************
 * Hole BTC-Währungen
 *****************************************/
$ticker = $api->prices();
$count = 0;
foreach($ticker as $symbol=>$value)
{
    if(
        (substr($symbol, -3) == "BTC")
        && $count<1
    )
    {
        $btcSymbols[] = $symbol;
        $count++;
    }
}

print_r($btcSymbols);


/*****************************************
 * Suche BTC-Währungen
 *****************************************/
foreach($btcSymbols as $btcSymbol)
{
    $prevDay = $api->prevDay($btcSymbol);
    print_r($prevDay);
    echo $btcSymbol." price change since yesterday: ".$prevDay['priceChangePercent']."%".PHP_EOL;

    $ticks = $api->candlesticks($btcSymbol, "1h", 1);
    print_r(current($ticks));

    // Anfangswert = openPrice
    // Endwert = lastPrice
    // Change = Endwert - Anfangswert / Anfangswert * 100


    echo $btcSymbol." price change since 1h: ".$ticks[0]["close"]."%".PHP_EOL;
}


echo "</pre>";