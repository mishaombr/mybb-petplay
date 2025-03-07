<?php

declare(strict_types=1);

namespace petplay\Hooks;

use petplay\AcpEntityManagementController;
use petplay\DbRepository\Types;
use petplay\DbRepository\Natures;
use petplay\DbRepository\Abilities;
use petplay\DbRepository\Moves;
use petplay\DbRepository\Species;
use petplay\DbRepository\Capsules;
use petplay\DbRepository\Pets;
use petplay\DbRepository\PetMoves;
use petplay\DbRepository\PetOwners;
use petplay\DbRepository\PetOwnershipHistory;

function admin_load(): void
{
    global $mybb, $db, $lang, $plugins, $run_module, $action_file, $page, $sub_tabs, $pageUrl;

    if ($run_module == 'config' && $action_file == 'petplay') {
        $pageUrl = 'index.php?module=' . $run_module . '-' . $action_file;

        $lang->load('petplay');

        $page->add_breadcrumb_item($lang->petplay_admin, $pageUrl);
        
        // Add FontAwesome to the header
        $page->extra_header .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />';

        $sub_tabs = [];

        $tabs = [
            'pets' => ['icon' => 'fa-solid fa-paw'],
            'species' => ['icon' => 'fa-solid fa-dna'],
            'types' => ['icon' => 'fa-solid fa-tags'],
            'moves' => ['icon' => 'fa-solid fa-arrows-up-down-left-right'],
            'abilities' => ['icon' => 'fa-solid fa-bolt'],
            'natures' => ['icon' => 'fa-solid fa-seedling'],
            'capsules' => ['icon' => 'fa-solid fa-capsules'],
        ];

        $plugins->run_hooks('petplay_admin_config_petplay_tabs', $tabs);

        foreach ($tabs as $tabName => $tabInfo) {
            $sub_tabs[$tabName] = [
                'link'        => $pageUrl . '&amp;action=' . $tabName,
                'title'       => '<i class="' . $tabInfo['icon'] . '"></i> ' . $lang->{"petplay_admin_$tabName"},
                'description' => $lang->{"petplay_admin_{$tabName}_page_description"},
            ];
        }

        $plugins->run_hooks('petplay_admin_config_petplay_begin');

        if ($mybb->input['action'] == 'pets' || empty($mybb->input['action'])) {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, $mybb->input['action'] ?: 'pets');
            $page->output_success($lang->petplay_admin_pets . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'species') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'species');
            $page->output_success($lang->petplay_admin_species . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'types') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'types');
            $page->output_success($lang->petplay_admin_types . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'moves') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'moves');
            $page->output_success($lang->petplay_admin_moves . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'abilities') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'abilities');
            $page->output_success($lang->petplay_admin_abilities . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'natures') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'natures');
            $page->output_success($lang->petplay_admin_natures . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'capsules') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'capsules');
            $page->output_success($lang->petplay_admin_capsules . " management page is under construction");
            $page->output_footer();
        }
    }
}

function admin_config_action_handler(array &$actions): void
{
    $actions['petplay'] = [
        'active' => 'petplay',
        'file' => 'petplay',
    ];
}

function admin_config_menu(array &$sub_menu): void
{
    global $lang;

    $lang->load('petplay');

    $sub_menu[] = [
        'id' => 'petplay',
        'link' => 'index.php?module=config-petplay',
        'title' => $lang->petplay_admin,
    ];
}
