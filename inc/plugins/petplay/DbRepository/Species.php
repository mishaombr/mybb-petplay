<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class Species extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_species';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'name' => ['type' => 'varchar', 'length' => 50, 'notNull' => true, 'uniqueKey' => 'name'],
        'type_primary_id' => [
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
        'type_secondary_id' => [
            'type' => 'integer', 
            'default' => 'NULL', 
            'foreignKeys' => [
                [
                    'table' => 'petplay_types', 
                    'column' => 'id',
                    'onDelete' => 'RESTRICT',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'description' => ['type' => 'text', 'notNull' => true, 'default' => "''"],
        'base_stats' => [
            'type' => 'jsonb', 
            'notNull' => true, 
            'default' => "'{\"hp\": 50, \"attack\": 50, \"defence\": 50, \"special_attack\": 50, \"special_defence\": 50, \"speed\": 50}'::jsonb"
        ],
        'sprite' => ['type' => 'varchar', 'length' => 255, 'notNull' => true],
        'sprite_shiny' => ['type' => 'varchar', 'length' => 255, 'default' => 'NULL'],
        'sprite_mini' => ['type' => 'varchar', 'length' => 255, 'default' => 'NULL'],
        'created_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
    
    public const TABLE_CONSTRAINTS = [
        'different_types' => [
            'type' => 'check',
            'check' => "(type_secondary_id IS NULL OR type_primary_id != type_secondary_id)"
        ]
    ];
}
