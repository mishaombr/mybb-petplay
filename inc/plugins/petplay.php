<?php

// core files
require MYBB_ROOT . 'inc/plugins/petplay/common.php';

// init
define('petplay\DEVELOPMENT_MODE', 0);

// hooks
\petplay\addHooksNamespace('petplay\Hooks');

function petplay_info()
{
    global $lang;

    $lang->load('petplay');

    return [
        'name'          => 'PetPlay',
        'description'   => $lang->petplay_plugin_description,
        'author'        => 'Misha Cierpisław',
        'version'       => '1.0.0',
        'codename'      => 'petplay',
        'compatibility' => '18*',
    ];
}

function petplay_install()
{
    global $db;

    $db->write_query("
        CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_species (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL, 
            description TEXT NOT NULL,
            type VARCHAR(10)[] NOT NULL CHECK (array_length(type, 1) BETWEEN 1 AND 2 AND type <@ ARRAY['normal'::VARCHAR(10), 'grass'::VARCHAR(10), 'fire'::VARCHAR(10), 'water'::VARCHAR(10)]),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->write_query("
        CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_pets (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            species_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_species(id) ON DELETE CASCADE,
            is_shiny BOOLEAN NOT NULL DEFAULT false,
            original_owner_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "users(uid),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->write_query("
        CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pets_species_id_idx 
        ON " . TABLE_PREFIX . "petplay_pets(species_id)
    ");
    
    $db->write_query("
        CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_user_pets (
            user_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "users(uid) ON DELETE CASCADE,
            pet_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_pets(id) ON DELETE CASCADE,
            acquired_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, pet_id)
        )
    ");
    
    $db->write_query("
        CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_user_pets_user_id_idx 
        ON " . TABLE_PREFIX . "petplay_user_pets(user_id)
    ");
    
    $db->write_query("
        CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_user_pets_pet_id_idx 
        ON " . TABLE_PREFIX . "petplay_user_pets(pet_id)
    ");
}

function petplay_uninstall()
{
    global $db, $PL;

    \petplay\loadPluginLibrary();

    // database
    $db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . "petplay_user_pets");
    $db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . "petplay_pets"); 
    $db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . "petplay_species");

    // settings
    $PL->settings_delete('petplay', true);
}

function petplay_is_installed()
{
    global $db;

    $query = $db->simple_select('settinggroups', 'gid', "name='petplay'");

    return (bool)$db->num_rows($query);
}

function petplay_activate()
{
    global $PL;

    \petplay\loadPluginLibrary();

    // settings
    $PL->settings(
        'petplay',
        'PetPlay',
        'Settings for the PetPlay plugin.',
        [
            'pets_limit' => [
                'title'       => 'Pets Limit',
                'description' => 'Choose how many pets a user can own. Set to 0 to allow unlimited number of pets.',
                'optionscode' => 'numeric',
                'value'       => '6',
            ],
        ]
    );
}
