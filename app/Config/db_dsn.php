<?php

function app_db_driver(): string
{
    return app_env('DB_CONNECTION', 'mysql') ?? 'mysql';
}

/** Corrige pooler Supabase sa-east-1 (projeto no aws-1, não aws-0). */
function app_normalize_db_host_port(string $host, string $port): array
{
    if ($host === 'aws-0-sa-east-1.pooler.supabase.com') {
        $host = 'aws-1-sa-east-1.pooler.supabase.com';
        if ($port === '6543') {
            $port = '5432';
        }
    }
    return [$host, $port];
}

function app_build_pdo_dsn(
    string $driver,
    string $host,
    string $port,
    string $dbname,
    ?string $cloudSqlInstance = null
): string {
    if ($driver === 'pgsql') {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $dbname);
        $sslmode = app_env('DB_SSLMODE', 'require');
        if ($sslmode !== '' && $sslmode !== 'disable') {
            $dsn .= ';sslmode=' . $sslmode;
        }
        return $dsn;
    }

    if ($cloudSqlInstance !== null && $cloudSqlInstance !== '') {
        return sprintf(
            'mysql:unix_socket=/cloudsql/%s;dbname=%s;charset=utf8mb4',
            $cloudSqlInstance,
            $dbname
        );
    }

    return sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $dbname
    );
}

function app_apply_db_timezone(PDO $pdo, string $driver): void
{
    if ($driver === 'pgsql') {
        $pdo->exec("SET TIME ZONE 'America/Sao_Paulo'");
        return;
    }
    $pdo->exec("SET time_zone = '-03:00'");
}
