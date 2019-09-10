<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Illuminate\Support\Arr;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

abstract class Rule
{
    public $column;
    public $key;
    public $operator = '/.*/';
    public $value = '/.*/';

    public function __construct($column, $rule = null)
    {
        if (is_null($rule)) {
            $rule = [];
        }

        if (is_string($rule)) {
            $rule = [ 'key' => $rule ];
        }

        $this->column = $column;
        $this->key = $this->getPattern($rule, 'key', $column);
        $this->operator = $this->getPattern($rule, 'operator');
        $this->value = $this->getPattern($rule, 'value');
    }

    public function match($key, $operator = null, $values = null)
    {
        $valueMatch = collect($values)->every(function ($value) {
            return preg_match($this->value, $value);
        });

        return preg_match($this->key, $key)
            && (is_null($operator) || preg_match($this->operator, $operator))
            && (is_null($values) || $valueMatch);
    }

    public function matchQuery(QuerySymbol $query)
    {
        return $this->match($query->key, $query->operator, $query->value);
    }

    protected function getPattern($rawRule, $key, $default = null)
    {
        $default = $default ?? $this->$key;
        $pattern = Arr::get($rawRule, $key, $default);
        $pattern = is_null($pattern) ? $default : $pattern;
        return $this->regexify($pattern);
    }

    protected function regexify($pattern)
    {
        try {
            preg_match($pattern, null);
            return $pattern;
        } catch (\Throwable $exception) {
            return '/^' . preg_quote($pattern, '/') . '$/';
        }
    }

    public function __toString()
    {
        return "[$this->key $this->operator $this->value]";
    }
}
