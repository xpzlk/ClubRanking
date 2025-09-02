<?php

class RemoteSQLResult {
    private $data = [];
    private $position = 0;
    private $columns = [];
    
    public function __construct($csvData) {
        $lines = str_getcsv($csvData, "\n");
        
        if (!empty($lines)) {
            // Première ligne = en-têtes
            $this->columns = str_getcsv($lines[0]);
            
            // Lignes suivantes = données
            for ($i = 1; $i < count($lines); $i++) {
                if (trim($lines[$i]) !== '') {
                    $row = str_getcsv($lines[$i]);
                    $this->data[] = array_combine($this->columns, $row);
                }
            }
        }
    }
    
    public function fetchAll($fetchStyle = PDO::FETCH_ASSOC) {
        switch ($fetchStyle) {
            case PDO::FETCH_ASSOC:
                return $this->data;
            case PDO::FETCH_NUM:
                return array_map('array_values', $this->data);
            case PDO::FETCH_BOTH:
                $result = [];
                foreach ($this->data as $row) {
                    $bothRow = $row; // Clés associatives
                    foreach (array_values($row) as $index => $value) {
                        $bothRow[$index] = $value; // Clés numériques
                    }
                    $result[] = $bothRow;
                }
                return $result;
            default:
                return $this->data;
        }
    }
    
    public function fetch($fetchStyle = PDO::FETCH_ASSOC) {
        if ($this->position >= count($this->data)) {
            return false;
        }
        
        $row = $this->data[$this->position];
        $this->position++;
        
        switch ($fetchStyle) {
            case PDO::FETCH_ASSOC:
                return $row;
            case PDO::FETCH_NUM:
                return array_values($row);
            case PDO::FETCH_BOTH:
                $bothRow = $row;
                foreach (array_values($row) as $index => $value) {
                    $bothRow[$index] = $value;
                }
                return $bothRow;
            default:
                return $row;
        }
    }
    
    public function rowCount() {
        return count($this->data);
    }
    
    public function columnCount() {
        return count($this->columns);
    }
    
    public function getColumnMeta($column) {
        if (is_int($column) && isset($this->columns[$column])) {
            return ['name' => $this->columns[$column]];
        }
        return false;
    }
}

function executeRemoteSQL($query, $serviceUrl) {
    // Validation basique de la requête
    if (!preg_match('/^\s*SELECT\s+/i', trim($query))) {
        throw new Exception('Seules les requêtes SELECT sont autorisées');
    }
    
    // Préparation des données POST
    $postData = http_build_query(['query' => $query]);
    
    // Configuration du contexte HTTP
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData,
            'timeout' => 30
        ]
    ]);
    
    // Appel du service
    $response = file_get_contents($serviceUrl, false, $context);
    
    if ($response === false) {
        throw new Exception('Erreur lors de l\'appel au service distant');
    }
    
    // Vérification si c'est une erreur
    if (strpos($response, 'Erreur:') === 0) {
        throw new Exception(substr($response, 7)); // Enlever "Erreur: "
    }
    
    return new RemoteSQLResult($response);
}


?>