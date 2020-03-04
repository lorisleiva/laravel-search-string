# Laravel Search String

🔍 Generates database queries based on one unique string using a simple and customizable syntax.

![Example of a search string syntax and its result](https://user-images.githubusercontent.com/3642397/40266921-6f7b4c70-5b54-11e8-8e40-000ae3b4e201.png)


## Introduction

Laravel Search String provides a simple solution for scoping your database queries using a human readable and customizable syntax. It will transform a simple string into a powerful query builder. For example, the following search string will fetch the latest blog articles that are either not published or titled "My blog article".

```php
Article::usingSearchString('title:"My blog article" or not published sort:-created_at');

// Equivalent to:
Article::where('title', 'My blog article')
       ->orWhere('published', false)
       ->orderBy('created_at', 'desc');
```

This next example will search for the term "John" on the customer and description columns whilst making sure the invoices are either paid or archived.

```php
Invoice::usingSearchString('John and status in (Paid,Archived) limit:10 from:10');

// Equivalent to:
Invoice::where(function ($query) {
           $query->where('customer', 'like', '%John%')
               ->orWhere('description', 'like', '%John%');
       })
       ->whereIn('status', ['Paid', 'Archived'])
       ->limit(10)
       ->offset(10);
```

You can also query for the existence of related records, for example, articles published in 2020, which have more than 100 comments which are not marked as spam:

```php
Article::usingSearchString('published >= "2020-01-01" and has(comments { not spam }) > 100');

// Equivalent to:
Article::where('published_at', '>=', '2020-01-01')
       ->whereHas('comments', function ($query) {
           $query->where('spam', false);
       }, '>', 100);
```

As you can see, not only it provides a very convenient way to communicate with your Laravel API (instead of allowing dozens of query fields), it also can be presented to your users as a tool to explore their data.

## Installation

```bash
# Install via composer
composer require lorisleiva/laravel-search-string

# (Optional) Publish the search-string.php configuration file
php artisan vendor:publish --tag=search-string
```

## Basic usage

Add the `SearchString` trait to your models and configure the columns and relations that can be used within your search string.

```php
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class Article extends Model
{
    use SearchString;

    protected $searchStringColumns = [
        'title', 'body', 'status', 'rating', 'published', 'created_at',
    ];

    protected $searchStringRelations = [
        'comments', 'user',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

Create a database query using the search string syntax.

```php
Article::usingSearchString('title:"Hello world" sort:-created_at,published')->get();
Article::usingSearchString('title:"Hello world" has(comments) > 100')->get();
```

## The search string syntax

Note that the spaces between operators don't matter.
### Column queries
#### Exact matches
```php
'title:Hello' // Strings without spaces do not need quotes
'title=Hello'
'title:"Hello World"' // Strings with spaces require quotes
'rating : 0'
'rating = 99.99'
'created_at: "2018-07-06 00:00:00"'
```
#### Comparisons
```php
'title < B'
'rating > 3'
'created_at >= "2018-07-06 00:00:00"'
```
#### In array
```php
'title in (Hello, Hi, "My super article")'
'status in(Finished,Archived)'
'status:Finished,Archived'
```
#### Date comparisons
* Column must be defined as a date
```php
'created_at = today'        // today between 00:00 and 23:59
'not created_at = today'    // any time before today 00:00 and after today 23:59
'created_at >= tomorrow'    // from tomorrow at 00:00
'created_at <= tomorrow'    // until tomorrow at 23:59
'created_at > tomorrow'     // from the day after tomorrow at 00:00
'created_at < tomorrow'     // until today at 23:59
```
#### Boolean comparisons
* Column must be defined as a boolean
```php
'published'         // published = true
'created_at'        // created_at is not null
```
#### Negations
```php
'not title:Hello'
'not title="My super article"'
'not rating:0'
'not rating>4'
'not status in (Finished,Archived)'
'not published'     // published = false
'not created_at'    // created_at is null
```
#### Null values
* Case-sensitive
```php
'body:NULL'         // body is null
'not body:NULL'     // body is not null
```
#### Search queries
* Term must not be defined as a boolean
* At least one column on the model must be defined as searchable
```php
'Apple'             // %Apple% like at least one of the searchable columns
'"John Doe"'        // %John Doe% like at least one of the searchable columns
'not "John Doe"'    // %John Doe% not like any of the searchable columns
```
#### And/Or nested queries and parentheses
```php
'title:Hello body:World'        // Implicit and
'title:Hello and body:World'    // Explicit and
'title:Hello or body:World'     // Explicit or
'A B or C D'                    // Equivalent to '(A and B) or (C and D)'
'A or B and C or D'             // Equivalent to 'A or (B and C) or D'
'(A or B) and (C or D)'         // Explicit nested priority
'not (A and B)'                 // Equivalent to 'not A or not B'
'not (A or B)'                  // Equivalent to 'not A and not B'
```
### Relation queries
* Currently supported relations:  
`hasMany`,`hasOne`, `belongsTo`, `belongsToMany`, `hasOneThrough`, `hasManyThrough`
* Polymorphic relations are not yet supported:  
`morphTo`, `morphOne`, `morphMany`, `morphToMany`

#### Relation existence queries
* Relation must be configured as searchable on parent model
* A relationship is configured in one direction only, so its inverse will not be queryable unless it is separately configured
```php
'has(comments)'                      // Only articles which have any comments
'not has(comments)'                  // Only articles which have no comments
```
#### Relation count queries
* If relation is defined as countable on parent model (true by default)
```php
'has(comments) >= 100'               // Only articles which have at least 100 comments
'has(comments) < 10'                 // Only articles which have less than 10 comments
```
#### Relation queries with child criteria
* If relation is defined as queryable on parent model (true by default)
* Related model must use the `SearchString` trait and have its own searchable columns configured
```php
'has(comments { not spam })'         // Only articles which have an least one comment which is not marked as spam
'not has(comments { spam })'         // Only articles which do not have any comments which are marked as spam
'has(user { name : "John Doe" })'    // Only articles by the user named John Doe
```
#### Nested relation queries
* All models in the chain must use the `SearchString` trait
* All related models must be configured as searchable on their parent models
* Dot notation can be used for deeply nested relationships if criteria are only on the deepest child level, i.e. `has(x.y { ... })`
* If criteria are needed on any of the upper levels of the chain, the queries are nested within one another, i.e. `has(x ... { has(y ... ) })})`
```php
// Only articles with comments by users who are not banned
'has(comments.user { not banned })'

// Equivalent to:
'has(comments has(user { not banned })})'

// Only articles by users who have written more than 10 articles since 1 Jan 2020
'has(user.articles { created_at >= "2020-01-01 00:00:00" }) > 10'

// Only articles with more than 10 comments that are not marked as spam by users who are not banned
'has(comments { not spam and has(user { not banned })}) > 10' 
```

### Special keywords
```php
'fields:title,body,created_at'  // Select only title, body, created_at
'not fields:rating'             // Select all columns but rating
'sort:rating,-created_at'       // Order by rating asc, created_at desc
'limit:1'                       // Limit 1
'from:10'                       // Offset 10
```

## Configuring columns

### Column aliases

If you want a column to be queried using a different name, you can define it as a key/value pair where the key is the database column name and the value is the alias you wish to use:

```php
protected $searchStringColumns = [
    'title',
    'body'         => 'content',
    'published_at' => 'published',
    'created_at'   => 'created',
];
```

You can also provide a regex pattern for a more flexible alias definition:

```php
protected $searchStringColumns = [
    'published_at' => '/^published|live$/',
    // ...
];
```

### Column options

You can configure a column even further by assigning it an array of options:

```php
protected $searchStringColumns = [
    'created_at' => [
        'key' => 'created',         // Default to column name: /^created_at$/
        'operator' => '/^:|=$/',    // Default to everything: /.*/
        'value' => '/^[\d\s-:]+$/', // Default to everything: /.*/
        'date' => true,             // Default to true only if the column is cast as date.
        'boolean' => true,          // Default to true only if the column is cast as boolean or date.
        'searchable' => false       // Default to false.
    ],
    // ...
];
```

#### Query Patterns
The `key` option is what we've been configuring so far, i.e. the alias of the column. The `operator` and `value` options allow you to restrict column queries respectively based on their operator and value. The `key`, `operator` and `value` options can each be either a regex pattern or a regular string for exact match.

#### Date columns
If a column is marked as a `date`, the value of the query will be intelligently parsed using `Carbon`. For example, if the `created_at` column is marked as a `date`:

```php
'created_at > tomorrow' // Equivalent to:
$query->where('created_at', '>', 'YYYY-MM-DD 00:00:00');
// where `YYYY-MM-DD` matches the date of tomorrow.

'created_at = "July, 6 2018"' // Equivalent to:
$query->where('created_at', '>=', '2018-07-06 00:00:00');
      ->where('created_at', '<=', '2018-07-06 23:59:59');
```

By default any column that is cast as a date (using Laravel properties), will be marked as a date for LaravelSearchString. You can force a column to not be marked as a date by assigning `date` to `false`.

#### Boolean columns
If a column is marked as a `boolean`, it can be used without any operator nor value. For exemple, if the `paid` column is marked as boolean:

```php
'paid' // Equivalent to:
$query->where('paid', true);

'not paid' // Equivalent to:
$query->where('paid', false);
```

If a column is marked as both `boolean` and `date`, it will be compared to `null` when used as a boolean. For example, if the `published_at` column is marked as `boolean` and `date` and uses the `published` alias:

```php
'published' // Equivalent to:
$query->whereNotNull('published');

'not published_at' // Equivalent to:
$query->whereNull('published');
```

By default any column that is cast as a boolean or as a date (using Laravel properties), will be marked as a boolean. You can force a column to not be marked as a boolean by assigning `boolean` to `false`.

#### Searchable columns
If a column is marked as a `searchable`, it will be used to match search queries, i.e. terms that are alone but are not booleans like `Apple Banana` or `"John Doe"`.

For example if both columns `title` and `description` are marked as `searchable`:

```php
'Apple Banana' // Equivalent to:
$query->where(function($query) {
          $query->where('title', 'like', '%Apple%')
                ->orWhere('description', 'like', '%Apple%');
      })
      ->where(function($query) {
          $query->where('title', 'like', '%Banana%')
                ->orWhere('description', 'like', '%Banana%');
      });

'"John Doe"' // Equivalent to:
$query->where(function($query) {
          $query->where('title', 'like', '%John Doe%')
                ->orWhere('description', 'like', '%John Doe%');
      });
```

If no searchable columns are provided, such terms or strings will be ignored.

## Configuring relations

### Relation aliases

Relations can also be queried using a different name, defined similarly to a column alias, where the key is the relation method name and the value is the alias you wish to use:

```php
protected $searchStringRelations = [
    'user' => 'author',
];
```

You can also provide a regex pattern for a more flexible alias definition:

```php
protected $searchStringRelations = [
    'user' => '/^author|writer$/',
    // ...
];
```

### Relation options

You can also configure a relation by assigning it an array of options:

```php
protected $searchStringRelations = [
    'user' => [
        'key' => 'author',      // Default to relation method name: /^user$/
        'queryable' => false,   // Whether the relation can have criteria
                                // applied to it with `has(x { ... })`,
                                // or will only allow a `has(x)` query.
                                // Default to true.
        'countable' => false,   // Whether the relation can be counted
                                // with `has(x) > 10` or will only allow
                                // a `has(x)` query. Default to true.
    ],
    // ...
];
```

#### Queryable and countable
These options will allow you to prevent exposing details about the number or fields of child records, if you choose.

The `queryable` and `countable` options can be separately configured:
| Queryable  | Countable  | Allowed queries |
| ---        | ---        | --- |
| **`true`** | **`true`** | `has(x)`, `has(x { ... })`, `has(x) > 10`, `has(x { ... }) > 10` (default) |
| **`true`** | `false`    | `has(x)`, `has(x { ... })` |
| `false`    | **`true`** | `has(x)`, `has(x) > 10` |
| `false`    | `false`    | `has(x)` |

## Configuring special keywords

```php
protected $searchStringKeywords = [
    'select'   => 'fields',   // Updates the selected query columns
    'order_by' => 'sort',     // Updates the order of the query results
    'limit'    => 'limit',    // Limits the number of results
    'offset'   => 'from',     // Starts the results at a further index
];
```

Similarly to column values you can provide an array to define the key, the operator and the value pattern of the keyword. Note that the date, boolean and searchable options are not applicable for keywords.

```php
protected $searchStringKeywords = [
    'select' => [
        'key'      => 'fields',
        'operator' => '/^:|=$/',
        'value'    => '/.*/',
    ],
    // ...
];
```

## Other places to configure

As we've seen so far, you can configure your columns, relations and special keywords using the `searchStringColumns`, `searchStringRelations` and `searchStringKeywords` properties on your model.

You can also override the `getSearchStringOptions` method on your model which defaults to:

```php
public function getSearchStringOptions()
{
    return [
        'columns'   => $this->searchStringColumns ?? [],
        'relations' => $this->searchStringRelations ?? [],
        'keywords'  => $this->searchStringKeywords ?? [],
    ];
}
```

If you'd rather not define any of these configurations on the model itself, you can define directly them on the `config/search-string.php` file:

```php
// config/search-string.php
return [
    'default' => [
        'keywords'  => [ /* ... */ ],
    ],

    Article::class => [
        'columns'   => [ /* ... */ ],
        'relations' => [ /* ... */ ],
        'keywords'  => [ /* ... */ ],
    ],
];
```

When resolving the options for a particular model, LaravelSearchString will merge those configurations in the following order:
1. First using the configurations defined on the model
2. Then using the config file at the key matching the model class
3. Then using the config file at the `default` key
4. Finally using some fallback configurations.

## Error handling

The provided search string can be invalid for numerous reasons.
- It does not comply to the search string syntax
- It tries to query an inexisting column or column alias
- It provides the wrong operator to a query
- It provides the wrong value to a query

Any of those errors will throw an `InvalidSearchStringException`. However you can choose whether you want these exceptions to bubble up to the Laravel exception handler or whether you want them to fail silently. For that, you need to choose a fail strategy on your `config/search-string.php` configuration file:

```php
// config/search-string.php
return [
    'fail' => 'all-results', // (Default) Silently fail with a query containing everything.
    'fail' => 'no-results',  // Silently fail with a query containing nothing.
    'fail' => 'exceptions',  // Throw exceptions.

    // ...
];
```
