<?php

namespace Lorisleiva\LaravelSearchString;

use Hoa\Compiler\Llk\Lexer;
use Hoa\Compiler\Llk\Llk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
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

    public function lex($input)
    {
        Llk::parsePP(file_get_contents(__DIR__ . '/Compiler/Grammar.pp'), $tokens, $rules, $pragmas, 'streamName');
        $lexer = new Lexer($pragmas);
        return $lexer->lexMe($input, $tokens);
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
