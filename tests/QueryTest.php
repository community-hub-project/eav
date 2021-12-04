<?php
declare(strict_types=1);

namespace Tests;

use CommunityHub\Eav\Query;

use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    /**
     * @test
     * @dataProvider provider
     */
    public function it_should_build_a_simple_static_query(string $method, string $expectedOperator): void
    {
        $result = Query::$method('attribute', 'value')->toArray();

        $expected = [
            'and',
            [
                ['attribute', $expectedOperator, 'value'],
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * @test
     * @dataProvider provider
     */
    public function it_should_build_a_simple_dynamic_query(string $method, string $expectedOperator): void
    {
        $result = Query::equals('attribute_1', 'value_1')
            ->$method('attribute_2', 'value_2')
            ->toArray();

        $expected = [
            'and',
            [
                ['attribute_1', '=', 'value_1'],
                ['attribute_2', $expectedOperator, 'value_2'],
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function it_should_build_a_static_and_query(): void
    {
        $query = Query::and(function (Query $query): void {
            $query
                ->equals('attribute 1', 'value 1')
                ->equals('attribute 2', 'value 2');
        })->equals('attribute 3', 'value 3');

        $expected = [
            'and',
            [
                [
                    'and',
                    [
                        ['attribute 1', '=', 'value 1'],
                        ['attribute 2', '=', 'value 2'],
                    ],
                ],
                ['attribute 3', '=', 'value 3'],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_should_build_a_static_or_query(): void
    {
        $query = Query::or(function (Query $query): void {
            $query
                ->equals('attribute 1', 'value 1')
                ->equals('attribute 2', 'value 2');
        })->equals('attribute 3', 'value 3');

        $expected = [
            'and',
            [
                [
                    'or',
                    [
                        ['attribute 1', '=', 'value 1'],
                        ['attribute 2', '=', 'value 2'],
                    ],
                ],
                ['attribute 3', '=', 'value 3']
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_should_build_a_dynamic_or_query(): void
    {
        $query = Query::equals('attribute 3', 'value 3')
            ->or(function (Query $query): void {
                $query
                    ->equals('attribute 1', 'value 1')
                    ->equals('attribute 2', 'value 2');
            });

        $expected = [
            'and',
            [
                ['attribute 3', '=', 'value 3'],
                [
                    'or',
                    [
                        ['attribute 1', '=', 'value 1'],
                        ['attribute 2', '=', 'value 2'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_should_remove_the_additional_and_from_an_or_query(): void
    {
        $query = Query::or(function (Query $query): void {
            $query
                ->equals('attribute 1', 'value 1')
                ->equals('attribute 2', 'value 2');
        });

        $expected = [
            'or',
            [
                ['attribute 1', '=', 'value 1'],
                ['attribute 2', '=', 'value 2'],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    /**
     * @test
     */
    public function it_should_remove_all_redundant_groups(): void
    {
        $query = Query::and(function (Query $query): void {
            $query->or(function (Query $query): void {
                $query
                    ->and(function (Query $query): void {
                        $query
                            ->equals('attribute 1', 'value 1')
                            ->equals('attribute 2', 'value 2');
                    })
                    ->equals('attribute 3', 'value 3');
            });
        });

        $expected = [
            'or',
            [
                [
                    'and',
                    [
                        ['attribute 1', '=', 'value 1'],
                        ['attribute 2', '=', 'value 2'],
                    ],
                ],
                ['attribute 3', '=', 'value 3'],
            ],
        ];

        $this->assertSame($expected, $query->toArray());
    }

    public function provider(): array
    {
        return [
            ['equals', '='],
            ['notEquals', '!='],
            ['greaterThan', '>'],
            ['greaterThanOrEqualTo', '>='],
            ['lessThan', '<'],
            ['lessThanOrEqualTo', '<='],
            ['leftLike', '*LIKE'],
            ['rightLike', 'LIKE*'],
            ['like', '*LIKE*'],
        ];
    }
}
