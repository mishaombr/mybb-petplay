<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class Types extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_types';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'name' => ['type' => 'varchar', 'length' => 50, 'notNull' => true, 'uniqueKey' => 'name'],
        'description' => ['type' => 'text', 'notNull' => true, 'default' => "''"],
        'colour' => ['type' => 'varchar', 'length' => 7, 'notNull' => true, 'default' => "'#A8A878'"],
        'sprite_path' => ['type' => 'varchar', 'length' => 255, 'default' => 'NULL'],
        'is_default' => ['type' => 'boolean', 'notNull' => true, 'default' => 'false'],
        'created_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
}
