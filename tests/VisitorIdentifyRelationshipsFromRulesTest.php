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
            ['comments: (favouritors)', 'EXISTS(comments, EXISTS(favouritors, EMPTY) > 0) > 0'],
            ['comments: (not favouritors)', 'EXISTS(comments, NOT_EXISTS(favouritors, EMPTY)) > 0'],
            ['not comments: (not favouritors)', 'NOT_EXISTS(comments, NOT_EXISTS(favouritors, EMPTY))'],

            // It recognises query symbols inside relationships.
            ['comments: (favouritors = 3)', 'EXISTS(comments, EXISTS(favouritors, EMPTY) = 3) > 0'],
            ['comments: (not favouritors > 0)', 'EXISTS(comments, NOT_EXISTS(favouritors, EMPTY)) > 0'],
            ['not comments: (favouritors = 0)', 'NOT_EXISTS(comments, NOT_EXISTS(favouritors, EMPTY))'],

            // It recognise symbols inside nested terms.
            ['comments.author', 'EXISTS(comments, EXISTS(author, EMPTY) > 0) > 0'],
            ['comments.author > 5', 'EXISTS(comments, EXISTS(author, EMPTY) > 5) > 0'],
            ['comments.favouritors', 'EXISTS(comments, EXISTS(favouritors, EMPTY) > 0) > 0'],
            ['not comments.favouritors', 'NOT_EXISTS(comments, EXISTS(favouritors, EMPTY) > 0)'],
            ['comments.favouritors.name = John', 'EXISTS(comments, EXISTS(favouritors, QUERY(name = John)) > 0) > 0'],
            ['not comments.favouritors.name = John', 'NOT_EXISTS(comments, EXISTS(favouritors, QUERY(name = John)) > 0)'],
            ['comments.favouritors: (not name = John)', 'EXISTS(comments, EXISTS(favouritors, QUERY(name != John)) > 0) > 0'],

            // It does not affect non-relationship symbols.
            ['title', 'SOLO(title)'],
            ['title = 3', 'QUERY(title = 3)'],
            ['comments: (published)', 'EXISTS(comments, SOLO(published)) > 0'],

            // It works with circular dependencies.
            ['comments.favourites.comment', 'EXISTS(comments, EXISTS(favourites, EXISTS(comment, EMPTY) > 0) > 0) > 0'],
            ['comments.favourites.comment = 0', 'EXISTS(comments, EXISTS(favourites, NOT_EXISTS(comment, EMPTY)) > 0) > 0'],
            ['comments.favouritors.comments', 'EXISTS(comments, EXISTS(favouritors, EXISTS(comments, EMPTY) > 0) > 0) > 0'],
            ['comments.favouritors.writtenComments', 'EXISTS(comments, EXISTS(favouritors, EXISTS(writtenComments, EMPTY) > 0) > 0) > 0'],
            ['comments.author.comments > 10', 'EXISTS(comments, EXISTS(author, EXISTS(comments, EMPTY) > 10) > 0) > 0'],
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
