<?php

namespace petplay;

// settings
function getSettingValue(string $name): string
{
    global $mybb;

    return $mybb->settings[__NAMESPACE__ . '_' . $name];
}

function getCsvSettingValues(string $name): array
{
    static $values;

    if (!isset($values[$name])) {
        $values[$name] = array_filter(explode(',', getSettingValue($name)));
    }

    return $values[$name];
}

function getDelimitedSettingValues(string $name): array
{
    static $values;

    if (!isset($values[$name])) {
        $values[$name] = array_filter(preg_split("/\\r\\n|\\r|\\n/", getSettingValue($name)));
    }

    return $values[$name];
}

function updateSettingValue(string $name, string $value): bool
{
    global $db;

    $result = (bool)$db->update_query('settings', [
        'value' => $db->escape_string($value),
    ], "name = 'mint_" . $db->escape_string($name) . "'");

    \rebuild_settings();

    return $result;
}

// datacache
function getCacheValue(string $key)
{
    global $cache;

    return $cache->read(__NAMESPACE__)[$key] ?? null;
}

function updateCache(array $values, bool $overwrite = false): void
{
    global $cache;

    if ($overwrite) {
        $cacheContent = $values;
    } else {
        $cacheContent = $cache->read(__NAMESPACE__);
        $cacheContent = array_merge($cacheContent, $values);
    }

    $cache->update(__NAMESPACE__, $cacheContent);
}

function addUniqueLogEvent(string $type, array $data): void
{
    $log = \mint\getCacheValue('unique_log_events') ?? [];

    $log[$type] = array_merge([
        'date' => \TIME_NOW,
    ], $data);

    \mint\updateCache([
        'unique_log_events' => $log,
    ]);
}

// languages
function loadExternalLanguageFile(string $languagesDirectory, string $section): void
{
    global $lang;

    if ($lang->language) {
        $language = $lang->language;
    } else {
        $language = $lang->fallback;
    }

    $lang->load('../../../' . $languagesDirectory . '/' . str_replace('/admin', null, $language) . '/' . $section, true);
}

// themes
function loadTemplates(array $templates, string $prefix = null): void
{
    global $templatelist;

    if (!empty($templatelist)) {
        $templatelist .= ',';
    }
    if ($prefix) {
        $templates = preg_filter('/^/', $prefix, $templates);
    }

    $templatelist .= implode(',', $templates);
}

function tpl(string $path): string
{
    global $templates;

    $components = explode('.', $path, 2);

    if (count($components) == 1) {
        $moduleName = false;
        $name = $components[0];
    } else {
        $moduleName = $components[0];
        $name = $components[1];
    }

    if ($moduleName) {
        $templateName = __NAMESPACE__ . '.' . $moduleName . '_' . $name;
        $directory = MYBB_ROOT . 'inc/plugins/' . __NAMESPACE__ . '/modules/' . $moduleName . '/templates/';
    } else {
        $templateName = __NAMESPACE__ . '_' . $name;
        $directory = MYBB_ROOT . 'inc/plugins/' . __NAMESPACE__ . '/templates/';
    }

    if (DEVELOPMENT_MODE) {
        $templateContent = str_replace(
            "\\'",
            "'",
            addslashes(
                file_get_contents($directory . $name . '.tpl')
            )
        );

        if (!isset($templates->cache[$templateName]) && !isset($templates->uncached_templates[$templateName])) {
            $templates->uncached_templates[$templateName] = $templateName;
        }

        return $templateContent;
    } else {
        return $templates->get($templateName);
    }
}

function replaceInTemplate(string $title, string $find, string $replace): bool
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    return \find_replace_templatesets($title, '#' . preg_quote($find, '#') . '#', $replace);
}

// filesystem
function getFilesContentInDirectory(string $path, string $fileNameSuffix): array
{
    $contents = [];

    if (is_dir($path)) {
        $directory = new \DirectoryIterator($path);

        foreach ($directory as $file) {
            if (!$file->isDot() && !$file->isDir()) {
                $templateName = $file->getPathname();
                $templateName = basename($templateName, $fileNameSuffix);
                $contents[$templateName] = file_get_contents($file->getPathname());
            }
        }
    }

    return $contents;
}

// database
function createTables(array $tables): void
{
    global $db;

    foreach ($tables as $tableName => $columnsOrClass) {
        if (class_exists($columnsOrClass) && is_subclass_of($columnsOrClass, DbEntityRepository::class)) {
            $tableConstraints = defined("$columnsOrClass::TABLE_CONSTRAINTS") ? $columnsOrClass::TABLE_CONSTRAINTS : [];
            $db->write_query(buildCreateTableQuery($columnsOrClass::TABLE_NAME, $columnsOrClass::COLUMNS, $tableConstraints));
        } else {
            $db->write_query(buildCreateTableQuery($tableName, $columnsOrClass));
        }
    }
}

function buildCreateTableQuery(string $tableName, array $columns, array $tableConstraints = []): string
{
    global $db;

    $columnDefinitions = [];
    $keyDefinitions = [];
    $keys = [
        'foreign' => [],
        'unique' => [],
        'check' => [],
    ];

    foreach ($columns as $columnName => $column) {
        // Use native PostgreSQL boolean type
        $columnType = $column['type'];

        $columnDefinition = $columnName;

        if (!empty($column['primaryKey'])) {
            if ($columnName == 'id') {
                // Use IDENTITY instead of serial (recommended in PostgreSQL 10+)
                $columnDefinition .= ' INTEGER GENERATED ALWAYS AS IDENTITY';
            } else {
                $columnDefinition .= ' ' . $columnType;
            }

            $keys['primary'][] = $columnName;
        } else {
            $columnDefinition .= ' ' . $columnType;

            if (!empty($column['length'])) {
                $columnDefinition .= '(' . $column['length'] . ')';
            }

            if (!empty($column['precision'])) {
                $columnDefinition .= '(';
                $columnDefinition .= $column['precision'];

                if (!empty($column['scale'])) {
                    $columnDefinition .= ', ' . $column['scale'];
                }

                $columnDefinition .= ')';
            }

            if (!empty($column['notNull'])) {
                $columnDefinition .= ' NOT NULL';
            }
        }

        if (isset($column['default'])) {
            // Handle boolean defaults properly for PostgreSQL
            if ($columnType === 'boolean' && is_bool($column['default'])) {
                $defaultValue = $column['default'] ? 'true' : 'false';
                $columnDefinition .= ' DEFAULT ' . $defaultValue;
            } else {
                $columnDefinition .= ' DEFAULT ' . $column['default'];
            }
        }

        // Add column-level check constraint if specified
        if (!empty($column['check'])) {
            $constraintName = $tableName . '_' . $columnName . '_check';
            $columnDefinition .= ' CONSTRAINT ' . $constraintName . ' CHECK (' . $column['check'] . ')';
        }

        if (!empty($column['uniqueKey'])) {
            $keys['unique'][$column['uniqueKey']][] = $columnName;
        }

        if (!empty($column['foreignKeys'])) {
            foreach ($column['foreignKeys'] as $foreignKey) {
                $keys['foreign'][$columnName] = $foreignKey;
            }
        }

        $columnDefinitions[$columnName] = $columnDefinition;
    }

    $tableNameEncoded = TABLE_PREFIX . $tableName;

    if (isset($keys['primary'])) {
        $keyDefinitions[] = 'PRIMARY KEY (' . implode(', ', $keys['primary']) . ')';
    }

    if (isset($keys['unique'])) {
        foreach ($keys['unique'] as $keyName => $columnNames) {
            // Use named constraints for better maintainability
            $constraintName = $tableName . '_' . implode('_', $columnNames) . '_unique';
            $keyDefinitions[] = 'CONSTRAINT ' . $constraintName . ' UNIQUE (' . implode(', ', $columnNames) . ')';
        }
    }

    // Add foreign key constraints with proper naming
    foreach ($keys['foreign'] as $referencingColumn => $foreignKey) {
        if (empty($foreignKey['noReference'])) {
            $constraintName = $tableName . '_' . $referencingColumn . '_fkey';
            $definition = 'CONSTRAINT ' . $constraintName . ' FOREIGN KEY (' . $referencingColumn . ') 
                REFERENCES ' . TABLE_PREFIX . $foreignKey['table'] . '(' . $foreignKey['column'] . ')';

            if (!empty($foreignKey['onDelete'])) {
                $definition .= ' ON DELETE ' . strtoupper($foreignKey['onDelete']);
            }

            $keyDefinitions[] = $definition;
        }
    }

    // Add table-level check constraints
    if (!empty($tableConstraints)) {
        foreach ($tableConstraints as $constraintName => $constraint) {
            if ($constraint['type'] === 'check') {
                $keyDefinitions[] = 'CONSTRAINT ' . $tableName . '_' . $constraintName . ' CHECK (' . $constraint['check'] . ')';
            }
        }
    }

    // Use IF NOT EXISTS for PostgreSQL 9.1+
    $query = '
        CREATE TABLE IF NOT EXISTS ' . $tableNameEncoded . ' (
            ' . implode(",\n            ", array_merge($columnDefinitions, $keyDefinitions)) . '
        )
    ';

    return $query;
}

function dropTables(array $tableNames, bool $onlyIfExists = false, bool $cascade = false): void
{
    global $db;

    foreach ($tableNames as $tableName) {
        if (class_exists($tableName) && is_subclass_of($tableName, DbEntityRepository::class)) {
            $tableName = $tableName::TABLE_NAME;
        }

        if (!$onlyIfExists || $db->table_exists($tableName)) {
            // Always use CASCADE for PostgreSQL to avoid dependency issues
            $db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . $tableName . " CASCADE");
        }
    }
}

function createColumns(array $tables, bool $dropIfExists = true): void
{
    global $db;

    foreach ($tables as $tableName => $tableColumns) {
        foreach ($tableColumns as $columnName => $columnDefinition) {
            if ($db->field_exists($columnName, $tableName)) {
                if ($dropIfExists === true) {
                    $db->drop_column($tableName, $columnName);
                } else {
                    $db->modify_column($tableName, $columnName, $columnDefinition);
                }
            }

            $db->add_column($tableName, $columnName, $columnDefinition);
        }
    }
}

function dropColumns(array $tables): void
{
    global $db;

    foreach ($tables as $tableName => $tableColumns) {
        foreach ($tableColumns as $columnName) {
            if ($db->field_exists($columnName, $tableName)) {
                $db->drop_column($tableName, $columnName);
            }
        }
    }
}

function queryResultAsArray($result, ?string $keyColumn = null, ?string $valueColumn = null): array
{
    global $db;

    $array = [];

    // conditions outside high iteration count loops
    if ($keyColumn !== null && $valueColumn !== null) {
        while ($row = $db->fetch_array($result)) {
            $array[$row[$keyColumn]] = $row[$valueColumn];
        }
    } elseif ($keyColumn !== null) {
        while ($row = $db->fetch_array($result)) {
            $array[$row[$keyColumn]] = $row;
        }
    } elseif ($valueColumn !== null) {
        while ($row = $db->fetch_array($result)) {
            $array[] = $row[$valueColumn];
        }
    } else {
        while ($row = $db->fetch_array($result)) {
            $array[] = $row;
        }
    }

    return $array;
}

// users
function userOnIgnoreList(int $subjectUserId, $targetUser): bool
{
    if (!is_array($targetUser)) {
        $targetUser = \get_user($targetUser);
    }

    return (
        !empty($targetUser['ignorelist']) &&
        strpos(',' . $targetUser['ignorelist'] . ',', ',' . $subjectUserId . ',') !== false
    );
}

function getUsersById(array $userIds, string $columnsCsv = '*'): array
{
    global $mybb, $db;

    $users = array_fill_keys($userIds, null);

    $idsToFetch = [];

    foreach ($userIds as $id) {
        if ($id == $mybb->user['uid'] && $id != 0) {
            $Users[$id] = $mybb->user;
        } else {
            $idsToFetch[] = $id;
        }
    }

    if ($idsToFetch) {
        $entries = \mint\queryResultAsArray(
            $db->simple_select('users', $columnsCsv, 'uid IN (' . \mint\getIntegerCsv($idsToFetch) . ')'),
            'uid'
        );

        foreach ($entries as $id => $entry) {
            $users[$id] = $entry;
        }
    }

    return $users;
}

function updateUser(int $userId, array $data): bool
{
    global $mybb, $db;

    $result = (bool)$db->update_query('users', $data, 'uid = ' . (int)$userId);

    if ($userId != 0 && $mybb->user['uid'] == $userId) {
        $mybb->user = array_merge($mybb->user, $data);
    }

    return $result;
}

// data
function getArraySubset(array $array, array $keys): array
{
    return array_intersect_key($array, array_flip($keys));
}

function getArraySplitByColumn(array $array, $columnName): array
{
    $result = [];

    foreach ($array as $value) {
        $result[ $value[$columnName] ][] = $value;
    }

    return $result;
}

function getIntegerCsv(array $values): string
{
    return implode(
        ',',
        array_map('intval', $values)
    );
}

// hooks
function addHooks(array $hooks, string $namespace = null): void
{
    global $plugins;

    if ($namespace) {
        $prefix = $namespace . '\\';
    } else {
        $prefix = null;
    }

    foreach ($hooks as $hook) {
        $plugins->add_hook($hook, $prefix . $hook);
    }
}

function addHooksNamespace(string $namespace): void
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;
        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

// reflection
function getClassMethodsNamesMatching($class, string $pattern): array
{
    $methodNames = [];

    $methods = get_class_methods($class);

    foreach ($methods as $method) {
        if (preg_match($pattern, $method, $matches)) {
            $methodNames[] = $matches[1];
        }
    }

    return $methodNames;
}

// requests
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function isStaticRender(): bool
{
    return !defined('THIS_SCRIPT') || !in_array(THIS_SCRIPT, [
        'xmlhttp.php',
        'newreply.php',
    ]);
}

// 3rd party
function loadMCommons(): void
{
    global $lang, $MC;

    $lang->load(__NAMESPACE__);

    if (!defined('MCOMMONS')) {
        define('MCOMMONS', MYBB_ROOT . 'inc/plugins/mcommons.php');
    }

    if (!file_exists(MCOMMONS)) {
        $errorKey = __NAMESPACE__ . '_admin_mcommons_missing';
        flash_message($lang->{$errorKey} ?? 'MCommons library is missing', 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    if (!isset($MC) || !$MC) {
        require_once MCOMMONS;
        
        // Verify the library was loaded correctly
        if (!isset($MC) || !($MC instanceof \MCommons)) {
            flash_message('MCommons library failed to initialize properly', 'error');
            admin_redirect('index.php?module=config-plugins');
        }
    }
}
