<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Simuler une base de données avec un fichier JSON
$dbFile = __DIR__ . '/alertes.json';

// Créer le fichier s'il n'existe pas
if (!file_exists($dbFile)) {
    $alertesInitiales = [
        [
            "id" => 1,
            "alerte" => "Vague de tempête",
            "niveau" => "Rouge",
            "description" => "Des vents violents et des vagues importantes sont attendus.",
            "zone" => "Côte Normande",
            "debut" => "2023-10-01 14:00:00",
            "fin" => "2023-10-02 18:00:00"
        ],
        [
            "id" => 2,
            "alerte" => "Vent Fort",
            "niveau" => "Orange",
            "description" => "Rafales de vent atteignant 80 km/h.",
            "zone" => "Deauville",
            "debut" => "2023-10-16 09:00:00",
            "fin" => "2023-10-16 20:00:00"
        ],
        [
            "id" => 3,
            "alerte" => "Marée Haute",
            "niveau" => "Jaune",
            "description" => "Marée exceptionnellement haute prévue.",
            "zone" => "Port de Deauville",
            "debut" => "2023-10-17 18:00:00",
            "fin" => "2023-10-17 22:00:00"
        ]
    ];
    file_put_contents($dbFile, json_encode($alertesInitiales, JSON_PRETTY_PRINT));
}

// Récupérer les données
function getAlertes() {
    global $dbFile;
    return json_decode(file_get_contents($dbFile), true);
}

// Sauvegarder les données
function saveAlertes($alertes) {
    global $dbFile;
    file_put_contents($dbFile, json_encode($alertes, JSON_PRETTY_PRINT));
}

// Traitement des requêtes CRUD
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Récupérer toutes les alertes ou une alerte spécifique
        if (isset($_GET['id'])) {
            $alertes = getAlertes();
            $id = (int)$_GET['id'];
            $alerte = null;
            
            foreach ($alertes as $a) {
                if ($a['id'] == $id) {
                    $alerte = $a;
                    break;
                }
            }
            
            if ($alerte) {
                echo json_encode($alerte);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Alerte non trouvée"]);
            }
        } else {
            echo json_encode(getAlertes());
        }
        break;
        
    case 'POST':
        // Créer une nouvelle alerte
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(["message" => "Données invalides"]);
            break;
        }
        
        $alertes = getAlertes();
        
        // Générer un nouvel ID
        $maxId = 0;
        foreach ($alertes as $alerte) {
            if ($alerte['id'] > $maxId) {
                $maxId = $alerte['id'];
            }
        }
        
        $data['id'] = $maxId + 1;
        $alertes[] = $data;
        
        saveAlertes($alertes);
        
        http_response_code(201);
        echo json_encode($data);
        break;
        
    case 'PUT':
        // Mettre à jour une alerte existante
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID non spécifié"]);
            break;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(["message" => "Données invalides"]);
            break;
        }
        
        $id = (int)$_GET['id'];
        $alertes = getAlertes();
        $updated = false;
        
        foreach ($alertes as $key => $alerte) {
            if ($alerte['id'] == $id) {
                $data['id'] = $id; // Conserver l'ID original
                $alertes[$key] = $data;
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            saveAlertes($alertes);
            echo json_encode($data);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Alerte non trouvée"]);
        }
        break;
        
    case 'DELETE':
        // Supprimer une alerte
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID non spécifié"]);
            break;
        }
        
        $id = (int)$_GET['id'];
        $alertes = getAlertes();
        $deleted = false;
        
        foreach ($alertes as $key => $alerte) {
            if ($alerte['id'] == $id) {
                array_splice($alertes, $key, 1);
                $deleted = true;
                break;
            }
        }
        
        if ($deleted) {
            saveAlertes($alertes);
            echo json_encode(["message" => "Alerte supprimée avec succès"]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Alerte non trouvée"]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Méthode non autorisée"]);
        break;
}
?>