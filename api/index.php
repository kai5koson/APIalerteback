<?php
// Indispensable pour les problèmes CORS - doit être avant tout output
// Accepter les requêtes de n'importe quelle origine - sera restreint dans le déploiement final
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, X-Requested-With, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 heures

// Gestion spéciale des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Accepter la prévalidation CORS
    header("HTTP/1.1 200 OK");
    exit;
}

// Définir le type de contenu après avoir géré les requêtes OPTIONS
header('Content-Type: application/json');

// DEBUG: Affichage des en-têtes reçus et envoyés pour diagnostiquer le problème CORS
error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);
error_log("URI: " . $_SERVER['REQUEST_URI']);
foreach (getallheaders() as $name => $value) {
    error_log("En-tête reçu - $name: $value");
}

// Remplacer la base de données SQLite par un tableau en mémoire pour tester
// si le problème vient de la DB SQLite
$alertes = [
    1 => [
        "id" => 1,
        "alerte" => "Vague de tempête",
        "niveau" => "Rouge",
        "description" => "Des vents violents et des vagues importantes sont attendus.",
        "zone" => "Côte Normande",
        "debut" => "2023-10-01 14:00:00",
        "fin" => "2023-10-02 18:00:00"
    ],
    2 => [
        "id" => 2,
        "alerte" => "Marée haute",
        "niveau" => "Orange",
        "description" => "Risque d'inondation dans les zones basses.",
        "zone" => "Bretagne",
        "debut" => "2023-10-05 10:00:00",
        "fin" => "2023-10-05 15:00:00"
    ]
];

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    switch ($method) {
        case 'GET':
            if ($id !== null && isset($alertes[$id])) {
                echo json_encode($alertes[$id], JSON_PRETTY_PRINT);
            } elseif ($id !== null) {
                http_response_code(404);
                echo json_encode(["error" => "Alerte non trouvée"]);
            } else {
                echo json_encode(array_values($alertes), JSON_PRETTY_PRINT);
            }
            break;

        case 'POST':
            $newId = count($alertes) > 0 ? max(array_keys($alertes)) + 1 : 1;
            $input['id'] = $newId;
            $alertes[$newId] = $input;
            echo json_encode([
                "message" => "Alerte créée",
                "data" => $alertes[$newId]
            ], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            if ($id !== null && isset($alertes[$id])) {
                $alertes[$id] = array_merge($alertes[$id], $input);
                echo json_encode([
                    "message" => "Alerte mise à jour",
                    "data" => $alertes[$id]
                ], JSON_PRETTY_PRINT);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Alerte à mettre à jour introuvable"]);
            }
            break;

        case 'DELETE':
            if ($id !== null && isset($alertes[$id])) {
                // Débug - Log de suppression
                error_log("Tentative de suppression de l'alerte #$id");
                
                unset($alertes[$id]);
                
                // Débug - Log après suppression
                error_log("Suppression réussie de l'alerte #$id");
                
                echo json_encode(["message" => "Alerte supprimée"]);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Alerte à supprimer introuvable"]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["error" => "Méthode non autorisée"]);
            break;
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur: " . $e->getMessage()]);
}
?>