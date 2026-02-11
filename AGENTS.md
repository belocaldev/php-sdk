# AGENTS.md — belocal/sdk (PHP)

## Project

- **Package**: `belocal/sdk` (Composer)
- **Role**: PHP library for text translation via BeLocal API
- **Publish**: Packagist
- **Independence**: Version is independent of JS/React SDKs.

## Structure

```
src/
├── BeLocalEngine.php      # Main API
├── BeLocalError.php
├── BeLocalErrorCode.php
├── BeLocalException.php
├── Transport.php          # HTTP (User-Agent uses composer version)
├── TranslateRequest.php
├── TranslateResponse.php
├── TranslateResult.php
├── TranslateManyResult.php
├── TranslateManyResultFactory.php
└── ...
tests/
├── Unit/
└── Docker/
examples/
```

## Version

- **Where**: `composer.json` → `"version": "X.Y.Z"`
- **Usage**: Transport (e.g. User-Agent) uses this version; no extra steps.

### How to bump version

1. Edit `composer.json` → set `"version": "X.Y.Z"`.
2. No need to change JS or React SDKs (PHP is independent).

SemVer: MAJOR = breaking, MINOR = feature, PATCH = fix.

## Tests

```bash
composer test
composer test-docker   # Multi-PHP (e.g. 7.4, 8.4)
```

## Conventions

### DO

- PSR-4: `BeLocal\` → `src/`.
- Use type hints and return types; keep Transport reusable.
- Follow Composer/SemVer for version bumps.

### DO NOT

- Break backward compatibility without a MAJOR bump.
- Add dependencies without justification.
- Mix concerns: keep Transport, Engine, Request/Response/Result classes clearly separated.
