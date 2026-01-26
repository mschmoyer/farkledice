# Planning Directory

This directory contains planning artifacts, feature specifications, and documentation for the Farkle Ten project.

## Directory Structure

```
planning/
├── README.md                 # This file
├── feature-list.json         # Feature INDEX (lightweight metadata)
├── features/                 # Individual feature specifications
│   └── feature-YYYYMMDD-NNN.json
├── extra-information/        # Additional documentation and guides
└── [feature-name]/           # Feature-specific planning (mockups, tasks, etc.)
```

## Task Tracking: Two Systems

This project uses **two complementary systems** for tracking work:

### 1. Claude Code Tasks (Real-time Progress)

Claude Code's native Tasks feature provides real-time visibility in the terminal:

- **Location:** `~/.claude/tasks/` (outside this repo)
- **Persistence:** Survives across sessions when using `CLAUDE_CODE_TASK_LIST_ID`
- **Purpose:** Track what's happening NOW
- **Visibility:** Press `Ctrl+T` to toggle task list in terminal

**Start Claude Code with persistent tasks:**
```bash
CLAUDE_CODE_TASK_LIST_ID=farkledice claude
```

### 2. JSON Feature Files (Auditable History)

JSON files in this directory serve as the source of truth:

- **Location:** `planning/features/`
- **Persistence:** Git-tracked, permanent history
- **Purpose:** Record what was DONE and WHY
- **Includes:** Requirements, tasks, verification results

## Feature File Schema

Each feature file (`planning/features/feature-YYYYMMDD-NNN.json`) contains:

```json
{
  "id": "feature-YYYYMMDD-NNN",
  "title": "Brief feature title",
  "description": "Full description from user",
  "created_at": "ISO timestamp",
  "status": "pending | in_progress | completed",
  "completed_at": null,

  "requirements": [
    {
      "id": "REQ-001",
      "detail": "Specific, testable requirement",
      "status": "pending | satisfied"
    }
  ],

  "tasks": [
    {
      "id": "task-001",
      "type": "implementation | fix | verification",
      "requirement_ids": ["REQ-001"],
      "description": "What needs to be done",
      "status": "pending | in_progress | completed",
      "completed_at": null
    }
  ],

  "verification_results": {
    "requirements_total": 0,
    "requirements_satisfied": 0,
    "tasks_completed": 0,
    "verified_at": null
  }
}
```

## Feature Index

The `feature-list.json` file is a lightweight index containing only metadata:

```json
{
  "features": [
    {
      "id": "feature-20260117-001",
      "title": "Add dark mode",
      "status": "completed",
      "created_at": "2026-01-17T10:00:00Z"
    }
  ]
}
```

## Using the Orchestrator

To develop a new feature with proper tracking:

```bash
/orchestrate-feature "your feature description"
```

The orchestrator will:
1. Extract and confirm requirements with you
2. Create the feature file and index entry
3. Create Claude Code Tasks for terminal visibility
4. Spawn coder agents one at a time
5. Verify each task in Docker
6. Update both tracking systems
7. Ask before committing

## Subdirectories

### `features/`
Contains individual feature specification JSON files. One file per feature.

### `extra-information/`
Documentation, guides, and reference materials that don't fit elsewhere:
- Deployment guides
- Architecture diagrams
- Implementation summaries

### `[feature-name]/`
Feature-specific subdirectories for complex features that need:
- Mockups (HTML/images)
- Detailed task breakdowns
- Implementation notes

## Best Practices

1. **Don't manually edit feature files** - Let the orchestrator manage them
2. **Use features/ for specs** - Not loose files in planning/
3. **Archive old features** - Move completed features to `features/archived/` if needed
4. **Keep mockups with features** - Put mockups in feature-specific subdirectories
