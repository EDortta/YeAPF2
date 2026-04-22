2026-04-22 — PLG-003 moved to closed after merge to master; branch `issue/plugin-system/authenticity-checker-in-constraint-pipeline` deleted
2026-04-22 — PLG-002 moved to closed after merge to master; branch `issue/plugin-system/plugin-registry-boot-freeze` deleted
2026-04-22 — PLG-001 moved to closed after merge to master; branch `issue/plugin-system/plugin-role-interfaces` deleted
2026-04-22 — PLG-003 moved to PR; branch `issue/plugin-system/authenticity-checker-in-constraint-pipeline` pushed and PR opened against PLG-002
2026-04-22 — PLG-002 moved to PR; branch `issue/plugin-system/plugin-registry-boot-freeze` pushed and PR opened against PLG-001
2026-04-22 — PLG-001 moved to PR; branch `issue/plugin-system/plugin-role-interfaces` pushed and PR opened against master
# CHANGELOG — plugin-system

2026-04-21 — PLG-003 moved to on-work; wired `authenticityChecker` into `SanitizedKeyData::checkConstraint()` with registry lookup and regression tests
2026-04-21 — PLG-002 moved to on-work; added Plugin Registry with role slots, freeze guard, and PluginList auto-registration bridge
2026-04-21 — PLG-001 moved to on-work; branch `issue/plugin-system/plugin-role-interfaces` created
2026-04-21 — Added PLG-011 to review and align unified plugin skeleton and startup chain docs (db/security/login/i18n), including `docs/database/how-to-connect-a-new-database-engine.md` and `docs/plugins/how-to-create-a-plugin.md`
2026-04-17 — Expanded to 10 issues: added Cache (PLG-007), I18n (PLG-008), Auth (PLG-009), LogHandler (PLG-010) roles; PLG-001 and PLG-002 updated to cover all roles; SerializerInterface explicitly deferred pending CQR-002
2026-04-17 — Epic created; scope defined, 6 issues opened in README.md
