<?php

namespace Lorisleiva\LaravelSearchString\Compiler;

class CompiledParser extends \Hoa\Compiler\Llk\Parser
{
    public function __construct()
    {
        parent::__construct(
            [
                'default' => [
                    'skip' => '\s',
                    'T_ASSIGNMENT' => ':|=',
                    'T_COMPARATOR' => '>=?|<=?',
                    'T_AND' => '(and|AND)(?![^\(\)\s])',
                    'T_OR' => '(or|OR)(?![^\(\)\s])',
                    'T_NOT' => '(not|NOT)(?![^\(\)\s])',
                    'T_IN' => '(in|IN)(?![^\(\)\s])',
                    'T_NULL' => '(NULL)(?![^\(\)\s])',
                    'T_SINGLE_LQUOTE:single_quote_string' => '\'',
                    'T_DOUBLE_LQUOTE:double_quote_string' => '"',
                    'T_LPARENTHESIS' => '\(',
                    'T_RPARENTHESIS' => '\)',
                    'T_COMMA' => ',',
                    'T_TERM' => '[^\s:><="\\\'\(\),]+',
                ],
                'single_quote_string' => [
                    'T_STRING' => '[^\']+',
                    'T_SINGLE_RQUOTE:default' => '\'',
                ],
                'double_quote_string' => [
                    'T_STRING' => '[^"]+',
                    'T_DOUBLE_RQUOTE:default' => '"',
                ],
            ],
            [
                'Expr' => new \Hoa\Compiler\Llk\Rule\Concatenation('Expr', ['OrNode'], null),
                1 => new \Hoa\Compiler\Llk\Rule\Token(1, 'T_OR', null, -1, false),
                2 => new \Hoa\Compiler\Llk\Rule\Concatenation(2, [1, 'AndNode'], '#OrNode'),
                3 => new \Hoa\Compiler\Llk\Rule\Repetition(3, 0, -1, 2, null),
                'OrNode' => new \Hoa\Compiler\Llk\Rule\Concatenation('OrNode', ['AndNode', 3], null),
                5 => new \Hoa\Compiler\Llk\Rule\Token(5, 'T_AND', null, -1, false),
                6 => new \Hoa\Compiler\Llk\Rule\Repetition(6, 0, 1, 5, null),
                7 => new \Hoa\Compiler\Llk\Rule\Concatenation(7, [6, 'TerminalNode'], '#AndNode'),
                8 => new \Hoa\Compiler\Llk\Rule\Repetition(8, 0, -1, 7, null),
                'AndNode' => new \Hoa\Compiler\Llk\Rule\Concatenation('AndNode', ['TerminalNode', 8], null),
                'TerminalNode' => new \Hoa\Compiler\Llk\Rule\Choice('TerminalNode', ['NestedExpr', 'NotNode', 'QueryNode', 'ListNode', 'SoloNode'], null),
                11 => new \Hoa\Compiler\Llk\Rule\Token(11, 'T_LPARENTHESIS', null, -1, false),
                12 => new \Hoa\Compiler\Llk\Rule\Token(12, 'T_RPARENTHESIS', null, -1, false),
                'NestedExpr' => new \Hoa\Compiler\Llk\Rule\Concatenation('NestedExpr', [11, 'Expr', 12], null),
                14 => new \Hoa\Compiler\Llk\Rule\Token(14, 'T_NOT', null, -1, false),
                'NotNode' => new \Hoa\Compiler\Llk\Rule\Concatenation('NotNode', [14, 'TerminalNode'], '#NotNode'),
                16 => new \Hoa\Compiler\Llk\Rule\Token(16, 'T_TERM', null, -1, true),
                'QueryNode' => new \Hoa\Compiler\Llk\Rule\Concatenation('QueryNode', [16, 'Operator', 'NullableScalar'], '#QueryNode'),
                18 => new \Hoa\Compiler\Llk\Rule\Token(18, 'T_TERM', null, -1, true),
                19 => new \Hoa\Compiler\Llk\Rule\Token(19, 'T_IN', null, -1, false),
                20 => new \Hoa\Compiler\Llk\Rule\Token(20, 'T_LPARENTHESIS', null, -1, false),
                21 => new \Hoa\Compiler\Llk\Rule\Token(21, 'T_RPARENTHESIS', null, -1, false),
                22 => new \Hoa\Compiler\Llk\Rule\Concatenation(22, [18, 19, 20, 'ScalarList', 21], '#ListNode'),
                23 => new \Hoa\Compiler\Llk\Rule\Token(23, 'T_TERM', null, -1, true),
                24 => new \Hoa\Compiler\Llk\Rule\Token(24, 'T_ASSIGNMENT', null, -1, false),
                25 => new \Hoa\Compiler\Llk\Rule\Concatenation(25, [23, 24, 'ScalarList'], '#ListNode'),
                'ListNode' => new \Hoa\Compiler\Llk\Rule\Choice('ListNode', [22, 25], null),
                27 => new \Hoa\Compiler\Llk\Rule\Concatenation(27, ['Scalar'], null),
                'SoloNode' => new \Hoa\Compiler\Llk\Rule\Concatenation('SoloNode', [27], '#SoloNode'),
                29 => new \Hoa\Compiler\Llk\Rule\Token(29, 'T_COMMA', null, -1, false),
                30 => new \Hoa\Compiler\Llk\Rule\Concatenation(30, [29, 'Scalar'], '#ScalarList'),
                31 => new \Hoa\Compiler\Llk\Rule\Repetition(31, 0, -1, 30, null),
                'ScalarList' => new \Hoa\Compiler\Llk\Rule\Concatenation('ScalarList', ['Scalar', 31], null),
                33 => new \Hoa\Compiler\Llk\Rule\Token(33, 'T_TERM', null, -1, true),
                'Scalar' => new \Hoa\Compiler\Llk\Rule\Choice('Scalar', ['String', 33], null),
                35 => new \Hoa\Compiler\Llk\Rule\Token(35, 'T_NULL', null, -1, true),
                'NullableScalar' => new \Hoa\Compiler\Llk\Rule\Choice('NullableScalar', ['Scalar', 35], null),
                37 => new \Hoa\Compiler\Llk\Rule\Token(37, 'T_SINGLE_LQUOTE', null, -1, false),
                38 => new \Hoa\Compiler\Llk\Rule\Token(38, 'T_STRING', null, -1, true),
                39 => new \Hoa\Compiler\Llk\Rule\Repetition(39, 0, 1, 38, null),
                40 => new \Hoa\Compiler\Llk\Rule\Token(40, 'T_SINGLE_RQUOTE', null, -1, false),
                41 => new \Hoa\Compiler\Llk\Rule\Concatenation(41, [37, 39, 40], null),
                42 => new \Hoa\Compiler\Llk\Rule\Token(42, 'T_DOUBLE_LQUOTE', null, -1, false),
                43 => new \Hoa\Compiler\Llk\Rule\Token(43, 'T_STRING', null, -1, true),
                44 => new \Hoa\Compiler\Llk\Rule\Repetition(44, 0, 1, 43, null),
                45 => new \Hoa\Compiler\Llk\Rule\Token(45, 'T_DOUBLE_RQUOTE', null, -1, false),
                46 => new \Hoa\Compiler\Llk\Rule\Concatenation(46, [42, 44, 45], null),
                'String' => new \Hoa\Compiler\Llk\Rule\Choice('String', [41, 46], null),
                48 => new \Hoa\Compiler\Llk\Rule\Token(48, 'T_ASSIGNMENT', null, -1, true),
                49 => new \Hoa\Compiler\Llk\Rule\Token(49, 'T_COMPARATOR', null, -1, true),
                'Operator' => new \Hoa\Compiler\Llk\Rule\Choice('Operator', [48, 49], null),
            ],
            [
            ]
        );

        $this->getRule('Expr')->setPPRepresentation(' OrNode()');
        $this->getRule('OrNode')->setPPRepresentation(' AndNode() ( ::T_OR:: AndNode() #OrNode )*');
        $this->getRule('AndNode')->setPPRepresentation(' TerminalNode() ( ::T_AND::? TerminalNode() #AndNode )*');
        $this->getRule('TerminalNode')->setPPRepresentation(' NestedExpr() | NotNode() | QueryNode() | ListNode() | SoloNode()');
        $this->getRule('NestedExpr')->setPPRepresentation(' ::T_LPARENTHESIS:: Expr() ::T_RPARENTHESIS::');
        $this->getRule('NotNode')->setDefaultId('#NotNode');
        $this->getRule('NotNode')->setPPRepresentation(' ::T_NOT:: TerminalNode()');
        $this->getRule('QueryNode')->setDefaultId('#QueryNode');
        $this->getRule('QueryNode')->setPPRepresentation(' <T_TERM> Operator() NullableScalar()');
        $this->getRule('ListNode')->setDefaultId('#ListNode');
        $this->getRule('ListNode')->setPPRepresentation(' <T_TERM> ::T_IN:: ::T_LPARENTHESIS:: ScalarList() ::T_RPARENTHESIS:: | <T_TERM> ::T_ASSIGNMENT:: ScalarList()');
        $this->getRule('SoloNode')->setDefaultId('#SoloNode');
        $this->getRule('SoloNode')->setPPRepresentation(' Scalar()');
        $this->getRule('ScalarList')->setDefaultId('#ScalarList');
        $this->getRule('ScalarList')->setPPRepresentation(' Scalar() ( ::T_COMMA:: Scalar() )*');
        $this->getRule('Scalar')->setPPRepresentation(' String() | <T_TERM>');
        $this->getRule('NullableScalar')->setPPRepresentation(' Scalar() | <T_NULL>');
        $this->getRule('String')->setPPRepresentation(' ::T_SINGLE_LQUOTE:: <T_STRING>? ::T_SINGLE_RQUOTE:: | ::T_DOUBLE_LQUOTE:: <T_STRING>? ::T_DOUBLE_RQUOTE::');
        $this->getRule('Operator')->setPPRepresentation(' <T_ASSIGNMENT> | <T_COMPARATOR>');
    }
}
