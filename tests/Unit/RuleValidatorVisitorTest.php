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
        $this->assertRuleIsValid('has(comments.author)');
        $this->assertRuleIsValid('has(comments.author.profiles)');
    }

    /** @test */
    public function it_fails_to_validate_invalid_relation_queries()
    {
        $this->assertRuleIsInvalid('has(tags) > 10', 'cannot be counted', 'tags');
        $this->assertRuleIsInvalid('has(foo)', 'does not exist', 'foo');
        $this->assertRuleIsInvalid('has(views { active })', 'cannot be queried', 'views');
        // $this->assertRuleIsInvalid('has(??)', 'cannot be queried'); //TODO
    }

    protected function assertRuleIsValid($input)
    {
        return $this->assertRuleIsInvalid($input, null);
    }

    protected function assertRuleIsInvalid($input, $reason, $relation = '%s')
    {
        $manager = $this->getSearchStringManager(null);

        $valid = is_null($reason);
        $validated = true;

        try {
            $this->parse($input)->accept(new RuleValidatorVisitor($manager));
        }
        catch (InvalidSearchStringException $e) {
            $validated = false;

            if (!$valid) {
                $this->assertStringMatchesFormat("The relation [$relation] $reason", $e->getMessage(), "Failed asserting that the invalid reason was [$reason]");
            }
        }
        catch (\Throwable $e) {
            $validated = false;
            throw $e;
        }

        $state = $valid ? 'valid' : 'invalid';

        $this->assertSame($valid, $validated, "Failed asserting that the rule is $state");
    }
}