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

            // It recognises solo symbols inside relationships.
            ['comments: (favouriteUsers)', 'EXISTS(comments, EXISTS(favouriteUsers, EMPTY) > 0) > 0'],
            ['comments: (not favouriteUsers)', 'EXISTS(comments, NOT_EXISTS(favouriteUsers, EMPTY)) > 0'],
            ['not comments: (not favouriteUsers)', 'NOT_EXISTS(comments, NOT_EXISTS(favouriteUsers, EMPTY))'],

            // It recognises query symbols inside relationships.
            ['comments: (favouriteUsers = 3)', 'EXISTS(comments, EXISTS(favouriteUsers, EMPTY) = 3) > 0'],
            ['comments: (not favouriteUsers > 0)', 'EXISTS(comments, NOT_EXISTS(favouriteUsers, EMPTY)) > 0'],
            ['not comments: (favouriteUsers = 0)', 'NOT_EXISTS(comments, NOT_EXISTS(favouriteUsers, EMPTY))'],

            // It recognise symbols inside nested terms.
            ['comments.favouriteUsers', 'EXISTS(comments, EXISTS(favouriteUsers, EMPTY) > 0) > 0'],
            ['not comments.favouriteUsers', 'NOT_EXISTS(comments, EXISTS(favouriteUsers, EMPTY) > 0)'],
            ['comments.favouriteUsers.name = John', 'EXISTS(comments, EXISTS(favouriteUsers, QUERY(name = John)) > 0) > 0'],
            ['not comments.favouriteUsers.name = John', 'NOT_EXISTS(comments, EXISTS(favouriteUsers, QUERY(name = John)) > 0)'],
            ['comments.favouriteUsers: (not name = John)', 'EXISTS(comments, EXISTS(favouriteUsers, QUERY(name != John)) > 0) > 0'],

            // It does not affect non-relationship symbols.
            ['title', 'SOLO(title)'],
            ['title = 3', 'QUERY(title = 3)'],
            ['comments: (published)', 'EXISTS(comments, SOLO(published)) > 0'],
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
