<?php

return [
    'token_map' => [
        '(>=?|<=?)'                 => 'T_COMPARATOR',
        '(=|:)'                     => 'T_ASSIGN',
        '(and|AND)(?:[\(\)\s]|$)'   => 'T_AND',
        '(or|OR)(?:[\(\)\s]|$)'     => 'T_OR',
        '(not|NOT)(?:[\(\)\s]|$)'   => 'T_NOT',
        '(in|IN)(?:[\(\)\s]|$)'     => 'T_IN',
        '(,)'                       => 'T_LIST_SEPARATOR',
        '(\()'                      => 'T_LPARENT',
        '(\))'                      => 'T_RPARENT',
        '(\s+)'                     => 'T_SPACE',
        '("[^"]*"|\'[^\']*\')'      => 'T_STRING',
        '([^\s:><="\'\(\),]+)'      => 'T_TERM',
    ]
];