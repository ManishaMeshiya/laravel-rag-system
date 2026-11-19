# Coding Standards

## PHP
- Use `declare(strict_types=1);` in new PHP files.
- Add parameter types and return types to every method.
- Follow PSR-12. After changing PHP files, run `vendor/bin/sail bin pint`.
- Small methods with clear names. No method over ~30 lines.
- Use FormRequest for validation. No inline $request->validate() in controllers.
- Services accept DTOs, not raw arrays. Create the DTO from the validated FormRequest data.
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
## Transactions and exceptions
- Put `DB::transaction()` in the Service/Action, never in the controller.
- Use the closure form `DB::transaction(fn () => ...)`. Do not write manual
  `beginTransaction/commit/rollBack`.
- Use a transaction only when two or more writes must succeed together.
- Do not catch exceptions unless you clean up (e.g. delete a stored file)
  or convert them to a specific domain exception. Always re-throw.
- Controllers do not contain try-catch.

## Queues
- Jobs that load a model created in the same request must run after the
  database commit: use `->afterCommit()` or implement `ShouldQueueAfterCommit`.
- Pass the model (or its ID) to the job. Do not pass large data or file contents.
- Clean up stored files if a later step fails.

## Tests
- Write PHPUnit tests for each Service/Action.
- Fake the AI SDK in tests. Never call a real AI provider in tests.

## Do not
- Do not change files I did not ask about.
- Do not add packages without asking me.
- Do not check a condition twice (for example, `exists()` and then `get()`). Call once and check the result.