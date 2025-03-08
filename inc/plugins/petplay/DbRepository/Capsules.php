<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class Capsules extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_capsules';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'name' => ['type' => 'varchar', 'length' => 50, 'notNull' => true, 'uniqueKey' => 'name'],
        'description' => ['type' => 'text', 'notNull' => false],
        'catch_rate' => ['type' => 'numeric', 'precision' => 4, 'scale' => 2, 'notNull' => true, 'default' => '1.0'],
        'sprite' => ['type' => 'varchar', 'length' => 255, 'notNull' => false],
        'is_default' => ['type' => 'boolean', 'notNull' => true, 'default' => 'false'],
        'created_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
} 
