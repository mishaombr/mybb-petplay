<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class PetMoves extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_pet_moves';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'pet_id' => [
            'type' => 'integer', 
            'notNull' => true,
            'foreignKeys' => [
                [
                    'table' => 'petplay_pets', 
                    'column' => 'id',
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'move_id' => [
            'type' => 'integer', 
            'notNull' => true,
            'foreignKeys' => [
                [
                    'table' => 'petplay_moves', 
                    'column' => 'id',
                    'onDelete' => 'RESTRICT',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'slot' => [
            'type' => 'integer', 
            'notNull' => true,
            'check' => "slot BETWEEN 1 AND 4"
        ],
        'created_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
    
    public const TABLE_CONSTRAINTS = [
        'unique_pet_slot' => [
            'type' => 'unique',
            'columns' => ['pet_id', 'slot']
        ],
        'unique_pet_move' => [
            'type' => 'unique',
            'columns' => ['pet_id', 'move_id']
        ]
    ];
} 
