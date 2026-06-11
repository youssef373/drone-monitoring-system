# Workflows

## Active Workflows

- **[drone-monitoring](/drone-monitoring)** — Primary workflow for the Drone Monitoring System project
  - Use for: implementing features, fixing bugs, database changes, UI updates
  - Triggers: drone, mission, telemetry, dashboard, flight operations

## Workflow Usage

When working on this project, invoke the appropriate workflow:

```
/drone-monitoring
```

This will load the project-specific guidance from `.windsurf/workflows/drone-monitoring.md`.

## Project Skills

The following AI skills are configured for this project in `.ai/skills/`:

1. **drone-monitoring-system** — Domain-specific guidance for UAV systems
2. **laravel-11-12-app-guidelines** — Laravel 12 development standards
3. **laravel-specialist** — Comprehensive Laravel patterns
4. **laravel-security** — Security best practices
5. **eloquent-best-practices** — ORM optimization
6. **laravel-patterns** — Design patterns

## MCP Servers

Configured in `.ai/mcp/mcp.json`:

- **code-review-graph** — Code knowledge graph for reviews
- **laravel-boost** — Laravel documentation and tools

## Quick Commands

```bash
# Start development
composer run dev

# Run tests
php artisan test

# Check code style
./vendor/bin/pint --dirty

# Database status
php artisan migrate:status

# Route list
php artisan route:list
```
