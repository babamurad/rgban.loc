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

if (isset($_GET['match_id'])) {
    $matchId = intval($_GET['match_id']);
    
    // Удаляем все баны для этого матча
    $stmt = $pdo->prepare("DELETE FROM bans WHERE match_id = :match_id");
    $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Сбрасываем состояние матча
    $stmt = $pdo->prepare("UPDATE matches SET current_player_id = NULL, last_action_time = NOW() WHERE id = :match_id");
    $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Перенаправляем на страницу выбора игрока с указанием номера матча
    header("Location: index.php?match=$matchId");
    exit();
}

// Если match_id не указан, перенаправляем на страницу создания матча
header("Location: create_match.php");
exit();
?>
