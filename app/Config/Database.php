<?php

namespace App\Config;



use PDO;

use PDOException;

use Illuminate\Database\Capsule\Manager as Capsule;



class Database {

    private static $instance = null;

    private $pdo;

    private $capsule;



    private function __construct() {

        require_once __DIR__ . '/env.php';

        require_once __DIR__ . '/db_dsn.php';
        require_once __DIR__ . '/sql_helpers.php';

        app_load_env(dirname(__DIR__, 2));



        date_default_timezone_set('America/Sao_Paulo');



        $driver = app_db_driver();

        $host = app_env('DB_HOST', $driver === 'pgsql' ? 'localhost' : 'mysql');

        $dbname = app_env('DB_DATABASE', $driver === 'pgsql' ? 'postgres' : 'sistema_labs');

        $usuario = app_env('DB_USERNAME', $driver === 'pgsql' ? 'postgres' : 'root');

        $senha = app_env('DB_PASSWORD', '');

        $port = app_env('DB_PORT', $driver === 'pgsql' ? '5432' : '3306');

        [$host, $port] = app_normalize_db_host_port($host, $port);

        $cloudSql = app_env('CLOUD_SQL_CONNECTION_NAME');



        try {

            $dsn = app_build_pdo_dsn($driver, $host, $port, $dbname, $cloudSql);

            $this->pdo = new PDO($dsn, $usuario, $senha);

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            app_apply_db_timezone($this->pdo, $driver);

        } catch (PDOException $e) {

            die("Erro ao conectar com o banco de dados via PDO: " . $e->getMessage());

        }



        $this->capsule = new Capsule;

        $capsuleConfig = [

            'driver'   => $driver,

            'host'     => $host,

            'port'     => $port,

            'database' => $dbname,

            'username' => $usuario,

            'password' => $senha,

            'prefix'   => '',

        ];



        if ($driver === 'mysql') {

            $capsuleConfig['charset'] = 'utf8mb4';

            $capsuleConfig['collation'] = 'utf8mb4_unicode_ci';

        }



        $this->capsule->addConnection($capsuleConfig);



        if (class_exists('\Illuminate\Events\Dispatcher') && class_exists('\Illuminate\Container\Container')) {

            $this->capsule->setEventDispatcher(new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container));

        }



        $this->capsule->setAsGlobal();

        $this->capsule->bootEloquent();

    }



    public static function getInstance() {

        if (self::$instance === null) {

            self::$instance = new self();

        }

        return self::$instance;

    }



    public function getPDO() {

        return $this->pdo;

    }



    private function __clone() {}



    public function __wakeup() {

        throw new \Exception("Não é permitido desserializar a instância de Database");

    }
}

