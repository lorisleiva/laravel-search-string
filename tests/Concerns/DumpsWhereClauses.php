<?php

namespace Lorisleiva\LaravelSearchString\Tests\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;

trait DumpsWhereClauses
{
    public function assertWhereClauses($input, $expected, $model = null)
    {
        if (! method_exists($this, 'getBuilderFor')) {
            return;
        }

        $wheres = $this->dumpWhereClauses($this->getBuilderFor($input, $model));
        $this->assertEquals($expected, $wheres);
    }

    public function dumpWhereClauses(EloquentBuilder $builder)
    {
        return $this->dumpWhereClausesForQuery($builder->getQuery());
    }

    public function dumpWhereClausesForQuery(QueryBuilder $query)
    {
        return collect($query->wheres)->mapWithKeys(function ($where, $i){
            $where = (object) $where;
            $key = "$where->type[{$where->boolean}][$i]";

            // if ($where->column instanceof Expression) {
                // $value = $where->column->getValue(); // TODO
            // }

            if (isset($where->query)) {
                $children = $this->dumpWhereClausesForQuery($where->query);
                return [$key => $children];
            }

            if ($where->type == 'Column') {
                return [$key => "$where->first $where->operator $where->second"];
            }

            $value = $where->value ?? $where->values ?? null;
            $value = is_array($value) ? ('[' . implode(', ', $value) . ']') : $value;
            $value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            $value = isset($where->operator) ? "$where->operator $value" : $value;

            return [$key => is_null($value) ? $where->column : "$where->column $value"];

        })->toArray();
    }
}