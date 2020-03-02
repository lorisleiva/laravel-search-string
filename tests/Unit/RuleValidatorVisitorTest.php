<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Parser\Symbol;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\RuleValidatorVisitor;

class RuleValidatorVisitorTest extends TestCase
{
    public function validRelationQueriesDataProvider()
    {
        return [
            ['has(comments)'],
            ['has(comments) > 2'],
            ['has(comments { active } ) > 2'],
            ['has(comments.author)'],
            ['has(comments.author.profiles)'],
        ];
    }

    /**
     * @test
     * @dataProvider validRelationQueriesDataProvider
     */
    public function it_validates_relation_queries($input)
    {
        $this->assertRuleIsValid($input);
    }

    public function invalidRelationQueriesDataProvider()
    {
        return [
            'Non countable relation cannot be counted'        => ['has(tags) > 10', 'cannot be counted', 'tags'],
            'Not existent relation does not exist'            => ['has(foo)', 'does not exist', 'foo'],
            'Non queryable relation cannot be queried'        => ['has(views { active })', 'cannot be queried', 'views'],
            // 'Non queryable nested relation cannot be queried' => ['has(??)', 'cannot be queried', '??'], // TODO
        ];
    }

    /**
     * @test
     * @dataProvider invalidRelationQueriesDataProvider
     */
    public function it_fails_to_validate_invalid_relation_queries($input, $reason, $relation)
    {
        $this->assertRuleIsInvalid($input, $reason, $relation);
    }

    protected function assertRuleIsValid($input)
    {
        return $this->assertRuleIsInvalid($input, null);
    }

    protected function assertRuleIsInvalid($input, $reason, $relation = null)
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
                $relation = $relation ?: '%s';
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