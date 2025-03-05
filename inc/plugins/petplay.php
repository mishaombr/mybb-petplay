<?php

// core files
require MYBB_ROOT . 'inc/plugins/petplay/common.php';

// hook files
require MYBB_ROOT . 'inc/plugins/petplay/hooks_acp.php';

// autoloading
spl_autoload_register(function (string $path): ?bool {
    $prefix = 'petplay\\';
    $baseDir = MYBB_ROOT . 'inc/plugins/petplay/';

    if (!str_starts_with($path, $prefix)) {
        return null;
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

function petplay_info(): array
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

function petplay_install(): void
{
    global $db;

    \petplay\loadMCommons();

    \petplay\createTables([
        \petplay\DbRepository\Types::class,
        //\petplay\DbRepository\Natures::class,
        //\petplay\DbRepository\Abilities::class,
        //\petplay\DbRepository\Capsules::class,
        //\petplay\DbRepository\Species::class,
        //\petplay\DbRepository\SpeciesTypes::class,
        //\petplay\DbRepository\Moves::class,
        //\petplay\DbRepository\Pets::class,
        //\petplay\DbRepository\PetMoves::class,
        //\petplay\DbRepository\PetOwnershipHistory::class,
    ]);
}

function petplay_uninstall(): void
{
    global $db, $MC;

    \petplay\loadMCommons();

    \petplay\dropTables([
        //\petplay\DbRepository\PetOwnershipHistory::class,
        //\petplay\DbRepository\PetMoves::class,
        //\petplay\DbRepository\Pets::class,
        //\petplay\DbRepository\Moves::class,
        //\petplay\DbRepository\SpeciesTypes::class,
        //\petplay\DbRepository\Species::class,
        //\petplay\DbRepository\Capsules::class,
        //\petplay\DbRepository\Natures::class,
        //\petplay\DbRepository\Abilities::class,
        \petplay\DbRepository\Types::class,
    ], true, true);

    $MC->settings_delete('petplay', true);
}

function petplay_is_installed(): bool
{
    global $db;

    $query = $db->simple_select('settinggroups', 'gid', "name = 'petplay'");
    
    return $db->num_rows($query) > 0;
}

function petplay_activate(): void
{
    global $MC;

    \petplay\loadMCommons();

    $MC->settings(
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

function petplay_deactivate(): void
{
    global $MC;

    \petplay\loadMCommons();

    $MC->templates_delete('petplay', true);
    $MC->stylesheet_delete('petplay', true);
}
