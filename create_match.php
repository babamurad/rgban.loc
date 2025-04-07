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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Создаем новый матч
    $stmt = $pdo->prepare("INSERT INTO matches (current_player_id, last_action_time) VALUES (NULL, NOW())");
    $stmt->execute();
    
    $matchId = $pdo->lastInsertId();
    
    // Перенаправляем на страницу матча
    header("Location: index.php?id=1&match=$matchId");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Создание нового матча</title>
    <style>
        body { font-family: sans-serif; }
        .button { 
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Создание нового матча</h1>
    
    <form method="post">
        <button type="submit" class="button">Создать новый матч</button>
    </form>
    
    <h2>Существующие матчи</h2>
    <?php
    $stmt = $pdo->query("SELECT id FROM matches ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<p>Матч #" . $row['id'] . " - ";
        echo "<a href='index.php?id=1&match=" . $row['id'] . "'>Игрок 1</a> | ";
        echo "<a href='index.php?id=2&match=" . $row['id'] . "'>Игрок 2</a></p>";
    }
    ?>
</body>
</html>