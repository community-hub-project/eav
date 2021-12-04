<?php
declare(strict_types=1);

namespace CommunityHub\Eav\Drivers;

use PDOException;
use PDOStatement;
use PDO;

use InvalidArgumentException;
use Throwable;

use function call_user_func;
use function sprintf;

trait UsesPdo
{
    protected function runPdoTask(callable $func): mixed
    {
        try {
            $func();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 0, $e);
        }
    }

    protected function bindAndExecuteStatement(PDO $pdo, string $sql, array $params = []): PDOStatement
    {
        $pdoStatement = $pdo->prepare($sql);

        if (false === $pdoStatement) {
            throw new PDOException(sprintf(
                'Could not prepare statement: %s.',
                $sql
            ));
        }

        foreach ($params as $name => $value) {
            switch ($phpType = gettype($value)) {
                case 'boolean':
                case 'integer':
                case 'double':
                case 'string':
                    $pdoType = PDO::PARAM_STR;
                    break;

                case 'NULL':
                    $pdoType = PDO::PARAM_NULL;
                    break;

                default:
                    $message = sprintf('Invalid PDO type: %s.', $phpType);

                    throw new InvalidArgumentException($message);
            }

            if (false === $pdoStatement->bindValue($name, $value, $pdoType)) {
                $message = sprintf('Could not bind parameter to statement: %s.', $name);

                throw new PDOException($message);
            }
        }

        if (!$pdoStatement->execute()) {
            $message = sprintf('Statement could not be executed: %s.', $sql);

            throw new PDOException($message);
        }

        return $pdoStatement;
    }

    protected function transaction(PDO $pdo, callable $func): mixed
    {
        if (false === $pdo->beginTransaction()) {
            throw new PDOException('Could not begin PDO transaction.');
        }

        try {
            $result = call_user_func($func, $pdo);
        } catch (Throwable $e) {
            if (false === $pdo->rollBack()) {
                throw new PDOException('Could not roll back transaction.', 0, $e);
            }

            throw $e;
        }

        if (false === $pdo->commit()) {
            throw new PDOException('Could not commit PDO transaction.');
        }

        return $result;
    }
}
