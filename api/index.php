<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Simulation d'une "base de données" simple
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
        "description" => "Risque d’inondation dans les zones basses.",
        "zone" => "Bretagne",
        "debut" => "2023-10-05 10:00:00",
        "fin" => "2023-10-05 15:00:00"
    ]
];

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

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
        $newId = max(array_keys($alertes)) + 1;
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
            unset($alertes[$id]);
            echo json_encode(["message" => "Alerte supprimée"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Alerte à supprimer introuvable"]);
        }
        break;

    case 'OPTIONS':
        http_response_code(204);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Méthode non autorisée"]);
        break;
}
?>