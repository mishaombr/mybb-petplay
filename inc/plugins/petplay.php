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

    // Create types table with description field
    $db->write_query("
        CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_types (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT NOT NULL DEFAULT '',
            is_default BOOLEAN NOT NULL DEFAULT false,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert default types with descriptions
    $db->write_query("
        INSERT INTO " . TABLE_PREFIX . "petplay_types (name, description, is_default)
        VALUES ('Normal', 'Adaptable and balanced, Normal types are the most common and versatile pets. They thrive in almost any environment and make excellent companions for beginners.', true),
               ('Grass', 'Peaceful and nurturing, Grass types have a deep connection to nature. They flourish in sunlight and often have calming, healing abilities that benefit their companions.', false),
               ('Fire', 'Passionate and energetic, Fire types radiate warmth and vitality. Their fierce loyalty and protective nature make them powerful allies, though they can be temperamental at times.', false),
               ('Water', 'Fluid and resilient, Water types possess a tranquil wisdom. Their adaptability allows them to overcome obstacles with grace, and they form deep emotional bonds with their companions.', false)
    ");

    // Create species table (modified to remove type array)
    $db->write_query("
        CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_species (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL, 
            description TEXT NOT NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create species_types junction table
    $db->write_query("
        CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_species_types (
            species_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_species(id) ON DELETE CASCADE,
            type_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_types(id) ON DELETE CASCADE,
            PRIMARY KEY (species_id, type_id)
        )
    ");
    
    // Create indexes for the junction table
    $db->write_query("
        CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_species_types_species_id_idx 
        ON " . TABLE_PREFIX . "petplay_species_types(species_id)
    ");
    
    $db->write_query("
        CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_species_types_type_id_idx 
        ON " . TABLE_PREFIX . "petplay_species_types(type_id)
    ");

    // Rest of the existing tables (pets and user_pets)
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
    $db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . "petplay_species_types");
    $db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . "petplay_species");
    $db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . "petplay_types");

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

    // Register hooks using the namespace system
    \petplay\addHooksNamespace('petplay\\Hooks');

    // Your existing settings code...
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

    // templates
    //$PL->templates(
    //    'petplay',
    //    'PetPlay',
    //    \petplay\getFilesContentInDirectory(MYBB_ROOT . 'inc/plugins/petplay/templates', '.tpl')
    //);
}

function petplay_deactivate()
{
    global $PL;

    \petplay\loadPluginLibrary();

    // templates
    $PL->templates_delete('petplay', true);
}
