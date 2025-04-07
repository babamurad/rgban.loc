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
    
    // Перенаправляем на страницу выбора игрока для нового матча
    header("Location: index.php?match=$matchId");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CS2 Map Ban System - Создание матча</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 30px 0;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 6px 10px rgba(0,0,0,.08);
            margin-bottom: 30px;
        }
        .match-card {
            transition: all .3s;
            cursor: pointer;
        }
        .match-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,.12);
        }
        .btn-create {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card p-4 mb-4">
                    <div class="card-body text-center">
                        <h1 class="card-title mb-4">Создание нового матча</h1>
                        <form method="post">
                            <button type="submit" class="btn btn-primary btn-lg btn-create">
                                <i class="bi bi-plus-circle"></i> Создать новый матч
                            </button>
                        </form>
                    </div>
                </div>
                
                <h2 class="mb-4">Существующие матчи</h2>
                <div class="row">
                    <?php
                    $stmt = $pdo->query("SELECT id FROM matches ORDER BY id");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="card match-card">
                                <div class="card-body">
                                    <h5 class="card-title">Матч #<?php echo $row['id']; ?></h5>
                                    <div class="d-flex justify-content-between mt-3">
                                        <a href="index.php?id=1&match=<?php echo $row['id']; ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-person-fill"></i> Игрок 1
                                        </a>
                                        <a href="index.php?id=2&match=<?php echo $row['id']; ?>" class="btn btn-outline-danger">
                                            <i class="bi bi-person-fill"></i> Игрок 2
                                        </a>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="reset_match.php?match_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-arrow-repeat"></i> Сбросить
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house-door"></i> На главную
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

