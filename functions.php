<?php

require_once "config.php";

// Database connection, always
try {
    $pdo = new PDO("mysql:host=$DB_SERVER;dbname=$DB_NAME", $DB_USER, $DB_PASSWORD);
    // set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logThis(5, "Connected to database successfully");
} catch(PDOException $e) {
    logThis(1, "Connection to database failed: " . $e->getMessage());
}

/**************************************** 
    DATABASE
*****************************************/

function dbSelect($sql) {
    global $pdo;

    $data = $pdo->query($sql)->fetchAll();
    return $data;
}

function dbInsert($sql) {
    global $pdo;

    $statement = $pdo->prepare($sql);
    $statement->execute();
}

function dbTransaction($transaction) {
    global $pdo;

    try {
        $pdo->beginTransaction();
        foreach($transaction as $statement) {
            $statement = $pdo->prepare($statement);
            $statement->execute();
        }
        $pdo->commit();
    } catch(PDOException $e) {
        print "Transaction failed..." . $e->getMessage() . " here is the transaction:\n";
        var_dump($transaction);
        logThis(0, "Database transaction failed! Error: ". $e->getMessage());
    }
}

function dbClose() {
    global $pdo;
    $pdo = null;
}

/**************************************** 
    LOGGING
*****************************************/

function logThis($level, $msg) {
    global $LOG_FILE, $LOG_LEVEL, $LOG_PRINT;

    $script = basename($_SERVER['PHP_SELF'], '.php');

    if ($level <= $LOG_LEVEL) {
        $now = date('Y-m-d H:i:s');
        $entry = "$now $script [$level] $msg\n";
        if ($level < 1) {
            $entry .= "$now $script [$level] ...exiting\n";
            print "$entry";
        }    
        $fp = fopen($LOG_FILE, 'a');
        fwrite($fp, $entry);
        fclose($fp);
        if ($LOG_PRINT) {
            print "$entry";
        }
    }
    if ($level < 1) {
        exit(0);
    }
}

/**************************************** 
    HELPERS 
*****************************************/

function enumBinary($digits) {
    $result = [];
    $max = bindec(str_pad('', $digits, '1', STR_PAD_LEFT));
    for ($i=0; $i<=$max; $i++) {
        $x = decbin($i);
        $y = str_pad($x, $digits, '0', STR_PAD_LEFT);
        $result[] = $y;
    }
    return $result;
}

function enumMemory($moves) {
    $result = [];
    for ($i=1; $i<=$moves; $i++) {
        $j= $i*2;
        $x = enumBinary($j);
        $result = array_merge($result, $x);
    }
    return $result;
}

function randomScores($experiment_id, $generation) {
    global $pdo;

    $sql = "
        SELECT id 
        FROM bots 
        WHERE experiment_id=$experiment_id 
          AND generation=$generation
          AND score IS NULL
    ";
    $bots = dbSelect($sql);
    $num = count($bots);

    $transaction = [];
    for ($i=0; $i<$num; $i++) {
        $score = rand(0, 500) / 100;
        $id = $bots[$i]['id'];
        $sql = "UPDATE bots SET score=$score WHERE id=$id;";
        $transaction[] = $sql;
    }
    dbTransaction($transaction);
}

function loadGeneration($experiment_id, $generation) {
    $sql = "
        SELECT bot_id, gene, allele 
        FROM genes 
        WHERE experiment_id=$experiment_id and generation=$generation
    ";
    $pop1 = [];
    $pop1 = dbSelect($sql);

    $pop = [];
    if (count($pop1) < 1) {
        $pop = false;
    } else {
        foreach($pop1 as $p) {
            $bot_id = $p['bot_id'];
            $gene = $p['gene'];
            $allele = $p['allele'];
            $pop[$bot_id][$gene]=$allele;
        }
    }
    return $pop;
}

function getExperimentOptions($experiment_id) {
    $sql = "SELECT experiment_options FROM experiments WHERE id=$experiment_id";
    $result = dbSelect($sql);
    $options = json_decode($result[0]['experiment_options'], true);
    return $options;
}