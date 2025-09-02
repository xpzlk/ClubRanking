<?php

function getInterclubTeamsData($config, $season, $leagueIds = []) {
    try {
        // Création de l'instance
        $db = new DatabaseConnector($config);
        
        // Construire la clause WHERE pour les ligues
        $whereClause = "ict.season_abstract = '" . $season . "'";
        
        if (!empty($leagueIds)) {
            // Sécuriser les IDs (s'assurer qu'ils sont numériques)
            $safeLeagueIds = array_filter($leagueIds, 'is_numeric');
            if (!empty($safeLeagueIds)) {
                $leagueIdsStr = implode(',', $safeLeagueIds);
                $whereClause .= " AND ict.league_id IN (" . $leagueIdsStr . ")";
            }
        }
        
        // Requête SQL
        $sql = "
        SELECT * 
        FROM `vw_interclub_team` ict 
        INNER JOIN interclub_league icl ON (ict.league_id = icl.id)
        WHERE " . $whereClause . "
        ORDER BY icl.level";

        // Exécution de la requête
        $results = $db->query($sql);
        
        // Transformation en tableau simple
        $teams = [];
        foreach ($results as $row) {
            $teams[] = $row;
        }
        
        return [
            'success' => true,
            'count' => count($teams),
            'teams' => $teams
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'teams' => []
        ];
    }
}