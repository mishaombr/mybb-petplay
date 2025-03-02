<?php

namespace petplay\admin;

class SpeciesManager
{
    public static function handle($action, $pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        switch ($action) {
            case 'add_species':
                self::handleSpeciesForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'edit_species':
                self::handleSpeciesForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, true);
                break;
            case 'delete_species':
                self::handleDeleteSpeciesPage($pageUrl, $page, $db, $lang, $mybb);
                break;
            case 'species':
            default:
                self::handleSpeciesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
        }
    }

    private static function handleSpeciesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        $page->output_header($lang->petplay_admin_species_list);
        $page->output_nav_tabs($sub_tabs, 'species');

        // Add button above table with tighter spacing
        echo '<div style="margin-bottom: 5px;">';
        echo '<a href="' . $pageUrl . '&amp;action=add_species" class="button"><span>+</span>' . $lang->petplay_admin_species_add_button . '</a>';
        echo '</div>';

        $itemsNum = $db->fetch_field(
            $db->query("
                SELECT COUNT(s.id) AS n
                FROM " . TABLE_PREFIX . "petplay_species s
            "),
            'n'
        );

        $listManager = new \petplay\ListManager([
            'mybb' => $mybb,
            'baseurl' => $pageUrl . '&amp;action=species',
            'order_columns' => ['id', 'name', 'description'],
            'order_dir' => 'asc',
            'items_num' => $itemsNum,
            'per_page' => 20,
        ]);

        $query = $db->query("
            SELECT s.*,
                STRING_AGG(t.name, ', ' ORDER BY t.name) as type_names
            FROM " . TABLE_PREFIX . "petplay_species s
            LEFT JOIN " . TABLE_PREFIX . "petplay_species_types st ON s.id = st.species_id
            LEFT JOIN " . TABLE_PREFIX . "petplay_types t ON st.type_id = t.id
            GROUP BY s.id
            " . $listManager->sql() . "
        ");

        $table = new \Table;
        $table->construct_header($listManager->link('id', $lang->petplay_admin_species_id), ['width' => '5%']);
        $table->construct_header('Sprite', ['width' => '5%', 'class' => 'align_center']);
        $table->construct_header($listManager->link('name', $lang->petplay_admin_species_name), ['width' => '15%']);
        $table->construct_header($listManager->link('description', $lang->petplay_admin_species_description), ['width' => '35%']);
        $table->construct_header($lang->petplay_admin_species_type, ['width' => '25%']);
        $table->construct_header($lang->options, ['width' => '15%', 'class' => 'align_center']);

        if ($itemsNum > 0) {
            while ($row = $db->fetch_array($query)) {
                $sprite_html = '';
                if (!empty($row['mini_sprite_path'])) {
                    $sprite_html = '<img src="' . $mybb->settings['bburl'] . '/' . $row['mini_sprite_path'] . '" alt="' . $row['name'] . '">';
                }

                $popup = new \PopupMenu('controls_' . $row['id'], $lang->options);
                $popup->add_item($lang->edit, $pageUrl . '&amp;action=edit_species&amp;id=' . $row['id']);
                $popup->add_item($lang->delete, $pageUrl . '&amp;action=delete_species&amp;id=' . $row['id']);
                $controls = $popup->fetch();

                $table->construct_cell($row['id']);
                $table->construct_cell($sprite_html, ['class' => 'align_center']);
                $table->construct_cell(\htmlspecialchars_uni($row['name']));
                $table->construct_cell(\htmlspecialchars_uni($row['description']));
                $table->construct_cell(\htmlspecialchars_uni($row['type_names'] ?: '-'));
                $table->construct_cell($controls, ['class' => 'align_center']);
                $table->construct_row();
            }
        } else {
            $table->construct_cell($lang->petplay_admin_species_empty, ['colspan' => '6', 'class' => 'align_center']);
            $table->construct_row();
        }

        $table->output($lang->petplay_admin_species_list);

        echo $listManager->pagination();
    }

    private static function handleSpeciesForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, $isEdit = false)
    {
        $species = [];
        $species_id = 0;
        $activeTab = $isEdit ? 'species' : 'add_species';
        $headerText = $isEdit ? $lang->petplay_admin_species_edit : $lang->petplay_admin_species_add;
        
        if ($isEdit) {
            $species_id = $mybb->get_input('id', \MyBB::INPUT_INT);
            $species = $db->fetch_array($db->simple_select('petplay_species', '*', "id = {$species_id}"));
            
            if (!$species) {
                \flash_message($lang->petplay_admin_species_invalid, 'error');
                \admin_redirect($pageUrl . '&action=species');
            }
            
            // Decode base stats for editing
            $species['base_stats'] = json_decode($species['base_stats'], true);
            
            // Get current types
            $query = $db->simple_select('petplay_species_types', 'type_id', "species_id = {$species_id}");
            $species['type_ids'] = [];
            while ($type = $db->fetch_array($query)) {
                $species['type_ids'][] = $type['type_id'];
            }
        }
        
        if ($mybb->request_method == 'post') {
            $name = trim($mybb->get_input('name'));
            $description = trim($mybb->get_input('description'));
            $type_ids = $mybb->get_input('type_ids', \MyBB::INPUT_ARRAY);
            
            // Get base stats from form
            $base_stats = [
                'hp' => (int)$mybb->get_input('base_stats_hp', \MyBB::INPUT_INT),
                'attack' => (int)$mybb->get_input('base_stats_attack', \MyBB::INPUT_INT),
                'defence' => (int)$mybb->get_input('base_stats_defence', \MyBB::INPUT_INT),
                'special_attack' => (int)$mybb->get_input('base_stats_special_attack', \MyBB::INPUT_INT),
                'special_defence' => (int)$mybb->get_input('base_stats_special_defence', \MyBB::INPUT_INT),
                'speed' => (int)$mybb->get_input('base_stats_speed', \MyBB::INPUT_INT)
            ];
            
            if (!empty($name)) {
                $data = [
                    'name' => $db->escape_string($name),
                    'description' => $db->escape_string($description),
                    'base_stats' => $db->escape_string(json_encode($base_stats))
                ];
                
                // Handle sprite uploads
                $sprites = ['sprite', 'shiny_sprite', 'mini_sprite'];
                foreach ($sprites as $sprite) {
                    if (isset($_FILES[$sprite]) && $_FILES[$sprite]['error'] == 0) {
                        $file = $_FILES[$sprite];
                        
                        // Validate file type
                        $allowed = ['image/png', 'image/gif'];
                        if (!in_array($file['type'], $allowed)) {
                            \flash_message($lang->sprintf($lang->petplay_admin_species_sprite_invalid, $sprite), 'error');
                            \admin_redirect($pageUrl . '&action=' . ($isEdit ? 'edit_species&id=' . $species_id : 'add_species'));
                        }
                        
                        // Create directory if it doesn't exist
                        $upload_dir = MYBB_ROOT . 'uploads/petplay/species/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Generate unique filename
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = uniqid('species_' . $sprite . '_') . '.' . $ext;
                        $filepath = $upload_dir . $filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $data[$sprite . '_path'] = 'uploads/petplay/species/' . $filename;
                            
                            // Delete old sprite if it exists
                            if ($isEdit && !empty($species[$sprite . '_path'])) {
                                @unlink(MYBB_ROOT . $species[$sprite . '_path']);
                            }
                        }
                    }
                }
                
                if ($isEdit) {
                    $db->update_query('petplay_species', $data, "id = {$species_id}");
                    
                    // Update types
                    $db->delete_query('petplay_species_types', "species_id = {$species_id}");
                    
                    \flash_message($lang->petplay_admin_species_updated, 'success');
                } else {
                    $db->insert_query('petplay_species', $data);
                    $species_id = $db->insert_id();
                    
                    \flash_message($lang->petplay_admin_species_added, 'success');
                }
                
                // Insert new types
                if (!empty($type_ids)) {
                    $type_inserts = [];
                    foreach ($type_ids as $type_id) {
                        $type_inserts[] = [
                            'species_id' => $species_id,
                            'type_id' => (int)$type_id
                        ];
                    }
                    $db->insert_query_multiple('petplay_species_types', $type_inserts);
                }
                
                \admin_redirect($pageUrl . '&action=species');
            }
        }
        
        $page->output_header($headerText);
        $page->output_nav_tabs($sub_tabs, $activeTab);
        
        $form = new \Form($pageUrl . '&amp;action=' . ($isEdit ? 'edit_species&amp;id=' . $species_id : 'add_species'), 'post', '', true);
        
        $form_container = new \FormContainer($headerText);
        
        echo '<div style="display: flex; gap: 20px;">';
        echo '<div style="flex: 2;">'; // Main details section takes up 2/3 of the space
        
        // Fetch types for the select box
        $type_options = [];
        $query = $db->simple_select('petplay_types', 'id, name', '', ['order_by' => 'name']);
        while ($type = $db->fetch_array($query)) {
            $type_options[$type['id']] = $type['name'];
        }

        // Recreate the main form container inside the flex layout
        $main_container = new \FormContainer($headerText);
        $main_container->output_row(
            $lang->petplay_admin_species_name . ' <em>*</em>',
            $lang->petplay_admin_species_name_description,
            $form->generate_text_box('name', $isEdit ? $species['name'] : '')
        );
        
        $main_container->output_row(
            $lang->petplay_admin_species_description,
            $lang->petplay_admin_species_description_desc,
            $form->generate_text_area('description', $isEdit ? $species['description'] : '')
        );
        
        $main_container->output_row(
            $lang->petplay_admin_species_type,
            $lang->petplay_admin_species_type_description,
            $form->generate_select_box(
                'type_ids[]',
                $type_options,
                $isEdit ? $species['type_ids'] : [],
                ['multiple' => true, 'size' => 5]
            )
        );
        
        // Sprite uploads
        $main_container->output_row(
            $lang->petplay_admin_species_sprite,
            $lang->petplay_admin_species_sprite_description,
            $form->generate_file_upload_box('sprite') .
            ($isEdit && !empty($species['sprite_path']) ?
                '<br /><img src="' . $mybb->settings['bburl'] . '/' . $species['sprite_path'] . '" alt="Current sprite">' :
                '')
        );
        
        $main_container->output_row(
            $lang->petplay_admin_species_shiny_sprite,
            $lang->petplay_admin_species_shiny_sprite_desc,
            $form->generate_file_upload_box('shiny_sprite') .
            ($isEdit && !empty($species['shiny_sprite_path']) ?
                '<br /><img src="' . $mybb->settings['bburl'] . '/' . $species['shiny_sprite_path'] . '" alt="Current shiny sprite">' :
                '')
        );
        
        $main_container->output_row(
            $lang->petplay_admin_species_mini_sprite,
            $lang->petplay_admin_species_mini_sprite_description,
            $form->generate_file_upload_box('mini_sprite') .
            ($isEdit && !empty($species['mini_sprite_path']) ?
                '<br /><img src="' . $mybb->settings['bburl'] . '/' . $species['mini_sprite_path'] . '" alt="Current mini sprite">' :
                '')
        );
        $main_container->end();
        echo '</div>';

        echo '<div style="flex: 1;">'; // Stats section takes up 1/3 of the space
        // Create form container for base stats
        $base_stats_container = new \FormContainer($lang->petplay_admin_species_base_stats);
        
        $default_stats = [
            'hp' => 50,
            'attack' => 50,
            'defence' => 50,
            'special_attack' => 50,
            'special_defence' => 50,
            'speed' => 50
        ];
        
        $current_stats = $isEdit ? $species['base_stats'] : $default_stats;
        
        $stat_fields = [
            'hp' => $lang->petplay_admin_species_stat_hp,
            'attack' => $lang->petplay_admin_species_stat_attack,
            'defence' => $lang->petplay_admin_species_stat_defence,
            'special_attack' => $lang->petplay_admin_species_stat_special_attack,
            'special_defence' => $lang->petplay_admin_species_stat_special_defence,
            'speed' => $lang->petplay_admin_species_stat_speed
        ];
        
        foreach ($stat_fields as $stat_key => $stat_label) {
            $base_stats_container->output_row(
                $stat_label,
                $lang->sprintf($lang->petplay_admin_species_stat_description, $stat_label),
                $form->generate_numeric_field("base_stats_{$stat_key}", $current_stats[$stat_key], [
                    'min' => 1,
                    'max' => 255,
                    'style' => 'width: 100px'
                ])
            );
        }
        
        $base_stats_container->end();
        echo '</div>';
        echo '</div>';
        
        $buttons[] = $form->generate_submit_button($lang->petplay_admin_species_submit);
        $form->output_submit_wrapper($buttons);
        $form->end();
    }

    private static function handleDeleteSpeciesPage($pageUrl, $page, $db, $lang, $mybb)
    {
        $species_id = $mybb->get_input('id', \MyBB::INPUT_INT);
        $species = $db->fetch_array($db->simple_select('petplay_species', '*', "id = {$species_id}"));
        
        if (!$species) {
            \flash_message($lang->petplay_admin_species_invalid, 'error');
            \admin_redirect($pageUrl . '&action=species');
        }
        
        if ($mybb->request_method == 'post') {
            if ($mybb->get_input('no')) {
                \admin_redirect($pageUrl . '&action=species');
            } else {
                // Delete sprite files
                $sprites = ['sprite_path', 'shiny_sprite_path', 'mini_sprite_path'];
                foreach ($sprites as $sprite) {
                    if (!empty($species[$sprite])) {
                        @unlink(MYBB_ROOT . $species[$sprite]);
                    }
                }
                
                $db->delete_query('petplay_species', "id = {$species_id}");
                \flash_message($lang->petplay_admin_species_deleted, 'success');
                \admin_redirect($pageUrl . '&action=species');
            }
        } else {
            $page->output_confirm_action(
                $pageUrl . '&action=delete_species&id=' . $species_id,
                $lang->petplay_admin_species_delete_confirm_message,
                $lang->petplay_admin_species_delete_confirm_title
            );
        }
    }
} 