<?php
function calculatePlayerEligibility($rankingWithClubRank, $teamsData) {
    if (!$rankingWithClubRank['success'] || !$teamsData['success']) {
        return [
            'success' => false,
            'error' => 'Données d\'entrée invalides',
            'rankings' => []
        ];
    }
    
    // Créer un mapping des équipes par club, triées par level puis team_sequence
    $teamsByClub = [];
    foreach ($teamsData['teams'] as $team) {
        $clubId = $team['club_id'];
        
        if (!isset($teamsByClub[$clubId])) {
            $teamsByClub[$clubId] = [];
        }

        $teamsByClub[$clubId][] = [
            'level' => $team['level'],
            'team_sequence' => $team['team_sequence'],
            'team_name' => $team['team_name'],
            'original_data' => $team
        ];
    }
    

    // Trier les équipes de chaque club par level (croissant) puis team_sequence (croissant)
    foreach ($teamsByClub as $clubId => $teams) {
        usort($teamsByClub[$clubId], function($a, $b) {
            // Trier d'abord par level (plus petit level = équipe plus forte)
            if ($a['level'] != $b['level']) {
                return $a['level'] <=> $b['level'];
            }
            // Si même level, trier par team_sequence
            return $a['team_sequence'] <=> $b['team_sequence'];
        });
        
        // Réindexer avec des numéros séquentiels (1, 2, 3, ...)
        $reindexed = [];
        foreach ($teamsByClub[$clubId] as $index => $team) {
            $teamNumber = $index + 1; // Commence à 1
            $teamNameWithLeague = $team['team_name'] . ' (' . $team['original_data']['abstract'] . ')';
            $reindexed[$teamNumber] = $teamNameWithLeague;
        }
        $teamsByClub[$clubId] = $reindexed;
    }
    
    // Calculer l'éligibilité pour chaque joueur
    $updatedRankings = [];
    foreach ($rankingWithClubRank['rankings'] as $player) {
        $clubId = $player['club_id'];
        $clubRank = $player['club_rank'];
        
        // Initialiser l'éligibilité
        $eligibleTeams = [];
        $eligibleTeamNames = [];
        
        // Si le joueur a un club_rank (donc est un joueur interclub)
        if ($clubRank !== null) {
            $maxTeams = isset($teamsByClub[$clubId]) ? count($teamsByClub[$clubId]) : 0;
            
            // Calculer le nombre d'équipes éligibles basé sur le club_rank (tranches de 4)
            $maxEligibleTeams = ceil($clubRank / 4);
            
            // Générer la liste des équipes éligibles (limitée aux équipes existantes)
            $eligibleTeams = range(1, min($maxEligibleTeams, $maxTeams));
            
            // Récupérer les noms des équipes éligibles
            foreach ($eligibleTeams as $teamNum) {
                if (isset($teamsByClub[$clubId][$teamNum])) {
                    $eligibleTeamNames[] = $teamsByClub[$clubId][$teamNum];
                }
            }
        }
        
        $player['eligible_teams'] = array_values($eligibleTeams);
        $player['eligible_team_names'] = $eligibleTeamNames;
        $player['max_team_level'] = empty($eligibleTeams) ? null : max($eligibleTeams);
        
        $updatedRankings[] = $player;
    }
    
    return [
        'success' => true,
        'count' => count($updatedRankings),
        'rankings' => $updatedRankings
    ];
}

