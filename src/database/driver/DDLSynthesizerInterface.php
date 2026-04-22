<?php declare(strict_types=1);

namespace YeAPF\Connection\DB\Driver;

interface DDLSynthesizerInterface
{
    public const OP_CREATE_TABLE = 'create_table';
    public const OP_ALTER_TABLE = 'alter_table';
    public const OP_CREATE_ENUM = 'create_enum';
    public const OP_ADD_FOREIGN_KEY = 'add_foreign_key';
    public const OP_CREATE_INDEX = 'create_index';

    /**
     * Canonical manifest-diff input model.
     *
     * @param array{
     *   operations:list<array{
     *     type:string,
     *     table?:string,
     *     schema?:string,
     *     name?:string,
     *     columns?:list<array<string,mixed>>,
     *     enum_values?:list<string>,
     *     foreign_key?:array<string,mixed>,
     *     index?:array<string,mixed>,
     *     changes?:list<array<string,mixed>>,
     *     if_not_exists?:bool
     *   }>,
     *   metadata?:array<string,mixed>
     * } $manifestDiff
     * @return array{
     *   operations:list<array{
     *     type:string,
     *     table?:string,
     *     schema?:string,
     *     name?:string,
     *     columns?:list<array<string,mixed>>,
     *     enum_values?:list<string>,
     *     foreign_key?:array<string,mixed>,
     *     index?:array<string,mixed>,
     *     changes?:list<array<string,mixed>>,
     *     if_not_exists?:bool
     *   }>,
     *   metadata:array<string,mixed>
     * }
     */
    public function normalizeManifestDiff(array $manifestDiff): array;

    /**
     * Generate an ordered and idempotent-aware DDL plan from canonical diff input.
     *
     * @param array{
     *   operations:list<array{
     *     type:string,
     *     table?:string,
     *     schema?:string,
     *     name?:string,
     *     columns?:list<array<string,mixed>>,
     *     enum_values?:list<string>,
     *     foreign_key?:array<string,mixed>,
     *     index?:array<string,mixed>,
     *     changes?:list<array<string,mixed>>,
     *     if_not_exists?:bool
     *   }>,
     *   metadata?:array<string,mixed>
     * } $manifestDiff
     * @return array{
     *   statements:list<array{
     *     kind:string,
     *     sql:string,
     *     rollback_sql?:string|null,
     *     idempotent:bool,
     *     metadata:array<string,mixed>
     *   }>,
     *   idempotent:bool,
     *   fingerprint:string,
     *   metadata:array<string,mixed>
     * }
     */
    public function synthesize(array $manifestDiff): array;

    /**
     * Validate output shape for a generated DDL plan.
     *
     * @param array{
     *   statements?:list<array{
     *     kind?:string,
     *     sql?:string,
     *     rollback_sql?:string|null,
     *     idempotent?:bool,
     *     metadata?:array<string,mixed>
     *   }>,
     *   idempotent?:bool,
     *   fingerprint?:string,
     *   metadata?:array<string,mixed>
     * } $plan
     */
    public function isValidPlan(array $plan): bool;
}
