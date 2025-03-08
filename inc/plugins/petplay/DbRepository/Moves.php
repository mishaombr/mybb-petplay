<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class Moves extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_moves';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'name' => ['type' => 'varchar', 'length' => 50, 'notNull' => true, 'uniqueKey' => 'name'],
        'type_id' => [
            'type' => 'integer', 
            'notNull' => true, 
            'foreignKeys' => [
                [
                    'table' => 'petplay_types', 
                    'column' => 'id',
                    'onDelete' => 'RESTRICT',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'category' => [
            'type' => 'varchar', 
            'length' => 10, 
            'notNull' => true,
            'check' => "category IN ('physical', 'special', 'status')"
        ],
        'description' => ['type' => 'text', 'default' => 'NULL'],
        'power_points' => ['type' => 'integer', 'notNull' => true, 'default' => 10],
        'power' => ['type' => 'integer', 'default' => 'NULL'],
        'accuracy' => ['type' => 'integer', 'default' => 'NULL'],
        'created_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
    
    public const TABLE_CONSTRAINTS = [
        'status_moves_power_null' => [
            'type' => 'check',
            'check' => "(category != 'status' OR power IS NULL)"
        ]
    ];
}
