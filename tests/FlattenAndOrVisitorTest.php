<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Visitor\FlattenAndOrVisitor;
use Lorisleiva\LaravelSearchString\Visitor\InlineDumpVisitor;

class FlattenAndOrVisitorTest extends TestCase
{
    /** @test */
    function it_flattens_and_or_operators()
    {
        $this->assertAstFor('A and (B and (C and D))', 'AND(A, B, C, D)');
        $this->assertAstFor('A or (B or (C or D))', 'OR(A, B, C, D)');
        $this->assertAstFor('A and (B and C) or D)', 'OR(AND(A, B, C), D)');
        $this->assertAstFor('(A or (B or C)) and D', 'AND(OR(A, B, C), D)');
    }

    /** @test */
    function it_does_not_conflicts_and_or_flattenings()
    {
        $this->assertAstFor('A or (B or C) and D or E)', 'OR(A, AND(OR(B, C), D), E)');
        $this->assertAstFor('(A or B) or (C and D or E))', 'OR(A, B, AND(C, D), E)');
        $this->assertAstFor('(A and B) and C and (D or E)', 'AND(A, B, C, OR(D, E))');
    }

    public function assertAstFor($input, $expectedAst)
    {
        $ast = $this->parse($input)
            ->accept(new FlattenAndOrVisitor())
            ->accept(new InlineDumpVisitor(true));

        $this->assertEquals($expectedAst, $ast);
    }
}