<?php
// Basisklasse


class Logik {

    private $dbcon;
    private $btcSymbols = array();
    private $api;
    private $selectedSymbol;
    private $selectedId;
    private $selectedKurs;

    private $anzahlSymbols = 9;
    private $changeTarget = 2;

    public function __construct()
    {
        // DB Verbindung
        require "config.php";
        $this->dbcon = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, $options);

        // API
        $this->api = new Binance\API( "binance.json" );
    }

    public function getOrderDB ()
    {
        echo "getOrderDB".PHP_EOL;
        try 
        {
            $sql = "SELECT * FROM orders WHERE buy IS NOT NULL AND sell IS NULL LIMIT 1";
            $handle = $this->dbcon->prepare($sql);
            $handle->execute();

            $result = $handle->fetch(PDO::FETCH_ASSOC);
            $this->selectedId = $result["id"];
            $this->selectedSymbol = $result["symbol"];
            $this->selectedKurs = $result["buy"];
        } catch(PDOException $error) 
        {
            echo $sql . "<br>" . $error->getMessage(); 
            return false;            
        }
        return $orderstatus = $handle->rowCount();        
    }

    function getBTCSymbols()
    {
        echo "getBTCSymbols".PHP_EOL;
        $ticker = $this->api->prices();
        $count = 0;
        foreach($ticker as $symbol=>$value)
        {
            if(
                (substr($symbol, -3) == "BTC")
                && $count < $this->anzahlSymbols
            )
            {
                $this->btcSymbols[$symbol]["name"] = $symbol;
                $this->btcSymbols[$symbol]["kurs"] = $value;
                $count++;
            }
        }
        return true;
    }

    function berechneChange()
    {
        echo "berechneChange".PHP_EOL;
        foreach($this->btcSymbols as $value)
        {
            $btcSymbol = $value["name"];

            // 24h
            $prevDay = $this->api->prevDay($btcSymbol);
            $this->btcSymbols[$btcSymbol]["24hChange"] = $prevDay['priceChangePercent'];

            #print_r($prevDay);
            #echo $btcSymbol." price change since yesterday: ".$prevDay['priceChangePercent']."%".PHP_EOL;

            // 1m 3m 5m 15m 30m 1h 2h 4h 6h 8h 12h 1d 3d 1w 1M
            $timekapsel = array("4h", "3d");
            
            foreach($timekapsel as $tDauer)
            {
                // customTime        
                $anzahl = 1;

                $ticks = $this->api->candlesticks($btcSymbol, $tDauer, $anzahl);
                #print_r(($ticks));    
                
                // Anfangswert = openPrice
                // Endwert = lastPrice
                // Change = Endwert - Anfangswert / Anfangswert * 100

                $change = ($this->btcSymbols[$btcSymbol]["kurs"] - current($ticks)["open"]) / current($ticks)["open"] * 100;
                #echo $btcSymbol." price change since ".$tDauer.": ".$change."%".PHP_EOL;

                $this->btcSymbols[$btcSymbol][$tDauer."Change"] = $change;
            }            
        }
        return true;
    }

    function selectSymbol()
    {
        echo "selectSymbol".PHP_EOL;
        array_multisort(array_column($this->btcSymbols, '3dChange'), SORT_DESC, $this->btcSymbols);
        foreach($this->btcSymbols as $symbol=>$values)
        {print_r($values);
            if(
                ($values["3dChange"] > 0)
                && ($values["4hChange"] > 0)
                && ($values["24hChange"] > 0)
            ) {
                echo "Beste: ".$symbol; 
                $this->selectedSymbol = $symbol;
                return true;                
            }            
        }
        return false;
    }

    function buySymbol() 
    {
        echo "buySymbol".PHP_EOL;

        // BUY


        // DB Log
        try {
            $sql = "INSERT INTO orders (symbol, buy, buydate) VALUES (?,?,?)";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->execute( [
                            $this->btcSymbols[$this->selectedSymbol]["name"], 
                            $this->btcSymbols[$this->selectedSymbol]["kurs"], 
                            date('Y-m-d H:i:s')
                            ]);
        } catch(PDOException $error) 
        {
            echo $sql . "<br>" . $error->getMessage(); 
            return false;
        }
        return true;
    }

    function sellSymbol() 
    {
        echo "sellSymbol".PHP_EOL;

        // SELL


        // DB Log
        echo "DBid:".$this->selectedId.PHP_EOL;
        try {
            $sql = "UPDATE orders SET (symbol, sell, selldate) VALUES (?,?,?) WHERE id LIKE ?";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->execute( [
                            $this->btcSymbols[$this->selectedSymbol]["name"], 
                            $this->btcSymbols[$this->selectedSymbol]["kurs"], 
                            date('Y-m-d H:i:s'),
                            $this->selectedId
                            ]);
        } catch(PDOException $error) 
        {
            echo $sql . "<br>" . $error->getMessage(); 
            return false;
        }
        return true;
    }

    function sellCheck()
    {
        echo "sellCheck".PHP_EOL;        
        $price = $this->api->price($this->selectedSymbol);        

        // Anfangswert = openPrice
        // Endwert = lastPrice
        // Change = Endwert - Anfangswert / Anfangswert * 100
        echo $change = ($price - $this->selectedKurs) / $this->selectedKurs * 100;
        echo PHP_EOL;

        if ($change > $this->changeTarget)
        {
            return true;
        }
        return false;
    }
}

