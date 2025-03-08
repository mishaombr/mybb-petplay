<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class Pets extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_pets';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'uuid' => [
            'type' => 'uuid', 
            'notNull' => true, 
            'uniqueKey' => 'uuid',
            'default' => 'gen_random_uuid()'
        ],
        'species_id' => [
            'type' => 'integer', 
            'notNull' => true, 
            'foreignKeys' => [
                [
                    'table' => 'petplay_species', 
                    'column' => 'id',
                    'onDelete' => 'RESTRICT',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'nickname' => ['type' => 'varchar', 'length' => 50, 'default' => 'NULL'],
        'gender' => [
            'type' => 'varchar', 
            'length' => 10, 
            'notNull' => true,
            'check' => "gender IN ('male', 'female', 'unknown')",
            'default' => "'unknown'"
        ],
        'capsule_id' => [
            'type' => 'integer', 
            'notNull' => true, 
            'foreignKeys' => [
                [
                    'table' => 'petplay_capsules', 
                    'column' => 'id',
                    'onDelete' => 'RESTRICT',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'nature_id' => [
            'type' => 'integer', 
            'notNull' => true, 
            'foreignKeys' => [
                [
                    'table' => 'petplay_natures', 
                    'column' => 'id',
                    'onDelete' => 'RESTRICT',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'ability_id' => [
            'type' => 'integer', 
            'default' => 'NULL', 
            'foreignKeys' => [
                [
                    'table' => 'petplay_abilities', 
                    'column' => 'id',
                    'onDelete' => 'RESTRICT',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'is_shiny' => ['type' => 'boolean', 'notNull' => true, 'default' => 'false'],
        'individual_values' => [
            'type' => 'jsonb', 
            'notNull' => true, 
            'default' => "'{\"hp\": 0, \"attack\": 0, \"defence\": 0, \"special_attack\": 0, \"special_defence\": 0, \"speed\": 0}'::jsonb"
        ],
        'effort_values' => [
            'type' => 'jsonb', 
            'notNull' => true, 
            'default' => "'{\"hp\": 0, \"attack\": 0, \"defence\": 0, \"special_attack\": 0, \"special_defence\": 0, \"speed\": 0}'::jsonb"
        ],
        'created_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
    
    public const TABLE_CONSTRAINTS = [
        'iv_range' => [
            'type' => 'check',
            'check' => "(
                jsonb_typeof(individual_values->'hp') = 'number' AND
                jsonb_typeof(individual_values->'attack') = 'number' AND
                jsonb_typeof(individual_values->'defence') = 'number' AND
                jsonb_typeof(individual_values->'special_attack') = 'number' AND
                jsonb_typeof(individual_values->'special_defence') = 'number' AND
                jsonb_typeof(individual_values->'speed') = 'number' AND
                (individual_values->>'hp')::integer BETWEEN 0 AND 31 AND
                (individual_values->>'attack')::integer BETWEEN 0 AND 31 AND
                (individual_values->>'defence')::integer BETWEEN 0 AND 31 AND
                (individual_values->>'special_attack')::integer BETWEEN 0 AND 31 AND
                (individual_values->>'special_defence')::integer BETWEEN 0 AND 31 AND
                (individual_values->>'speed')::integer BETWEEN 0 AND 31
            )"
        ],
        'ev_range' => [
            'type' => 'check',
            'check' => "(
                jsonb_typeof(effort_values->'hp') = 'number' AND
                jsonb_typeof(effort_values->'attack') = 'number' AND
                jsonb_typeof(effort_values->'defence') = 'number' AND
                jsonb_typeof(effort_values->'special_attack') = 'number' AND
                jsonb_typeof(effort_values->'special_defence') = 'number' AND
                jsonb_typeof(effort_values->'speed') = 'number' AND
                (effort_values->>'hp')::integer BETWEEN 0 AND 252 AND
                (effort_values->>'attack')::integer BETWEEN 0 AND 252 AND
                (effort_values->>'defence')::integer BETWEEN 0 AND 252 AND
                (effort_values->>'special_attack')::integer BETWEEN 0 AND 252 AND
                (effort_values->>'special_defence')::integer BETWEEN 0 AND 252 AND
                (effort_values->>'speed')::integer BETWEEN 0 AND 252
            )"
        ],
        'ev_total' => [
            'type' => 'check',
            'check' => "(
                (effort_values->>'hp')::integer + 
                (effort_values->>'attack')::integer + 
                (effort_values->>'defence')::integer + 
                (effort_values->>'special_attack')::integer + 
                (effort_values->>'special_defence')::integer + 
                (effort_values->>'speed')::integer <= 510
            )"
        ]
    ];
} 
