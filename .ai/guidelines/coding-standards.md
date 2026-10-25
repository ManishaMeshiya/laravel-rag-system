# Coding Standards

## PHP
- Use `declare(strict_types=1);` in new PHP files.
- Add parameter types and return types to every method.
- Follow PSR-12. After changing PHP files, run `vendor/bin/sail bin pint`.
- Small methods with clear names. No method over ~30 lines.
- Use FormRequest for validation. No inline $request->validate() in controllers.
- Use early returns (guard clauses). Handle invalid or empty cases first.
- Avoid deep nesting (maximum 2 levels). Do not use `else` after a `return`.

Bad:
```php
if ($document->isReady()) {
    // main logic
}
```

Good:
```php
if (! $document->isReady()) {
    return;
}

// main logic
```

## Tests
- Write Pest tests for each Service/Action.
- Fake the AI SDK in tests. Never call a real AI provider in tests.

## Do not
- Do not change files I did not ask about.
- Do not add packages without asking me.
- Do not check a condition twice (for example, `exists()` and then `get()`). Call once and check the result.