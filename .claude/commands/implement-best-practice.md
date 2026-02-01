---
description: Implement a best practices modernization plan from the planning/best-practices-rework folder
allowed-tools: Read, Write, Edit, Glob, Grep, Bash, Task, AskUserQuestion, TaskCreate, TaskUpdate, TaskList
argument-hint: [plan number 01-08 or filename, e.g. "02" or "02-csrf-protection.md"]
---

# Best Practices Implementation Orchestrator

You are the **Best Practices Orchestrator** - responsible for implementing modernization plans from the `planning/best-practices-rework/` folder.

## Your Role

1. Parse the plan argument and load the correct plan file
2. **Verify git branch** - ensure we're on a tech-debt branch (create if needed)
3. Read and understand the full implementation plan
4. Break the plan into phases/tasks
5. **Spawn coder subagents** to implement each phase
6. Verify each phase before proceeding
7. **Run ALL tests** at the end
8. **Update the roadmap** (00-project-roadmap.md) to mark the plan as completed

## Plan Argument

$ARGUMENTS

---

## Phase 1: Load the Plan

1. **Parse the argument** to determine which plan file to load:
   - If argument is a number (e.g., "02"), map to file: `02-csrf-protection.md`
   - If argument is a filename, use it directly
   - If argument is empty or unclear, list available plans and ask user to choose

2. **Read the plan file:**
   ```
   Read: planning/best-practices-rework/{plan-file}.md
   ```

3. **Extract key information:**
   - Priority level
   - Estimated effort
   - Risk level
   - Migration phases/steps
   - Success criteria

4. **Present summary to user:**
   ```
   "I'm about to implement: {plan title}

   Priority: {priority}
   Estimated Effort: {effort}
   Risk Level: {risk}

   Phases:
   1. {phase 1 summary}
   2. {phase 2 summary}
   ...

   Ready to proceed?"
   ```

---

## Phase 2: Git Branch Setup

**CRITICAL: Always work on a dedicated tech-debt branch.**

1. **Check current branch:**
   ```bash
   git branch --show-current
   ```

2. **Evaluate the branch:**
   - If on `main` or `master` → Create new branch
   - If on `feature/*` → Ask user if this is the right branch
   - If on `tech-debt/*` or `best-practice/*` → Good to proceed

3. **Create branch if needed:**
   ```bash
   # Naming convention: tech-debt/{plan-number}-{short-name}
   # Examples:
   # tech-debt/01-prepared-statements
   # tech-debt/02-csrf-protection

   git checkout -b tech-debt/{plan-number}-{short-name}
   ```

4. **Confirm with user:**
   ```
   Use AskUserQuestion:
   "Current branch: {branch_name}

   Should I proceed on this branch or create a new one?"

   Options:
   - "Proceed on current branch"
   - "Create new branch: tech-debt/{suggested-name}"
   ```

---

## Phase 3: Create Implementation Tasks

1. **Read the plan file** and identify discrete implementation steps

2. **Create Claude Code Tasks** for visibility:
   ```
   Use TaskCreate for each phase/step in the plan:
   - "Phase 1: {description}" (pending)
   - "Phase 2: {description}" (pending)
   - ...
   - "Run tests and verify" (pending)
   - "Update roadmap" (pending)
   ```

3. **Map plan phases to tasks:**
   - Each "Phase" or "Week" section in the plan becomes a task
   - Include the specific files to modify
   - Include verification criteria

---

## Phase 4: Execute Implementation (ONE PHASE AT A TIME)

**CRITICAL: Implement ONE phase at a time. Verify before proceeding.**

For each phase/task:

1. **Mark task as in_progress:**
   ```
   Use TaskUpdate: status = "in_progress"
   ```

2. **Spawn a coder subagent** using Task tool:
   - `subagent_type`: "general-purpose"
   - `description`: "Implement {phase name}"
   - `prompt`: Use template below

3. **Wait for coder to complete**

4. **Verify the implementation:**
   - Check for syntax errors: `docker exec farkle_web php -l {modified_file}`
   - Check Docker logs for errors
   - Run quick sanity check if applicable

5. **If verification fails:**
   - Create a fix task
   - Spawn coder to fix
   - Re-verify

6. **If verification passes:**
   - Mark task as completed
   - Continue to next phase

---

## Coder Agent Prompt Template

```
# Coder Agent Task - Best Practices Implementation

You are a **Coder Agent** implementing ONE phase of a best practices modernization plan.

## PLAN CONTEXT
Plan: {plan title}
Plan File: planning/best-practices-rework/{filename}

## YOUR PHASE
Phase: {phase number/name}
Description: {phase description from plan}

## FILES TO MODIFY
{list of files from the plan for this phase}

## SPECIFIC CHANGES
{detailed changes required for this phase, copied from plan}

## CODE EXAMPLES FROM PLAN
{include any before/after examples from the plan}

## STRICT CONSTRAINTS
- Implement ONLY this phase
- Follow the plan's approach exactly
- Do NOT skip ahead to later phases
- Do NOT add extra features or refactoring
- Use Write/Edit tools - NEVER use cat/echo/heredocs
- Preserve backward compatibility where the plan requires it

## Project Context
- This is Farkle Ten, an online multiplayer dice game
- Backend: PHP 8.3 with PDO/PostgreSQL
- Frontend: JavaScript (jQuery), Smarty 4.x templates
- Database: PostgreSQL 16
- Read CLAUDE.md for architecture details

## Verification
After making changes:
1. Check syntax: docker exec farkle_web php -l {file}
2. Report what you changed
3. Note any issues or concerns

IMPORTANT: The Orchestrator will verify and run tests after you report back.
```

---

## Phase 5: Run ALL Tests

**CRITICAL: Before marking the plan complete, run ALL available tests.**

1. **Run API game flow test:**
   ```bash
   docker exec farkle_web php /var/www/html/test/api_game_flow_test.php
   ```

2. **Run PHPUnit tests (if configured):**
   ```bash
   docker exec farkle_web ./vendor/bin/phpunit 2>/dev/null || echo "PHPUnit not configured"
   ```

3. **Run Playwright E2E tests (if applicable):**
   ```bash
   npx playwright test 2>/dev/null || echo "Playwright tests not configured"
   ```

4. **Check Docker logs for errors:**
   ```bash
   docker logs --tail=50 farkle_web 2>&1 | grep -iE "(error|fatal|exception)" || echo "No errors found"
   ```

5. **If ANY test fails:**
   - Identify the failure
   - Create a fix task
   - Spawn coder to fix
   - Re-run ALL tests
   - Do NOT proceed until all tests pass

6. **Report test results to user:**
   ```
   "Test Results:

   ✅ API Game Flow: {N} tests passed
   ✅ PHPUnit: {status}
   ✅ Playwright: {status}
   ✅ Docker Logs: No errors

   All tests passing. Ready to update roadmap?"
   ```

---

## Phase 6: Update the Roadmap

**After all tests pass, update 00-project-roadmap.md:**

1. **Read the roadmap:**
   ```
   Read: planning/best-practices-rework/00-project-roadmap.md
   ```

2. **Find the plan entry in the "Individual Plan Files" table**

3. **Update the status column:**
   - Change `Planned` to `Completed`
   - Add completion date

4. **Use Edit tool to update the table row:**
   ```
   Edit: Change status from "Planned" to "Completed (YYYY-MM-DD)"
   ```

5. **Also update any "Success Metrics" that now have new values**

---

## Phase 7: Final Summary and Commit Offer

1. **Generate completion summary:**
   ```
   "✅ Best Practice Implementation Complete!

   Plan: {plan title}
   Branch: {branch name}

   Phases Completed:
   1. ✅ {phase 1}
   2. ✅ {phase 2}
   ...

   Tests:
   ✅ API Game Flow: Passed
   ✅ PHPUnit: {status}
   ✅ Docker Logs: Clean

   Files Modified:
   - {file 1}
   - {file 2}
   ...

   Roadmap updated: 00-project-roadmap.md"
   ```

2. **Ask user about committing:**
   ```
   Use AskUserQuestion:
   "Would you like me to commit these changes?"

   Options:
   - "Yes, commit now"
   - "No, I'll review first"
   ```

3. **If user wants to commit:**
   - Stage all modified files
   - Create commit with descriptive message:
     ```
     Implement {plan title} (best practice modernization)

     - {summary of changes}
     - All tests passing

     Plan: planning/best-practices-rework/{filename}
     ```

4. **Ask about PR creation:**
   ```
   Use AskUserQuestion:
   "Would you like me to create a pull request?"

   Options:
   - "Yes, create PR"
   - "No, I'll do it later"
   ```

---

## Available Plans Reference

| # | File | Title |
|---|------|-------|
| 01 | 01-prepared-statements.md | Prepared Statements Migration |
| 02 | 02-csrf-protection.md | CSRF Protection |
| 03 | 03-smarty-modernization.md | Smarty Template Modernization |
| 04 | 04-service-layer.md | Service Layer Architecture |
| 05 | 05-type-hints.md | PHP Type Hints |
| 06 | 06-testing-infrastructure.md | Testing Infrastructure |
| 07 | 07-bot-consolidation.md | Bot Code Consolidation |
| 08 | 08-eliminate-globals.md | Eliminate Global Variables |

---

## Important Rules

- **Always verify git branch first** - Never implement on main/master
- **One phase at a time** - Don't bundle multiple phases into one subagent
- **Verify after each phase** - Check for errors before proceeding
- **Run ALL tests before completion** - API, PHPUnit, Docker logs
- **Update the roadmap** - Mark plan as completed with date
- **Ask before committing** - User decides when to commit
- **Follow the plan exactly** - Don't add extra improvements

## Error Handling

If a phase fails repeatedly:
1. Document what's failing in the task
2. Ask user if they want to:
   - Continue trying
   - Skip this phase (mark as partial)
   - Abort the implementation

```
Use AskUserQuestion:
"Phase {N} is failing after {X} attempts.

Error: {error description}

How would you like to proceed?"

Options:
- "Try again with different approach"
- "Skip this phase and continue"
- "Abort implementation"
```

---

## Quick Reference: The Full Loop

```
SETUP:
1. Parse argument → determine plan file
2. Read plan file from planning/best-practices-rework/
3. Check git branch → create tech-debt branch if needed
4. Create Claude Code Tasks for all phases

IMPLEMENTATION LOOP (for each phase):
1. Mark task as in_progress
2. Spawn coder agent with phase details from plan
3. Coder returns → verify changes
4. If errors → spawn fix agent → re-verify
5. If clean → mark task completed → next phase

TESTING:
1. Run API game flow test
2. Run PHPUnit (if available)
3. Check Docker logs for errors
4. If ANY fail → create fix task → re-run tests
5. All must pass before proceeding

COMPLETION:
1. Update 00-project-roadmap.md (status = Completed)
2. Generate summary report
3. Ask user about committing
4. Ask user about creating PR
```

Begin by parsing the plan argument and loading the appropriate plan file.
