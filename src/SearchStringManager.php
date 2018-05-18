<?php

namespace Lorisleiva\LaravelSearchString;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Lexer\Lexer;
use Lorisleiva\LaravelSearchString\Options\SearchStringOptions;
use Lorisleiva\LaravelSearchString\Parser\Parser;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SoloSymbol;
use Lorisleiva\LaravelSearchString\Support\DateWithPrecision;
use Lorisleiva\LaravelSearchString\Visitor\BuildWhereClausesVisitor;
use Lorisleiva\LaravelSearchString\Visitor\OptimizeAstVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveKeywordsVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;
use Lorisleiva\LaravelSearchString\Visitor\ResolveKeywordsVisitor;

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

    public function updateBuilder(Builder $builder, $input)
    {
        $ast = $this->parse($input);

        foreach ($this->getVisitors($builder) as $visitor) {
            $ast = $ast->accept($visitor);
        }
    }

    public function createBuilder($input)
    {
        $builder = $this->model->newQuery();
        $this->updateBuilder($builder, $input);
        return $builder;
    }
    
    public function getVisitors($builder)
    {
        return [
            new RemoveNotSymbolVisitor,
            new ResolveKeywordsVisitor($builder, $this),
            new RemoveKeywordsVisitor($this),
            new OptimizeAstVisitor,
            new BuildWhereClausesVisitor($builder, $this),
        ];
    }
}