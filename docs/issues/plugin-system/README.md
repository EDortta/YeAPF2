# Epic: plugin-system

## Goal
Define typed plugin roles with boot-time registration, safe guardrails, and testability. Replace the current flat `PluginList` with a role-aware `PluginRegistry` and wire each pluggable concern (document validation, type definitions, DB drivers, cache, auth, translation, logging) through a single consistent registration and lookup mechanism.

## Motivation
The current `PluginList` is a flat bag keyed by class name with no notion of capability or role:
- `document-checker.php` holds validators for UY/AR/PE as private methods on a monolithic class, registered via a global variable, disconnected from the constraint pipeline. Brazil is missing entirely.
- `RedisConnection` is hardwired inside `PersistenceContext` — not swappable.
- `yJWT` is a concrete class with no plugin contract — auth scheme is not swappable.
- `Translator` already loads as a plugin but has no typed interface — translation backend is not swappable.
- `yLogger` has a hardcoded sink — log destination is not pluggable.
- Third parties have no contract to implement or conformance test to run.

## Design principles
- **Boot-time only.** The registry freezes after `loadPlugins()` completes. HTTP2 server starts once and runs standalone — no runtime registration.
- **Self-contained plugins.** A plugin brings both the implementation AND any type/constraint definitions it owns. No editing of core files to add a country or swap a backend.
- **Non-intrusive.** `Registry::get*()` returning `null` (plugin not loaded) must never crash the framework. Callers degrade gracefully.
- **One interface per role.** A plugin class may implement multiple interfaces.
- **Conformance-tested.** Every role has an abstract PHPUnit base class third parties must extend.

## Scope
- `PluginRoleInterface` marker + all role interfaces (PLG-001)
- `Registry` class with role-keyed slots and boot-time freeze (PLG-002)
- `authenticityChecker` constraint field in `SanitizedKeyData` (PLG-003)
- `BrazilDocumentPlugin` — CNPJ + CPF (PLG-004)
- `LatamDocumentPlugin` — UY/AR/PE migration from `document-checker.php` (PLG-005)
- `DocumentValidatorConformanceTest` abstract PHPUnit base (PLG-006)
- `RedisCachePlugin` — Redis behind `CacheProviderInterface` (PLG-007)
- `Translator` migration to `TranslationProviderInterface` + CQR-007 fix (PLG-008)
- `JWTAuthPlugin` adapter + CQR-008 fix (PLG-009)
- `LogHandlerInterface` wired into `yLogger` (PLG-010)

## Out of Scope
- Runtime plugin loading or hot-reload after boot
- `SerializerInterface` — deferred until Protobuf support is fixed and stabilized (CQR-002)
- UI or API surface for plugin management
- Mailer, queue, SMS — outside framework scope

## Plugin role map

```
PluginRoleInterface (marker)
  ├── Validator\DocumentValidatorInterface   (keyed slot, many per registry)
  ├── Type\TypeProviderInterface             (list slot, many per registry)
  ├── DB\DBDriverInterface                   (keyed by engine, defined in db-driver-contract)
  ├── Cache\CacheProviderInterface           (singleton slot)
  ├── Auth\AuthProviderInterface             (singleton slot)
  ├── I18n\TranslationProviderInterface      (singleton slot)
  └── Log\LogHandlerInterface                (singleton slot)
```

`SerializerInterface` will extend `PluginRoleInterface` when added (future).

## Namespace
`YeAPF\Plugins\` — role sub-namespaces:
- `YeAPF\Plugins\Validator\DocumentValidatorInterface`
- `YeAPF\Plugins\Type\TypeProviderInterface`
- `YeAPF\Plugins\Cache\CacheProviderInterface`
- `YeAPF\Plugins\Auth\AuthProviderInterface`
- `YeAPF\Plugins\Auth\AuthResult` (value object)
- `YeAPF\Plugins\I18n\TranslationProviderInterface`
- `YeAPF\Plugins\Log\LogHandlerInterface`
- `YeAPF\Plugins\Registry`

## Cross-epic dependency
`db-driver-contract` epic: `DBDriverInterface` extends `PluginRoleInterface` (PLG-001 must merge before `db-driver-contract` can complete its interface definitions).

## Agent
`code-quality-agent`

## Issues
- [PLG-001-plugin-role-interfaces-[PR].md](./PLG-001-plugin-role-interfaces-[PR].md)
- [PLG-002-plugin-registry-boot-freeze-[on-work].md](./PLG-002-plugin-registry-boot-freeze-[on-work].md)
- [PLG-003-authenticity-checker-in-constraint-pipeline-[on-work].md](./PLG-003-authenticity-checker-in-constraint-pipeline-[on-work].md)
- [PLG-004-brazil-document-plugin-[opened].md](./PLG-004-brazil-document-plugin-[opened].md)
- [PLG-005-latam-document-plugin-migration-[opened].md](./PLG-005-latam-document-plugin-migration-[opened].md)
- [PLG-006-document-validator-conformance-test-[opened].md](./PLG-006-document-validator-conformance-test-[opened].md)
- [PLG-007-redis-cache-plugin-[opened].md](./PLG-007-redis-cache-plugin-[opened].md)
- [PLG-008-i18n-translation-plugin-[opened].md](./PLG-008-i18n-translation-plugin-[opened].md)
- [PLG-009-jwt-auth-plugin-[opened].md](./PLG-009-jwt-auth-plugin-[opened].md)
- [PLG-010-log-handler-plugin-[opened].md](./PLG-010-log-handler-plugin-[opened].md)
- [PLG-011-unified-plugin-skeleton-and-startup-chain-review-[opened].md](./PLG-011-unified-plugin-skeleton-and-startup-chain-review-[opened].md)
