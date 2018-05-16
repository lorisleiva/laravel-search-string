<?php

namespace Lorisleiva\LaravelSearchString\Tests\Concerns;

use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;

trait GeneratesEloquentBuilder
{
    protected function getDummyBuilder($model = null)
    {
        return ($model ?? new DummyModel)->newQuery();
    }

    public function getBuilderFor($input, $model = null)
    {
        $builder = $this->getDummyBuilder($model);
        $manager = $this->getSearchStringManager($model);
        $ast = $this->parse($input);

        foreach ($this->visitors($builder, $manager) as $visitor) {
            $ast = $ast->accept($visitor);
        }

        return $builder;
    }

    public function visitors($builder, $manager)
    {
        return [];
    }
}