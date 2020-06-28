<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;

abstract class VisitorTest extends TestCase
{
    public function visitors($manager, $builder, $model)
    {
        return [];
    }

    public function getAst($input, $model = null)
    {
        $manager = $this->getSearchStringManager($model = $model ?? new DummyModel);
        $ast = $this->parse($input);

        foreach ($this->visitors($manager, $model->newQuery(), $model) as $visitor) {
            $ast = $ast->accept($visitor);
        }

        return $ast;
    }

    public function assertAstFor($input, $expectedAst, $model = null)
    {
        $this->assertEquals($expectedAst, $this->getAst($input, $model));
    }

    public function assertAstFails($input, $unexpectedToken = null, $model = null)
    {
        try {
            $ast = $this->getAst($input, $model);
            $this->fail("Expected \"$input\" to fail. Instead got: \"$ast\"");
        } catch (InvalidSearchStringException $e) {
            if ($unexpectedToken) {
                $this->assertEquals($unexpectedToken, $e->getToken());
            } else {
                $this->assertTrue(true);
            }
        }
    }
}
