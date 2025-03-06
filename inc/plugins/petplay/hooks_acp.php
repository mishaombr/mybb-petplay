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

        $sub_tabs = [];

        $tabs = [
            'types',
            'natures',
            'abilities',
            'moves',
            'species',
            'capsules',
        ];

        $plugins->run_hooks('petplay_admin_config_petplay_tabs', $tabs);

        foreach ($tabs as $tabName) {
            $sub_tabs[$tabName] = [
                'link'        => $pageUrl . '&amp;action=' . $tabName,
                'title'       => $lang->{"petplay_admin_$tabName"},
                'description' => $lang->{"petplay_admin_{$tabName}_page_description"},
            ];
        }

        $plugins->run_hooks('petplay_admin_config_petplay_begin');

        if ($mybb->input['action'] == 'types' || empty($mybb->input['action'])) {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, $mybb->input['action'] ?: 'types');
            $page->output_success($lang->petplay_admin_types . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'natures') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'natures');
            $page->output_success($lang->petplay_admin_natures . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'abilities') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'abilities');
            $page->output_success($lang->petplay_admin_abilities . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'moves') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'moves');
            $page->output_success($lang->petplay_admin_moves . " management page is under construction");
            $page->output_footer();
        } elseif ($mybb->input['action'] == 'species') {
            $page->output_header($lang->petplay_admin);
            $page->output_nav_tabs($sub_tabs, 'species');
            $page->output_success($lang->petplay_admin_species . " management page is under construction");
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
