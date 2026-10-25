- `declare(strict_types=1);`, typed parameters and return types.
- Follow PSR-12 (run `vendor/bin/sail bin pint`).
- Small methods with clear names. No method over ~30 lines.
- Write Pest tests for each Service/Action. Fake the AI SDK in tests;
  never call a real AI provider in tests.
- Do not change files I did not ask about. Do not add packages without asking.