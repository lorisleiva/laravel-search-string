<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\EmptySymbol;
use Lorisleiva\LaravelSearchString\AST\NotSymbol;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\Visitors\AttachRulesVisitor;
use Lorisleiva\LaravelSearchString\Visitors\IdentifyRelationshipsFromRulesVisitor;
use Lorisleiva\LaravelSearchString\Visitors\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitors\OptimizeAstVisitor;
use Lorisleiva\LaravelSearchString\Visitors\RemoveNotSymbolVisitor;

/**
 * @see OptimizeAstVisitor
 */
class VisitorOptimizeAstTest extends VisitorTest
{
    public function visitors($manager, $builder, $model)
    {
        return [
            new AttachRulesVisitor($manager),
            new IdentifyRelationshipsFromRulesVisitor(),
            new RemoveNotSymbolVisitor(),
            new OptimizeAstVisitor(),
            new InlineDumpVisitor(true),
        ];
    }

    public function success()
    {
        return [
            // Flatten And/Or.
            ['A and (B and (C and D))', 'AND(A, B, C, D)'],
            ['A or (B or (C or D))', 'OR(A, B, C, D)'],
            ['A and (B and C) or D', 'OR(AND(A, B, C), D)'],
            ['(A or (B or C)) and D', 'AND(OR(A, B, C), D)'],
            ['A or (B or C) and D or E', 'OR(A, AND(OR(B, C), D), E)'],
            ['(A or B) or (C and D or E)', 'OR(A, B, AND(C, D), E)'],
            ['(A and B) and C and (D or E)', 'AND(A, B, C, OR(D, E))'],

            // Inline And/Or with only one element.
            [new AndSymbol([new QuerySymbol('foo', '=', 'bar')]), 'foo = bar'],
            [new OrSymbol([new QuerySymbol('foo', '=', 'bar')]), 'foo = bar'],

            // Remove Or with no children.
            [new OrSymbol, 'EMPTY'],
            [new OrSymbol([new AndSymbol, new AndSymbol]), 'EMPTY'],
            [new OrSymbol([new EmptySymbol]), 'EMPTY'],

            // Remove And with no children.
            [new AndSymbol, 'EMPTY'],
            [new AndSymbol([new OrSymbol, new OrSymbol]), 'EMPTY'],
            [new AndSymbol([new EmptySymbol]), 'EMPTY'],

            // Remove Not with no children.
            [new NotSymbol(new OrSymbol), 'EMPTY'],
            [new NotSymbol(new NotSymbol(new AndSymbol)), 'EMPTY'],
            [new NotSymbol(new EmptySymbol), 'EMPTY'],

            // Flatten relationships.
            ['comments.title = A comments.title = B', 'EXISTS(comments, AND(title = A, title = B))'],
            ['comments.title = A or comments.title = B', 'EXISTS(comments, OR(title = A, title = B))'],
            ['comments.title = A comments.title = B and foobar', 'AND(EXISTS(comments, AND(title = A, title = B)), foobar)'],
            ['comments.author.name = John and comments.title = "My Comment"', 'EXISTS(comments, AND(EXISTS(author, name = John), title = My Comment))'],
            ['comments.author.name = John and comments.author.name = Jane', 'EXISTS(comments, EXISTS(author, AND(name = John, name = Jane)))'],
            ['comments.author.name = John or comments.author.name = Jane', 'EXISTS(comments, EXISTS(author, OR(name = John, name = Jane)))'],
            // ['comments.title = A comments.title = B foobar comments > 10', 'AND(EXISTS(comments, AND(title = A, title = B)), foobar, comments > 10)'],

            //
            ['comments and comments', 'EXISTS(comments, EMPTY)'],

            // Keep relationship separate if they don't have the same count operation.

        ];
    }

    /**
     * @test
     * @dataProvider success
     * @param $input
     * @param $expected
     */
    public function visitor_optimize_ast_success($input, $expected)
    {
        $this->assertAstEquals($input, $expected);
    }
}
