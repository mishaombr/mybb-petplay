<?php

namespace petplay;

function addHooks(array $hooks, string $namespace = null)
{
    global $plugins;

    if ($namespace) {
        $prefix = $namespace . '\\';
    } else {
        $prefix = null;
    }

    foreach ($hooks as $hook) {
        $plugins->add_hook($hook, $prefix . $hook);
    }
}

function addHooksNamespace(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;
        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

function loadTemplates(array $templates, string $prefix = null): void
{
    global $templatelist;

    if (!empty($templatelist)) {
        $templatelist .= ',';
    }
    if ($prefix) {
        $templates = preg_filter('/^/', $prefix, $templates);
    }

    $templatelist .= implode(',', $templates);
}

function tpl(string $name): string
{
    global $templates;

    $templateName = 'petplay_' . $name;
    $directory = MYBB_ROOT . 'inc/plugins/petplay/templates/';

    if (DEVELOPMENT_MODE) {
        $templateContent = str_replace(
            "\\'",
            "'",
            addslashes(
                file_get_contents($directory . $name . '.tpl')
            )
        );

        if (!isset($templates->cache[$templateName]) && !isset($templates->uncached_templates[$templateName])) {
            $templates->uncached_templates[$templateName] = $templateName;
        }

        return $templateContent;
    } else {
        return $templates->get($templateName);
    }
}

function replaceInTemplate(string $title, string $find, string $replace): bool
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    return \find_replace_templatesets($title, '#' . preg_quote($find, '#') . '#', $replace);
}

function getFilesContentInDirectory(string $path, string $fileNameSuffix)
{
    $contents = [];

    if (!is_dir($path)) {
        return $contents;
    }

    $directory = new \DirectoryIterator($path);

    foreach ($directory as $file) {
        if (!$file->isDot() && !$file->isDir()) {
            $filePath = $file->getPathname();
            if (substr($filePath, -strlen($fileNameSuffix)) === $fileNameSuffix) {
                $templateName = basename($filePath, $fileNameSuffix);
                $contents[$templateName] = file_get_contents($filePath);
            }
        }
    }

    return $contents;
}

function loadPluginLibrary(): void
{
    global $lang, $PL;

    $lang->load('petplay');

    if (!defined('PLUGINLIBRARY')) {
        define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
    }

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->petplay_admin_pluginlibrary_missing, 'error');

        admin_redirect('index.php?module=config-plugins');
    } elseif (!$PL) {
        require_once PLUGINLIBRARY;
    }
}

/**
 * Get the appropriate sprite for a pet
 * 
 * @param array $pet The pet data
 * @param string $size 'normal' or 'mini'
 * @return string URL to the sprite
 */
function getPetSprite($pet, $size = 'normal')
{
    global $config, $db;
    
    // Get species data if not already included
    if (!isset($pet['sprite_path'])) {
        $species = $db->fetch_array($db->simple_select('petplay_species', '*', 'id = ' . (int)$pet['species_id']));
    } else {
        $species = $pet;
    }
    
    // Determine which sprite to use
    if ($size == 'mini') {
        if (!empty($species['mini_sprite_path'])) {
            return $config['mybb_url'] . '/' . $species['mini_sprite_path'];
        }
        // Fall back to normal sprite if mini doesn't exist
    }
    
    // Use shiny sprite if pet is shiny
    if (isset($pet['is_shiny']) && $pet['is_shiny'] && !empty($species['shiny_sprite_path'])) {
        return $config['mybb_url'] . '/' . $species['shiny_sprite_path'];
    }
    
    // Use normal sprite
    if (!empty($species['sprite_path'])) {
        return $config['mybb_url'] . '/' . $species['sprite_path'];
    }
    
    // Default placeholder
    return $config['mybb_url'] . '/images/default_pet.png';
}
