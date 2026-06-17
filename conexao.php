<?php

require_once __DIR__ . '/app/Config/env.php';
require_once __DIR__ . '/app/Config/foto_helpers.php';

require_once __DIR__ . '/app/Config/db_dsn.php';
require_once __DIR__ . '/app/Config/sql_helpers.php';

app_load_env(__DIR__);



date_default_timezone_set('America/Sao_Paulo');



$driver = app_db_driver();

$host = app_env('DB_HOST', $driver === 'pgsql' ? 'localhost' : 'mysql');

$dbname = app_env('DB_DATABASE', $driver === 'pgsql' ? 'postgres' : 'sistema_labs');

$usuario = app_env('DB_USERNAME', $driver === 'pgsql' ? 'postgres' : 'root');

$senha = app_env('DB_PASSWORD', $driver === 'pgsql' ? '' : 'root');

$port = app_env('DB_PORT', $driver === 'pgsql' ? '5432' : '3306');

[$host, $port] = app_normalize_db_host_port($host, $port);



try {

    $cloudSql = app_env('CLOUD_SQL_CONNECTION_NAME');

    $dsn = app_build_pdo_dsn($driver, $host, $port, $dbname, $cloudSql);

    $pdo = new PDO($dsn, $usuario, $senha);

    app_apply_db_timezone($pdo, $driver);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Erro ao conectar com o banco de dados: " . $e->getMessage());

}

