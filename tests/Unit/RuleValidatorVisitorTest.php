<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Parser\Symbol;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\RuleValidatorVisitor;

class RuleValidatorVisitorTest extends TestCase
{
    /** @test */
    public function it_validates_relation_queries()
    {
        $this->assertRuleIsValid('has(comments)');
        $this->assertRuleIsValid('has(comments) > 2');
        $this->assertRuleIsValid('has(comments { active } ) > 2');
    }

    /** @test */
    public function it_fails_to_validate_invalid_relation_queries()
    {
        $this->assertRuleIsValid('has(tags) > 10', 'cannot be counted');
        $this->assertRuleIsValid('has(foo)', 'does not exist');
        $this->assertRuleIsValid('has(views { active })', 'cannot be queried');
    }

    public function assertRuleIsValid($input, $reason = null)
    {
        $manager = $this->getSearchStringManager(null);

        $valid = is_null($reason);
        $validated = true;

        try {
            $this->parse($input)->accept(new RuleValidatorVisitor($manager));
        }
        catch (\Throwable $e) {
            $validated = false;

            if (!$valid) {
                $this->assertInstanceOf(InvalidSearchStringException::class, $e, 'Failed asserting that an invalid search string exception was thrown');
                $this->assertStringMatchesFormat("The relation [%s] $reason", $e->getMessage(), "Failed asserting that the invalid reason was [$reason]");
            }
        }

        $state = $valid ? 'valid' : 'invalid';

        $this->assertSame($valid, $validated, "Failed asserting that the rule is $state");
    }
}