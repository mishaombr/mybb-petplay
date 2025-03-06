<?php

declare(strict_types=1);

namespace petplay\DbRepository;

use petplay\DbEntityRepository;

class PetOwners extends DbEntityRepository
{
    public const TABLE_NAME = 'petplay_pet_owners';
    
    public const COLUMNS = [
        'id' => ['type' => 'serial', 'primaryKey' => true],
        'pet_id' => [
            'type' => 'integer', 
            'notNull' => true, 
            'uniqueKey' => 'pet_id',
            'foreignKeys' => [
                [
                    'table' => 'petplay_pets', 
                    'column' => 'id',
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'CASCADE'
                ]
            ]
        ],
        'user_id' => [
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
        'acquired_at' => ['type' => 'timestamptz', 'notNull' => true, 'default' => 'CURRENT_TIMESTAMP']
    ];
}
