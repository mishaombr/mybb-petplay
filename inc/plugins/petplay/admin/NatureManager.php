<?php

namespace petplay\admin;

class NatureManager
{
    public static function handle($action, $pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        switch ($action) {
            case 'add_nature':
                self::handleNatureForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'edit_nature':
                self::handleNatureForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, true);
                break;
            case 'delete_nature':
                self::handleDeleteNaturePage($pageUrl, $page, $db, $lang, $mybb);
                break;
            case 'natures':
            default:
                self::handleNaturesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
        }
    }

    private static function handleNaturesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        $page->output_header($lang->petplay_admin_natures_list);
        $page->output_nav_tabs($sub_tabs, 'natures');

        // Add button above table with tighter spacing
        echo '<div style="margin-bottom: 5px;">';
        echo '<a href="' . $pageUrl . '&amp;action=add_nature" class="button"><span>+</span>' . $lang->petplay_admin_natures_add_button . '</a>';
        echo '</div>';

        $itemsNum = $db->fetch_field(
            $db->query("
                SELECT COUNT(n.id) AS n
                FROM " . TABLE_PREFIX . "petplay_natures n
            "),
            'n'
        );

        $listManager = new \petplay\ListManager([
            'mybb' => $mybb,
            'baseurl' => $pageUrl . '&amp;action=natures',
            'order_columns' => ['id', 'name', 'increased_stat', 'decreased_stat'],
            'order_dir' => 'asc',
            'items_num' => $itemsNum,
            'per_page' => 50,
        ]);

        $query = $db->query("
            SELECT n.*,
                (SELECT COUNT(p.id)
                 FROM " . TABLE_PREFIX . "petplay_pets p
                 WHERE p.nature_id = n.id) as pets_count
            FROM " . TABLE_PREFIX . "petplay_natures n
            " . $listManager->sql() . "
        ");

        $table = new \Table;
        $table->construct_header($listManager->link('id', $lang->petplay_admin_natures_id), ['width' => '5%']);
        $table->construct_header($listManager->link('name', $lang->petplay_admin_natures_name), ['width' => '20%']);
        $table->construct_header($listManager->link('increased_stat', $lang->petplay_admin_natures_increased_stat), ['width' => '20%']);
        $table->construct_header($listManager->link('decreased_stat', $lang->petplay_admin_natures_decreased_stat), ['width' => '20%']);
        $table->construct_header($lang->petplay_admin_natures_is_default, ['width' => '10%', 'class' => 'align_center']);
        $table->construct_header($lang->options, ['width' => '15%', 'class' => 'align_center']);

        if ($itemsNum > 0) {
            while ($row = $db->fetch_array($query)) {
                $popup = new \PopupMenu('controls_' . $row['id'], $lang->options);
                $popup->add_item($lang->edit, $pageUrl . '&amp;action=edit_nature&amp;id=' . $row['id']);
                
                if ($row['pets_count'] == 0 && !$row['is_default']) {
                    $popup->add_item($lang->delete, $pageUrl . '&amp;action=delete_nature&amp;id=' . $row['id']);
                }
                
                $controls = $popup->fetch();

                $table->construct_cell($row['id']);
                $table->construct_cell(\htmlspecialchars_uni($row['name']));
                $table->construct_cell(self::formatStatName($row['increased_stat'], $lang));
                $table->construct_cell(self::formatStatName($row['decreased_stat'], $lang));
                $table->construct_cell($row['is_default'] ? $lang->yes : $lang->no, ['class' => 'align_center']);
                $table->construct_cell($controls, ['class' => 'align_center']);
                $table->construct_row();
            }
        } else {
            $table->construct_cell($lang->petplay_admin_natures_empty, ['colspan' => '6', 'class' => 'align_center']);
            $table->construct_row();
        }

        $table->output($lang->petplay_admin_natures_list);

        echo $listManager->pagination();
    }

    private static function handleNatureForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, $isEdit = false)
    {
        $nature_id = 0;
        $nature = [];
        $headerText = $isEdit ? $lang->petplay_admin_natures_edit : $lang->petplay_admin_natures_add;
        $activeTab = 'natures';
        
        if ($isEdit) {
            $nature_id = $mybb->get_input('id', \MyBB::INPUT_INT);
            $nature = $db->fetch_array($db->simple_select('petplay_natures', '*', "id = {$nature_id}"));
            
            if (!$nature) {
                \flash_message($lang->petplay_admin_natures_invalid, 'error');
                \admin_redirect($pageUrl . '&action=natures');
            }
        }
        
        if ($mybb->request_method == 'post') {
            $errors = [];
            
            // Validate name
            $name = trim($mybb->get_input('name'));
            if (empty($name)) {
                $errors[] = $lang->petplay_admin_natures_no_name;
            }
            
            // Validate increased_stat
            $increased_stat = $mybb->get_input('increased_stat');
            if (empty($increased_stat)) {
                $errors[] = $lang->petplay_admin_natures_no_increased_stat;
            }
            
            // Validate decreased_stat
            $decreased_stat = $mybb->get_input('decreased_stat');
            if (empty($decreased_stat)) {
                $errors[] = $lang->petplay_admin_natures_no_decreased_stat;
            }
            
            // Check if is_default is being changed
            $is_default = $mybb->get_input('is_default', \MyBB::INPUT_INT);
            if ($is_default && (!$isEdit || !$nature['is_default'])) {
                // If setting this nature as default, unset any existing default
                $db->update_query('petplay_natures', ['is_default' => 0], 'is_default = 1');
            } else if (!$is_default && $isEdit && $nature['is_default']) {
                // Check if there's another default nature
                $default_count = $db->fetch_field(
                    $db->simple_select('petplay_natures', 'COUNT(id) as count', "is_default = 1 AND id != {$nature_id}"),
                    'count'
                );
                
                if ($default_count == 0) {
                    $errors[] = $lang->petplay_admin_natures_default_exists;
                }
            }
            
            if (empty($errors)) {
                $data = [
                    'name' => $db->escape_string($name),
                    'description' => $db->escape_string($mybb->get_input('description')),
                    'increased_stat' => $db->escape_string($increased_stat),
                    'decreased_stat' => $db->escape_string($decreased_stat),
                    'is_default' => $is_default ? 1 : 0
                ];
                
                if ($isEdit) {
                    $db->update_query('petplay_natures', $data, "id = {$nature_id}");
                    \flash_message($lang->petplay_admin_natures_updated, 'success');
                } else {
                    $db->insert_query('petplay_natures', $data);
                    \flash_message($lang->petplay_admin_natures_added, 'success');
                }
                
                \admin_redirect($pageUrl . '&action=natures');
            } else {
                $page->output_inline_error($errors);
            }
        }
        
        $page->output_header($headerText);
        $page->output_nav_tabs($sub_tabs, $activeTab);
        
        $form = new \Form($pageUrl . '&amp;action=' . ($isEdit ? 'edit_nature&amp;id=' . $nature_id : 'add_nature'), 'post');
        
        $form_container = new \FormContainer($headerText);
        
        $form_container->output_row(
            $lang->petplay_admin_natures_name . ' <em>*</em>',
            $lang->petplay_admin_natures_name_description,
            $form->generate_text_box('name', $isEdit ? $nature['name'] : '')
        );
        
        $form_container->output_row(
            $lang->petplay_admin_natures_description,
            $lang->petplay_admin_natures_description_desc,
            $form->generate_text_area('description', $isEdit ? $nature['description'] : '')
        );
        
        $stat_options = [
            'hp' => $lang->petplay_admin_species_stat_hp,
            'attack' => $lang->petplay_admin_species_stat_attack,
            'defence' => $lang->petplay_admin_species_stat_defence,
            'special_attack' => $lang->petplay_admin_species_stat_special_attack,
            'special_defence' => $lang->petplay_admin_species_stat_special_defence,
            'speed' => $lang->petplay_admin_species_stat_speed
        ];
        
        $form_container->output_row(
            $lang->petplay_admin_natures_increased_stat . ' <em>*</em>',
            $lang->petplay_admin_natures_increased_stat_desc,
            $form->generate_select_box('increased_stat', $stat_options, $isEdit ? $nature['increased_stat'] : '')
        );
        
        $form_container->output_row(
            $lang->petplay_admin_natures_decreased_stat . ' <em>*</em>',
            $lang->petplay_admin_natures_decreased_stat_desc,
            $form->generate_select_box('decreased_stat', $stat_options, $isEdit ? $nature['decreased_stat'] : '')
        );
        
        $form_container->output_row(
            $lang->petplay_admin_natures_is_default,
            $lang->petplay_admin_natures_is_default_description,
            $form->generate_yes_no_radio('is_default', $isEdit ? $nature['is_default'] : 0)
        );
        
        $form_container->end();
        
        $buttons[] = $form->generate_submit_button($lang->petplay_admin_natures_submit);
        $form->output_submit_wrapper($buttons);
        $form->end();
    }

    private static function handleDeleteNaturePage($pageUrl, $page, $db, $lang, $mybb)
    {
        $nature_id = $mybb->get_input('id', \MyBB::INPUT_INT);
        $nature = $db->fetch_array($db->simple_select('petplay_natures', '*', "id = {$nature_id}"));
        
        if (!$nature) {
            flash_message($lang->petplay_admin_natures_invalid, 'error');
            admin_redirect($pageUrl . '&action=natures');
        }
        
        // Check if this is a default nature
        if ($nature['is_default']) {
            flash_message($lang->petplay_admin_natures_default_exists, 'error');
            admin_redirect($pageUrl . '&action=natures');
        }
        
        // Check if this nature is in use
        $pets_count = $db->fetch_field(
            $db->simple_select('petplay_pets', 'COUNT(id) as count', "nature_id = {$nature_id}"),
            'count'
        );
        
        if ($pets_count > 0) {
            flash_message($lang->petplay_admin_natures_in_use, 'error');
            admin_redirect($pageUrl . '&action=natures');
        }
        
        if ($mybb->request_method == 'post') {
            $db->delete_query('petplay_natures', "id = {$nature_id}");
            flash_message($lang->petplay_admin_natures_deleted, 'success');
            admin_redirect($pageUrl . '&action=natures');
        }
        
        $page->output_header($lang->petplay_admin_natures_delete_confirm_title);
        $page->output_nav_tabs($sub_tabs, 'natures');
        
        $form = new \Form($pageUrl . "&action=delete_nature&id={$nature_id}", 'post');
        
        echo "<div class=\"confirm_action\">\n";
        echo "<p>{$lang->petplay_admin_natures_delete_confirm_message}</p>\n";
        echo "<br />\n";
        echo "<p class=\"buttons\">\n";
        echo $form->generate_submit_button($lang->yes, ['class' => $form->generate_submit_button_class('delete')]);
        echo $form->generate_submit_button($lang->no, [
            'onclick' => "javascript:history.go(-1); return false;",
            'class' => $form->generate_submit_button_class()
        ]);
        echo "</p>\n";
        echo "</div>\n";
        
        $form->end();
        
        $page->output_footer();
    }
    
    private static function formatStatName($stat, $lang)
    {
        $stat_labels = [
            'hp' => $lang->petplay_admin_species_stat_hp,
            'attack' => $lang->petplay_admin_species_stat_attack,
            'defence' => $lang->petplay_admin_species_stat_defence,
            'special_attack' => $lang->petplay_admin_species_stat_special_attack,
            'special_defence' => $lang->petplay_admin_species_stat_special_defence,
            'speed' => $lang->petplay_admin_species_stat_speed
        ];
        
        return isset($stat_labels[$stat]) ? $stat_labels[$stat] : $stat;
    }
} 