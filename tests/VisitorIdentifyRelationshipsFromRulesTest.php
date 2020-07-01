<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Visitors\AttachRulesVisitor;
use Lorisleiva\LaravelSearchString\Visitors\IdentifyRelationshipsFromRulesVisitor;
use Lorisleiva\LaravelSearchString\Visitors\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitors\RemoveNotSymbolVisitor;

class VisitorIdentifyRelationshipsFromRulesTest extends VisitorTest
{
    public function visitors($manager, $builder, $model)
    {
        return [
            new AttachRulesVisitor($manager),
            new IdentifyRelationshipsFromRulesVisitor(),
            new RemoveNotSymbolVisitor(),
            new InlineDumpVisitor(),
        ];
    }

    public function success()
    {
        return [
            // It recognises solo symbols.
            ['comments', 'EXISTS(comments, EMPTY) > 0'],
            ['not comments', 'NOT_EXISTS(comments, EMPTY)'],

            // It recognises query symbols.
            ['comments = 3', 'EXISTS(comments, EMPTY) = 3'],
            ['comments <= 1', 'EXISTS(comments, EMPTY) <= 1'],
            ['not comments = 3', 'EXISTS(comments, EMPTY) != 3'],
            ['not comments > 1', 'EXISTS(comments, EMPTY) <= 1'],
            ['not comments > 0', 'NOT_EXISTS(comments, EMPTY)'],

            // It does not affect non-relationship symbols.
            ['title', 'SOLO(title)'],
        ];
    }

    /**
     * @test
     * @dataProvider success
     * @param $input
     * @param $expected
     */
    public function visitor_identify_relationships_from_rules_success($input, $expected)
    {
        $this->assertAstEquals($input, $expected);
    }
}
