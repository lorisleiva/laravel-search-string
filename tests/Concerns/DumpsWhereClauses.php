<?php

namespace Lorisleiva\LaravelSearchString\Tests\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait DumpsWhereClauses
{
    public function assertWhereClauses($input, $expected)
    {
        if (! method_exists($this, 'getBuilderFor')) {
            return;
        }

        $wheres = $this->dumpWhereClauses($this->getBuilderFor($input));
        $this->assertEquals($expected, $wheres);
    }

    public function dumpWhereClauses(EloquentBuilder $builder)
    {
        return $this->dumpWhereClausesForQuery($builder->getQuery());
    }

    public function dumpWhereClausesForQuery(QueryBuilder $query)
    {
        return collect($query->wheres)->mapWithKeys(function ($where) {
            $where = (object) $where;
            $key = "$where->type[$where->boolean]";

            if (isset($where->query)) {
                $children = $this->dumpWhereClausesForQuery($where->query);
                return [$key => $children];
            }

            $value = $where->value ?? $where->values;
            $value = is_array($value) ? ('[' . implode(', ', $value) . ']') : $value;
            $value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            $value = isset($where->operator) ? "$where->operator $value" : $value;

            return [$key => "$where->column $value"];
        })->toArray();
    }
}