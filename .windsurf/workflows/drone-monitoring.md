---
name: drone-monitoring-workflow
description: Development workflow for the Drone Monitoring System Laravel project. Use for implementing features, fixing bugs, database changes, and UI updates.
triggers: drone, monitoring, flight, mission, telemetry, dashboard, map, geofence, battery, altitude
---

# Drone Monitoring System Workflow

## Pre-Flight Check (Before Starting Work)

1. **Read project context**
   - Check `AGENTS.md` for project-specific guidance
   - Review existing models in `app/Models/`
   - Review migrations in `database/migrations/`

2. **Verify environment**
   ```bash
   php artisan migrate:status
   ```

3. **Check current routes**
   ```bash
   php artisan route:list
   ```

## Development Cycle

### 1. Database Changes
- Create migrations with `php artisan make:migration`
- Include all column attributes when altering
- Run `php artisan migrate` and verify status
- Ask before destructive operations (fresh/rollback)

### 2. Models & Relationships
- Create models with `php artisan make:model`
- Define Eloquent relationships
- Use eager loading (`::with()`) to prevent N+1
- Add casts for enums and dates

### 3. Controllers & Requests
- Create Form Requests for validation
- Keep controllers thin, use service classes
- Type hint all parameters and returns

### 4. Routes
- Add routes to `routes/web.php` or `routes/api.php`
- Use named routes
- Verify with `php artisan route:list`

### 5. Views (Blade + Tailwind v4)
- Create Blade templates in `resources/views/`
- Use Tailwind v4 syntax: `@import "tailwindcss";`
- Follow existing component patterns

### 6. Testing
- Generate tests with `php artisan make:test --phpunit`
- Target >85% coverage
- Run `php artisan test` before committing

### 7. Code Quality
- Run `vendor/bin/pint --dirty` for PSR-12 compliance
- Review for type safety and eager loading

## Validation Checkpoints

| Stage | Command | Success Criteria |
|-------|---------|------------------|
| After migrations | `php artisan migrate:status` | All migrations show `Ran` |
| After routes | `php artisan route:list` | Routes appear correctly |
| After code | `php artisan test` | All tests pass |
| Before commit | `vendor/bin/pint --dirty` | No style issues |

## Common Patterns for Drone System

### Models (likely needed)
- `Drone` — aircraft information, status, battery
- `Mission` — flight missions with waypoints
- `Telemetry` — real-time flight data
- `Pilot` — operator information, certifications
- `Geofence` — restricted flight zones
- `Alert` — system notifications

### Key Features
- Real-time telemetry dashboard
- Mission planning with map integration
- Battery and altitude monitoring
- Geofencing alerts
- Flight history and logs
