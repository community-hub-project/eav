<?php
declare(strict_types=1);

namespace CommunityHub\Eav\Drivers;

use UnexpectedValueException;

final class Statement
{
    private $paramNameGenerator;

    private array $parameters = [];

    private string $text = '';

    public function __construct(?callable $paramNameGenerator = null)
    {
        if (null === $paramNameGenerator) {
            $paramNameGenerator = function (): string {
                static $i;

                $i = (null === $i) ? 0 : ++$i;

                return '{param' . $i . '}';
            };
        }

        $this->paramNameGenerator = $paramNameGenerator;
    }

    public function addText(string $text): static
    {
        $this->text .= $text;

        return $this;
    }

    /**
     * @throws UnexpectedValueException
     */
    public function addValue(mixed $value): static
    {
        $key = $this->findParameter($value);

        if (null === $key) {
            $key = $this->addParameter($value);
        }

        $this->text .= $key;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getText(): string
    {
        return $this->text;
    }

    private function findParameter(mixed $value): ?string
    {
        $result = array_search($value, $this->parameters);

        return $result ? $result : null;
    }

    /**
     * @throws UnexpectedValueException
     */
    private function addParameter(mixed $value): string
    {
        $key = ($this->paramNameGenerator)();

        if (!is_string($key)) {
            $message = sprintf(
                'Parameter name generator must return a string, got %s.',
                gettype($key)
            );

            throw new UnexpectedValueException($message);
        }

        if (empty($key)) {
            throw new UnexpectedValueException(
                'Parameter name generator must not return an empty string.'
            );
        }

        if (array_key_exists($key, $this->parameters)) {
            $message = sprintf('Parameter name already exists: %s.', $key);

            throw new UnexpectedValueException($message);
        }

        $this->parameters[$key] = $value;

        return $key;
    }
}
