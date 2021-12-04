<?php
declare(strict_types=1);

namespace Drivers;

use CommunityHub\Eav\Drivers\Statement;

use PHPUnit\Framework\TestCase;

use UnexpectedValueException;

class StatementTest extends TestCase
{
    private Statement $statement;

    /**
     * @test
     */
    public function it_should_have_an_empty_text_string_by_default(): void
    {
        $this->assertSame('', $this->statement->getText());
    }

    /**
     * @test
     */
    public function it_should_return_an_empty_parameter_array_by_default(): void
    {
        $this->assertSame([], $this->statement->getParameters());
    }

    /**
     * @test
     */
    public function it_should_add_text(): void
    {
        $return = $this->statement->addText('TEXT');

        $this->assertSame($this->statement, $return);
        $this->assertSame('TEXT', $this->statement->getText());
        $this->assertSame([], $this->statement->getParameters());
    }

    /**
     * @test
     */
    public function it_should_add_a_value(): void
    {
        $return = $this->statement->addValue('VALUE');

        $this->assertSame($this->statement, $return);
        $this->assertSame('{param0}', $this->statement->getText());
        $this->assertSame(['{param0}' => 'VALUE'], $this->statement->getParameters());
    }

    /**
     * @test
     */
    public function it_should_add_2_values(): void
    {
        $return1 = $this->statement->addValue('VALUE 1');
        $return2 = $this->statement->addValue('VALUE 2');

        $expected = [
            '{param0}' => 'VALUE 1',
            '{param1}' => 'VALUE 2'
        ];

        $this->assertSame($this->statement, $return1);
        $this->assertSame($this->statement, $return2);
        $this->assertSame('{param0}{param1}', $this->statement->getText());
        $this->assertSame($expected, $this->statement->getParameters());
    }

    /**
     * @test
     */
    public function it_should_not_add_the_same_value_twice(): void
    {
        $return1 = $this->statement->addValue('VALUE');
        $return2 = $this->statement->addValue('VALUE');

        $expected = [
            '{param0}' => 'VALUE',
        ];

        $this->assertSame($this->statement, $return1);
        $this->assertSame($this->statement, $return2);
        $this->assertSame('{param0}{param0}', $this->statement->getText());
        $this->assertSame($expected, $this->statement->getParameters());
    }

    /**
     * @test
     */
    public function it_should_accept_a_non_standard_parameter_generator(): void
    {
        $statement = new Statement(function (): string {
            static $i;

            $i = (null === $i) ? 10 : ++$i;

            return '{param' . $i . '}';
        });

        $return1 = $statement->addValue('VALUE 1');
        $return2 = $statement->addValue('VALUE 2');

        $expected = [
            '{param10}' => 'VALUE 1',
            '{param11}' => 'VALUE 2'
        ];

        $this->assertSame($statement, $return1);
        $this->assertSame($statement, $return2);
        $this->assertSame('{param10}{param11}', $statement->getText());
        $this->assertSame($expected, $statement->getParameters());
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_returned_parameter_name_is_not_a_string(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $statement = new Statement(function (): int {
            return 0;
        });

        $statement->addValue('VALUE 1');
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_returned_parameter_is_an_empty_string(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $statement = new Statement(function (): string {
            return '';
        });

        $statement->addValue('VALUE 1');
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_returned_parameter_is_always_the_same(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $statement = new Statement(function (): string {
            return 'a';
        });

        $statement->addValue('VALUE 1');
        $statement->addValue('VALUE 2');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->statement = new Statement();
    }
}
