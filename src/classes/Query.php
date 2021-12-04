<?php
declare(strict_types=1);

namespace CommunityHub\Eav;

use LogicException;
use Throwable;

use function call_user_func;
use function method_exists;
use function sprintf;
use function count;

final class Query
{
    private const AND = 'and';

    private const OR = 'or';

    private const EQUAL = '=';

    private const NOT_EQUAL = '!=';

    private const GREATER_THAN = '>';

    private const GREATER_THAN_OR_EQUAL_TO = '>=';

    private const LESS_THAN = '<';

    private const LESS_THAN_OR_EQUAL_TO = '<=';

    private const LEFT_LIKE = '*LIKE';

    private const RIGHT_LIKE = 'LIKE*';

    private const LIKE = '*LIKE*';

    private array $conditions = [];

    private string $type;

    public static function __callStatic(string $name, array $args): mixed
    {
        return (new self(self::AND))->__call($name, $args);
    }

    public function __call(string $name, array $args): mixed
    {
        try {
            return call_user_func([$this, 'call' . $name], ...$args);
        } catch (Throwable $e) {
            if (!method_exists($this, 'call' . $name)) {
                throw new LogicException(sprintf(
                    'No such method exists: %s::%s.',
                    __CLASS__,
                    $name
                ));
            }

            throw $e;
        }
    }

    public function toArray(): array
    {
        return $this->compile($this->type, $this->conditions);
    }

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    private function callAnd(callable $func): self
    {
        $query = new self(self::AND);

        $func($query);

        $this->conditions[] = clone $query;

        return $this;
    }

    private function callOr(callable $func): self
    {
        $query = new self(self::OR);

        $func($query);

        $this->conditions[] = clone $query;

        return $this;
    }

    private function callGreaterThan(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::GREATER_THAN);
    }

    private function callGreaterThanOrEqualTo(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::GREATER_THAN_OR_EQUAL_TO);
    }

    private function callLessThan(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::LESS_THAN);
    }

    private function callLessThanOrEqualTo(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::LESS_THAN_OR_EQUAL_TO);
    }

    private function callNotEquals(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::NOT_EQUAL);
    }

    private function callEquals(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::EQUAL);
    }

    private function callLike(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::LIKE);
    }

    private function callLeftLike(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::LEFT_LIKE);
    }

    private function callRightLike(string $attribute, mixed $value): self
    {
        return $this->addCondition($attribute, $value, self::RIGHT_LIKE);
    }

    private function addCondition(string $attribute, mixed $value, string $operator): self
    {
        $this->conditions[] = [$attribute, $operator, $value];

        return $this;
    }

    private function compile(string $type, array $conditions): array
    {
        foreach ($conditions as $i => $condition) {
            if ($condition instanceof self) {
                $condition = $condition->toArray();

                if (1 === count($conditions)) {
                    return $condition;
                }
            }

            $conditions[$i] = $condition;
        }

        return [$type, $conditions];
    }
}
