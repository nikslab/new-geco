#!/usr/bin/php
<?php

require_once "functions.php";

logThis(3, "Starting...");

$experiment_id = $argv[1];
$run_generations = $argv[2];

// Figure out last generation to work on
$sql = "
    SELECT max(generation) as gen 
    FROM bots 
    WHERE experiment_id=$experiment_id 
      AND score IS NULL
";
$result = dbSelect($sql);
$current_generation = $result[0]['gen'];
logThis(4, "Experiment $experiment_id max generation without score: $current_generation");

if ($current_generation == '') {
    // Are there no bots or do we need to first create a new generation with a mating season
    $sql = "
        SELECT max(generation) as gen FROM bots 
        WHERE experiment_id=$experiment_id
    ";
    $result = dbSelect($sql);
    $current_generation = $result[0]['gen'];
    logThis(4, "Experiment $experiment_id max generation with score: $current_generation");

    if ($current_generation == '') {
        print "No bot for this experiment. First create one with randompop1.php 
        $experiment_id\n";
        exit(0);
    } else {
        matingSeason($experiment_id, $current_generation);
        $current_generation++;
    }
}

// Main loop
for ($i=0; $i<$run_generations; $i++) {
    ipdTournament($experiment_id, $current_generation);
    matingSeason($experiment_id, $current_generation);
    $current_generation ++;
}
// End of main loop

dbClose();
logThis(0, "Finished");
exit(0);

/**************************************************************************/

function ipdTournament($experiment_id, $generation) {
    logThis(3, "IPD tournament for experiment $experiment_id, generation $generation");
}

function matingSeason($experiment_id, $generation) {
    logThis(3, "Mating season for experiment $experiment_id, generation $generation");
    loadGeneration($experiment_id, $generation);
    $generation++;
    logThis(3, "Created new generation experiment $experiment_id, generation $generation");
}
