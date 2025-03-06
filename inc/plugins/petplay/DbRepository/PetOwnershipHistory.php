<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class PetOwnershipHistory extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_pet_ownership_history';
    
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
        'from_user_id' => [
            'type' => 'integer', 
            'default' => 'NULL',
            'foreignKeys' => [
                [
                    'table' => 'users', 
                    'column' => 'uid',
                    'onDelete' => 'SET NULL',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'to_user_id' => [
            'type' => 'integer', 
            'notNull' => true,
            'foreignKeys' => [
                [
                    'table' => 'users', 
                    'column' => 'uid',
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'transfer_type' => [
            'type' => 'varchar', 
            'length' => 20, 
            'notNull' => true,
            'check' => "transfer_type IN ('initial', 'trade', 'gift', 'admin', 'other')"
        ],
        'notes' => ['type' => 'text', 'default' => 'NULL'],
        'transferred_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
    
    public const TABLE_CONSTRAINTS = [
        'valid_transfer' => [
            'type' => 'check',
            'check' => "(transfer_type != 'initial' OR from_user_id IS NULL)"
        ]
    ];
}
