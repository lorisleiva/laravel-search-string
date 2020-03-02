<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyChild;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyGrandChild;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(DummyModel::class, function (Faker $faker) {
    return [
        'name'             => $faker->words(3, true),
        'price'            => $faker->numberBetween(1, 100),
        'description'      => $faker->sentences(4, true),
        'paid'             => $faker->boolean,
        'boolean_variable' => $faker->boolean,
    ];
});

$factory->define(DummyChild::class, function (Faker $faker) {
    return [
        'title'   => $faker->words(3, true),
        'active'  => $faker->boolean,
        // 'post_id' => function ($post) {
        //     return factory(DummyModel::class);
        // },
    ];
});

$factory->define(DummyGrandChild::class, function (Faker $faker) {
    return [
        'name'    => $faker->name,
        'active'  => $faker->boolean,
        // 'user_id' => function ($child) {
        //     return factory(DummyChild::class);
        // }
    ];
});