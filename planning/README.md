# Feature Planning & Bug Tracking

This directory tracks feature development and bug fixes for Farkle Ten.

## Structure

```
planning/
├── feature-list.json          # Index of all features (metadata only)
├── bugfixes.json              # Bug tracking and resolution log
├── features/                  # Individual feature files
│   ├── feature-20260117-001.json
│   └── ...
└── README.md                  # This file
```

## Using the Orchestrator

To develop a new feature using the orchestrator:

```bash
/orchestrate-feature "Add support for tournament brackets"
```

The orchestrator will:
1. Ask clarifying questions about the feature
2. Extract and confirm requirements with you
3. Break down into granular tasks
4. Spawn coder agents one at a time
5. Verify each task completion
6. Ensure all requirements are satisfied
7. Offer to commit when complete

## Using Triage and Fix

To automatically detect and fix errors in the Docker logs:

```bash
/triage-and-fix
```

The triage agent will:
1. Analyze logs/docker-server.log for errors and warnings
2. Categorize issues by severity (CRITICAL → LOW)
3. Create bugfix entries in bugfixes.json
4. Spawn coder agents one at a time to fix issues
5. Verify each fix in the logs
6. Ask for user verification after critical fixes
7. Generate a summary report when complete

## File Formats

### feature-list.json

Lightweight index with basic metadata:

```json
{
  "features": [
    {
      "id": "feature-20260117-001",
      "title": "Tournament brackets",
      "status": "in_progress",
      "created_at": "2026-01-17T12:00:00Z"
    }
  ]
}
```

### features/{feature-id}.json

Complete feature specification including:
- Full description
- Requirements (with IDs and status)
- Tasks (linked to requirements)
- Verification results

See the orchestrator documentation in `.claude/commands/orchestrate-feature.md` for the full schema.

## Feature Statuses

- `in_progress` - Feature is being developed
- `completed` - All requirements satisfied and verified
- `deferred` - Feature postponed for later
- `cancelled` - Feature abandoned

## Best Practices

1. **Clear requirements** - Each requirement should be atomic and testable
2. **One task at a time** - Let the orchestrator spawn coders sequentially
3. **Verify everything** - Test in browser after each task
4. **Commit strategically** - Let the orchestrator offer to commit when complete
