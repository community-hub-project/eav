<?php
declare(strict_types=1);

namespace CommunityHub\Eav\Drivers;

use CommunityHub\Eav\Entity;
use CommunityHub\Eav\Query;

use InvalidArgumentException;
use RuntimeException;

use PDOException;
use PDO;

final class Sqlite implements Driver
{
    use UsesPdo;

    private const TABLE_ATTRIBUTES = '_attributes';

    private const TABLE_ENTITIES = '_entities';

    private array $pots = [];

    private string $prefix;

    private PDO $pdo;

    /**
     * @throws PDOException
     */
    public static function inMemory(string $prefix = ''): self
    {
        $pdo = new PDO('sqlite::memory:');

        return new self($pdo, $prefix);
    }

    /**
     * @throws RuntimeException
     * @throws PDOException
     */
    public static function inFile(string $path, string $prefix = ''): self
    {
        if (!file_exists($path) && !touch($path)) {
            throw new RuntimeException('Could not create sqlite file: %s.', $path);
        }

        $pdo = new PDO('sqlite:' . $path);

        return new self($pdo, $prefix);
    }

    public function remove(string $pot, Query $query): static
    {
        $entities = $this->get($pot, $query);

        if (empty($entities)) {
            return $this;
        }

        $uids = array_map(fn ($entity) => $entity->getUid(), $entities);

        $entitiesTable = $this->prefix . $pot . self::TABLE_ENTITIES;

        $statement = (new Statement)
            ->addText('DELETE FROM ' . $entitiesTable)
            ->addText('    WHERE ' . $entitiesTable . '.id IN (');

        foreach ($uids as $i => $uid) {
            $statement->addValue($uid);

            if ($i !== (count($uids) - 1)) {
                $statement->addText(',');
            }
        }

        $statement->addText(')');

        $this->runPdoTask(fn () => $this->bindAndExecuteStatement(
            $this->pdo,
            $statement->getText(),
            $statement->getParameters()
        ));

        return $this;
    }

    public function get(string $pot, Query $query, int $offset = 0, ?int $length = null): array
    {
        if (0 > $offset) {
            throw new InvalidArgumentException('Offset cannot be less than 0');
        }

        if ((null !== $length) || (0 > $length)) {
            throw new InvalidArgumentException('Length cannot be less than 0');
        }

        $this->processQuerySegment($statement = new Statement, $pot, $query->toArray());

        //

        $rows = $this->runPdoTask(
            fn () => $this->bindAndExecuteStatement(
                $this->pdo,
                $statement->getText(),
                $statement->getParemeters()
            )
        );
    }

    public function put(string $pot, Entity ...$entities): array
    {
        //
    }

    private function __construct(PDO $pdo, string $prefix)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->prefix = $prefix;
        $this->pdo = $pdo;
    }

    private function processQuerySegment(Statement $statement, string $pot, array $query): void
    {
        [$type, $conditions] = $query;

        $delimiter = ' ' . strtoupper($type) . ' ';

        foreach ($conditions as $i => $condition) {
            if (0 !== $i) {
                $statement->addText($delimiter);
            }

            if (2 === count($condition)) {
                $statement->addText('(');

                $this->processQuerySegment($statement, $pot, $condition);

                $statement->addText(')');

                continue;
            }

            [$attribute, $operator, $value] = $condition;

            $statement
                ->addText('(')
                ->addText($this->prefix . $pot . '_attributes.name = ')
                ->addValue($attribute)
                ->addText(' AND ')
                ->addText($this->prefix . $pot . '_entities.value ')
                ->addText($operator)
                ->addText(' ')
                ->addValue($value)
                ->addText(')');
        }
    }




    /**
     * @throws Exception
     */
    private function createPotIfNotExists(string $pot): void
    {
        if (!in_array($pot, $this->pots)) {
            $this->assertValidTableName($this->prefix . $pot . self::TABLE_ATTRIBUTES);
            $this->assertValidTableName($this->prefix . $pot . self::TABLE_ENTITIES);

            if (!$this->potExists($pot)) {
                $this->createPot($pot);
            }

            $this->pots[] = $pot;
        }
    }

    /**
     * @throws Exception
     */
    private function assertValidTableName(string $name): void
    {
        if (!preg_match('~^[a-zA-Z0-9]+$~', $name)) {
            throw new Exception(sprintf(
                'Table names must be alphanumeric characters only'
                . ' (to help prevent injection), got %s.',
                $name
            ));
        }

        if (str_starts_with($name, 'sqlite_')) {
            throw new Exception(sprintf(
                'Table names cannot begin with sqlite: %s.',
                $name
            ));
        }
    }

    /**
     * @throws Exception
     */
    private function potExists(string $pot): bool
    {
        $prefix = $this->prefix . $pot;
        $attributesTableExists = $this->tableExists($prefix . self::TABLE_ATTRIBUTES);
        $entitiesTableExists = $this->tableExists($prefix . self::TABLE_ENTITIES);

        if (!$attributesTableExists || !$entitiesTableExists) {
            if ($attributesTableExists) {
                throw new Exception(sprintf(
                    'Pot attributes table exists, pot entities table does not: %s.',
                    $pot
                ));
            }

            if ($entitiesTableExists) {
                throw new Exception(sprintf(
                    'Pot entities table exists, pot attributes table does not: %s.',
                    $pot
                ));
            }

            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    private function tableExists(string $name): bool
    {
        $statement = (new Statement)
            ->addText('SELECT COUNT(*) as exists')
            ->addText('    FROM sqlite_master')
            ->addText('    WHERE type="table"')
            ->addText('    AND name="' . $name . '"');

        $rows = $this->runPdoTask(
            fn () => $this
                ->bindAndExecuteStatement($this->pdo, $statement->getText())
                ->fetchAll(PDO::FETCH_NUM)
        );

        return 0 === $rows[0][0];
    }

    /**
     * @throws Exception
     */
    private function createPot(string $pot)
    {
        $this->runPdoTask(
            fn () => $this->transaction($this->pdo, function () use ($pot): void {
                $this->createAttributesTable($pot);
                $this->createEntitiesTable($pot);
            })
        );
    }

    /**
     * @throws Exception
     */
    private function createAttributesTable(string $pot): void
    {
        $table = $this->prefix . $pot;

        $statement = (new Statement)
            ->addText('CREATE TABLE ' . $table . self::TABLE_ATTRIBUTES)
            ->addText('    id TEXT PRIMARY KEY,')
            ->addText('    name TEXT')
            ->addText(') WITHOUT ROWID');

        $this->runPdoTask(
            fn () => $this->bindAndExecuteStatement($this->pdo, $statement->getText())
        );
    }

    /**
     * @throws Exception
     */
    private function createEntitiesTable(string $pot): void
    {
        $table = $this->prefix . $pot;

        $statement = (new Statement)
            ->addText('CREATE TABLE ' . $table . self::TABLE_ENTITIES)
            ->addText('    id TEXT PRIMARY KEY,')
            ->addText('    name TEXT')
            ->addText(') WITHOUT ROWID');

        $this->runPdoTask(
            fn () => $this->bindAndExecuteStatement($this->pdo, $statement->getText())
        );
    }
}
