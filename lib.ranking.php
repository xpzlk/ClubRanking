<?php

function calculateClubRanking($ranking, $pastPlayers) {
    // Créer un tableau des IDs des joueurs interclub (actuel + précédent)
    $interclubPlayerIds = [];
    
    if ($pastPlayers['success']) {
        $interclubPlayerIds = array_merge($interclubPlayerIds, $pastPlayers['participants']);
    }
    
    // Supprimer les doublons
    $interclubPlayerIds = array_unique($interclubPlayerIds);
    
    if (!$ranking['success']) {
        return $ranking;
    }
    
    // Filtrer les joueurs interclub et les grouper par club
    $playersByClub = [];
    foreach ($ranking['rankings'] as $player) {
        $userId = (int)$player['id'];
        
        // Vérifier si le joueur est dans les listes interclub
        if (in_array($userId, $interclubPlayerIds)) {
            $clubId = $player['club_id']; // Ajustez le nom de la colonne selon votre structure
            
            if (!isset($playersByClub[$clubId])) {
                $playersByClub[$clubId] = [];
            }
            
            $playersByClub[$clubId][] = $player;
        }
    }
    
    // Calculer le rang par club
    $updatedRankings = [];
    foreach ($ranking['rankings'] as $player) {
        $userId = (int)$player['id'];
        $clubId = $player['club_id']; // Ajustez le nom de la colonne selon votre structure
        
        // Si le joueur est dans les listes interclub
        if (in_array($userId, $interclubPlayerIds) && isset($playersByClub[$clubId])) {
            // Trier les joueurs du club par current_points décroissant
            $clubPlayers = $playersByClub[$clubId];
            usort($clubPlayers, function($a, $b) {
                return $b['current_points'] <=> $a['current_points'];
            });
            
            // Trouver le rang dans le club
            $clubRank = null;
            foreach ($clubPlayers as $index => $clubPlayer) {
                if ((int)$clubPlayer['id'] === $userId) {
                    $clubRank = $index + 1;
                    break;
                }
            }
            
            $player['club_rank'] = $clubRank;
        } else {
            // Pas un joueur interclub
            $player['club_rank'] = null;
        }
        
        $updatedRankings[] = $player;
    }
    
    return [
        'success' => true,
        'count' => count($updatedRankings),
        'rankings' => $updatedRankings
    ];
}

function getRankingData($config, $gender = '') {
    try {
        // Création de l'instance
        $db = new DatabaseConnector($config);
        
        // Construire la clause WHERE
        $whereClause = "is_international = 0";
        
        if (!empty($gender) && in_array($gender, ['H', 'F'])) {
            $whereClause .= " AND gender_id = '" . $gender . "'";
        }
        
        // Requête SQL
        $sql = "
        SELECT 
            *,
            ROW_NUMBER() OVER (ORDER BY current_points DESC) AS rank
        FROM vw_ranking 
        WHERE " . $whereClause . "
        ORDER BY current_points DESC";

        // Exécution de la requête
        $results = $db->query($sql);
        
        // Transformation en tableau simple
        $rankings = [];
        foreach ($results as $row) {
            $rankings[] = $row;
        }
        
        return [
            'success' => true,
            'count' => count($rankings),
            'rankings' => $rankings
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'rankings' => []
        ];
    }
}
