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
                    colour VARCHAR(7) NOT NULL DEFAULT '#A8A878',
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
                    sprite_path VARCHAR(255) DEFAULT NULL,
                    shiny_sprite_path VARCHAR(255) DEFAULT NULL,
                    mini_sprite_path VARCHAR(255) DEFAULT NULL,
                    base_stats JSONB NOT NULL DEFAULT '{
                        \"hp\": 50,
                        \"attack\": 50,
                        \"defence\": 50,
                        \"special_attack\": 50,
                        \"special_defence\": 50,
                        \"speed\": 50
                    }'::jsonb,
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
        'petplay_natures' => [
            'query' => "
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_natures (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(50) NOT NULL UNIQUE,
                    description TEXT NOT NULL DEFAULT '',
                    increased_stat VARCHAR(20) NOT NULL,
                    decreased_stat VARCHAR(20) NOT NULL,
                    is_default BOOLEAN NOT NULL DEFAULT false,
                    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'indexes' => []
        ],
        'petplay_pets' => [
            'query' => "
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_pets (
                    id SERIAL PRIMARY KEY,
                    nickname VARCHAR(100) DEFAULT NULL,
                    species_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_species(id) ON DELETE CASCADE,
                    gender VARCHAR(6) CHECK (gender IN ('male', 'female', '')) DEFAULT '',
                    is_shiny BOOLEAN NOT NULL DEFAULT false,
                    nature_id INTEGER REFERENCES " . TABLE_PREFIX . "petplay_natures(id) ON DELETE SET NULL,
                    ability VARCHAR(100) DEFAULT NULL,
                    is_fainted BOOLEAN NOT NULL DEFAULT false,
                    individual_values JSONB NOT NULL DEFAULT '{
                        \"hp\": 0,
                        \"attack\": 0,
                        \"defence\": 0,
                        \"special_attack\": 0,
                        \"special_defence\": 0,
                        \"speed\": 0
                    }'::jsonb,
                    original_owner_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "users(uid),
                    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'indexes' => [
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pets_species_id_idx 
                 ON " . TABLE_PREFIX . "petplay_pets(species_id)",
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pets_original_owner_id_idx 
                 ON " . TABLE_PREFIX . "petplay_pets(original_owner_id)",
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pets_nature_id_idx 
                 ON " . TABLE_PREFIX . "petplay_pets(nature_id)"
            ]
        ],
        'petplay_pet_ownership_history' => [
            'query' => "
                CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "petplay_pet_ownership_history (
                    id SERIAL PRIMARY KEY,
                    pet_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "petplay_pets(id) ON DELETE CASCADE,
                    user_id INTEGER NOT NULL REFERENCES " . TABLE_PREFIX . "users(uid) ON DELETE CASCADE,
                    acquired_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    released_at TIMESTAMPTZ DEFAULT NULL,
                    is_current_owner BOOLEAN NOT NULL DEFAULT true,
                    CONSTRAINT check_released_at CHECK (NOT is_current_owner OR released_at IS NULL)
                )
            ",
            'indexes' => [
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pet_ownership_history_pet_id_idx 
                 ON " . TABLE_PREFIX . "petplay_pet_ownership_history(pet_id)",
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pet_ownership_history_user_id_idx 
                 ON " . TABLE_PREFIX . "petplay_pet_ownership_history(user_id)",
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pet_ownership_history_current_idx 
                 ON " . TABLE_PREFIX . "petplay_pet_ownership_history(is_current_owner) 
                 WHERE is_current_owner = true",
                "CREATE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pet_ownership_history_user_current_idx 
                 ON " . TABLE_PREFIX . "petplay_pet_ownership_history(user_id, is_current_owner) 
                 WHERE is_current_owner = true",
                "CREATE UNIQUE INDEX IF NOT EXISTS " . TABLE_PREFIX . "petplay_pet_ownership_history_current_owner_idx 
                 ON " . TABLE_PREFIX . "petplay_pet_ownership_history(pet_id) 
                 WHERE is_current_owner = true"
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
        INSERT INTO " . TABLE_PREFIX . "petplay_types (name, description, colour, is_default)
        VALUES 
            ('Normal', 'Adaptable and balanced, Normal types are the most common and versatile pets. They thrive in almost any environment and make excellent companions for beginners.', '#A8A878', true),
            ('Fire', 'Passionate and energetic, Fire types radiate warmth and vitality. Their fierce loyalty and protective nature make them powerful allies, though they can be temperamental at times.', '#F08030', false),
            ('Water', 'Fluid and resilient, Water types possess a tranquil wisdom. Their adaptability allows them to overcome obstacles with grace, and they form deep emotional bonds with their companions.', '#6890F0', false),
            ('Electric', 'Energetic and quick-witted, Electric types crackle with vibrant energy. Their dynamic personalities and lightning-fast reflexes make them exciting, if sometimes unpredictable, companions.', '#F8D030', false),
            ('Grass', 'Peaceful and nurturing, Grass types have a deep connection to nature. They flourish in sunlight and often have calming, healing abilities that benefit their companions.', '#78C850', false),
            ('Ice', 'Elegant and composed, Ice types embody winter''s beauty. Their cool demeanor masks a surprising resilience, though they require special care to maintain their ideal environment.', '#98D8D8', false),
            ('Fighting', 'Disciplined and determined, Fighting types possess unwavering courage. Their strong sense of justice and rigorous training ethic make them respected partners in any endeavor.', '#C03028', false),
            ('Poison', 'Mysterious and cunning, Poison types are often misunderstood. Their complex nature and unique abilities make them fascinating companions for those who appreciate their subtle strengths.', '#A040A0', false),
            ('Ground', 'Steady and reliable, Ground types are deeply connected to the earth. Their stable nature and practical wisdom make them dependable allies who provide a strong foundation for their companions.', '#E0C068', false),
            ('Flying', 'Free-spirited and graceful, Flying types soar above ordinary concerns. Their adventurous nature and perspective from above make them inspiring companions who encourage their partners to reach new heights.', '#A890F0', false),
            ('Psychic', 'Intelligent and intuitive, Psychic types possess extraordinary mental capabilities. Their deep understanding of emotions and remarkable insight make them exceptional guides and mentors.', '#F85888', false),
            ('Bug', 'Industrious and adaptable, Bug types demonstrate remarkable transformation abilities. Their fascinating life cycles and cooperative nature make them surprisingly engaging companions.', '#A8B820', false),
            ('Rock', 'Sturdy and protective, Rock types are unshakeable in their loyalty. Their enduring nature and defensive instincts make them reliable guardians who stand firm in adversity.', '#B8A038', false),
            ('Ghost', 'Enigmatic and mysterious, Ghost types transcend ordinary understanding. Their unique perspective on life and death makes them intriguing companions who often help others process deep emotions.', '#705898', false),
            ('Dragon', 'Majestic and powerful, Dragon types command respect through their presence alone. Their noble nature and ancient wisdom make them exceptional partners for those worthy of their trust.', '#7038F8', false),
            ('Dark', 'Cunning and resourceful, Dark types thrive in challenging situations. Their strategic minds and survival instincts make them valuable allies who excel at overcoming obstacles.', '#705848', false),
            ('Steel', 'Resilient and steadfast, Steel types combine strength with precision. Their unwavering determination and protective nature make them reliable partners who never yield to pressure.', '#B8B8D0', false),
            ('Fairy', 'Enchanting and benevolent, Fairy types possess mysterious magical abilities. Their playful nature masks incredible power, making them delightful companions who bring joy and wonder to their partners.', '#EE99AC', false)
    ");
    
    // Insert default natures
    $db->write_query("
        INSERT INTO " . TABLE_PREFIX . "petplay_natures (name, description, increased_stat, decreased_stat, is_default)
        VALUES 
            ('Adamant', 'A nature that increases Attack and decreases Special Attack.', 'attack', 'special_attack', false),
            ('Bashful', 'A neutral nature that increases and decreases Special Attack.', 'special_attack', 'special_attack', false),
            ('Bold', 'A nature that increases Defense and decreases Attack.', 'defence', 'attack', false),
            ('Brave', 'A nature that increases Attack and decreases Speed.', 'attack', 'speed', false),
            ('Calm', 'A nature that increases Special Defense and decreases Attack.', 'special_defence', 'attack', false),
            ('Careful', 'A nature that increases Special Defense and decreases Special Attack.', 'special_defence', 'special_attack', false),
            ('Docile', 'A neutral nature that increases and decreases Defense.', 'defence', 'defence', false),
            ('Gentle', 'A nature that increases Special Defense and decreases Defense.', 'special_defence', 'defence', false),
            ('Hardy', 'A neutral nature that increases and decreases Attack.', 'attack', 'attack', true),
            ('Hasty', 'A nature that increases Speed and decreases Defense.', 'speed', 'defence', false),
            ('Impish', 'A nature that increases Defense and decreases Special Attack.', 'defence', 'special_attack', false),
            ('Jolly', 'A nature that increases Speed and decreases Special Attack.', 'speed', 'special_attack', false),
            ('Lax', 'A nature that increases Defense and decreases Special Defense.', 'defence', 'special_defence', false),
            ('Lonely', 'A nature that increases Attack and decreases Defense.', 'attack', 'defence', false),
            ('Mild', 'A nature that increases Special Attack and decreases Defense.', 'special_attack', 'defence', false),
            ('Modest', 'A nature that increases Special Attack and decreases Attack.', 'special_attack', 'attack', false),
            ('Naive', 'A nature that increases Speed and decreases Special Defense.', 'speed', 'special_defence', false),
            ('Naughty', 'A nature that increases Attack and decreases Special Defense.', 'attack', 'special_defence', false),
            ('Quiet', 'A nature that increases Special Attack and decreases Speed.', 'special_attack', 'speed', false),
            ('Quirky', 'A neutral nature that increases and decreases Special Defense.', 'special_defence', 'special_defence', false),
            ('Rash', 'A nature that increases Special Attack and decreases Special Defense.', 'special_attack', 'special_defence', false),
            ('Relaxed', 'A nature that increases Defense and decreases Speed.', 'defence', 'speed', false),
            ('Sassy', 'A nature that increases Special Defense and decreases Speed.', 'special_defence', 'speed', false),
            ('Serious', 'A neutral nature that increases and decreases Speed.', 'speed', 'speed', false),
            ('Timid', 'A nature that increases Speed and decreases Attack.', 'speed', 'attack', false)
    ");
    
    $upload_dir = MYBB_ROOT . 'uploads/petplay';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $species_dir = $upload_dir . '/species';
    if (!is_dir($species_dir)) {
        mkdir($species_dir, 0755, true);
    }
}

function petplay_uninstall()
{
    global $db, $PL;

    \petplay\loadPluginLibrary();

    $tables = [
        'petplay_pet_ownership_history',
        'petplay_pets',
        'petplay_species_types',
        'petplay_species',
        'petplay_types',
        'petplay_natures'
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
            'enable_trades' => [
                'title'       => 'Enable Pet Trades',
                'description' => 'Allow users to trade pets with each other.',
                'optionscode' => 'yesno',
                'value'       => '0',
            ],
            'trade_groups' => [
                'title'       => 'Allowed Trade Groups',
                'description' => 'Select groups that are allowed to trade pets.',
                'optionscode' => 'groupselect',
                'value'       => '2',
            ],
        ]
    );
}

function petplay_deactivate()
{
    global $PL;

    \petplay\loadPluginLibrary();

    $PL->templates_delete('petplay', true);
}
