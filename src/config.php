<?php

return [
    'token_map' => [
        'T_COMPARATOR' => '(>=?|<=?)',
        'T_ASSIGN' => '(=|:)',
        'T_AND' => '(and|AND)[\(\s]',
        'T_OR' => '(or|OR)[\(\s]',
        'T_NOT' => '(not|NOT)[\(\s]',
        'T_IN' => '(in|IN)[\(\s]',
        'T_LIST_SEPARATOR' => '(,)',
        'T_LPARENT' => '(\()',
        'T_RPARENT' => '(\))',
        'T_SPACE' => '(\s+)',
        'T_STRING' => '("[^"]*"|\'[^\']*\')',
        'T_TERM' => '([^\s:><="\'\(\),]+)',
    ]
];