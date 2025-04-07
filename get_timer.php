<?php
$host = 'localhost';
$dbname = 'rgbandb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Ошибка подключения к базе данных: ' . $e->getMessage()]));
}

$matchId = isset($_GET['match_id']) ? intval($_GET['match_id']) : 1;

// Получаем время последнего действия
$stmt = $pdo->prepare("SELECT UNIX_TIMESTAMP(last_action_time) as last_time FROM matches WHERE id = :match_id");
$stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
$stmt->execute();
$lastAction = $stmt->fetch(PDO::FETCH_ASSOC)['last_time'];
$timeElapsed = time() - $lastAction;

// Возвращаем JSON с информацией о времени
header('Content-Type: application/json');
echo json_encode([
    'elapsed' => $timeElapsed,
    'last_action' => $lastAction
]);