# Laravel Search String

🔍 Generates database queries based one unique string using a simple and customizable syntax.

## Examples
```php
Article::usingSearchString('title:"My blog article" sort:-created_at not published');

// Equivalent to:
Article::where('title', 'My blog article')
       ->where('published', false)
       ->orderBy('created_at', 'desc');
```

```php
Invoice::usingSearchString('Finished or status in (Paid,Archived) limit:10 from:10');

// Equivalent to:
Invoice::where(function ($query) {
           $query->where('title', 'like', '%Finished%')
               ->orWhere('description', 'like', '%Finished%');
       })
       ->whereIn('status', ['Paid', 'Archived'])
       ->limit(10)
       ->offset(10);
```

## Installation
*TODO: Add to composer*
```bash
composer require lorisleiva/laravel-search-string
```

## Usage
Add the `SearchString` trait to your models and configure the columns that should be used within your search string.
*TODO: Default to visible, hidden, dates and casts properties.*
```php
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class Article extends Model
{
    use SearchString;

    protected $searchStringOptions = [
        'columns' => [
            'visible' => [],
            'searchable' => [],
            'boolean' => [],
            'date' => [],
        ]
    ];
}
```

Use the `usingSearchString` local scope to generate a query builder that matches your search string.
```php
Article::usingSearchString($input)->get();
```

## Syntax
*TODO: Document default syntax (grammar + rules).*

## Options
*TODO: Document all options.*

## Custom SearchStringManager
*TODO: Document SearchStringManager and overridable methods.*
