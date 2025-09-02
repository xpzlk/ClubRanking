<?php
require_once 'class.DatabaseConnector.php';
require_once 'lib.season.php';
require_once 'lib.ICTeams.php';
require_once 'lib.ICParticipants.php';  
require_once 'lib.ranking.php';
require_once 'lib.PlayerEligibility.php';   

$simulDate = date('Y-m-d');
$menLeague = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
$womenLeague = [16, 17];
$genderFilter = ''; // 'H' pour hommes, 'F' pour femmes, '' pour tous

# On récupére les données des équipes interclubs pour la saison en cours
if ($genderFilter == '') {
    $teamsData = getInterclubTeamsData($config, getSeason($simulDate), $menLeague); // ligue hommes
} else if ($genderFilter == 'F') {
    $teamsData = getInterclubTeamsData($config, getSeason($simulDate), $womenLeague); // ligue femmes
}

if (getSeasonPart($simulDate) === 1) {
    #echo "Nous sommes dans la 1ʳᵉ partie de saison (01.09–31.12)\n";
    # On récupére les participants de la saison précédente
    if (getSeason($simulDate) === '25/26') {
        // On récupére les joueurs de la saison 2024/25 depuis l'extrait CSV de Tournament Software
        $csvFile = './data/swissSquash_players_IC202425.csv';
        $pastPlayers = getInterclubParticipantsFromCSV($csvFile);
    } else {
        // Pour les autres saisons, utiliser les données de la saison précédente
        $pastPlayers = getInterclubParticipants($config, 1, 'full', $simulDate);
    }
    $currentPlayers = getInterclubParticipants($config, 0, 'full', $simulDate);

    // Fusionner les deux tableaux participants
    $mergedParticipants = array_merge($pastPlayers['participants'], $currentPlayers['participants']);

    // Supprimer les doublons
    $uniqueParticipants = array_unique($mergedParticipants);

    // Optionnel : réindexer le tableau pour avoir des clés numériques consécutives
    $uniqueParticipants = array_values($uniqueParticipants);

    // Créer le tableau final fusionné
    $eligiblePlayers = [
        'success' => 1,
        'count' => count($uniqueParticipants),
        'participants' => $uniqueParticipants
    ];    
} else {
    #echo "Nous sommes dans la 2ᵉ partie de saison (01.01–31.08)\n";
    $eligiblePlayers = getInterclubParticipants($config, 0, 'full', $simulDate);
}


#On récupére le ranking actuel
$ranking = getRankingData($config, $genderFilter);

# On calcule le ranking avec le rang du club
$rankingWithClubRank = calculateClubRanking($ranking, $eligiblePlayers);

# On calcule l'éligibilité des joueurs
$rankingWithEligibility = calculatePlayerEligibility($rankingWithClubRank, $teamsData);


/*
$filename = './output/ranking_eligibility_.json';
$jsonData = json_encode($rankingWithEligibility, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($filename, $jsonData);


$clubIdFilter = 5; // Remplacez par l'ID du club souhaité

$playersShown = 0;
foreach ($rankingWithEligibility['rankings'] as $player) {
    #if (($player['club_id'] ?? null) == $clubIdFilter && 
    #    $playersShown < 100) {
        
        // Déterminer quoi afficher pour l'éligibilité
        if ($player['club_rank'] !== null && !empty($player['eligible_team_names'])) {
            $eligibilityText = implode(', ', $player['eligible_team_names']);
        } elseif ($player['club_rank'] !== null) {
            $eligibilityText = 'Aucune équipe';
        } else {
            $eligibilityText = 'Non éligible (pas interclub)';
        }
        
        echo "Rang général: " . $player['rank'] . 
             " - " . $player['last_name'] . " " . $player['first_name'] .
             " - Club: " . ($player['club_name'] ?? 'N/A') . 
             " - Club rank: " . ($player['club_rank'] ?? 'N/A') . 
             " - Éligible équipes: " . $eligibilityText . 
             " - Points: " . $player['current_points'] . "\n";
        
        $playersShown++;
    #}
}

if ($playersShown === 0) {
    echo "Aucun joueur trouvé pour le club '$clubFilter'\n";
}
*/