<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan
<!-- SPECKIT END -->

# Drone Monitoring System — AI Context

## Project Overview
- **Name**: Drone Monitoring System
- **Type**: Laravel 12 + Tailwind CSS v4 + Vite
- **PHP**: 8.2+
- **Database**: Default Laravel (configured in .env)

## Technology Stack
- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Blade templates, Tailwind CSS v4, Vite
- **Testing**: PHPUnit, Laravel Pint (PSR-12)
- **Dev Tools**: Laravel Sail (Docker), Laravel Pail, concurrently

## Key Commands
```bash
# Development (runs server, queue, logs, vite)
composer run dev

# Build for production
npm run build

# Run tests
composer run test
# or
php artisan test

# Code linting
./vendor/bin/pint --dirty

# Database
php artisan migrate
php artisan migrate:status
```

## Project Structure
- `app/` — Application code
- `resources/views/` — Blade templates
- `resources/css/` — Tailwind styles
- `routes/` — Web and API routes
- `database/migrations/` — Database schema
- `tests/Feature/` — Feature tests

## Conventions
- Use PHP 8.2+ features (readonly, typed properties, enums)
- Type hint all methods
- Follow PSR-12 (enforced by Pint)
- Use Eloquent relationships, eager loading
- Create Form Requests for validation
- Write tests >85% coverage
- Use Tailwind v4 syntax (`@import "tailwindcss";`, `@theme`)

## When Working on This Project
1. Read `AGENTS.md` first (this file)
2. Check existing migrations and models before creating new ones
3. Run `php artisan migrate:status` to verify database state
4. Run `php artisan route:list` after route changes
5. Run `php artisan test` before committing
6. Run `vendor/bin/pint --dirty` for code formatting
