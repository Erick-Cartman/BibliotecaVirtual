# Repository Guidelines

## Project Structure & Module Organization

The main application is in `biblioteca-activa/`, a Laravel 13 project using PHP 8.3+, Livewire 4, MongoDB, Tailwind CSS 4, and Vite. Keep application logic in `biblioteca-activa/app/`, routes in `routes/`, Livewire pages and Blade components in `resources/views/`, and frontend styles/scripts in `resources/css/` and `resources/js/`. Seeders and imported catalog data live under `database/seeders/`; automated checks are in `tests/Feature/` and `tests/Unit/`. Workspace-level source spreadsheets, documents, and helper scripts remain at the repository root.

The nested `biblioteca-activa/AGENTS.md` contains Laravel-specific rules and applies to files inside that project.

## Build, Test, and Development Commands

Run commands from `C:\modular\BibliotecaActiva\biblioteca-activa`:

- `powershell -ExecutionPolicy Bypass -File .\scripts\start-local.ps1` starts MongoDB and the Laravel server at `http://127.0.0.1:8000`.
- `npm.cmd run build` compiles the Vite assets for production.
- `$env:PHPRC='C:\modular\.tools'; C:\php\php.exe artisan test --compact` runs the PHPUnit suite with the project PHP configuration.
- `$env:PHPRC='C:\modular\.tools'; C:\php\php.exe vendor/bin/pint --dirty --format agent` formats changed PHP files.

## Coding Style & Naming Conventions

Use four-space indentation in PHP and Blade, explicit PHP types, curly braces, and Laravel conventions for classes, methods, routes, and components. Use `PascalCase` classes, `camelCase` methods and variables, and descriptive Blade component names such as `x-book-cover`. Prefer existing components and Tailwind utility patterns before introducing new abstractions.

## Testing Guidelines

PHPUnit is the test framework. Name feature tests after the behavior under test, for example `CatalogTest` or `AuthenticationTest`, and use `test_...` methods. Add focused feature coverage for behavior changes and run the full suite before submitting a change.

## Commit & Pull Request Guidelines

Recent commits use short, imperative Spanish descriptions such as `Agrega búsqueda automatizada de portadas`; follow the same concise `Verbo + objeto` style. Pull requests should explain the change, list tests and build commands run, call out data or configuration changes, and include screenshots for visual updates.

## Security & Configuration

Never commit `.env`, credentials, or production connection strings. Use `.env.example` for documented configuration, keep MongoDB local during development, and treat seeded demo accounts as localhost-only credentials.
