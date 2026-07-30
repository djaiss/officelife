---
name: tests
description: Conventions for defining tests. Use when the user wants to create or modify tests, including test structure, naming conventions, and best practices.
---

# Tests

- Use `use PHPUnit\Framework\Attributes\Test;` and `#[Test]` for test methods instead of `public function testX()`.
- Names must be descriptive and use `snake_case` for test methods, like `it_can_list_the_api_keys_of_the_current_user`.

## Test for models

-
