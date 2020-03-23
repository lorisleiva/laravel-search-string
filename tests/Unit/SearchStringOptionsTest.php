<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;
use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;
use Lorisleiva\LaravelSearchString\Tests\TestCase;

class SearchStringOptionsTest extends TestCase
{
    /** @test */
    public function it_parses_columns_and_keywords_options_into_rules()
    {
        $this->assertColumnsRulesFor(new DummyModel, [
            'name' =>               '[/^name$/ /.*/ /.*/][searchable]',
            'price' =>              '[/^price$/ /.*/ /.*/][]',
            'description' =>        '[/^description$/ /.*/ /.*/][searchable]',
            'paid' =>               '[/^paid$/ /.*/ /.*/][boolean]',
            'boolean_variable' =>   '[/^boolean_variable$/ /.*/ /.*/][boolean]',
            'created_at' =>         '[/^created_at$/ /.*/ /.*/][boolean][date]',
        ]);

        $this->assertKeywordRulesFor(new DummyModel, [
            'order_by' =>   '[/^sort$/ /.*/ /.*/]',
            'select' =>     '[/^fields$/ /.*/ /.*/]',
            'limit' =>      '[/^limit$/ /.*/ /.*/]',
            'offset' =>     '[/^from$/ /.*/ /.*/]',
        ]);
    }

    /** @test */
    public function it_can_create_rules_without_explicit_configurations()
    {
        $model = $this->getModelWithColumns(['name']);

        $this->assertColumnsRulesFor($model, [
            'name' => '[/^name$/ /.*/ /.*/][]',
        ]);
    }

    /** @test */
    public function it_can_create_rules_with_key_alias_only()
    {
        $model = $this->getModelWithColumns(['name' => 'alias']);

        $this->assertColumnsRulesFor($model, [
            'name' => '[/^alias$/ /.*/ /.*/][]',
        ]);
    }

    /** @test */
    public function it_can_define_columns_as_searchable()
    {
        $model = $this->getModelWithColumns(['title' => ['searchable' => true]]);

        $this->assertColumnsRulesFor($model, [
            'title' => '[/^title$/ /.*/ /.*/][searchable]',
        ]);
    }

    /** @test */
    public function it_can_define_columns_as_booleans()
    {
        $model = $this->getModelWithColumns(['paid' => ['boolean' => true]]);

        $this->assertColumnsRulesFor($model, [
            'paid' => '[/^paid$/ /.*/ /.*/][boolean]',
        ]);
    }

    /** @test */
    public function it_can_define_columns_as_dates()
    {
        $model = $this->getModelWithColumns(['published_at' => ['date' => true]]);

        $this->assertColumnsRulesFor($model, [
            'published_at' => '[/^published_at$/ /.*/ /.*/][date]',
        ]);
    }

    /** @test */
    public function it_default_boolean_to_true_if_column_is_cast_as_boolean()
    {
        $model = new class extends Model {
            use SearchString;
            protected $casts = ['paid' => 'boolean'];
            protected $searchStringColumns = ['paid'];
        };

        $this->assertColumnsRulesFor($model, [
            'paid' => '[/^paid$/ /.*/ /.*/][boolean]',
        ]);
    }

    /** @test */
    public function it_default_date_and_boolean_to_true_if_column_is_cast_as_date()
    {
        // Cast as datetime
        $model = new class extends Model {
            use SearchString;
            protected $casts = ['published_at' => 'datetime'];
            protected $searchStringColumns = ['published_at'];
        };
        $this->assertColumnsRulesFor($model, [
            'published_at' => '[/^published_at$/ /.*/ /.*/][boolean][date]',
        ]);

        // Cast as date
        $model = new class extends Model {
            use SearchString;
            protected $casts = ['published_at' => 'date'];
            protected $searchStringColumns = ['published_at'];
        };
        $this->assertColumnsRulesFor($model, [
            'published_at' => '[/^published_at$/ /.*/ /.*/][boolean][date]',
        ]);

        // Added to dates
        $model = new class extends Model {
            use SearchString;
            protected $dates = ['published_at'];
            protected $searchStringColumns = ['published_at'];
        };
        $this->assertColumnsRulesFor($model, [
            'published_at' => '[/^published_at$/ /.*/ /.*/][boolean][date]',
        ]);
    }

    /** @test */
    public function it_can_force_boolean_and_date_to_false_when_casted_as_boolean_or_date()
    {
        // Disable boolean option.
        $model = new class extends Model {
            use SearchString;
            protected $casts = ['paid' => 'boolean'];
            protected $searchStringColumns = ['paid' => ['boolean' => false]];
        };
        $this->assertColumnsRulesFor($model, [
            'paid' => '[/^paid$/ /.*/ /.*/][]',
        ]);

        // Disable boolean and date option.
        $model = new class extends Model {
            use SearchString;
            protected $casts = ['published_at' => 'datetime'];
            protected $searchStringColumns = ['published_at' => [
                'date' => false,
                'boolean' => false,
            ]];
        };
        $this->assertColumnsRulesFor($model, [
            'published_at' => '[/^published_at$/ /.*/ /.*/][]',
        ]);
    }

    public function assertColumnsRulesFor($model, $expected)
    {
        $manager = $this->getSearchStringManager($model);
        $options = $manager->getOption('columns')->map->__toString()->toArray();
        $this->assertEquals($expected, $options);
    }

    /** @test */
    public function it_does_nothing_to_the_key_map()
    {
        $model = $this->getModelWithColumns([
            'testing' => [
                'map' => [
                    'test' => 1
                ]
            ]
        ]);

        $this->assertColumnsRulesFor($model, [
            'testing' => '[/^testing$/ /.*/ /.*/][][test=1]'
        ]);
    }

    public function assertKeywordRulesFor($model, $expected)
    {
        $manager = $this->getSearchStringManager($model);
        $options = $manager->getOption('keywords')->map->__toString()->toArray();
        $this->assertEquals($expected, $options);
    }
}