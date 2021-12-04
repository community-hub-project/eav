<?php
declare(strict_types=1);

namespace CommunityHub\Eav;

use CommunityHub\Eav\Drivers\Sqlite;

use RuntimeException;
use PDOException;

use function call_user_func;

/**
 * @throws PDOException
 */
function create_sqlite_in_memory(string $prefix = ''): Sqlite
{
    call_user_func(require __DIR__ . '/load_sqlite.php');

    return Sqlite::inMemory($prefix);
}

/**
 * @throws RuntimeException
 * @throws PDOException
 */
function create_sqlite_in_file(string $path, string $prefix = ''): Sqlite
{
    call_user_func(require __DIR__ . '/load_sqlite.php');

    return Sqlite::inFile($path, $prefix);
}
