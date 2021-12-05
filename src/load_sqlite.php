<?php
declare(strict_types=1);

return function (): void {
    static $loaded = false;

    if ($loaded) {
        return;
    }

    if (!class_exists(\CommunityHub\Eav\Entity::class, false)) {
        require __DIR__ . '/classes/Entity.php';
    }

    if (!class_exists(\CommunityHub\Eav\Query::class, false)) {
        require __DIR__ . '/classes/Query.php';
    }

    if (!interface_exists(\CommunityHub\Eav\Drivers\Driver::class, false)) {
        require __DIR__ . '/classes/Drivers/Driver.php';
    }

    if (!trait_exists(\CommunityHub\Eav\Drivers\UsesPdo::class, false)) {
        require __DIR__ . '/classes/Drivers/UsesPdo.php';
    }

    if (!class_exists(\CommunityHub\Eav\Drivers\Statement::class, false)) {
        require __DIR__ . '/classes/Drivers/Statement.php';
    }

    if (!class_exists(\CommunityHub\Eav\Drivers\Sqlite::class, false)) {
        require __DIR__ . '/classes/Drivers/Sqlite.php';
    }

    $loaded = true;
};
