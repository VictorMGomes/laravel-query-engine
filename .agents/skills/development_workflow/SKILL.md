---
name: development_workflow
description:
    Guide on how to use Docker Compose to run tests, static analysis, and code
    formatting on this project.
---

# Development Workflow & Docker Commands

This project includes a fully isolated development environment using Docker
Compose (`docker-compose.yml`) and a custom `Dockerfile` containing PHP 8.5 CLI,
Composer, PCOV (for test coverage), and Node.js/npm (for linting and
formatting).

As an AI agent, you should **never** run composer, PHP, or npm commands directly
on the host if they require specific environments. Instead, always use the
Docker container `dev`.

## Running Commands via Docker

To execute commands within the isolated container, use the following syntax:

```bash
docker compose run --rm dev <command>
```

## Available Scripts & QA Checks

Always check the `scripts` section in `composer.json` to discover the available
commands (e.g., `check:*`, `format:*`, `test:*`, `act:*`, etc.).

When executing them, follow this pattern:

```bash
docker compose run --rm dev composer run <script-name>
```

### Best Practices for AI Agents

- **Piping Outputs:** When executing commands that can produce hundreds of lines
  of output (like static analysis, mutation tests, or code quality checks),
  **always** pipe the output to a file inside the `logs/` directory to avoid
  flooding your terminal context.
  - Example:
      `docker compose run --rm dev composer run check:types > logs/analyse.txt`
  - After piping, use the `view_file` tool to read the log selectively.
- **Environment:** The Docker container maps the current directory `/app`, so it
  uses the host's `vendor` folder dynamically. Do not run `composer install`
  unless explicitly asked.
- **Releases & Commits:** We use Release Please for automated semantic versioning.
  You **must** use Conventional Commits (e.g., `feat:`, `fix:`).
  To trigger a major version bump for breaking changes, you **must** append an exclamation mark `!` to the commit type (e.g., `refactor!:` or `feat!:`).
