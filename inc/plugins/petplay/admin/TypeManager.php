<?php

namespace petplay\admin;

class TypeManager
{
    public static function handle($action, $pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        switch ($action) {
            case 'add_type':
                self::handleTypeForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'edit_type':
                self::handleTypeForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, true);
                break;
            case 'delete_type':
                self::handleDeleteTypePage($pageUrl, $page, $db, $lang, $mybb);
                break;
            case 'types':
            default:
                self::handleTypesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
        }
    }

    private static function handleTypesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        $page->output_header($lang->petplay_admin_types_list);
        $page->output_nav_tabs($sub_tabs, 'types');

        // Add button above table with tighter spacing
        echo '<div style="margin-bottom: 5px;">';
        echo '<a href="' . $pageUrl . '&amp;action=add_type" class="button"><span>+</span>' . $lang->petplay_admin_types_add_button . '</a>';
        echo '</div>';

        $itemsNum = $db->fetch_field(
            $db->query("
                SELECT COUNT(t.id) AS n
                FROM " . TABLE_PREFIX . "petplay_types t
            "),
            'n'
        );

        $listManager = new \petplay\ListManager([
            'mybb' => $mybb,
            'baseurl' => $pageUrl . '&amp;action=types',
            'order_columns' => ['id', 'name', 'description', 'colour'],
            'order_dir' => 'asc',
            'items_num' => $itemsNum,
            'per_page' => 20,
        ]);

        $query = $db->query("
            SELECT t.*,
                (SELECT COUNT(st.species_id)
                 FROM " . TABLE_PREFIX . "petplay_species_types st
                 WHERE st.type_id = t.id) as species_count
            FROM " . TABLE_PREFIX . "petplay_types t
            " . $listManager->sql() . "
        ");

        $table = new \Table;
        $table->construct_header($listManager->link('id', $lang->petplay_admin_types_id), ['width' => '5%']);
        $table->construct_header($listManager->link('name', $lang->petplay_admin_types_name), ['width' => '15%']);
        $table->construct_header($listManager->link('colour', $lang->petplay_admin_types_colour), ['width' => '10%']);
        $table->construct_header($listManager->link('description', $lang->petplay_admin_types_description), ['width' => '40%']);
        $table->construct_header($lang->options, ['width' => '15%', 'class' => 'align_center']);

        if ($itemsNum > 0) {
            while ($row = $db->fetch_array($query)) {
                $popup = new \PopupMenu('controls_' . $row['id'], $lang->options);
                $popup->add_item($lang->edit, $pageUrl . '&amp;action=edit_type&amp;id=' . $row['id']);
                
                if ($row['species_count'] == 0) {
                    $popup->add_item($lang->delete, $pageUrl . '&amp;action=delete_type&amp;id=' . $row['id']);
                }
                
                $controls = $popup->fetch();

                $table->construct_cell($row['id']);
                $table->construct_cell(\htmlspecialchars_uni($row['name']));
                $table->construct_cell('<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . \htmlspecialchars_uni($row['colour']) . '; vertical-align: middle; margin-right: 5px;"></span>' . \htmlspecialchars_uni($row['colour']));
                $table->construct_cell(\htmlspecialchars_uni($row['description']));
                $table->construct_cell($controls, ['class' => 'align_center']);
                $table->construct_row();
            }
        } else {
            $table->construct_cell($lang->petplay_admin_types_empty, ['colspan' => '5', 'class' => 'align_center']);
            $table->construct_row();
        }

        $table->output($lang->petplay_admin_types_list);

        echo $listManager->pagination();
    }

    private static function handleTypeForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, $isEdit = false)
    {
        $type = [];
        $type_id = 0;
        $activeTab = $isEdit ? 'types' : 'add_type';
        $headerText = $isEdit ? $lang->petplay_admin_types_edit : $lang->petplay_admin_types_add;
        
        if ($isEdit) {
            $type_id = $mybb->get_input('id', \MyBB::INPUT_INT);
            $type = $db->fetch_array($db->simple_select('petplay_types', '*', "id = {$type_id}"));
            
            if (!$type) {
                \flash_message($lang->petplay_admin_types_invalid, 'error');
                \admin_redirect($pageUrl . '&action=types');
            }
        }
        
        if ($mybb->request_method == 'post') {
            $name = trim($mybb->get_input('name'));
            $description = trim($mybb->get_input('description'));
            $colour = trim($mybb->get_input('colour'));
            $isDefault = $mybb->get_input('is_default', \MyBB::INPUT_INT);
            
            // Validate color format
            if (!preg_match('/^#[a-fA-F0-9]{6}$/', $colour)) {
                $colour = '#A8A878'; // Default to Normal type color if invalid
            }
            
            if (!empty($name)) {
                // Check if we're removing the only default type
                if ($isEdit && $type['is_default'] && !$isDefault) {
                    $defaultCount = $db->fetch_field(
                        $db->simple_select('petplay_types', 'COUNT(id) as count', 'is_default = TRUE'),
                        'count'
                    );
                    
                    if ($defaultCount <= 1) {
                        \flash_message($lang->petplay_admin_types_default_exists, 'error');
                        \admin_redirect($pageUrl . '&action=edit_type&id=' . $type_id);
                    }
                }
                
                // If setting as default, clear other defaults
                if ($isDefault) {
                    $db->update_query('petplay_types', ['is_default' => FALSE]);
                }
                
                // If no default exists, force this one to be default
                if (!$isEdit) {
                    $defaultExists = $db->fetch_field(
                        $db->simple_select('petplay_types', 'COUNT(id) as count', 'is_default = TRUE'),
                        'count'
                    );
                    
                    if (!$defaultExists) {
                        $isDefault = 1;
                    }
                }
                
                $data = [
                    'name' => $db->escape_string($name),
                    'description' => $db->escape_string($description),
                    'colour' => $db->escape_string($colour),
                    'is_default' => $isDefault ? 'TRUE' : 'FALSE'
                ];
                
                if ($isEdit) {
                    $db->update_query('petplay_types', $data, "id = {$type_id}");
                    \flash_message($lang->petplay_admin_types_updated, 'success');
                } else {
                    $db->insert_query('petplay_types', $data);
                    \flash_message($lang->petplay_admin_types_added, 'success');
                }
                
                \admin_redirect($pageUrl . '&action=types');
            }
        }
        
        $page->output_header($headerText);
        $page->output_nav_tabs($sub_tabs, $activeTab);
        
        $form = new \Form($pageUrl . '&amp;action=' . ($isEdit ? 'edit_type&amp;id=' . $type_id : 'add_type'), 'post');
        
        $form_container = new \FormContainer($headerText);
        
        $form_container->output_row(
            $lang->petplay_admin_types_name . ' <em>*</em>',
            $lang->petplay_admin_types_name_description,
            $form->generate_text_box('name', $isEdit ? $type['name'] : '')
        );
        
        $form_container->output_row(
            $lang->petplay_admin_types_description,
            $lang->petplay_admin_types_description_desc,
            $form->generate_text_area('description', $isEdit ? $type['description'] : '')
        );
        
        $form_container->output_row(
            $lang->petplay_admin_types_colour . ' <em>*</em>',
            $lang->petplay_admin_types_colour_description,
            $form->generate_text_box('colour', $isEdit ? $type['colour'] : '#A8A878', ['id' => 'type_colour']) .
            ' <input type="color" onchange="$(\'#type_colour\').val(this.value)" value="' . ($isEdit ? $type['colour'] : '#A8A878') . '">'
        );
        
        $form_container->output_row(
            $lang->petplay_admin_types_is_default,
            $lang->petplay_admin_types_is_default_description,
            $form->generate_yes_no_radio('is_default', $isEdit ? $type['is_default'] : 0)
        );
        
        $form_container->end();
        
        $buttons[] = $form->generate_submit_button($lang->petplay_admin_types_submit);
        $form->output_submit_wrapper($buttons);
        $form->end();
    }

    private static function handleDeleteTypePage($pageUrl, $page, $db, $lang, $mybb)
    {
        $type_id = $mybb->get_input('id', \MyBB::INPUT_INT);
        $type = $db->fetch_array($db->simple_select('petplay_types', '*', "id = {$type_id}"));
        
        if (!$type) {
            \flash_message($lang->petplay_admin_types_invalid, 'error');
            \admin_redirect($pageUrl . '&action=types');
        }
        
        // Check if type is in use
        $inUse = $db->fetch_field(
            $db->query("
                SELECT COUNT(st.species_id) as count
                FROM " . TABLE_PREFIX . "petplay_species_types st
                WHERE st.type_id = {$type_id}
            "),
            'count'
        );
        
        if ($inUse > 0) {
            \flash_message($lang->petplay_admin_types_in_use, 'error');
            \admin_redirect($pageUrl . '&action=types');
        }
        
        if ($mybb->request_method == 'post') {
            if ($mybb->get_input('no')) {
                \admin_redirect($pageUrl . '&action=types');
            } else {
                $db->delete_query('petplay_types', "id = {$type_id}");
                \flash_message($lang->petplay_admin_types_deleted, 'success');
                \admin_redirect($pageUrl . '&action=types');
            }
        } else {
            $page->output_confirm_action(
                $pageUrl . '&action=delete_type&id=' . $type_id,
                $lang->petplay_admin_types_delete_confirm_message,
                $lang->petplay_admin_types_delete_confirm_title
            );
        }
    }
} 