<?php
declare(strict_types=1);

return function (): void {
    static $loaded = false;

    if ($loaded) {
        return;
    }

    if (!class_exists(\CommunityHub\Eav\Entity::class)) {
        require __DIR__ . '/classes/Entity.php';
    }

    if (!class_exists(\CommunityHub\Eav\Query::class)) {
        require __DIR__ . '/classes/Query.php';
    }

    if (!interface_exists(\CommunityHub\Eav\Drivers\Driver::class)) {
        require __DIR__ . '/classes/Drivers/Driver.php';
    }

    if (!trait_exists(\CommunityHub\Eav\Drivers\UsesPdo::class)) {
        require __DIR__ . '/classes/Drivers/UsesPdo.php';
    }

    if (!class_exists(\CommunityHub\Eav\Drivers\Statement::class)) {
        require __DIR__ . '/classes/Drivers/Statement.php';
    }

    if (!class_exists(\CommunityHub\Eav\Drivers\Sqlite::class)) {
        require __DIR__ . '/classes/Drivers/Sqlite.php';
    }

    $loaded = true;
};
