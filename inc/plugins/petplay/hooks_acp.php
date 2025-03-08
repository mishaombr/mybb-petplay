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
use petplay\AcpPetsController;

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

        if ($mybb->input['action'] == 'types') {
            $controller = new \petplay\AcpEntityManagementController(
                'types',
                \petplay\DbRepository\Types::class,
                [
                    'name' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_types_name,
                        'description' => $lang->petplay_admin_types_name_desc,
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'label' => $lang->petplay_admin_types_description,
                        'description' => $lang->petplay_admin_types_description_desc,
                        'required' => false
                    ],
                    'colour' => [
                        'type' => 'color',
                        'label' => $lang->petplay_admin_types_colour,
                        'description' => $lang->petplay_admin_types_colour_desc,
                        'required' => true,
                        'default' => '#A8A878'
                    ],
                    'sprite_path' => [
                        'type' => 'file',
                        'label' => $lang->petplay_admin_types_sprite_path,
                        'description' => $lang->petplay_admin_types_sprite_path_desc,
                        'upload_dir' => 'uploads/petplay/type/'
                    ],
                    'is_default' => [
                        'type' => 'checkbox',
                        'label' => $lang->petplay_admin_types_is_default,
                        'description' => $lang->petplay_admin_types_is_default_desc,
                        'default' => false
                    ]
                ],
                [
                    'id' => ['width' => '5%', 'label' => $lang->petplay_admin_id],
                    'name' => ['width' => '15%', 'label' => $lang->petplay_admin_types_name],
                    'description' => ['width' => '35%', 'label' => $lang->petplay_admin_types_description],
                    'sprite_path' => ['width' => '15%', 'label' => $lang->petplay_admin_types_sprite],
                    'colour' => ['width' => '10%', 'label' => $lang->petplay_admin_types_colour],
                    'is_default' => ['width' => '10%', 'label' => $lang->petplay_admin_types_is_default],
                    'actions' => ['width' => '10%', 'label' => $lang->petplay_admin_actions]
                ],
                $pageUrl . '&amp;action=types'
            );
            
            $controller->handleRequest($mybb->input);
        } elseif ($mybb->input['action'] == 'species') {
            $controller = new \petplay\AcpEntityManagementController(
                'species',
                \petplay\DbRepository\Species::class,
                [
                    'name' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_species_name,
                        'description' => $lang->petplay_admin_species_name_desc,
                        'required' => true
                    ],
                    'type_primary_id' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_species_type_primary,
                        'description' => $lang->petplay_admin_species_type_primary_desc,
                        'required' => true,
                        'default' => function() use ($db) {
                            $query = $db->simple_select('petplay_types', 'id', 'is_default = TRUE');
                            $defaultType = $db->fetch_array($query);
                            return $defaultType ? $defaultType['id'] : null;
                        },
                        'options' => function() use ($db) {
                            $types = [];
                            $query = $db->simple_select('petplay_types', 'id, name', '', ['order_by' => 'name']);
                            while ($type = $db->fetch_array($query)) {
                                $types[$type['id']] = $type['name'];
                            }
                            return $types;
                        }
                    ],
                    'type_secondary_id' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_species_type_secondary,
                        'description' => $lang->petplay_admin_species_type_secondary_desc,
                        'required' => false,
                        'options' => function() use ($db) {
                            $types = ['' => '-- None --'];
                            $query = $db->simple_select('petplay_types', 'id, name', '', ['order_by' => 'name']);
                            while ($type = $db->fetch_array($query)) {
                                $types[$type['id']] = $type['name'];
                            }
                            return $types;
                        }
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'label' => $lang->petplay_admin_species_description,
                        'description' => $lang->petplay_admin_species_description_desc,
                        'required' => false
                    ],
                    'base_stats' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_species_base_stats,
                        'description' => $lang->petplay_admin_species_base_stats_desc,
                        'required' => true,
                        'default' => '{"hp": 50, "attack": 50, "defence": 50, "special_attack": 50, "special_defence": 50, "speed": 50}'
                    ],
                    'sprite' => [
                        'type' => 'file',
                        'label' => $lang->petplay_admin_species_sprite,
                        'description' => $lang->petplay_admin_species_sprite_desc,
                        'upload_dir' => 'uploads/petplay/species/'
                    ],
                    'sprite_shiny' => [
                        'type' => 'file',
                        'label' => $lang->petplay_admin_species_sprite_shiny,
                        'description' => $lang->petplay_admin_species_sprite_shiny_desc,
                        'upload_dir' => 'uploads/petplay/species/'
                    ],
                    'sprite_mini' => [
                        'type' => 'file',
                        'label' => $lang->petplay_admin_species_sprite_mini,
                        'description' => $lang->petplay_admin_species_sprite_mini_desc,
                        'upload_dir' => 'uploads/petplay/species/'
                    ]
                ],
                [
                    'id' => ['width' => '5%', 'label' => $lang->petplay_admin_id],
                    'sprite_mini' => ['width' => '10%', 'label' => $lang->petplay_admin_species_sprite],
                    'name' => ['width' => '15%', 'label' => $lang->petplay_admin_species_name],
                    'type_primary_id' => ['width' => '15%', 'label' => $lang->petplay_admin_species_type_primary],
                    'type_secondary_id' => ['width' => '15%', 'label' => $lang->petplay_admin_species_type_secondary],
                    'description' => ['width' => '30%', 'label' => $lang->petplay_admin_species_description],
                    'actions' => ['width' => '10%', 'label' => $lang->petplay_admin_actions]
                ],
                $pageUrl . '&amp;action=species'
            );
            
            $controller->handleRequest($mybb->input);
        } elseif ($mybb->input['action'] == 'moves') {
            // Get all types for the dropdown
            $query = $db->simple_select('petplay_types', 'id, name', '', ['order_by' => 'name', 'order_dir' => 'ASC']);
            $types = [];
            while ($type = $db->fetch_array($query)) {
                $types[$type['id']] = $type['name'];
            }
            
            // Get default type
            $query = $db->simple_select('petplay_types', 'id', 'is_default = true');
            $defaultType = $db->fetch_field($query, 'id');
            
            $controller = new \petplay\AcpEntityManagementController(
                'moves',
                \petplay\DbRepository\Moves::class,
                [
                    'name' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_moves_name,
                        'description' => $lang->petplay_admin_moves_name_desc,
                        'required' => true
                    ],
                    'type_id' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_moves_type,
                        'description' => $lang->petplay_admin_moves_type_desc,
                        'options' => $types,
                        'required' => true,
                        'default' => $defaultType
                    ],
                    'category' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_moves_category,
                        'description' => $lang->petplay_admin_moves_category_desc,
                        'options' => [
                            'physical' => $lang->petplay_admin_moves_category_physical,
                            'special' => $lang->petplay_admin_moves_category_special,
                            'status' => $lang->petplay_admin_moves_category_status
                        ],
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'label' => $lang->petplay_admin_moves_description,
                        'description' => $lang->petplay_admin_moves_description_desc,
                        'required' => false
                    ],
                    'power_points' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_moves_power_points,
                        'description' => $lang->petplay_admin_moves_power_points_desc,
                        'required' => true,
                        'default' => '10'
                    ],
                    'power' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_moves_power,
                        'description' => $lang->petplay_admin_moves_power_desc,
                        'required' => false
                    ],
                    'accuracy' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_moves_accuracy,
                        'description' => $lang->petplay_admin_moves_accuracy_desc,
                        'required' => false
                    ]
                ],
                [
                    'id' => ['width' => '5%', 'label' => $lang->petplay_admin_id],
                    'name' => ['width' => '15%', 'label' => $lang->petplay_admin_moves_name],
                    'type_id' => ['width' => '10%', 'label' => $lang->petplay_admin_moves_type],
                    'category' => ['width' => '10%', 'label' => $lang->petplay_admin_moves_category],
                    'description' => ['width' => '25%', 'label' => $lang->petplay_admin_moves_description],
                    'power_points' => ['width' => '10%', 'label' => $lang->petplay_admin_moves_power_points],
                    'power' => ['width' => '5%', 'label' => $lang->petplay_admin_moves_power],
                    'accuracy' => ['width' => '5%', 'label' => $lang->petplay_admin_moves_accuracy],
                    'actions' => ['width' => '10%', 'label' => $lang->petplay_admin_actions]
                ],
                $pageUrl . '&amp;action=moves'
            );
            
            $controller->handleRequest($mybb->input);
        } elseif ($mybb->input['action'] == 'abilities') {
            $controller = new \petplay\AcpEntityManagementController(
                'abilities',
                \petplay\DbRepository\Abilities::class,
                [
                    'name' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_abilities_name,
                        'description' => $lang->petplay_admin_abilities_name_desc,
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'label' => $lang->petplay_admin_abilities_description,
                        'description' => $lang->petplay_admin_abilities_description_desc,
                        'required' => false
                    ]
                ],
                [
                    'id' => ['width' => '5%', 'label' => $lang->petplay_admin_id],
                    'name' => ['width' => '20%', 'label' => $lang->petplay_admin_abilities_name],
                    'description' => ['width' => '55%', 'label' => $lang->petplay_admin_abilities_description],
                    'actions' => ['width' => '10%', 'label' => $lang->petplay_admin_actions]
                ],
                $pageUrl . '&amp;action=abilities'
            );
            
            $controller->handleRequest($mybb->input);
        } elseif ($mybb->input['action'] == 'natures') {
            // Define valid stats for natures
            $validStats = [
                'attack' => $lang->petplay_admin_species_stat_attack,
                'defence' => $lang->petplay_admin_species_stat_defence,
                'sp_attack' => $lang->petplay_admin_species_stat_sp_attack,
                'sp_defence' => $lang->petplay_admin_species_stat_sp_defence,
                'speed' => $lang->petplay_admin_species_stat_speed
            ];
            
            $controller = new \petplay\AcpEntityManagementController(
                'natures',
                \petplay\DbRepository\Natures::class,
                [
                    'name' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_natures_name,
                        'description' => $lang->petplay_admin_natures_name_desc,
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'label' => $lang->petplay_admin_natures_description,
                        'description' => $lang->petplay_admin_natures_description_desc,
                        'required' => false
                    ],
                    'increased_stat' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_natures_increased_stat,
                        'description' => $lang->petplay_admin_natures_increased_stat_desc,
                        'options' => $validStats,
                        'required' => true
                    ],
                    'decreased_stat' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_natures_decreased_stat,
                        'description' => $lang->petplay_admin_natures_decreased_stat_desc,
                        'options' => $validStats,
                        'required' => true
                    ],
                    'is_default' => [
                        'type' => 'checkbox',
                        'label' => $lang->petplay_admin_natures_is_default,
                        'description' => $lang->petplay_admin_natures_is_default_desc,
                        'default' => false
                    ]
                ],
                [
                    'id' => ['width' => '5%', 'label' => $lang->petplay_admin_id],
                    'name' => ['width' => '15%', 'label' => $lang->petplay_admin_natures_name],
                    'description' => ['width' => '30%', 'label' => $lang->petplay_admin_natures_description],
                    'increased_stat' => ['width' => '15%', 'label' => $lang->petplay_admin_natures_increased_stat],
                    'decreased_stat' => ['width' => '15%', 'label' => $lang->petplay_admin_natures_decreased_stat],
                    'is_default' => ['width' => '10%', 'label' => $lang->petplay_admin_natures_is_default],
                    'actions' => ['width' => '10%', 'label' => $lang->petplay_admin_actions]
                ],
                $pageUrl . '&amp;action=natures'
            );
            
            $controller->handleRequest($mybb->input);
        } elseif ($mybb->input['action'] == 'capsules') {
            $controller = new \petplay\AcpEntityManagementController(
                'capsules',
                \petplay\DbRepository\Capsules::class,
                [
                    'name' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_capsules_name,
                        'description' => $lang->petplay_admin_capsules_name_desc,
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'textarea',
                        'label' => $lang->petplay_admin_capsules_description,
                        'description' => $lang->petplay_admin_capsules_description_desc,
                        'required' => false
                    ],
                    'catch_rate' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_capsules_catch_rate,
                        'description' => $lang->petplay_admin_capsules_catch_rate_desc,
                        'required' => true,
                        'default' => '1.0'
                    ],
                    'sprite' => [
                        'type' => 'file',
                        'label' => $lang->petplay_admin_capsules_sprite,
                        'description' => $lang->petplay_admin_capsules_sprite_desc,
                        'upload_dir' => 'uploads/petplay/capsules/',
                        'required' => false
                    ],
                    'is_default' => [
                        'type' => 'checkbox',
                        'label' => $lang->petplay_admin_capsules_is_default,
                        'description' => $lang->petplay_admin_capsules_is_default_desc,
                        'default' => false
                    ]
                ],
                [
                    'id' => ['width' => '5%', 'label' => $lang->petplay_admin_id],
                    'sprite' => ['width' => '10%', 'label' => $lang->petplay_admin_capsules_sprite],
                    'name' => ['width' => '15%', 'label' => $lang->petplay_admin_capsules_name],
                    'description' => ['width' => '30%', 'label' => $lang->petplay_admin_capsules_description],
                    'catch_rate' => ['width' => '10%', 'label' => $lang->petplay_admin_capsules_catch_rate],
                    'is_default' => ['width' => '10%', 'label' => $lang->petplay_admin_capsules_is_default],
                    'actions' => ['width' => '10%', 'label' => $lang->petplay_admin_actions]
                ],
                $pageUrl . '&amp;action=capsules'
            );
            
            $controller->handleRequest($mybb->input);
        } elseif ($mybb->input['action'] == 'pets' || empty($mybb->input['action'])) {
            // Get all species for the dropdown
            $query = $db->simple_select('petplay_species', 'id, name', '', ['order_by' => 'name', 'order_dir' => 'ASC']);
            $species = [];
            while ($speciesItem = $db->fetch_array($query)) {
                $species[$speciesItem['id']] = $speciesItem['name'];
            }
            
            // Get all natures for the dropdown
            $query = $db->simple_select('petplay_natures', 'id, name', '', ['order_by' => 'name', 'order_dir' => 'ASC']);
            $natures = [];
            while ($nature = $db->fetch_array($query)) {
                $natures[$nature['id']] = $nature['name'];
            }
            
            // Get all capsules for the dropdown
            $query = $db->simple_select('petplay_capsules', 'id, name', '', ['order_by' => 'name', 'order_dir' => 'ASC']);
            $capsules = [];
            while ($capsule = $db->fetch_array($query)) {
                $capsules[$capsule['id']] = $capsule['name'];
            }
            
            // Get all abilities for the dropdown
            $query = $db->simple_select('petplay_abilities', 'id, name', '', ['order_by' => 'name', 'order_dir' => 'ASC']);
            $abilities = ['' => '-- None --'];
            while ($ability = $db->fetch_array($query)) {
                $abilities[$ability['id']] = $ability['name'];
            }
            
            // Create a custom controller for pets
            $controller = new \petplay\AcpPetsController(
                'pets',
                \petplay\DbRepository\Pets::class,
                [
                    'species_id' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_pets_species,
                        'description' => $lang->petplay_admin_pets_species_desc,
                        'options' => $species,
                        'required' => true
                    ],
                    'nickname' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_pets_nickname,
                        'description' => $lang->petplay_admin_pets_nickname_desc,
                        'required' => false
                    ],
                    'gender' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_pets_gender,
                        'description' => $lang->petplay_admin_pets_gender_desc,
                        'options' => [
                            'male' => $lang->petplay_admin_pets_gender_male,
                            'female' => $lang->petplay_admin_pets_gender_female,
                            'unknown' => $lang->petplay_admin_pets_gender_unknown
                        ],
                        'required' => true,
                        'default' => 'unknown'
                    ],
                    'capsule_id' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_pets_capsule,
                        'description' => $lang->petplay_admin_pets_capsule_desc,
                        'options' => $capsules,
                        'required' => true
                    ],
                    'nature_id' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_pets_nature,
                        'description' => $lang->petplay_admin_pets_nature_desc,
                        'options' => $natures,
                        'required' => true
                    ],
                    'ability_id' => [
                        'type' => 'select',
                        'label' => $lang->petplay_admin_pets_ability,
                        'description' => $lang->petplay_admin_pets_ability_desc,
                        'options' => $abilities,
                        'required' => false
                    ],
                    'is_shiny' => [
                        'type' => 'checkbox',
                        'label' => $lang->petplay_admin_pets_is_shiny,
                        'description' => $lang->petplay_admin_pets_is_shiny_desc,
                        'default' => false
                    ],
                    'individual_values' => [
                        'type' => 'json',
                        'label' => $lang->petplay_admin_pets_individual_values,
                        'description' => $lang->petplay_admin_pets_individual_values_desc,
                        'required' => true,
                        'default' => '{"hp": 0, "attack": 0, "defence": 0, "special_attack": 0, "special_defence": 0, "speed": 0}'
                    ],
                    'effort_values' => [
                        'type' => 'json',
                        'label' => $lang->petplay_admin_pets_effort_values,
                        'description' => $lang->petplay_admin_pets_effort_values_desc,
                        'required' => true,
                        'default' => '{"hp": 0, "attack": 0, "defence": 0, "special_attack": 0, "special_defence": 0, "speed": 0}'
                    ],
                    'owner_id' => [
                        'type' => 'text',
                        'label' => $lang->petplay_admin_pets_owner,
                        'description' => $lang->petplay_admin_pets_owner_desc,
                        'required' => true
                    ]
                ],
                [
                    'id' => ['width' => '5%', 'label' => $lang->petplay_admin_id],
                    'species' => ['width' => '15%', 'label' => $lang->petplay_admin_pets_species],
                    'nickname' => ['width' => '15%', 'label' => $lang->petplay_admin_pets_nickname],
                    'gender' => ['width' => '5%', 'label' => $lang->petplay_admin_pets_gender],
                    'owner' => ['width' => '20%', 'label' => $lang->petplay_admin_pets_owner],
                    'capsule' => ['width' => '10%', 'label' => $lang->petplay_admin_pets_capsule],
                    'is_shiny' => ['width' => '5%', 'label' => $lang->petplay_admin_pets_is_shiny],
                    'actions' => ['width' => '10%', 'label' => $lang->petplay_admin_actions]
                ],
                $pageUrl . '&amp;action=pets'
            );
            
            $controller->handleRequest($mybb->input);
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
