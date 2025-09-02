<?php

class DatabaseConnector {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $remoteScriptUrl;
    private $isLocal;
    private $pdo;
    
    public function __construct($config) {
        $this->host = $config['host'];
        $this->dbname = $config['dbname'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->remoteScriptUrl = $config['remote_script_url'];
        
        // Détection automatique du contexte (local/distant)
        $this->isLocal = $this->detectLocalEnvironment();
        
        // Initialisation de la connexion PDO si en local
        if ($this->isLocal) {
            $this->initPDOConnection();
        }
    }
    
    /**
     * Exécute une requête SELECT et retourne les résultats
     */
    public function query($sql) {
        // Vérification que c'est bien un SELECT
        if (!preg_match('/^\s*(SELECT|WITH)\s+/i', trim($sql))) {
            throw new Exception('Seules les requêtes SELECT et WITH sont autorisées');
        }
        
        if ($this->isLocal) {
            return $this->queryLocal($sql);
        } else {
            return $this->queryRemote($sql);
        }
    }
    
    /**
     * Exécute une requête en local via PDO
     */
    private function queryLocal($sql) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Erreur lors de l\'exécution de la requête : ' . $e->getMessage());
        }
    }
    
    /**
     * Exécute une requête à distance via le script PHP
     */
    private function queryRemote($sql) {
        $postData = http_build_query(['query' => $sql]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ]);
        
        $result = file_get_contents($this->remoteScriptUrl, false, $context);
        
        if ($result === false) {
            throw new Exception('Erreur lors de l\'appel au script distant');
        }
        
        // Vérification si c'est une erreur
        if (strpos($result, 'Erreur:') === 0) {
            throw new Exception($result);
        }
        
        // Parse du CSV retourné
        return $this->parseCsvResult($result);
    }
    
    /**
     * Parse le résultat CSV en tableau associatif
     */
    private function parseCsvResult($csvData) {
        // Correction 1: Vérifier que csvData n'est pas null et spécifier le paramètre escape
        if ($csvData === null || $csvData === '') {
            return [];
        }

        $lines = str_getcsv($csvData, "\n", '"', '\\'); // Ajout du paramètre escape
        
        if (empty($lines)) {
            return [];
        }
        
        // Première ligne = en-têtes
        $headers = str_getcsv(array_shift($lines), ',', '"', '\\'); // Ajout du paramètre escape
        $result = [];
        
        foreach ($lines as $line) {
           if ($line !== null && !empty(trim($line))) {
                $values = str_getcsv($line, ',', '"', '\\'); // Ajout du paramètre escape
                if (count($values) === count($headers)) {
                    $result[] = array_combine($headers, $values);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Détecte si on est en environnement local
     */
    private function detectLocalEnvironment() {
        // Vous pouvez adapter cette logique selon vos besoins
        // Exemples de détection :
        
        // Par adresse IP
        $localIPs = ['127.0.0.1', '::1', 'localhost'];
        if (in_array($_SERVER['SERVER_ADDR'] ?? '', $localIPs) || 
            in_array($_SERVER['REMOTE_ADDR'] ?? '', $localIPs)) {
            return true;
        }
        
        // Par nom de domaine
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        if (strpos($serverName, 'localhost') !== false || 
            strpos($serverName, '.local') !== false) {
            return true;
        }
        
        // Par variable d'environnement personnalisée
        if (getenv('DB_MODE') === 'local') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Initialise la connexion PDO pour l'environnement local
     */
    private function initPDOConnection() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8",
                $this->username,
                $this->password
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Erreur de connexion à la base de données : ' . $e->getMessage());
        }
    }
    
    /**
     * Retourne le mode de connexion actuel
     */
    public function getConnectionMode() {
        return $this->isLocal ? 'local' : 'remote';
    }
}