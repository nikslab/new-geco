<?php

require_once "config.php";

try {
    $pdo = new PDO("mysql:host=$DB_SERVER;dbname=$DB_NAME", $DB_USER, $DB_PASSWORD);
    // set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logThis(5, "Connected to database successfully");
} catch(PDOException $e) {
    logThis(1, "Connection to database failed: " . $e->getMessage());
}

function dbInsert($sql) {
    global $pdo;

    $statement = $pdo->prepare($sql);
    $statement->execute();
}

function dbSelect($sql) {
    global $pdo;

    $data = $pdo->query($sql)->fetchAll();
    return $data;
}

function dbTransaction($transaction) {
    global $pdo;

    $pdo->beginTransaction();
    foreach($transaction as $statement) {
        $statement = $pdo->prepare($statement);
        $statement->execute();
    }
    $pdo->commit();
}

function dbClose() {
    $conn = null;
}

function logThis($level, $msg) {
    global $LOG_FILE;
    global $LOG_LEVEL;

    if ($level <= $LOG_LEVEL) {
        $now = date('Y-m-d H:i:s');
        $entry = "$now [$level] $msg\n";
        $fp = fopen($LOG_FILE, 'a');
        fwrite($fp, $entry);
        fclose($fp);
    }
}

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