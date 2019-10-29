<?php

namespace Fixtures\Tests;

/**
 * @runParallel
 * @pizzaBox
 */
class UnitTestWithClassAnnotationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @group fixtures
     * @pizza
     */
    public function testTruth()
    {
        $this->assertTrue(true);
    }

    /**
     * @group fixtures
     * @pizza
     */
    public function testFalsehood()
    {
        $this->assertFalse(true);
    }

    /**
     * @group fixtures
     */
    public function testArrayLength()
    {
        $elems = [1,2,3,4,5];
        $this->assertEquals(5, sizeof($elems));
    }

    /**
     * @test
     */
    public function itsATest()
    {
        $this->assertTrue(true);
    }

    private function helperFunction()
    {
        echo 'I am super helpful';
    }
}
