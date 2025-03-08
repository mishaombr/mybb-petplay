<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class Abilities extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_abilities';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'name' => ['type' => 'varchar', 'length' => 50, 'notNull' => true, 'uniqueKey' => 'name'],
        'description' => ['type' => 'text', 'default' => 'NULL'],
        'created_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
}
