# AI Developer Guidelines for `query-params`

You are an AI coding assistant helping to develop and maintain the
`victormgomes/query-params` package. This package is a schema-aware API
filtering engine for Laravel that handles complex query parameters (filtering,
sorting, field selection, relationship loading, and pagination) using native
Laravel validation and highly optimized Eloquent queries.

Whenever you work on this project, you **must** strictly adhere to the following
rules:

## 1. Commit Standards (Conventional Commits)

All commit messages **must be in English** and follow the Conventional Commits
specification. Use one of the following prefixes:

- `feat`: A new feature.
- `fix`: A bug fix.
- `docs`: Documentation only changes (e.g., changes to `README.md`, `SKILL.md`,
  or the `docs/` folder).
- `style`: Changes that do not affect the meaning of the code (white-space,
  formatting, missing semi-colons, etc.).
- `refactor`: A code change that neither fixes a bug nor adds a feature.
- `perf`: A code change that improves performance.
- `test`: Adding missing tests or correcting existing tests.
- `build`: Changes that affect the build system or external dependencies (e.g.,
  updating `composer.json`).
- `ci`: Changes to our CI configuration files and scripts (e.g.,
  `.github/workflows/`).
- `chore`: Maintenance tasks, refactoring tooling, or other changes that don't
  modify `src/` or `tests/`.
- `revert`: Reverts a previous commit.

## 2. Code Quality & Strict Typing

- **Strict Types:** Every PHP file must start with `declare(strict_types=1);`.
- **Explicit Return Types:** Always specify return types for methods and
  closures. If a function or closure does not return anything, explicitly define
  `: void` (e.g., `function (): void { ... }`).
- **Code Formatting:** Adhere to the formatting tool configured in the project
  (check the `scripts` section in `composer.json`, typically Laravel Pint).
- **Static Analysis:** The project enforces high code quality. Always check
  `composer.json` for the configured static analysis tools (like PHPStan or PHP
  Insights). Avoid `mixed` types when possible and document types using PHPDoc
  if PHP native typing is insufficient.

## 3. Architecture & Design Principles

- **Database Agnosticism:** Never write raw SQL unless it is absolutely
  necessary and isolated to a specific database driver (e.g.,
  `PostgresHandler`). Always prefer native Eloquent Builder methods to guarantee
  compatibility across MySQL, PostgreSQL, SQLite, and SQL Server.
- **Security & Visibility:** The package is designed to be secure by default.
  Always respect the model's `$visible` and `$hidden` attributes. Unrecognized
  parameters must fail validation, not fail silently or execute blindly.
- **Delegation & Strategy Pattern:** Keep the codebase modular and avoid God
  Classes.
  - `QueryNormalizer` must delegate all specific logic to focused `Normalizer`
      classes (e.g., `FiltersNormalizer`).
  - `Resource` must delegate metadata gathering to specialized `Generator`
      classes (e.g., `FilterGenerator`).
  - Generators should use the **Instantiable Generator Pattern**, avoiding
      static monolithic methods.
  - Filter operations and database drivers must be cleanly delegated to
      focused Handler classes (e.g. `StringHandler`).
- **Performance:** Avoid runtime schema introspection overhead when possible.
  The package relies on caching mechanisms for schema validation rules.

## 4. Testing & Development Workflow

- **Framework:** Check `composer.json` to determine the active testing framework
  (e.g., Pest).
- **Test-Driven:** Every new feature or bug fix must be accompanied by an
  automated test.
- **Database Tests:** Always review `tests/TestCase.php` to understand the
  available test schema and database configuration before writing new
  database-dependent tests.
- **IMPORTANT:** Do not run PHP or Composer commands directly on the host.
  Always use the provided Docker environment. Refer to the
  `development_workflow` skill for instructions on discovering and executing QA
  checks.
