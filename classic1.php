#!/usr/bin/php
<?php

require_once "functions.php";

logThis(3, "Starting...");

$experiment_id = $argv[1];
$run_generations = $argv[2];

/**************************************** 
    PREP
*****************************************/

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
        print "No bot for this experiment. First create one with randompop1.php $experiment_id\n";
        exit(0);
    } else {
        matingSeason($experiment_id, $current_generation);
        $current_generation++;
    }
}

$target_generation = $current_generation + $run_generations;

/**************************************** 
    MAIN LOOP
*****************************************/

for ($i=0; $i<$run_generations; $i++) {
    $start_time = time();
    ipdTournament($experiment_id, $current_generation);
    matingSeason($experiment_id, $current_generation);
    $current_generation ++;
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    $remaining = gmdate("H:i:s", round(($target_generation-$current_generation)*$elapsed_time, 0));
    if (!$LOG_PRINT) {
        print "$current_generation/$target_generation ($elapsed_time"."s per generation, ~ $remaining"."s remaining)\r";
    }
}

// End of main loop

print"\n";
dbClose();
logThis(0, "Finished");


/**************************************************************************/

function ipdTournament($experiment_id, $generation) {
    randomScores($experiment_id, $generation);
    logThis(3, "IPD tournament for experiment $experiment_id, generation $generation");
}

function matingSeason($experiment_id, $generation) {
    
    logThis(3, "Mating season for experiment $experiment_id, generation $generation");
    $population = loadGeneration($experiment_id, $generation);

    if ($population) {

        $new_generation = $generation + 1;
        $population_size = count($population);
        logThis(4, "Read in population size $population_size");

        // Calculate fitness
        $sql = "
            SELECT distinct bot_id, score 
            FROM bots RIGHT JOIN genes ON bot_id=bots.id 
            WHERE experiment_id=$experiment_id and generation=$generation 
            ORDER BY score DESC
        ";
        $scores = dbSelect($sql);
        $max = count($scores)-1;
        $high = $scores[0]['score'];
        $low = $scores[$max]['score'];
        $range = $high - $low;
        logThis(4, "High score = $high; Low score = $low");
        $transaction = [];
        for($i=0; $i<=$max; $i++) {
            $bot_id = $scores[$i]['bot_id'];
            $fitness = round(1 - (($high - $scores[$i]['score']) / $range), 2);
            $scores[$i]['fitness'] = $fitness;
            $sql = "UPDATE bots SET fitness=$fitness WHERE id=$bot_id;";
            $transaction[] = $sql;
        }
        logThis(4, "Updating fitness scores");
        dbTransaction($transaction);
        
        // Mate
        $transaction = [];

        for ($new=1; $new <= $population_size; $new++) {

            logThis(5, "Creating new bot $new");

            // Create a party
            $party = [];
            while (count($party) < 2) {
                $invite = rand(0,100)/100;
                $party = [];
                foreach($scores as $bot) {
                    if ($bot['fitness'] > $invite) {
                        $party[] = $bot['bot_id'];
                    }
                }
            }
            logThis(5, "Party size is ".count($party));

            // Pick mother and father
            $size = count($party)-1;
            $mother_idx = rand(0, $size);
            $father_idx = rand(0, $size);
            while ($mother_idx == $father_idx) {
                $father_idx = rand(0, $size);
            }
            $mother = $party[$mother_idx];
            $father = $party[$father_idx];
            logThis(5, "Mating mother bot_id=$mother and father bot_id=$father");

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
                    $new_generation
                )
            ";
            dbInsert($sql);

            // Get bot_id
            $sql = "SELECT id FROM bots WHERE bot_uniq='$uniq'";
            $result = dbSelect($sql);
            $bot_id = $result[0]['id'];

            // Sex
            $DNA_len = count($population[$mother]);
            $cut = rand(0, $DNA_len-1);
            $child = [];
            $array_keys = array_keys($population[$mother]);
            $c = $cut;
            foreach ($array_keys as $gene) {
                if ($c > 0) {
                    $allele = $population[$mother][$gene];
                    $child[$gene] = $allele;
                    $sql = "INSERT INTO genes (bot_id, gene, allele) VALUES ($bot_id, '$gene', '$allele');";
                    $transaction[] = $sql;
                }
                $c--;
            }
            $c = $cut;
            foreach ($array_keys as $gene) {
                if ($c <= 0) {
                    $allele = $population[$father][$gene];
                    $child[$gene] = $allele;
                    $sql = "INSERT INTO genes (bot_id, gene, allele) VALUES ($bot_id, '$gene', '$allele');";
                    $transaction[] = $sql;    
                }
                $c--;
            }

        }
        dbTransaction($transaction);
        //var_dump($transaction);
        logThis(3, "Created new generation experiment $experiment_id, generation $generation");

    } else {
        logthis(0, "Ups! Something went wrong, can't read experiment $experiment_id, generation $generation");
    }

}
