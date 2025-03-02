<?php

namespace petplay\Hooks;

require_once MYBB_ROOT . 'inc/plugins/petplay/ListManager.php';
require_once MYBB_ROOT . 'inc/plugins/petplay/admin/SpeciesManager.php';
require_once MYBB_ROOT . 'inc/plugins/petplay/admin/TypeManager.php';
require_once MYBB_ROOT . 'inc/plugins/petplay/admin/PetManager.php';
require_once MYBB_ROOT . 'inc/plugins/petplay/admin/NatureManager.php';

function admin_config_menu(&$sub_menu)
{
    global $lang;
    $lang->load('petplay');
    
    $sub_menu[] = [
        'id' => 'petplay',
        'title' => $lang->petplay_admin_menu_petplay_title,
        'link' => 'index.php?module=config-petplay'
    ];
}

function admin_config_action_handler(&$actions)
{
    $actions['petplay'] = [
        'active' => 'petplay',
        'file' => 'petplay'
    ];
}

function admin_load()
{
    global $mybb, $db, $lang, $run_module, $action_file, $page, $sub_tabs;

    $module = 'config';
    $actionFile = 'petplay';
    $pageUrl = 'index.php?module=' . $module . '-' . $actionFile;

    if ($run_module == $module && $action_file == $actionFile) {
        $lang->load('petplay');
        $lang->load('petplay', true);

        $page->add_breadcrumb_item($lang->petplay_admin_menu_petplay_title, $pageUrl);

        setupSubTabs($pageUrl, $lang, $sub_tabs);

        $action = $mybb->input['action'] ?? '';
        
        switch ($action) {
            case 'types':
            case 'add_type':
            case 'edit_type':
            case 'delete_type':
                \petplay\admin\TypeManager::handle($action, $pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
                
            case 'natures':
            case 'add_nature':
            case 'edit_nature':
            case 'delete_nature':
                \petplay\admin\NatureManager::handle($action, $pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
                
            case 'pets':
            case 'add_pet':
            case 'edit_pet':
            case 'delete_pet':
            case 'pet_history':
            case 'transfer_pet':
                \petplay\admin\PetManager::handle($action, $pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
                
            default:
                \petplay\admin\SpeciesManager::handle($action, $pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
        }

        $page->output_footer();
    }
}

function setupSubTabs($pageUrl, $lang, &$sub_tabs)
{
    $sub_tabs['species'] = [
        'link'        => $pageUrl . '&action=species',
        'title'       => $lang->petplay_admin_species_list,
        'description' => $lang->petplay_admin_species_list_description
    ];
    
    $sub_tabs['types'] = [
        'link'        => $pageUrl . '&action=types',
        'title'       => $lang->petplay_admin_types_list,
        'description' => $lang->petplay_admin_types_list_description
    ];
    
    $sub_tabs['natures'] = [
        'link'        => $pageUrl . '&action=natures',
        'title'       => $lang->petplay_admin_natures_list,
        'description' => $lang->petplay_admin_natures_list_description
    ];
    
    $sub_tabs['pets'] = [
        'link'        => $pageUrl . '&action=pets',
        'title'       => $lang->petplay_admin_pets_list,
        'description' => $lang->petplay_admin_pets_list_description,
    ];
}
