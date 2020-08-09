<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// for documenation use `php-cs-fixer describe <rule>`
$rules = [
    //'@PSR2' => true, no. We use some exceptions, notably } and else on separate lines exceptions
    // PSR2
    'blank_line_after_namespace' => true,       //There MUST be one blank line after the namespace declaration.
    // we dont use braces as this places } on the same line as else without option to suppress this
    //    'braces' => [                         //The body of each structure MUST be enclosed by braces. Braces should be properly placed. Body of braces should be properly indented.
    //        //'position_before_control_structures'=>true,
    //        'allow_single_line_closure'=>true,
    //        'position_after_anonymous_constructs'=>'same',
    //        'position_after_control_structures'=>'same',
    //        'position_after_functions_and_oop_constructs'=>'same',
    //    ],
    'class_definition'=>true,                   // Whitespace around the keywords of a class, trait or interfaces definition should be one space.
    'constant_case'=>true,                      // The PHP constants `true`, `false`, and `null` MUST be written using the correct casing.
    'elseif'=>true,                             // The keyword `elseif` should be used instead of `else if` so that all control keywords look like single words.
    'encoding'=>true,                           // PHP code MUST use only UTF-8 without BOM (remove BOM).
    'full_opening_tag'=>true,                   // PHP code must use the long `<?php` tags or short-echo `<?=` tags and not other tag variations.
    'function_declaration'=>true,               // Spaces should be properly placed in a function declaration.
    'indentation_type'=>true,                   // Code MUST use configured indentation type.
    'line_ending'=>true,                        // All PHP files must use same line ending.
    'lowercase_keywords'=>true,                 // PHP keywords MUST be in lower case.
    'method_argument_space '=> [                // In method arguments and method call, there MUST NOT be a space before each comma and there MUST be one space after each comma.
                                                // Argument lists MAY be split across multiple lines, where each subsequent line is indented once. When doing so, the first item in the list MUST be on the next line, and there MUST be only one argument per line.
                                                // | Configuration: ['on_multiline' => 'ensure_fully_multiline']
        'keep_multiple_spaces_after_comma'=>true,   // Sometimes argument lists have a custom layout not to touch
        'on_multiline'=>'ignore',
    ],

    'no_break_comment'=> [                      //  There must be a comment when fall-through is intentional in a non-empty case body.
        'comment_text'=>'fallthrough'
    ],
    'no_closing_tag'=>true,                     //  The closing `?''>` tag MUST be omitted from files containing only PHP.
    'no_spaces_after_function_name'=>true,      // When making a method or function call, there MUST NOT be a space between the method or function name and the opening parenthesis.
    'no_spaces_inside_parenthesis'=>true,       //There MUST NOT be a space after the opening parenthesis. There MUST NOT be a space before the closing parenthesis.
    'no_trailing_whitespace'=>true,             // Remove trailing whitespace at the end of non-blank lines.
    'no_trailing_whitespace_in_comment'=>true,  // There MUST be no trailing spaces inside comment or PHPDoc.
    'single_blank_line_at_eof'=>true,           // A PHP file without end tag must always end with a single empty line feed.
    'single_class_element_per_statement'=> [    // There MUST NOT be more than one property or constant declared per statement.
        'elements' => ['property'],             // not for 'const'
    ],
    'single_import_per_statement'=>true,        // There MUST be one use keyword per declaration.
    'single_line_after_imports'=>true,          // Each namespace use MUST go on its own line and there MUST be one blank line after the use statements block.
    'switch_case_semicolon_to_colon'=>true,     // A case should be followed by a colon and not a semicolon.
    'switch_case_space'=>true,                  // Removes extra spaces between colon and case value.
    'visibility_required'=>true,                // Visibility MUST be declared on all properties and methods; `abstract` and `final` MUST be declared before the visibility; `static` MUST be declared after the visibility.

    // Other
    'blank_line_before_return' => true,
    'array_syntax'=> [                          // PHP arrays should be declared using the configured syntax. ( long = array() short = [] )
        'syntax'=>'short',
    ],
];

$finder = Finder::create()
    ->notPath('bootstrap/cache')
    ->notPath('storage')
    ->notPath('vendor')
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return Config::create()->setRules($rules)
             ->setFinder($finder)
             ->in(__DIR__)
             ->setUsingCache(false)
             ->setIndent("\t")
             ->setLineEnding("\n")
             ->setRiskyAllowed(true);
