<?php

namespace Lorisleiva\LaravelSearchString\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;
use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Visitors\DumpVisitor;

class DumpAstCommand extends Command
{
    protected $signature = 'search-string:ast {model} {query*}';
    protected $description = 'Parses the given search string and dumps the resulting AST';

    public function handle()
    {
        $ast = $this->getManager()->visit($this->getQuery());
        $dump = $ast->accept(new DumpVisitor());

        $this->getOutput()->write($dump);
    }

    public function getModel(): ?Model
    {
        $model = $this->argument('model');
        $model = str_replace('/', '\\', $model);
        $model = Str::startsWith($model, '\\') ? $model : sprintf('App\\%s', $model);

        if (! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException(sprintf('Class [%s] must be a Eloquent Model.', $model));
        }

        $model = new $model();

        if (! method_exists($model, 'getSearchStringManager')) {
            throw new InvalidArgumentException(sprintf('Class [%s] must use the SearchString trait.', $model));
        }

        return $model;
    }

    public function getManager(?Model $model = null): SearchStringManager
    {
        /** @var SearchString $model */
        $model = $model ?: $this->getModel();
        $manager = $model->getSearchStringManager();

        if (! $manager instanceof SearchStringManager) {
            throw new InvalidArgumentException('Method getSearchStringManager must return an instance of SearchStringManager.', $model);
        }

        return $manager;
    }

    public function getQuery(): string
    {
        return implode(' ', $this->argument('query'));
    }
}
