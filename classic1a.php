#!/usr/bin/php
<?php

require_once "functions.php";

logThis(3, "Starting...");

$experiment_id = $argv[1];
$run_generations = $argv[2];

/**************************************** 
    PREP
*****************************************/

// Read experiment options
$options = getExperimentOptions($experiment_id);
$memory = $options['memory'];
$coop = $options['cooperative'];
$population = $options['population'];
$mutation_rate = $options['mutation_rate'];
$coverage = $options['coverage'];
$option_w = $options['w'];
logThis(4, "Read options: memory=$memory; coop=$coop; pop=$population; mutation_rate=$mutation_rate");
logThis(4, "Read options: coverage=$coverage; w=$option_w;");

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
    logThis(2, "GENERATION $current_generation");
    $options = getExperimentOptions($experiment_id); // Reread options
    $start_time = time();
    ipdTournament($experiment_id, $current_generation);
    matingSeason($experiment_id, $current_generation);
    $current_generation ++;
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    $remaining = gmdate("H:i:s", round(($target_generation-$current_generation)*$elapsed_time, 0));
    if (!$LOG_PRINT) {
        print "$current_generation/$target_generation ($elapsed_time"."s per generation, ~ $remaining"."s remaining)\n";
    }
}

// End of main loop

print"\n";
dbClose();
logThis(0, "Finished");


/**************************************************************************/

function ipdTournament($experiment_id, $generation) {
    //randomScores($experiment_id, $generation);
    global $options;

    logThis(3, "IPD tournament for experiment $experiment_id, generation $generation");

    $population = loadGeneration($experiment_id, $generation);
    $bots = array_keys($population);
    
    $play_games = ($options['coverage']*sizeof($population))/2;
    
    $scores = [];
    $games_played = [];
    $transaction = [];

    foreach($bots as $bot1_id) {
        $pg = $play_games;
        while ($pg > 0) {
            $pg--;
            // Pick the other player
            $bot2_id = $bot1_id;
            while($bot1_id == $bot2_id) {
                $dice = array_rand($bots);
                $bot2_id = $bots[$dice];    
            }
            $player1 = $population[$bot1_id];
            $player2 = $population[$bot2_id];

            // Create a game in ipd_games
            $game_id = uniqid();
            $sql = "
                INSERT INTO ipd_games (
                    id, 
                    created_at, 
                    experiment_id, 
                    generation,
                    bot1_id,
                    bot2_id
                ) VALUES (
                    '$game_id', 
                    NOW(), 
                    $experiment_id,
                    $generation,
                    '$bot1_id',
                    '$bot2_id'
                )
            ";
            $transaction[] = $sql;

            logThis(5, "iPD game between bots id $bot1_id and $bot2_id");
            $r = iPD($game_id, $bot1_id, $player1, $bot2_id, $player2, $options);
            $score1 = $r['score1'];
            $score2 = $r['score2'];
            $history = $r['history'];
            $t = $r['transaction'];
            $transaction = array_merge($transaction, $t);

            if (isset($games_played[$bot1_id])) {
                $games_played[$bot1_id]++;
            } else { $games_played[$bot1_id]=1; }

            if (isset($games_played[$bot2_id])) {
                $games_played[$bot2_id]++;
            } else { $games_played[$bot2_id]=1; }

            if (isset($scores[$bot1_id])) {
                $scores[$bot1_id] += $score1;
            } else { $scores[$bot1_id] = $score1; }

            if (isset($scores[$bot2_id])) {
                $scores[$bot2_id] += $score1;
            } else { $scores[$bot2_id] = $score2; }

            logThis(5, "iPD game results: $score1:$score2 $history");
        }
    }
    //dbTransaction($transaction);


    // Tournament finished compute average scores and update database
    //$avg_scores = [];
    //$transaction = [];
    foreach($bots as $bot_id) {
        $avg = round($scores[$bot_id] / $games_played[$bot_id], 4);
        //$avg_scores[$bot_id] = $avg;
        $sql = "UPDATE bots SET score=$avg WHERE id='$bot_id';";
        $transaction[] = $sql;
    }
    dbTransaction($transaction);

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
        if ($range == 0) { $range = 0.0000001; }
        logThis(4, "High score = $high; Low score = $low");
        $transaction = [];
        for($i=0; $i<=$max; $i++) {
            $bot_id = $scores[$i]['bot_id'];
            $fitness = round(1 - (($high - $scores[$i]['score']) / $range), 4);
            $scores[$i]['fitness'] = $fitness;
            $sql = "UPDATE bots SET fitness=$fitness WHERE id='$bot_id';";
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
            $bot_id = uniqid();
            $sql = "
                INSERT INTO bots (
                    id, 
                    created_at, 
                    experiment_id, 
                    generation
                ) VALUES (
                    '$bot_id', 
                    NOW(), 
                    $experiment_id,
                    $new_generation
                )
            ";
            $transaction[] = $sql;

            // Sex
            $DNA_len = count($population[$mother]);
            $cut = rand(0, $DNA_len-1);
            $child = [];
            $array_keys = array_keys($population[$mother]);
            // Mother's genes
            $c = $cut;
            foreach ($array_keys as $gene) {
                if ($c > 0) {
                    $allele = $population[$mother][$gene];
                    $m = mutated($allele);
                    if ($m != $allele) { $mutated = "'1'"; }
                    else { $mutated = "NULL"; }
                    $child[$gene] = $m;
                    $sql = "INSERT INTO genes (bot_id, gene, allele, mutated) VALUES ('$bot_id', '$gene', '$m', $mutated);";
                    $transaction[] = $sql;
                }
                $c--;
            }
            // Father's genes
            $c = $cut;
            foreach ($array_keys as $gene) {
                if ($c <= 0) {
                    $allele = $population[$father][$gene];
                    $m = mutated($allele);
                    if ($m != $allele) { $mutated = "'1'"; }
                    else { $mutated = "NULL"; }
                    $child[$gene] = $m;
                    $sql = "INSERT INTO genes (bot_id, gene, allele, mutated) VALUES ('$bot_id', '$gene', '$m', $mutated);";
                    $transaction[] = $sql;
                }
                $c--;
            }
            // Geneaology
            $sql = "INSERT INTO geneaology (bot_id, parent_id) VALUES ('$bot_id', '$mother');";
            $transaction[] = $sql;        
            $sql = "INSERT INTO geneaology (bot_id, parent_id) VALUES ('$bot_id', '$father');";
            $transaction[] = $sql;        
        }
        dbTransaction($transaction);
        logThis(3, "Created new generation experiment $experiment_id, generation $new_generation");

    } else {
        logthis(0, "Ups! Something went wrong, can't read experiment $experiment_id, generation $generation");
    }

}

function mutated($allele) {
    global $options;

    $roll = rand(0,100000)/100000;
    if ($roll < $options['mutation_rate']) {
        $allele = abs($allele - 1);
    }
    return $allele;
}

function iPD($game_id, $bot1_id, $p1_DNA, $bot2_id, $p2_DNA, $options) {

    $memory = $options['memory'];
    $w = $options['w'];
    $rewards = $options['rewards'];
 
    $history1 = ""; $history2 = "";
    $score1 = 0; $score2 = 0;
    $moves = 0;
    $transaction = [];

    $dice = 0;
    while ($dice < $w) {
        
        $moves++;
        
        $h1 = substr($history1, $memory*2*-1);
        $h2 = substr($history2, $memory*2*-1);
        $play1 = $p1_DNA[$h1];
        $play2 = $p2_DNA[$h2];

        $sql = "INSERT INTO pd_moves (ipd_game_id, bot_id, gene, allele) VALUES ('$game_id', '$bot1_id', '$h1', '$play1');";
        $transaction[] = $sql;
        $sql = "INSERT INTO pd_moves (ipd_game_id, bot_id, gene, allele) VALUES ('$game_id', '$bot2_id', '$h2', '$play2');";
        $transaction[] = $sql;

        $h1 = $play1 . $play2;
        $h2 = $play2 . $play1;
        $history1 .= $h1;
        $history2 .= $h2;
        $score1 += $rewards[$h1];
        $score2 += $rewards[$h2];

        $dice = rand(0,1000)/1000;
        
    }

    $avg_score1 = round($score1 / $moves, 4);
    $avg_score2 = round($score2 / $moves, 4);
    
    $sql = "UPDATE ipd_games SET score1=$avg_score1, score2=$avg_score2, moves=$moves, history1='$history1' WHERE id='$game_id';";
    $transaction[] = $sql;
  
    $result['history'] = $history1;
    $result['score1'] = $avg_score1;
    $result['score2'] = $avg_score2;
    $result['transaction'] = $transaction;

    return $result;
}
