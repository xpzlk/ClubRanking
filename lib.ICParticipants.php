<?php

function getInterclubParticipants($config, $seasonsAgo = 0, $seasonPart = 'full', $referenceDate = null) {
    try {
        // Création de l'instance
        $db = new DatabaseConnector($config);
        
        // Si aucune date de référence n'est fournie, utiliser la date actuelle
        if ($referenceDate === null) {
            $currentDate = 'CURDATE()';
        } else {
            // Valider et formater la date de référence
            $timestamp = strtotime($referenceDate);
            if ($timestamp === false) {
                throw new Exception("Date de référence invalide : $referenceDate");
            }
            $currentDate = "'" . date('Y-m-d', $timestamp) . "'";
        }
        
        $sql = "";
        
        if ($seasonsAgo == 0 && $seasonPart !== 'full') {
            // Gestion spéciale pour la saison actuelle avec partie spécifique
            if ($seasonPart === 'start') {
                // Début de saison : du 1er septembre au 31 décembre de l'année actuelle
                $sql = "
                WITH period_season AS (
                    SELECT 
                        DATE(CONCAT(
                            YEAR($currentDate) - CASE WHEN MONTH($currentDate) < 9 THEN 1 ELSE 0 END, 
                            '-09-01'
                        )) AS start_periode,
                        DATE(CONCAT(
                            YEAR($currentDate) - CASE WHEN MONTH($currentDate) < 9 THEN 0 ELSE 1 END, 
                            '-12-31'
                        )) AS end_periode
                )";
            } elseif ($seasonPart === 'end') {
                // Fin de saison : du 1er janvier au 31 août de l'année suivante
                $sql = "
                WITH period_season AS (
                    SELECT 
                        DATE(CONCAT(
                            YEAR($currentDate) - CASE WHEN MONTH($currentDate) < 9 THEN -1 ELSE 0 END, 
                            '-01-01'
                        )) AS start_periode,
                        DATE(CONCAT(
                            YEAR($currentDate) + CASE WHEN MONTH($currentDate) >= 9 THEN 1 ELSE 0 END, 
                            '-08-31'
                        )) AS end_periode
                )";
            }
        } else {
            // Logique originale pour saison complète ou saisons précédentes
            $startYearAdjust = $seasonsAgo + 1;
            $endYearAdjust = $seasonsAgo;
            
            $sql = "
            WITH period_season AS (
                SELECT 
                    DATE(CONCAT(
                        YEAR($currentDate) - CASE WHEN MONTH($currentDate) < 9 THEN $startYearAdjust ELSE $seasonsAgo END, 
                        '-09-01'
                    )) AS start_periode,
                    DATE(CONCAT(
                        YEAR($currentDate) - CASE WHEN MONTH($currentDate) < 9 THEN $endYearAdjust ELSE $seasonsAgo END + 1, 
                        '-08-31'
                    )) AS end_periode
            )";
        }
        
        // Partie commune de la requête
        $sql .= "
        SELECT *
        FROM (
            SELECT DISTINCT winner_id AS user_id  
            FROM `fixture` f 
            INNER JOIN interclub_fixture icf ON (f.interclub_fixture_id = icf.id AND f.event_type_id = 'I')
            CROSS JOIN period_season p
            WHERE icf.fixture_date BETWEEN p.start_periode AND p.end_periode
            
            UNION
            
            SELECT DISTINCT loser_id AS user_id    
            FROM `fixture` f 
            INNER JOIN interclub_fixture icf ON (f.interclub_fixture_id = icf.id AND f.event_type_id = 'I')
            CROSS JOIN period_season p
            WHERE icf.fixture_date BETWEEN p.start_periode AND p.end_periode
        ) AS participants_interclub";
        
        // Exécution de la requête
        $results = $db->query($sql);
        
        // Transformation en tableau simple d'IDs
        $participants = [];
        foreach ($results as $row) {
            $participants[] = (int)$row['user_id'];
        }
        
        // Construction du label de saison
        $seasonLabel = '';
        if ($seasonsAgo == 0) {
            $seasonLabel = $seasonPart === 'full' ? 'current (full season)' : "current ($seasonPart of season)";
        } else {
            $seasonLabel = "previous ($seasonsAgo seasons ago)";
        }
        
        // Ajouter info sur la date de référence si spécifiée
        if ($referenceDate !== null) {
            $seasonLabel .= " [ref: $referenceDate]";
        }
        
        return [
            'success' => true,
            'count' => count($participants),
            'participants' => $participants,
            'season' => $seasonLabel
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'participants' => []
        ];
    }
}


function getInterclubParticipantsFromCSV($csvFilePath) {
    try {
        // Vérifier si le fichier existe
        if (!file_exists($csvFilePath)) {
            throw new Exception("Le fichier CSV n'existe pas : $csvFilePath");
        }
        
        // Ouvrir le fichier CSV
        $handle = fopen($csvFilePath, 'r');
        if ($handle === false) {
            throw new Exception("Impossible d'ouvrir le fichier CSV : $csvFilePath");
        }
        
        // Lire la première ligne (en-têtes)
        $headers = fgetcsv($handle, 1000, ',', '"', '\\');
        
        // Nettoyer les en-têtes (supprimer BOM, espaces, etc.)
        $headers = array_map(function($header) {
            // Supprimer le BOM UTF-8 si présent
            $header = str_replace("\xEF\xBB\xBF", '', $header);
            // Supprimer les espaces et caractères invisibles
            return trim($header);
        }, $headers);

        // Vérifier que les colonnes attendues existent
        $fullnameIndex = array_search('Fullname', $headers);
        $idIndex = array_search('ID', $headers);
        
        if ($fullnameIndex === false || $idIndex === false) {
            fclose($handle);
            throw new Exception("Les colonnes 'Fullname' et 'ID' sont requises dans le CSV");
        }
        
        $participants = [];
        
        // Lire chaque ligne du CSV
        while (($row = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
            // Récupérer l'ID
            $id = isset($row[$idIndex]) ? trim($row[$idIndex]) : '';
            
            // Omettre les lignes avec un ID vide
            if (!empty($id) && is_numeric($id)) {
                $participants[] = (int)$id;
            }
        }
        
        fclose($handle);
        
        // Supprimer les doublons et réindexer
        $participants = array_values(array_unique($participants));
        
        return [
            'success' => true,
            'count' => count($participants),
            'participants' => $participants
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'participants' => []
        ];
    }
}