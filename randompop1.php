#!/usr/bin/php
<?php

require_once "functions.php";

$experiment_id = $argv[1];

// Read experiment options
$sql = "SELECT experiment_options FROM experiments WHERE id=$experiment_id";
$result = dbSelect($sql);
$options = json_decode($result[0]['experiment_options'], true);
$memory = $options['memory'];
$coop = $options['cooperative'];
$population = $options['population'];

$transaction = [];

for ($pop=1; $pop<=$population; $pop++) {

    print "$pop/$population    \r";
    
    // Create bot
    $uniq = uniqid();
    $sql = "
        INSERT INTO bots (
            bot_uniq, 
            created_at, 
            experiment_id, 
            generation
        ) VALUES (
            '$uniq', 
            NOW(), 
            $experiment_id,
            '0'
        )
    ";
    dbInsert($sql);

    // Get bot_id
    $sql = "SELECT id FROM bots WHERE bot_uniq='$uniq'";
    $result = dbSelect($sql);
    $bot_id = $result[0]['id'];

    // Generate DNA
    $genes = [];
    $a[0] = "";
    $b = enumMemory($memory);
    $genes = array_merge($a, $b);

    $DNA = [];
    foreach ($genes as $gene) {
        // Pick response
        $die = rand(0,100);
        $allele= 0;
        if ($die <= ($coop*100)) {
            $allele = 1;
        }
        $pack[0]=$gene;
        $pack[1]=$allele;
        $DNA[] = $pack;
    }

    // Create transaction for genes
    foreach ($DNA as $g) {
        $gene = $g[0];
        $allele = $g[1];
        $sql = "INSERT INTO genes (bot_id, gene, allele) VALUES ($bot_id, '$gene', '$allele');";
        $transaction[] = $sql;
    }
}
dbTransaction($transaction);
print "\nFinished.\n";

function randomGene($minlen, $maxlen, $coop) {
    
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