<?php

require_once "config.php";

try {
    $pdo = new PDO("mysql:host=$DB_SERVER;dbname=$DB_NAME", $DB_USER, $DB_PASSWORD);
    // set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully\n";
} catch(PDOException $e) {
    echo "Connection to database failed: " . $e->getMessage() . "\n";
}

function run_transaction($transaction) {

    $pdo->beginTransaction();
    foreach($transaction as $statement) {
        
    }
    $pdo->commit();

}

function close_database() {
    $conn = null;
}