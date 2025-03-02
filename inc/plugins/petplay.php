<?php

// core files
require MYBB_ROOT . 'inc/plugins/petplay/common.php';

// hook files
require MYBB_ROOT . 'inc/plugins/petplay/hooks_acp.php';

// autoloading
spl_autoload_register(function ($path) {
    $prefix = 'petplay\\';
    $baseDir = MYBB_ROOT . 'inc/plugins/petplay/';

    if (strpos($path, $prefix) !== 0) {
        return;
    }
    
    $relativeClass = substr($path, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
        return true;
    }
    
    return false;
});

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
        'website'       => 'https://github.com/mishaombr/mybb-petplay',
        'author'        => 'Misha Cierpisław',
        'version'       => '1.0.0',
        'codename'      => 'petplay',
        'compatibility' => '18*',
    ];
}

function petplay_install()
{
    global $db;

    $tables = [
        'petplay_types' => [
            'query' => "
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_types (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(50) NOT NULL UNIQUE,
                    description TEXT NOT NULL DEFAULT '',
                    is_default BOOLEAN NOT NULL DEFAULT false,
                    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'indexes' => []
        ],
        'petplay_species' => [
            'query' => "
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_species (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(100) NOT NULL, 
                    description TEXT NOT NULL,
                    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'indexes' => []
        ],
        'petplay_species_types' => [
            'query' => "
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_species_types (
                    species_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_species(id) ON DELETE CASCADE,
                    type_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_types(id) ON DELETE CASCADE,
                    PRIMARY KEY (species_id, type_id)
                )
            ",
            'indexes' => [
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_species_types_species_id_idx 
                 ON " . TABLE_PREFIX . "petplay_species_types(species_id)",
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_species_types_type_id_idx 
                 ON " . TABLE_PREFIX . "petplay_species_types(type_id)"
            ]
        ],
        'petplay_pets' => [
            'query' => "
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_pets (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    species_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_species(id) ON DELETE CASCADE,
                    is_shiny BOOLEAN NOT NULL DEFAULT false,
                    original_owner_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "users(uid),
                    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'indexes' => [
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pets_species_id_idx 
                 ON " . TABLE_PREFIX . "petplay_pets(species_id)"
            ]
        ],
        'petplay_user_pets' => [
            'query' => "
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_user_pets (
                    user_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "users(uid) ON DELETE CASCADE,
                    pet_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_pets(id) ON DELETE CASCADE,
                    acquired_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (user_id, pet_id)
                )
            ",
            'indexes' => [
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_user_pets_user_id_idx 
                 ON " . TABLE_PREFIX . "petplay_user_pets(user_id)",
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_user_pets_pet_id_idx 
                 ON " . TABLE_PREFIX . "petplay_user_pets(pet_id)"
            ]
        ]
    ];

    // Create tables and indexes
    foreach ($tables as $table => $structure) {
        $db->write_query($structure['query']);
        
        foreach ($structure['indexes'] as $index) {
            $db->write_query($index);
        }
    }

    $db->write_query("
        INSERT INTO " . TABLE_PREFIX . "petplay_types (name, description, is_default)
        VALUES ('Normal', 'Adaptable and balanced, Normal types are the most common and versatile pets. They thrive in almost any environment and make excellent companions for beginners.', true),
               ('Grass', 'Peaceful and nurturing, Grass types have a deep connection to nature. They flourish in sunlight and often have calming, healing abilities that benefit their companions.', false),
               ('Fire', 'Passionate and energetic, Fire types radiate warmth and vitality. Their fierce loyalty and protective nature make them powerful allies, though they can be temperamental at times.', false),
               ('Water', 'Fluid and resilient, Water types possess a tranquil wisdom. Their adaptability allows them to overcome obstacles with grace, and they form deep emotional bonds with their companions.', false)
    ");
}

function petplay_uninstall()
{
    global $db, $PL;

    \petplay\loadPluginLibrary();

    $tables = [
        'petplay_user_pets',
        'petplay_pets',
        'petplay_species_types',
        'petplay_species',
        'petplay_types'
    ];

    foreach ($tables as $table) {
        $db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . $table);
    }

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

    // Settings
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

    $templatesDir = MYBB_ROOT . 'inc/plugins/petplay/templates';
    if (is_dir($templatesDir)) {
        $PL->templates(
            'petplay',
            'PetPlay',
            \petplay\getFilesContentInDirectory($templatesDir, '.tpl')
        );
    }
}

function petplay_deactivate()
{
    global $PL;

    \petplay\loadPluginLibrary();

    $PL->templates_delete('petplay', true);
}
