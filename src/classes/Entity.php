<?php
declare(strict_types=1);

namespace CommunityHub\Eav;

use InvalidArgumentException;

final class Entity
{
    private const VALID_TYPES = [
        'boolean',
        'integer',
        'double',
        'string',
        'NULL',
    ];

    private array $attributes = [];

    private ?string $uid = null;

    public function __construct(?string $uid = null)
    {
        $this->uid = $uid;
    }

    public function withUid(?string $uid): self
    {
        $new = clone $this;
        $new->uid = $uid;

        return $new;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function withoutAttribute(string $name): self
    {
        $new = clone $this;

        if (isset($new->attributes[$name])) {
            unset($new->attributes[$name]);
        }

        return $new;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function withAttribute(string $name, mixed $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $this->assertValidValue($value);

        return $new;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $this->assertValidValue($default);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function assertValidValue(mixed $value): mixed
    {
        $type = gettype($value);

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (!in_array($type, self::VALID_TYPES)) {
            $type = is_object($value) ? get_class($value) : $type;
            $message = sprintf('Invalid attribute type: %s. ', $type);

            throw new InvalidArgumentException($message);
        }

        return $value;
    }
}
