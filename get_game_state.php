<?php
require_once 'db.php';

$matchId = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($matchId <= 0 || $userId <= 0) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Получаем текущее состояние игры
$stmt = $pdo->prepare("SELECT current_player_id, last_action_time FROM matches WHERE id = :match_id");
$stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
$stmt->execute();
$matchData = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем количество забаненных карт
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bans WHERE match_id = :match_id");
$stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
$stmt->execute();
$banCount = $stmt->fetchColumn();

// Возвращаем информацию о необходимости обновления
// Обновление нужно, если:
// 1. Текущий игрок изменился
// 2. Прошло более 60 секунд с последнего действия
$lastActionTime = strtotime($matchData['last_action_time']);
$timeElapsed = time() - $lastActionTime;
$needsUpdate = ($matchData['current_player_id'] == $userId) || ($timeElapsed >= 60);

echo json_encode([
    'needsUpdate' => $needsUpdate,
    'currentPlayer' => $matchData['current_player_id'],
    'banCount' => $banCount,
    'timeElapsed' => $timeElapsed
]);
?>