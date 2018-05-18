<?php

namespace Lorisleiva\LaravelSearchString;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Lexer\Lexer;
use Lorisleiva\LaravelSearchString\Options\SearchStringOptions;
use Lorisleiva\LaravelSearchString\Parser\Parser;

class SearchStringManager
{
    use SearchStringOptions;

    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->generateOptions($model);
    }

    public function lex($input, $tokenMap = null, $delimiter = null)
    {
        return (new Lexer($tokenMap, $delimiter))->lex($input);
    }

    public function parse($input)
    {
        return (new Parser)->parse($this->lex($input));
    }

    public function build(Builder $builder, $input)
    {
        $ast = $this->parse($input);
        $visitors = $this->model->getSearchStringVisitors($this, $builder);

        foreach ($visitors as $visitor) {
            $ast = $ast->accept($visitor);
        }
    }

    public function updateBuilder(Builder $builder, $input)
    {
        try {
            $this->build($builder, $input);
        } catch (InvalidSearchStringException $e) {
            switch (config('search-string.fail')) {
                case 'exceptions':
                    throw $e;

                case 'no-results':
                    return $builder->limit(0);
                
                default:
                    return $builder;
            }
        }
    }

    public function createBuilder($input)
    {
        $builder = $this->model->newQuery();
        $this->updateBuilder($builder, $input);
        return $builder;
    }
}