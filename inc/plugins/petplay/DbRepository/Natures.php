<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class Natures extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_natures';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'name' => ['type' => 'varchar', 'length' => 50, 'notNull' => true, 'uniqueKey' => 'name'],
        'description' => ['type' => 'text', 'notNull' => true, 'default' => "''"],
        'increased_stat' => ['type' => 'varchar', 'length' => 20, 'notNull' => true],
        'decreased_stat' => ['type' => 'varchar', 'length' => 20, 'notNull' => true],
        'is_default' => ['type' => 'boolean', 'notNull' => true, 'default' => 'false'],
        'created_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
}
