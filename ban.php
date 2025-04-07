<?php
$host = 'localhost';
$dbname = 'rgbandb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['map_name']) && isset($_POST['match_id']) && isset($_POST['user_id'])) {
    $mapName = $_POST['map_name'];
    $matchId = intval($_POST['match_id']);
    $userId = intval($_POST['user_id']);

    // Проверка валидности ID пользователя
    if ($userId !== 1 && $userId !== 2) {
        echo "Ошибка: Некорректный ID пользователя.";
        exit();
    }

    // Проверка, что карта еще не забанена
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bans WHERE match_id = :match_id AND map_name = :map_name");
    $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
    $stmt->bindParam(':map_name', $mapName, PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        // Карта уже забанена, можно вернуть сообщение об ошибке, если нужно
        echo generateMapList($pdo, $matchId);
        exit();
    }

    // Получение текущего игрока
    $stmt = $pdo->prepare("SELECT current_player_id FROM matches WHERE id = :match_id");
    $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
    $stmt->execute();
    $currentPlayerId = $stmt->fetchColumn();

    if ($currentPlayerId === $userId) {
        // Бан карты
        $stmt = $pdo->prepare("INSERT INTO bans (match_id, user_id, map_name) VALUES (:match_id, :user_id, :map_name)");
        $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':map_name', $mapName, PDO::PARAM_STR);
        $stmt->execute();

        // Передача хода другому игроку
        $nextPlayerId = ($userId === 1) ? 2 : 1;
        $stmt = $pdo->prepare("UPDATE matches SET current_player_id = :current_player_id, last_action_time = NOW() WHERE id = :match_id");
        $stmt->bindParam(':current_player_id', $nextPlayerId, PDO::PARAM_INT);
        $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
        $stmt->execute();
    }

    echo generateMapList($pdo, $matchId);
} else {
    // Некорректный запрос
    echo "Ошибка: Некорректный запрос.";
}

// Функция для генерации HTML списка карт (используется в AJAX-обработчике)
function generateMapList($pdo, $matchId) {
    $stmt = $pdo->prepare("SELECT name FROM maps");
    $stmt->execute();
    $allMaps = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT map_name FROM bans WHERE match_id = :match_id");
    $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
    $stmt->execute();
    $bannedMaps = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $availableMaps = array_diff($allMaps, $bannedMaps);

    $stmt = $pdo->prepare("SELECT current_player_id FROM matches WHERE id = :match_id");
    $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
    $stmt->execute();
    $currentPlayerId = $stmt->fetchColumn();

    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : null; // Получаем ID пользователя из POST

    $html = '';
    foreach ($allMaps as $map) {
        $html .= '<div class="map-item ' . (in_array($map, $bannedMaps) ? 'banned' : '') . '"';
        $html .= ' data-map="' . $map . '"';
        if (!in_array($map, $bannedMaps) && $currentPlayerId === $userId && count($availableMaps) > 1) {
            $html .= ' onclick="banMap(\'' . $map . '\', ' . $matchId . ', ' . $userId . ')"';
        }
        $html .= '>' . $map . '</div>';
    }
    return $html;
}
?>
