<?php
require_once 'class.remoteSQLResult.php';

// Exemple d'utilisation
try {
    $serviceUrl = 'https://ranking.squash.ch/ICRanking/query.php';
    $query = 'SELECT * FROM vw_ranking';
    
    $result = executeRemoteSQL($query, $serviceUrl);
    
    // Utilisation comme PDO
    echo "Nombre de lignes: " . $result->rowCount() . "\n";
    
    // Récupérer toutes les lignes
    $allRows = $result->fetchAll();
    print_r($allRows);
    
    // Ou récupérer ligne par ligne
    /*
    while ($row = $result->fetch()) {
        print_r($row);
    }
    */
    
} catch (Exception $e) {
    echo 'Erreur: ' . $e->getMessage();
}
?>



