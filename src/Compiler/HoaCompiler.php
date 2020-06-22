<?php

namespace Lorisleiva\LaravelSearchString\Compiler;

use Hoa\Compiler\Exception\UnrecognizedToken;
use Hoa\Compiler\Llk\Lexer;
use Hoa\Compiler\Llk\Llk;
use Hoa\File\Read;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use Lorisleiva\LaravelSearchString\AST\EmptySymbol;
use Lorisleiva\LaravelSearchString\AST\Symbol;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\SearchStringManager;

class HoaCompiler implements CompilerInterface
{
    protected $manager;

    public function __construct(SearchStringManager $manager)
    {
        $this->manager = $manager;
    }

    public function lex(string $input): Enumerable
    {
        Llk::parsePP($this->manager->getGrammar(), $tokens, $rules, $pragmas, 'streamName');
        $lexer = new Lexer($pragmas);

        try {
            $generator = $lexer->lexMe($input, $tokens);
        } catch (UnrecognizedToken $exception) {
            throw InvalidSearchStringException::fromLexer($exception->getMessage(), $exception->getArguments()[1]);
        }

        return LazyCollection::make($generator);
    }

    public function parse(string $input): Symbol
    {
        if (! $input) {
            return new EmptySymbol();
        }

        try {
            $ast = $this->getParser()->parse($input);
        } catch (UnrecognizedToken $exception) {
            throw InvalidSearchStringException::fromLexer($exception->getMessage(), $exception->getArguments()[1]);
        }

       return $ast->accept(new HoaConverterVisitor());
    }

    public function updateParser(): void
    {
        // TODO: Implement updateParser() method.
    }

    protected function getParser()
    {
        // TODO: save and use compiled parser.

        return Llk::load(new Read($this->manager->getGrammarFile()));
    }
}
