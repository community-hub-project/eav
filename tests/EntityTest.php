<?php
declare(strict_types=1);

namespace Tests;

use CommunityHub\Eav\Entity;

use PHPUnit\Framework\TestCase;

use InvalidArgumentException;

class EntityTest extends TestCase
{
    private Entity $entity;

    /**
     * @test
     */
    public function it_should_return_an_id(): void
    {
        $this->assertSame('UID', $this->entity->getUid());
    }

    /**
     * @test
     */
    public function it_should_not_set_a_uid_by_default(): void
    {
        $entity = new Entity;

        $this->assertNull($entity->getUid());
    }

    /**
     * @test
     */
    public function it_should_return_a_different_object_with_a_new_uid(): void
    {
        $entity = $this->entity->withUid('UID2');

        $this->assertSame('UID2', $entity->getUid());
        $this->assertSame('UID', $this->entity->getUid());
        $this->assertNotSame($this->entity, $entity);
    }

    /**
     * @test
     */
    public function it_should_return_a_different_object_with_a_uid_of_null(): void
    {
        $entity = $this->entity->withUid(null);

        $this->assertNull($entity->getUid());
        $this->assertSame('UID', $this->entity->getUid());
        $this->assertNotSame($this->entity, $entity);
    }

    /**
     * @test
     */
    public function it_should_return_a_different_object_with_an_attribute(): void
    {
        $entity = $this->entity->withAttribute('attribute', 'value');

        $this->assertSame('value', $entity->getAttribute('attribute'));
        $this->assertNull($this->entity->getAttribute('attribute'));
        $this->assertNotSame($this->entity, $entity);
    }

    /**
     * @test
     */
    public function it_should_return_a_default_value_if_an_attribute_does_not_exist(): void
    {
        $attribute = $this->entity->getAttribute('attribute', 'default');

        $this->assertSame('default', $attribute);
    }

    /**
     * @test
     */
    public function it_should_return_a_null_by_default_if_an_attribute_does_not_exist(): void
    {
        $attribute = $this->entity->getAttribute('attribute');

        $this->assertNull($attribute);
    }

    /**
     * @test
     */
    public function it_should_not_set_an_attribute_of_an_illegal_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->entity->withAttribute('attribute', []);
    }

    /**
     * @test
     */
    public function it_should_not_return_a_default_attribute_of_an_illegal_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->entity->getAttribute('attribute', []);
    }

    /**
     * @test
     */
    public function it_should_unset_an_attribute(): void
    {
        $entity = $this
            ->entity
            ->withAttribute('attribute 1', 'value 1')
            ->withAttribute('attribute 2', 'value 2')
            ->withoutAttribute('attribute 1');

        $this->assertSame(
            ['attribute 2' => 'value 2'],
            $entity->getAttributes()
        );
    }

    /**
     * @test
     */
    public function it_should_do_nothing_if_unsetting_an_attribute_that_does_not_exist(): void
    {
        $entity = $this
            ->entity
            ->withAttribute('attribute 1', 'value 1')
            ->withAttribute('attribute 2', 'value 2')
            ->withoutAttribute('attribute 3');

        $this->assertSame(
            [
                'attribute 1' => 'value 1',
                'attribute 2' => 'value 2',
            ],
            $entity->getAttributes()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = new Entity('UID');
    }
}
