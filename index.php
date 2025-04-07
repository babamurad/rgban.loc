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

// Проверяем, что параметр id существует и является числом
$userId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Если ID не указан, показываем страницу выбора
if ($userId === null || $userId === 0) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Выбор игрока</title>
        <style>
            body { font-family: sans-serif; text-align: center; margin-top: 50px; }
            .button { 
                display: inline-block; 
                padding: 10px 20px; 
                margin: 10px;
                background-color: #4CAF50; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px;
            }
        </style>
    </head>
    <body>
        <h1>Выберите игрока</h1>
        <a href="index.php?id=1&match=1" class="button">Игрок 1</a>
        <a href="index.php?id=2&match=1" class="button">Игрок 2</a>
    </body>
    </html>
    <?php
    exit();
}

// Проверяем, что id равен 1 или 2
if ($userId !== 1 && $userId !== 2) {
    die("Некорректный ID пользователя. Используйте id=1 или id=2");
}

$matchId = isset($_GET['match']) ? intval($_GET['match']) : 1; // По умолчанию матч 1

// Получение списка карт
$stmt = $pdo->prepare("SELECT name FROM maps");
$stmt->execute();
$allMaps = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Получение забаненных карт для текущего матча
$stmt = $pdo->prepare("SELECT map_name FROM bans WHERE match_id = :match_id");
$stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
$stmt->execute();
$bannedMaps = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Оставшиеся карты
$availableMaps = array_diff($allMaps, $bannedMaps);

// Определение текущего игрока
$stmt = $pdo->prepare("SELECT current_player_id FROM matches WHERE id = :match_id");
$stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
$stmt->execute();
$matchData = $stmt->fetch(PDO::FETCH_ASSOC);
$currentPlayerId = $matchData['current_player_id'];

// Определение сообщения для пользователя
$message = '';
if (count($availableMaps) > 1) {
    if ($currentPlayerId === null) {
        // Первый ход, случайный игрок начинает
        $currentPlayerId = rand(1, 2);
        $stmt = $pdo->prepare("UPDATE matches SET current_player_id = :current_player_id, last_action_time = NOW() WHERE id = :match_id");
        $stmt->bindParam(':current_player_id', $currentPlayerId, PDO::PARAM_INT);
        $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
        $stmt->execute();
    }

    if ($currentPlayerId === $userId) {
        $message = "Ваш выбор карты!";
    } else {
        $message = "Ожидайте, выбирает другой игрок...";
    }

    // Проверка времени последнего действия
    $stmt = $pdo->prepare("SELECT UNIX_TIMESTAMP(last_action_time) as last_time FROM matches WHERE id = :match_id");
    $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
    $stmt->execute();
    $lastAction = $stmt->fetch(PDO::FETCH_ASSOC)['last_time'];
    $timeElapsed = time() - $lastAction;

    if ($timeElapsed >= 60 && count($availableMaps) > 1) {
        // Время вышло, автоматический бан случайной карты другим игроком
        $otherPlayerId = ($userId === 1) ? 2 : 1;
        $randomIndex = array_rand($availableMaps);
        $autoBannedMap = $availableMaps[$randomIndex];

        $stmt = $pdo->prepare("INSERT INTO bans (match_id, user_id, map_name) VALUES (:match_id, :user_id, :map_name)");
        $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $otherPlayerId, PDO::PARAM_INT);
        $stmt->bindParam(':map_name', $autoBannedMap, PDO::PARAM_STR);
        $stmt->execute();

        // Передача хода текущему игроку
        $stmt = $pdo->prepare("UPDATE matches SET current_player_id = :current_player_id, last_action_time = NOW() WHERE id = :match_id");
        $stmt->bindParam(':current_player_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
        $stmt->execute();

        // Обновление списка доступных карт
        $stmt = $pdo->prepare("SELECT map_name FROM bans WHERE match_id = :match_id");
        $stmt->bindParam(':match_id', $matchId, PDO::PARAM_INT);
        $stmt->execute();
        $bannedMaps = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $availableMaps = array_diff($allMaps, $bannedMaps);
    }
} elseif (count($availableMaps) === 1) {
    $message = "Осталась карта: <strong>" . reset($availableMaps) . "</strong>";
} else {
    $message = "Все карты забанены. Что-то пошло не так.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Баны карт CS:GO</title>
    <style>
        body { font-family: sans-serif; }
        .map-list { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .map-item { border: 1px solid #ccc; padding: 10px; cursor: pointer; }
        .banned { background-color: #fdd; color: #800; text-decoration: line-through; cursor: default; }
        #message { margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Баны карт CS:GO (Матч #<?php echo $matchId; ?>)</h1>
    <div id="message"><?php echo $message; ?></div>

    <div class="map-list" id="map-list">
        <?php foreach ($allMaps as $map): ?>
            <div class="map-item <?php if (in_array($map, $bannedMaps)) echo 'banned'; ?>"
                 data-map="<?php echo $map; ?>"
                 <?php if (!in_array($map, $bannedMaps) && $currentPlayerId === $userId && count($availableMaps) > 1): ?>
                     onclick="banMap('<?php echo $map; ?>', <?php echo $matchId; ?>, <?php echo $userId; ?>)"
                 <?php endif; ?>>
                <?php echo $map; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Функция для отправки AJAX-запроса на бан карты
        function banMap(mapName, matchId, userId) {
            fetch('ban.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `map_name=${encodeURIComponent(mapName)}&match_id=${encodeURIComponent(matchId)}&user_id=${encodeURIComponent(userId)}`
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('map-list').innerHTML = data;
                updatePage(); // Обновляем сообщение и состояние после бана
            })
            .catch(error => {
                console.error('Ошибка:', error);
            });
        }

        // Функция для периодического обновления страницы
        function updatePage() {
            fetch(`index.php?id=<?php echo $userId; ?>&match=<?php echo $matchId; ?>`)
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                document.getElementById('message').innerHTML = doc.getElementById('message').innerHTML;
                document.getElementById('map-list').innerHTML = doc.getElementById('map-list').innerHTML;

                // Переназначение обработчиков клика для оставшихся карт
                const mapItems = document.querySelectorAll('.map-item:not(.banned)');
                mapItems.forEach(item => {
                    item.onclick = function() {
                        banMap(this.dataset.map, <?php echo $matchId; ?>, <?php echo $userId; ?>);
                    };
                });

                // Запускаем следующее обновление через 2 секунды (можно настроить)
                setTimeout(updatePage, 2000);
            })
            .catch(error => {
                console.error('Ошибка обновления:', error);
            });
        }

        // Запускаем периодическое обновление при загрузке страницы
        updatePage();
    </script>
    <div style="margin-top: 20px;">
        <a href="reset_match.php?match_id=<?php echo $matchId; ?>">Сбросить матч</a> |
        <a href="create_match.php">Создать новый матч</a>
    </div>
</body>
</html>
