<?php

namespace LaravelScaffolding\Scaffolding\Support;

/**
 * Represents a single database/model field definition.
 * Used across all generators for consistent scaffolding.
 */
final class FieldDefinition
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $type,        // string|text|longText|integer|bigInteger|smallInteger|boolean|decimal|float|date|datetime|enum|foreignId|json
        public readonly bool    $nullable  = false,
        public readonly mixed   $default   = null,
        public readonly array   $enumValues = [],
        public readonly ?string $relatedModel = null,  // for foreignId columns
        public readonly bool    $unique    = false,
        public readonly bool    $isAudit   = false,    // true for created_by / updated_by
    ) {}

    public function isEnum(): bool
    {
        return $this->type === 'enum';
    }

    public function isForeignKey(): bool
    {
        return $this->type === 'foreignId';
    }

    public function isNumeric(): bool
    {
        return in_array($this->type, ['integer', 'bigInteger', 'smallInteger', 'decimal', 'float'], true);
    }

    public function isDate(): bool
    {
        return in_array($this->type, ['date', 'datetime'], true);
    }

    public function castType(): ?string
    {
        return match ($this->type) {
            'boolean'                               => 'boolean',
            'integer', 'bigInteger', 'smallInteger' => 'integer',
            'decimal', 'float'                      => 'float',
            'date'                                  => 'date',
            'datetime'                              => 'datetime',
            'json'                                  => 'array',
            default                                 => null,
        };
    }

    /**
     * Returns the Eloquent migration Blueprint method name for this field type.
     */
    public function migrationMethod(): string
    {
        return match ($this->type) {
            'string'       => 'string',
            'text'         => 'text',
            'longText'     => 'longText',
            'integer'      => 'integer',
            'bigInteger'   => 'bigInteger',
            'smallInteger' => 'smallInteger',
            'boolean'      => 'boolean',
            'decimal'      => 'decimal',
            'float'        => 'float',
            'date'         => 'date',
            'datetime'     => 'timestamp',
            'json'         => 'json',
            'enum'         => 'enum',
            'foreignId'    => 'foreignId',
            default        => 'string',
        };
    }

    /**
     * Returns the HTML input type for form generation.
     */
    public function formInputType(): string
    {
        return match ($this->type) {
            'text', 'longText'                      => 'textarea',
            'integer', 'bigInteger', 'smallInteger' => 'number',
            'decimal', 'float'                      => 'number',
            'boolean'                               => 'checkbox',
            'date'                                  => 'date',
            'datetime'                              => 'datetime-local',
            'enum'                                  => 'select',
            'foreignId'                             => 'select',
            default                                 => 'text',
        };
    }

    /**
     * Human-readable label for view rendering.
     */
    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->name));
    }

    /**
     * Whether this field should appear in the DataTable columns.
     */
    public function isDataTableColumn(): bool
    {
        return !$this->isForeignKey() && !in_array($this->type, ['text', 'longText', 'json'], true);
    }

    /**
     * The data key used in the DataTable JSON response.
     * Enum / boolean fields get a _label variant.
     */
    public function dataKey(): string
    {
        if ($this->type === 'enum' || $this->type === 'boolean') {
            return $this->name . '_label';
        }

        return $this->name;
    }
}
