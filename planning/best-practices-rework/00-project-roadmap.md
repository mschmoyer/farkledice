# Best Practices Modernization Roadmap

**Total Estimated Duration:** 38-50 weeks (sequential) or 26-34 weeks (with parallelization)
**Last Updated:** 2026-02-01

---

## Executive Summary

This roadmap prioritizes 8 modernization initiatives for the Farkle Ten codebase. The order balances:
1. **Security first** - Address vulnerabilities before feature work
2. **Foundation before refactoring** - Testing enables safe changes
3. **Dependencies** - Some plans build on others
4. **Risk management** - Lower-risk changes validate the approach

---

## Dependency Graph

```
                    ┌─────────────────────┐
                    │  06. Testing        │
                    │  Infrastructure     │
                    └─────────┬───────────┘
                              │ enables validation
        ┌─────────────────────┼─────────────────────┐
        ▼                     ▼                     ▼
┌───────────────┐   ┌─────────────────┐   ┌─────────────────┐
│ 02. CSRF      │   │ 01. Prepared    │   │ 05. Type Hints  │
│ (2-3 days)    │   │ Statements      │   │ (5-6 weeks)     │
└───────────────┘   │ (6-8 weeks)     │   └────────┬────────┘
                    └─────────────────┘            │
                                                   │ foundation for
                              ┌────────────────────┤
                              ▼                    ▼
                    ┌─────────────────┐   ┌─────────────────┐
                    │ 08. Eliminate   │   │ 04. Service     │
                    │ Globals         │──▶│ Layer           │
                    │ (4-5 weeks)     │   │ (6-8 weeks)     │
                    └─────────────────┘   └────────┬────────┘
                                                   │
                              ┌─────────────────────┤
                              ▼                    ▼
                    ┌─────────────────┐   ┌─────────────────┐
                    │ 07. Bot         │   │ 03. Smarty      │
                    │ Consolidation   │   │ Modernization   │
                    │ (4-5 weeks)     │   │ (6-8 weeks)     │
                    └─────────────────┘   └─────────────────┘
                                          (can run in parallel)
```

---

## Recommended Implementation Order

### Phase 1: Foundation (Weeks 1-6)

| # | Plan | Duration | Why First |
|---|------|----------|-----------|
| 1 | **06. Testing Infrastructure** | 4-6 weeks | Enables safe validation of ALL subsequent changes. Without tests, every refactoring is high-risk. |

**Deliverables:**
- PHPUnit configured with unit and integration test suites
- 30%+ code coverage on critical paths (dice scoring, game flow)
- CI/CD pipeline running tests on every PR

---

### Phase 2: Security (Weeks 7-16)

| # | Plan | Duration | Why Now |
|---|------|----------|---------|
| 2 | **02. CSRF Protection** | 2-3 days | Quick security win. Low effort, high impact. |
| 3 | **01. Prepared Statements** | 6-8 weeks | Critical SQL injection prevention. Tests validate the migration. |

**Deliverables:**
- CSRF tokens on all state-changing endpoints
- Zero SQL injection vulnerabilities
- All 210+ queries migrated to prepared statements

---

### Phase 3: Code Quality (Weeks 17-22)

| # | Plan | Duration | Why Now |
|---|------|----------|---------|
| 4 | **05. Type Hints** | 5-6 weeks | Foundation for refactoring. IDE support catches bugs early. Required before major architectural changes. |

**Deliverables:**
- 90%+ functions with type hints
- 60%+ files with `declare(strict_types=1)`
- PHPStan level 5 passing

---

### Phase 4: Architecture (Weeks 23-36)

| # | Plan | Duration | Why Now |
|---|------|----------|---------|
| 5 | **08. Eliminate Globals** | 4-5 weeks | Creates App Container pattern needed for service layer. |
| 6 | **04. Service Layer** | 6-8 weeks | Major refactoring. Benefits from typed code and DI container. |

**Deliverables:**
- Zero `global $` statements
- App Container with lazy-loaded services
- Business logic extracted from `farkle_fetch.php`
- 90%+ testable functions

---

### Phase 5: Feature & Frontend (Weeks 37-50)

| # | Plan | Duration | Why Now |
|---|------|----------|---------|
| 7 | **07. Bot Consolidation** | 4-5 weeks | Uses service layer patterns. 67% code reduction. |
| 8 | **03. Smarty Modernization** | 6-8 weeks | Frontend work, lowest dependency on backend changes. |

**Deliverables:**
- Bot code reduced from 5,416 to ~1,800 lines
- Template inheritance with `base.tpl`
- Custom Smarty modifiers
- CSS extracted from templates

---

## Parallelization Opportunities

These plans can run concurrently to reduce total duration:

| Parallel Track A | Parallel Track B | Savings |
|------------------|------------------|---------|
| 01. Prepared Statements | 02. CSRF Protection | 2-3 days |
| 08. Eliminate Globals | 03. Smarty Modernization | 4-5 weeks |
| 04. Service Layer | 03. Smarty Modernization | 6-8 weeks |

**Optimized Timeline:** With parallelization, total duration reduces to ~26-34 weeks.

---

## Risk Assessment

| Plan | Risk | Mitigation |
|------|------|------------|
| 01. Prepared Statements | High - touches 210+ queries | Migrate file-by-file, test after each |
| 02. CSRF Protection | Low - additive change | Feature flag to disable if issues |
| 03. Smarty | Low - frontend only | Visual regression testing |
| 04. Service Layer | Medium - major refactor | Keep procedural code during transition |
| 05. Type Hints | Low - gradual adoption | No strict_types until tests exist |
| 06. Testing | Low - additive | No production code changes |
| 07. Bot Consolidation | Medium - affects gameplay | Feature flag between old/new systems |
| 08. Eliminate Globals | Medium - touches many files | Compatibility layer during migration |

---

## Quick Wins (Can Start Immediately)

If you want to make progress before the full roadmap:

1. **02. CSRF Protection** (2-3 days) - Standalone security improvement
2. **06. Testing** - Just the dice scoring tests (1-2 days) - Immediate value
3. **03. Smarty** - Extract inline CSS only (1 week) - No PHP changes

---

## Success Metrics

| Metric | Current | After Phase 2 | After Phase 4 | Final |
|--------|---------|---------------|---------------|-------|
| Test Coverage | ~10% (65 tests) | 30% | 60% | 80%+ |
| SQL Injection Risk | High | None | None | None |
| CSRF Protection | None | Full | Full | Full |
| Typed Functions | ~5% | ~5% | 90% | 90% |
| Global Variables | 10+ | 10+ | 0 | 0 |
| Testable Functions | 0% | 30% | 90% | 95% |

---

## Individual Plan Files

| File | Priority | Effort | Status |
|------|----------|--------|--------|
| [01-prepared-statements.md](./01-prepared-statements.md) | HIGH | 6-8 weeks | Planned |
| [02-csrf-protection.md](./02-csrf-protection.md) | HIGH | 2-3 days | Planned |
| [03-smarty-modernization.md](./03-smarty-modernization.md) | Medium | 6-8 weeks | Planned |
| [04-service-layer.md](./04-service-layer.md) | Medium-High | 6-8 weeks | Planned |
| [05-type-hints.md](./05-type-hints.md) | Medium | 5-6 weeks | Planned |
| [06-testing-infrastructure.md](./06-testing-infrastructure.md) | Medium-High | 4-6 weeks | **Completed (2026-02-01)** |
| [07-bot-consolidation.md](./07-bot-consolidation.md) | Medium | 4-5 weeks | Planned |
| [08-eliminate-globals.md](./08-eliminate-globals.md) | Medium | 4-5 weeks | Planned |

---

## Notes

- **Version bumps:** Each completed plan should increment the minor version
- **Release notes:** Document each phase completion in `data/release-notes.json`
- **Branch strategy:** One feature branch per plan, merge to main when complete
- **Testing gate:** No plan is complete until API tests pass
