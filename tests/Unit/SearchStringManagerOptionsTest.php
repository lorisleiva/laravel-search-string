<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Concerns\SearchString;
use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModelWithoutOptions;
use Lorisleiva\LaravelSearchString\Tests\TestCase;

class SearchStringManagerOptionsTest extends TestCase
{
    protected $configsForDummyModelWithoutOptions = [
        'columns' => [
            'visible' => ['dummy', 'model', 'without', 'options'],
            'searchable' => ['dummy'],
            'boolean' => ['model'],
            'date' => [],
        ],
        'keywords' => [
            'order_by' => 'dummy_sort',
            'select' => 'dummy_fields',
            'limit' => 'dummy_limit',
            // no offset keyword.
        ],
    ];

    /** @test */
    function is_uses_search_string_options_from_the_model()
    {
        // Given a model that has search string options.
        $model = new DummyModel;

        // When using a search string manager on that model.
        $manager = new SearchStringManager($model);

        // Then the manager options matches the model options.
        $this->assertEquals($model->searchStringOptions, $manager->getOptions());
    }

    /** @test */
    function is_uses_model_search_string_options_from_the_configs()
    {
        // Given a model without search string options on the model but on the configs.
        $model = new DummyModelWithoutOptions;

        // When using a search string manager on that model.
        $manager = new SearchStringManager($model);

        // Then the manager options matches the model's config options.
        $this->assertArraySubset($this->configsForDummyModelWithoutOptions, $manager->getOptions());

        // And the missing offset keyword has been filled with the default configs.
        $this->assertEquals('from', $manager->getOption('keywords.offset'));
    }

    /** @test */
    function is_uses_default_configs_and_fallback_options_to_fill_the_gaps()
    {
        // Given a model without search string options on the model nor on the configs.
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            use SearchString;
        };

        // When using a search string manager on that model.
        $manager = new SearchStringManager($model);

        // Then the manager options matches the default configs.
        $this->assertArraySubset(config('search-string.default'), $manager->getOptions());

        // And missing options fallback to the $fallbackOptions property of the manager.
        $this->assertArraySubset([
            'columns' => [
                'visible' => null,
                'searchable' => null,
                'boolean' => null,
                'date' => null,
            ],
        ], $manager->getOptions());
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set(
            'search-string.' . DummyModelWithoutOptions::class, 
            $this->configsForDummyModelWithoutOptions
        );
    }
}