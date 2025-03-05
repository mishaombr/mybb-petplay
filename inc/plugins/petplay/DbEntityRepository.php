<?php

namespace petplay;

abstract class DbEntityRepository
{
    public const TABLE_NAME = null;
    public const COLUMNS = [];

    protected $db;

    public static function with(\DB_Base $db)
    {
        static $instances = [];

        if (!array_key_exists(static::class, $instances)) {
            $instances[static::class] = new static($db);
        }

        return $instances[static::class];
    }

    public function __construct(\DB_Base $db)
    {
        $this->db = $db;
    }

    /**
     * Inserts row with escaped column values.
     */
    public function insert(array $data): ?int
    {
        $this->db->insert_query(
            static::TABLE_NAME,
            $this->getEscapedArray($data, false),
            false // DB_PgSQL only
        );

        if (count(array_filter(array_column(static::COLUMNS, 'primaryKey'))) == 1) {
            if ($this->db->type == 'pgsql') {
                $insertId = $this->db->fetch_field(
                    $this->db->query('SELECT lastval() AS i'),
                    'i'
                );
            } else {
                $insertId = $this->db->insert_id();
            }

            return $insertId;
        } else {
            return null;
        }
    }

    /**
     * Inserts rows with escaped column values.
     */
    public function insertMultiple(array $data): void
    {
        $escapedData = [];

        foreach ($data as $row) {
            $escapedData[] = $this->getEscapedArray($row, false);
        }

        $this->db->insert_query_multiple(
            static::TABLE_NAME,
            $escapedData
        );
    }

    /**
     * @param int|array $id
     */
    public function getById($id): ?array
    {
        $entries = $this->getByColumn('id', $id);

        if (is_array($id)) {
            return $entries;
        } else {
            if (count($entries) == 1) {
                return current($entries);
            } else {
                return null;
            }
        }
    }

    public function getByColumn(string $columnName, $value, ?array $fields = null): array
    {
        $keyColumn = null;

        if ($fields === null) {
            $fields = '*';

            if (array_key_exists('id', static::COLUMNS)) {
                $keyColumn = 'id';
            }
        } else {
            if (!in_array('id', $fields) && array_key_exists('id', static::COLUMNS)) {
                $fields[] = 'id';
                $keyColumn = 'id';
            }

            $fields = implode(',', $fields);
        }

        if (is_array($value)) {
            if (!empty($value)) {
                $escapedValues = $this->getEscapedColumnValues($columnName, $value);

                $conditions = $columnName . ' IN (' . implode(',', $escapedValues) . ')';
            } else {
                return [];
            }
        } else {
            $conditions = $columnName . ' = ' . $this->getEscapedColumnValue($columnName, $value);
        }

        $query = $this->db->simple_select(static::TABLE_NAME, $fields, $conditions);

        return \petplay\queryResultAsArray($query, $keyColumn);
    }

    public function get(string $columns = '*', ?string $conditions = null, array $foreignTableColumns = [])
    {
        $tableIndex = 1;

        $columns = array_map(
            function (string $value) {
                if (strpos($value, '(') !== false) {
                    return str_replace('(', '(t1.', $value);
                } else {
                    return 't1.' . trim($value);
                }
            },
            explode(',', $columns)
        );

        $tables = [
            TABLE_PREFIX . static::TABLE_NAME . ' t' . $tableIndex++
        ];

        if ($foreignTableColumns) {
            foreach (static::COLUMNS as $columnName => $column) {
                if (!empty($column['foreignKeys'])) {
                    foreach ($column['foreignKeys'] as $foreignKey) {
                        if (isset($foreignTableColumns[ $foreignKey['table'] ])) {
                            $tableAlias = 't' . $tableIndex;

                            $foreignColumns = array_map(
                                function (string $value) use ($tableAlias, $columnName) {
                                    return $tableAlias . '.' . trim($value) . ' AS ' . str_replace('_id', null, $columnName) . '_' . $value;
                                },
                                $foreignTableColumns[ $foreignKey['table'] ]
                            );

                            $columns = array_merge($columns, $foreignColumns);

                            $tables[] = 'LEFT JOIN ' . TABLE_PREFIX . $foreignKey['table'] . ' ' . $tableAlias . ' ON t1.' . $columnName . ' = ' . $tableAlias . '.' . $foreignKey['column'];

                            $tableIndex++;
                        }
                    }
                }
            }
        }

        $columns = implode(', ', $columns);
        $tables = implode("\n", $tables);

        return $this->db->query("
            SELECT
                " . $columns . "
            FROM
                " . $tables . "
            " . $conditions . "
        ");
    }

    public function count(?string $whereConditions = null): int
    {
        return $this->db->fetch_field(
            $this->db->simple_select(static::TABLE_NAME, 'COUNT(id) AS n', $whereConditions),
            'n'
        );
    }

    /**
     * Updates chosen row with escaped column values.
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->update($data, $this->getEscapedComparison('id', '=', $id));
    }

    /**
     * Updates chosen rows with escaped column values. The $whereString is not escaped.
     * @param array $data
     * @param string|null $whereString
     * @return int
     */
    public function update(array $data, ?string $whereString = null): int
    {
        $result = $this->db->update_query(
            static::TABLE_NAME,
            $this->getEscapedArray($data, true),
            $whereString,
            null,
            true
        );

        return $this->db->affected_rows();
    }

    public function deleteById(int $id): bool
    {
        $result = $this->db->delete_query(
            static::TABLE_NAME,
            'id = ' . (int)$id
        );

        return (bool)$result;
    }

    public function getEscapedComparison(string $columnName, string $operator, $value): string
    {
        return $columnName . ' ' . $operator . ' ' . $this->getEscapedColumnValue($columnName, $value);
    }

    protected function getEscapedArray(array $data, bool $includeQuotes = true): array
    {
        $escapedData = [];

        foreach ($data as $columnName => $value) {
            $escapedData[$columnName] = $this->getEscapedColumnValue($columnName, $value, $includeQuotes);
        }

        return $escapedData;
    }

    protected function getEscapedColumnValue(string $columnName, $value, bool $includeQuotes = true)
    {
        $column = static::COLUMNS[$columnName];

        if ($value === null && empty($column['notNull'])) {
            $escapedValue = 'NULL';
        } else {
            switch ($column['type']) {
                case 'bool':
                    if ($this->db->type == 'pgsql') {
                        $boolValue = (bool)$value;
                        $escapedValue = $boolValue ? 'TRUE' : 'FALSE';
                    } else {
                        $escapedValue = (int)(bool)$value;
                    }
                    break;
                case 'integer':
                    $escapedValue = (int)$value;
                    break;
                case 'numeric':
                case 'text':
                case 'varchar':
                    $escapedValue = $this->db->escape_string($value);

                    if ($includeQuotes) {
                        $escapedValue = '\'' . $escapedValue . '\'';
                    }
                    break;
                default:
                    $escapedValue = null;
            }
        }

        return $escapedValue;
    }

    protected function getEscapedColumnValues(string $columnName, array $values): array
    {
        $escapedValues = [];

        foreach ($values as $value) {
            $escapedValues[] = $this->getEscapedColumnValue($columnName, $value);
        }

        return $escapedValues;
    }
}
