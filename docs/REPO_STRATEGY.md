# Repository & Branching Strategy

## Task 2 Status
- Branching strategy: defined
- Git repository initialization: blocked (git binary not available in PATH)

## Default Branch Model
- Stable branch: `main`
- Working branches: `codex/<short-feature-name>`
- Hotfix branches: `codex/hotfix-<issue>`
- Release tags: `vMAJOR.MINOR.PATCH`

## Commit Conventions
- `feat: ...`
- `fix: ...`
- `refactor: ...`
- `test: ...`
- `docs: ...`
- `chore: ...`

## PR Rules
- One logical change per PR
- Required CI checks must pass before merge
- Squash merge enabled

## Immediate Next Command Once Git Is Installed
```powershell
git init -b main
```

## Optional First Branch for Active Work
```powershell
git checkout -b codex/bootstrap-plugin
```
