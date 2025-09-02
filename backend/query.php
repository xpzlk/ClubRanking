<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="export.csv"');
require_once '../config.php';

try {
    // Récupération de la requête SQL
    $sql = $_GET['query'] ?? $_POST['query'] ?? '';
    
    if (empty($sql)) {
        throw new Exception('Aucune requête SQL fournie');
    }
    
    if (!preg_match('/^\s*(SELECT|WITH)\s+/i', trim($sql))) {
        throw new Exception('Seules les requêtes SELECT et WITH sont autorisées');
    }
    
    // Connexion à MariaDB
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Exécution de la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // Ouverture du flux de sortie
    $output = fopen('php://output', 'w');
    
    // Écriture des en-têtes CSV (noms des colonnes)
    $firstRow = true;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($firstRow) {
            fputcsv($output, array_keys($row));
            $firstRow = false;
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    // En cas d'erreur, retourner un message d'erreur en CSV
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Erreur: ' . $e->getMessage();
}
?>