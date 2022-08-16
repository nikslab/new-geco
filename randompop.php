#!/usr/bin/php
<?php

require_once "functions.php";

$experiment_id = $argv[1];


// Create bot
$uniq = uniqid();
$sql = "INSERT INTO bots (bot_uniq, created_at, experiment_id) VALUES ";

// Get bot_id
$sql = "SELECT id FROM bots WHERE bot_uniq='$uniq'";
$bot_id = 0;

// Generate DNA
$DNA = randomDNA(10, 1, 5, 0.1);

// Create transaction for genes
$transaction = [];
foreach ($DNA as $g) {
    $gene = $g[0];
    $allele = $g[1];
    $sql = "INSERT INTO genes (bot_id, gene, allele) VALUES ($bot_id, '$gene', '$allele');";
    $transaction[] = $sql;
}
//runDBtransaction($transaction);
var_dump($transaction);

function randomGene($minlen, $maxlen, $coop) {
    
    if ($minlen < 1) { $minlen = 1; }
    $gene = [];
    
    // Generate pattern
    $pattern = "";
    $len = round(rand($minlen, $maxlen));
    for ($i=1; $i<=$len; $i++) {
        $bit = rand(0,1);
        $pattern .= $bit;
    }
    array_push($gene, $pattern);

    // Pick response
    $die = rand(0,100);
    $response= 0;
    if ($die <= ($coop*100)) {
        $response = 1;
    }
    array_push($gene, $response);

    return $gene;
}

function randomDNA($numgenes, $minlen, $maxlen, $coop) {
    $DNA = [];
    for ($i=1; $i<=$numgenes; $i++) {
        $gene = randomGene($minlen, $maxlen, $coop);
        $DNA[] = $gene;
    }
    return $DNA;
}