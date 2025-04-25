<?php
// Gérer les en-têtes CORS pour toutes les requêtes
header('Access-Control-Allow-Origin: https://ap-ialerte.vercel.app');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // Cache des préférences CORS pour 24 heures

// Répondre immédiatement aux requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Envoi des en-têtes seulement, pas de contenu
    http_response_code(200);
    exit;
}

// Si la requête est différente de OPTIONS, définir le Content-Type
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    header('Content-Type: application/json');
}

// Configuration de la base de données SQLite
$db_path = __DIR__ . '/alertes.db';
$db_exists = file_exists($db_path);

try {
    // Connexion à la base de données
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Création de la table si elle n'existe pas
    if (!$db_exists) {
        $db->exec('
            CREATE TABLE alertes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                alerte TEXT NOT NULL,
                niveau TEXT NOT NULL,
                description TEXT NOT NULL,
                zone TEXT NOT NULL,
                debut TEXT NOT NULL,
                fin TEXT NOT NULL
            )
        ');
        
        // Ajouter des données de démonstration
        $initData = [
            [
                "alerte" => "Vague de tempête",
                "niveau" => "Rouge",
                "description" => "Des vents violents et des vagues importantes sont attendus.",
                "zone" => "Côte Normande",
                "debut" => "2023-10-01 14:00:00",
                "fin" => "2023-10-02 18:00:00"
            ],
            [
                "alerte" => "Marée haute",
                "niveau" => "Orange",
                "description" => "Risque d'inondation dans les zones basses.",
                "zone" => "Bretagne",
                "debut" => "2023-10-05 10:00:00",
                "fin" => "2023-10-05 15:00:00"
            ]
        ];
        
        $stmt = $db->prepare('
            INSERT INTO alertes (alerte, niveau, description, zone, debut, fin)
            VALUES (:alerte, :niveau, :description, :zone, :debut, :fin)
        ');
        
        foreach ($initData as $data) {
            $stmt->execute([
                ':alerte' => $data['alerte'],
                ':niveau' => $data['niveau'],
                ':description' => $data['description'],
                ':zone' => $data['zone'],
                ':debut' => $data['debut'],
                ':fin' => $data['fin']
            ]);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur de base de données: " . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    switch ($method) {
        case 'GET':
            if ($id !== null) {
                // Récupérer une alerte spécifique
                $stmt = $db->prepare('SELECT * FROM alertes WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $alerte = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($alerte) {
                    echo json_encode($alerte, JSON_PRETTY_PRINT);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Alerte non trouvée"]);
                }
            } else {
                // Récupérer toutes les alertes
                $stmt = $db->query('SELECT * FROM alertes');
                $alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($alertes, JSON_PRETTY_PRINT);
            }
            break;

        case 'POST':
            // Créer une nouvelle alerte
            $stmt = $db->prepare('
                INSERT INTO alertes (alerte, niveau, description, zone, debut, fin)
                VALUES (:alerte, :niveau, :description, :zone, :debut, :fin)
            ');
            $stmt->execute([
                ':alerte' => $input['alerte'],
                ':niveau' => $input['niveau'],
                ':description' => $input['description'],
                ':zone' => $input['zone'],
                ':debut' => $input['debut'],
                ':fin' => $input['fin']
            ]);
            
            $newId = $db->lastInsertId();
            
            // Récupérer l'alerte créée
            $stmt = $db->prepare('SELECT * FROM alertes WHERE id = :id');
            $stmt->execute([':id' => $newId]);
            $newAlerte = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "message" => "Alerte créée",
                "data" => $newAlerte
            ], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            if ($id !== null) {
                // Vérifier si l'alerte existe
                $stmt = $db->prepare('SELECT COUNT(*) FROM alertes WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $exists = (bool) $stmt->fetchColumn();
                
                if ($exists) {
                    // Mettre à jour l'alerte
                    $stmt = $db->prepare('
                        UPDATE alertes
                        SET alerte = :alerte,
                            niveau = :niveau,
                            description = :description,
                            zone = :zone,
                            debut = :debut,
                            fin = :fin
                        WHERE id = :id
                    ');
                    $stmt->execute([
                        ':id' => $id,
                        ':alerte' => $input['alerte'],
                        ':niveau' => $input['niveau'],
                        ':description' => $input['description'],
                        ':zone' => $input['zone'],
                        ':debut' => $input['debut'],
                        ':fin' => $input['fin']
                    ]);
                    
                    // Récupérer l'alerte mise à jour
                    $stmt = $db->prepare('SELECT * FROM alertes WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $updatedAlerte = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        "message" => "Alerte mise à jour",
                        "data" => $updatedAlerte
                    ], JSON_PRETTY_PRINT);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Alerte à mettre à jour introuvable"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "ID d'alerte manquant pour la mise à jour"]);
            }
            break;

        case 'DELETE':
            if ($id !== null) {
                // Vérifier si l'alerte existe
                $stmt = $db->prepare('SELECT COUNT(*) FROM alertes WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $exists = (bool) $stmt->fetchColumn();
                
                if ($exists) {
                    // Supprimer l'alerte
                    $stmt = $db->prepare('DELETE FROM alertes WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    
                    echo json_encode(["message" => "Alerte supprimée"]);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Alerte à supprimer introuvable"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "ID d'alerte manquant pour la suppression"]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["error" => "Méthode non autorisée"]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur de base de données: " . $e->getMessage()]);
}
?>