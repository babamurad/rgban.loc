<?php
$host = 'localhost';
$dbname = 'rgbandb';
$username = 'root';
$password = '';

// Путь для сохранения бэкапа
$backup_file = 'backup/rgbandb_' . date("Y-m-d-H-i-s") . '.sql';

// Создаем директорию для бэкапа, если она не существует
if (!file_exists('backup')) {
    mkdir('backup', 0777, true);
}

// Команда для создания бэкапа
$command = "mysqldump --opt -h $host -u $username ";
if ($password) {
    $command .= "-p'$password' ";
}
$command .= "$dbname > $backup_file";

// Выполняем команду
system($command, $return_var);

if ($return_var === 0) {
    echo "Бэкап успешно создан: $backup_file";
} else {
    echo "Ошибка при создании бэкапа";
}
?>