# Babbler

## Settings

| Setting                | Description                                 | Default |
|:-----------------------|---------------------------------------------|:-------:|
| babblerAuthorSize      | defines author column character width       |  `32`   | 
| babblerCategorySize    | defines  category column character width    |  `64`   | 
| babblerSubCategorySize | defines sub_category column character width |  `64`   | 
| babblerTitleSize       | defines title column character width        |  `255`  |

## Usage

### Add Entry

```php
add_entry(
    string $category,
    string $subCategory,
    string $title,
    string $content,
    string $author,
    string $created = null,
    string $published = "",
    bool   $isDraft = false,
    bool   $isHidden = false
): int|false
```

### Edit Entry

```php
edit_entry(
    string|int $entry_id,
    string     $editor,
    string     $category = "",
    string     $subCategory = "",
    string     $title = "",
    string     $content = "",
    string     $published = "",
    bool       $isDraft = null,
    bool       $isHidden = null,
): int|false
```

### Delete Entry

```php
delete_entry(int $entry_id): bool
```

### Search Entries

```php
search_entries(string $words, string $category = "*", bool $strict = true, int $buffer = 100): array
```

### Search Title

```php
search_title(string $title): array
```

### Search URL Title

```php
search_url_title(string $title): array
```

### Fetch Entry Table

```php
fetch_entry_table(string $orderBy = "", string $sort = "", int $limit = 50, int $start = 0): array
```

### Fetch Entry

```php
fetch_entry(int $entry_id): array
```

### Fetch Categories

```php
fetch_categories(): array
```

### Fetch Subcategories

```php
fetch_sub_categories(string $category = ''): array
```

### Fetch Entries by Category

```php
fetch_entries_by_category(string $category, string $subCategory = '', string $orderBy = 'created', string $sort = '', int $start = 0, int $limit = 10): array
```

### Fetch Entry by Category and Title

```php
fetch_entry_by_category_and_title(string $category, string $title, string $subCategory = ''): array
```