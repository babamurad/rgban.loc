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
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>CS2 Map Ban System - Выбор игрока</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f8f9fa;
                padding-top: 50px;
            }
            .card {
                border-radius: 15px;
                box-shadow: 0 6px 10px rgba(0,0,0,.08);
                transition: all .3s;
            }
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0,0,0,.12);
            }
            .btn-player {
                padding: 12px 30px;
                font-weight: 600;
                margin: 10px;
                border-radius: 50px;
                transition: all .3s;
            }
            .btn-player-1 {
                background-color: #007bff;
                border-color: #007bff;
            }
            .btn-player-2 {
                background-color: #dc3545;
                border-color: #dc3545;
            }
            .logo {
                max-width: 150px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card p-5 text-center">
                        <div class="card-body">
                            <h1 class="card-title mb-4">CS2 Map Ban System</h1>
                            <p class="card-text mb-4">Выберите игрока, чтобы начать процесс бана карт</p>
                            
                            <div class="d-grid gap-2 d-md-block">
                                <a href="index.php?id=1&match=1" class="btn btn-primary btn-lg btn-player btn-player-1">
                                    <i class="bi bi-person-fill"></i> Игрок 1
                                </a>
                                <a href="index.php?id=2&match=1" class="btn btn-danger btn-lg btn-player btn-player-2">
                                    <i class="bi bi-person-fill"></i> Игрок 2
                                </a>
                            </div>
                            
                            <div class="mt-4">
                                <a href="create_match.php" class="btn btn-outline-secondary">Создать новый матч</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
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
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CS2 Map Ban System - Матч #<?php echo $matchId; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 30px 0;
        }
        .map-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }
        .map-item {
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            width: 180px;
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,.05);
            transition: all .3s;
            cursor: pointer;
            font-weight: 600;
            border: 2px solid #e9ecef;
        }
        .map-item:hover:not(.banned) {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,.1);
            border-color: #007bff;
        }
        .banned {
            background-color: #f8d7da;
            color: #842029;
            text-decoration: line-through;
            opacity: 0.7;
            cursor: default;
            border-color: #f5c2c7;
        }
        .last-map {
            background-color: #d1e7dd;
            color: #0f5132;
            border-color: #badbcc;
            position: relative;
            overflow: hidden;
        }
        .final-map-badge {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            font-size: 0.8rem;
            background-color: #0f5132;
            color: white;
            padding: 2px 0;
        }
        .player-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
            margin-left: 10px;
        }
        .player-1 {
            background-color: #cfe2ff;
            color: #084298;
        }
        .player-2 {
            background-color: #f8d7da;
            color: #842029;
        }
        .message-box {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,.05);
            border-left: 5px solid #0d6efd;
        }
        .timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
            min-width: 150px; /* Фиксированная минимальная ширина */
            height: 1.5rem; /* Фиксированная высота */
            display: inline-block;
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>CS2 Map Ban System 
                        <span class="badge bg-secondary">Матч #<?php echo $matchId; ?></span>
                        <span class="player-badge <?php echo $userId === 1 ? 'player-1' : 'player-2'; ?>">
                            Игрок <?php echo $userId; ?>
                        </span>
                    </h1>
                </div>
                
                <div class="message-box" id="message">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><?php echo $message; ?></div>
                        <div class="timer" id="timer"></div>
                    </div>
                </div>

                <div class="map-list" id="map-list">
                    <?php foreach ($allMaps as $map): ?>
                        <?php 
                        $isBanned = in_array($map, $bannedMaps);
                        $isLastMap = count($availableMaps) === 1 && !$isBanned;
                        $canBeBanned = !$isBanned && $currentPlayerId === $userId && count($availableMaps) > 1;
                        
                        $classes = [];
                        if ($isBanned) $classes[] = 'banned';
                        if ($isLastMap) $classes[] = 'last-map';
                        $classString = !empty($classes) ? 'class="map-item ' . implode(' ', $classes) . '"' : 'class="map-item"';
                        ?>
                        
                        <div <?php echo $classString; ?> data-map="<?php echo $map; ?>"
                             <?php if ($canBeBanned): ?>
                                 onclick="banMap('<?php echo $map; ?>', <?php echo $matchId; ?>, <?php echo $userId; ?>)"
                             <?php endif; ?>>
                            <?php echo $map; ?>
                            <?php if ($isLastMap): ?>
                                <div class="final-map-badge">Финальная карта</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="footer text-center">
                    <a href="reset_match.php?match_id=<?php echo $matchId; ?>" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-repeat"></i> Сбросить матч
                    </a>
                    <a href="create_match.php" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Создать новый матч
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
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

        // Функция для обновления таймера
        function updateTimer() {
            fetch(`get_timer.php?match_id=<?php echo $matchId; ?>`)
            .then(response => response.json())
            .then(data => {
                const timeLeft = 60 - data.elapsed;
                const timerElement = document.getElementById('timer');
                
                if (timeLeft > 0) {
                    timerElement.textContent = `Осталось: ${timeLeft} сек`;
                } else {
                    timerElement.textContent = 'Время вышло!';
                    // Если время вышло, обновляем страницу для применения автоматического бана
                    checkForUpdates(); // Вместо полного обновления страницы, проверяем изменения
                }
            })
            .catch(error => {
                console.error('Ошибка обновления таймера:', error);
            });
        }

        // Функция для проверки изменений в игре без обновления всей страницы
        function checkForUpdates() {
            fetch(`get_game_state.php?match_id=<?php echo $matchId; ?>&user_id=<?php echo $userId; ?>`)
            .then(response => response.json())
            .then(data => {
                // Обновляем только если есть изменения в состоянии игры
                if (data.needsUpdate) {
                    updatePage();
                }
            })
            .catch(error => {
                console.error('Ошибка проверки обновлений:', error);
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
                    if (!item.classList.contains('last-map')) {
                        item.onclick = function() {
                            banMap(this.dataset.map, <?php echo $matchId; ?>, <?php echo $userId; ?>);
                        };
                    }
                });
            })
            .catch(error => {
                console.error('Ошибка обновления:', error);
            });
        }

        // Запускаем периодическое обновление при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            updatePage();
            // Обновляем таймер каждую секунду
            setInterval(updateTimer, 1000);
            // Проверяем изменения в игре каждые 5 секунд
            setInterval(checkForUpdates, 5000);
        });
    </script>
</body>
</html>
