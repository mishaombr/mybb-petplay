<?php

namespace petplay\Hooks;

require_once MYBB_ROOT . 'inc/plugins/petplay/ListManager.php';

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
                handleTypesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'add_type':
                handleTypeForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'edit_type':
                handleTypeForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, true);
                break;
            case 'delete_type':
                handleDeleteTypePage($pageUrl, $page, $db, $lang, $mybb);
                break;
            case 'add_species':
                handleSpeciesForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'edit_species':
                handleSpeciesForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, true);
                break;
            case 'delete_species':
                handleDeleteSpeciesPage($pageUrl, $page, $db, $lang, $mybb);
                break;
            case 'species':
            default:
                handleSpeciesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
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
        'description' => $lang->petplay_admin_species_list_description,
    ];
    
    $sub_tabs['add_species'] = [
        'link'        => $pageUrl . '&action=add_species',
        'title'       => $lang->petplay_admin_species_add,
        'description' => $lang->petplay_admin_species_add_description,
    ];
    
    $sub_tabs['types'] = [
        'link'        => $pageUrl . '&action=types',
        'title'       => $lang->petplay_admin_types_list,
        'description' => $lang->petplay_admin_types_list_description,
    ];
    
    $sub_tabs['add_type'] = [
        'link'        => $pageUrl . '&action=add_type',
        'title'       => $lang->petplay_admin_types_add,
        'description' => $lang->petplay_admin_types_add_description,
    ];
}

function handleTypesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
{
    $page->output_header($lang->petplay_admin_types_list);
    $page->output_nav_tabs($sub_tabs, 'types');

    $itemsNum = $db->fetch_field(
        $db->query("
            SELECT
                COUNT(id) AS n
            FROM
                " . $db->table_prefix . "petplay_types    
        "),
        'n'
    );

    $listManager = new \petplay\ListManager([
        'mybb' => $mybb,
        'baseurl' => $pageUrl . '&amp;action=types',
        'order_columns' => ['id', 'name'],
        'order_dir' => 'asc',
        'items_num' => $itemsNum,
        'per_page' => 20,
    ]);

    $query = $db->query("
        SELECT
            *
        FROM
            " . $db->table_prefix . "petplay_types
        " . $listManager->sql() . "
    ");

    $table = new \Table;
    $table->construct_header($listManager->link('id', $lang->petplay_admin_types_id), ['width' => '5%', 'class' => 'align_center']);
    $table->construct_header($listManager->link('name', $lang->petplay_admin_types_name), ['width' => '15%', 'class' => 'align_center']);
    $table->construct_header($lang->petplay_admin_types_description, ['width' => '60%', 'class' => 'align_center']);
    $table->construct_header($lang->petplay_admin_types_is_default, ['width' => '10%', 'class' => 'align_center']);
    $table->construct_header($lang->options, ['width' => '10%', 'class' => 'align_center']);

    if ($itemsNum > 0) {
        while ($row = $db->fetch_array($query)) {
            $name = \htmlspecialchars_uni($row['name']);
            $description = \htmlspecialchars_uni(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : '');
            $isDefault = $row['is_default'] ? '✓' : '';
            
            $popup = new \PopupMenu('controls_' . $row['id'], $lang->options);
            $popup->add_item($lang->edit, $pageUrl . '&amp;action=edit_type&amp;id=' . $row['id']);
            $popup->add_item($lang->delete, $pageUrl . '&amp;action=delete_type&amp;id=' . $row['id']);
            $controls = $popup->fetch();

            $table->construct_cell($row['id'], ['class' => 'align_center']);
            $table->construct_cell($name, ['class' => 'align_center']);
            $table->construct_cell($description, ['class' => 'align_center']);
            $table->construct_cell($isDefault, ['class' => 'align_center']);
            $table->construct_cell($controls, ['class' => 'align_center']);
            $table->construct_row();
        }
    } else {
        $table->construct_cell($lang->petplay_admin_types_empty, ['colspan' => '5', 'class' => 'align_center']);
        $table->construct_row();
    }

    $table->output($lang->petplay_admin_types_list);

    echo $listManager->pagination();
    
    echo '<br />';
    echo '<div class="float_right">';
    echo '<a href="' . $pageUrl . '&amp;action=add_type" class="button">' . $lang->petplay_admin_types_add_button . '</a>';
    echo '</div>';
}

function handleTypeForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, $isEdit = false)
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
        $isDefault = $mybb->get_input('is_default', \MyBB::INPUT_INT);
        
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
                'is_default' => $isDefault ? TRUE : FALSE
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
        $lang->petplay_admin_types_name,
        $lang->petplay_admin_types_name_description,
        $form->generate_text_box('name', $isEdit ? $type['name'] : '')
    );
    $form_container->output_row(
        $lang->petplay_admin_types_description,
        $lang->petplay_admin_types_description_desc,
        $form->generate_text_area('description', $isEdit ? $type['description'] : '')
    );
    $form_container->output_row(
        $lang->petplay_admin_types_is_default,
        $lang->petplay_admin_types_is_default_description,
        $form->generate_check_box('is_default', 1, '', ['checked' => $isEdit ? $type['is_default'] : false])
    );
    
    $form_container->end();
    
    $buttons = [];
    $buttons[] = $form->generate_submit_button($lang->petplay_admin_types_submit);
    $form->output_submit_wrapper($buttons);
    $form->end();
}

function handleDeleteTypePage($pageUrl, $page, $db, $lang, $mybb)
{
    $type_id = $mybb->get_input('id', \MyBB::INPUT_INT);
    $type = $db->fetch_array($db->simple_select('petplay_types', '*', "id = {$type_id}"));
    
    if (!$type) {
        \flash_message($lang->petplay_admin_types_invalid, 'error');
        \admin_redirect($pageUrl . '&action=types');
    }
    
    if ($type['is_default']) {
        $defaultCount = $db->fetch_field(
            $db->simple_select('petplay_types', 'COUNT(id) as count', 'is_default = TRUE'),
            'count'
        );
        
        if ($defaultCount <= 1) {
            \flash_message($lang->petplay_admin_types_default_exists, 'error');
            \admin_redirect($pageUrl . '&action=types');
        }
    }
    
    if ($mybb->request_method == 'post') {
        if ($mybb->get_input('no')) {
            \admin_redirect($pageUrl . '&action=types');
        } else {
            $defaultTypeId = $db->fetch_field(
                $db->simple_select('petplay_types', 'id', 'is_default = TRUE AND id != ' . $type_id, ['limit' => 1]),
                'id'
            );
            
            $query = $db->query("
                SELECT s.id
                FROM " . $db->table_prefix . "petplay_species s
                WHERE (
                    SELECT COUNT(*)
                    FROM " . $db->table_prefix . "petplay_species_types st
                    WHERE st.species_id = s.id
                ) = 1
                AND EXISTS (
                    SELECT 1
                    FROM " . $db->table_prefix . "petplay_species_types st
                    WHERE st.species_id = s.id AND st.type_id = {$type_id}
                )
            ");
            
            while ($row = $db->fetch_array($query)) {
                $db->insert_query('petplay_species_types', [
                    'species_id' => $row['id'],
                    'type_id' => $defaultTypeId
                ]);
            }
            
            $db->delete_query('petplay_species_types', "type_id = {$type_id}");
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

function handleSpeciesListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
{
    $page->output_header($lang->petplay_admin_species_list);
    $page->output_nav_tabs($sub_tabs, 'species');

    $itemsNum = $db->fetch_field(
        $db->query("
            SELECT
                COUNT(id) AS n
            FROM
                " . $db->table_prefix . "petplay_species    
        "),
        'n'
    );

    $listManager = new \petplay\ListManager([
        'mybb' => $mybb,
        'baseurl' => $pageUrl . '&amp;action=species',
        'order_columns' => ['id', 'name'],
        'order_dir' => 'asc',
        'items_num' => $itemsNum,
        'per_page' => 20,
    ]);

    $query = $db->query("
        SELECT
            s.*,
            STRING_AGG(t.name, ', ') as type_names
        FROM
            " . $db->table_prefix . "petplay_species s
        LEFT JOIN
            " . $db->table_prefix . "petplay_species_types st ON s.id = st.species_id
        LEFT JOIN
            " . $db->table_prefix . "petplay_types t ON st.type_id = t.id
        GROUP BY
            s.id
        " . $listManager->sql() . "
    ");

    $table = new \Table;
    $table->construct_header($listManager->link('id', $lang->petplay_admin_species_id), ['width' => '10%', 'class' => 'align_center']);
    $table->construct_header($listManager->link('name', $lang->petplay_admin_species_name), ['width' => '30%', 'class' => 'align_center']);
    $table->construct_header($lang->petplay_admin_species_type, ['width' => '20%', 'class' => 'align_center']);
    $table->construct_header($lang->petplay_admin_species_description, ['width' => '20%', 'class' => 'align_center']);
    $table->construct_header($lang->options, ['width' => '20%', 'class' => 'align_center']);

    if ($itemsNum > 0) {
        while ($row = $db->fetch_array($query)) {
            $name = \htmlspecialchars_uni($row['name']);
            $description = \htmlspecialchars_uni(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : '');
            $types = \htmlspecialchars_uni($row['type_names']);
            
            $popup = new \PopupMenu('controls_' . $row['id'], $lang->options);
            $popup->add_item($lang->edit, $pageUrl . '&amp;action=edit_species&amp;id=' . $row['id']);
            $popup->add_item($lang->delete, $pageUrl . '&amp;action=delete_species&amp;id=' . $row['id']);
            $controls = $popup->fetch();

            $table->construct_cell($row['id'], ['class' => 'align_center']);
            $table->construct_cell($name, ['class' => 'align_center']);
            $table->construct_cell($types, ['class' => 'align_center']);
            $table->construct_cell($description, ['class' => 'align_center']);
            $table->construct_cell($controls, ['class' => 'align_center']);
            $table->construct_row();
        }
    } else {
        $table->construct_cell($lang->petplay_admin_species_empty, ['colspan' => '5', 'class' => 'align_center']);
        $table->construct_row();
    }

    $table->output($lang->petplay_admin_species_list);

    echo $listManager->pagination();
    
    echo '<br />';
    echo '<div class="float_right">';
    echo '<a href="' . $pageUrl . '&amp;action=add_species" class="button">' . $lang->petplay_admin_species_add_button . '</a>';
    echo '</div>';
}

function handleSpeciesForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, $isEdit = false)
{
    $species = [];
    $species_id = 0;
    $activeTab = $isEdit ? 'species' : 'add_species';
    $headerText = $isEdit ? $lang->petplay_admin_species_edit : $lang->petplay_admin_species_add;
    $current_types = [];
    
    if ($isEdit) {
        $species_id = $mybb->get_input('id', \MyBB::INPUT_INT);
        $species = $db->fetch_array($db->simple_select('petplay_species', '*', "id = {$species_id}"));
        
        if (!$species) {
            \flash_message($lang->petplay_admin_species_invalid, 'error');
            \admin_redirect($pageUrl . '&action=species');
        }
        
        $query = $db->simple_select('petplay_species_types', 'type_id', "species_id = {$species_id}");
        while ($type = $db->fetch_array($query)) {
            $current_types[] = $type['type_id'];
        }
    }
    
    if ($mybb->request_method == 'post') {
        $name = trim($mybb->get_input('name'));
        $description = trim($mybb->get_input('description'));
        $types = $mybb->get_input('types', \MyBB::INPUT_ARRAY);
        
        if (!empty($name) && !empty($description)) {
            if (empty($types)) {
                $defaultType = $db->fetch_field(
                    $db->simple_select('petplay_types', 'id', 'is_default = TRUE', ['limit' => 1]),
                    'id'
                );
                $types = [$defaultType];
            }
            
            $data = [
                'name' => $db->escape_string($name),
                'description' => $db->escape_string($description)
            ];
            
            if ($isEdit) {
                $db->update_query('petplay_species', $data, "id = {$species_id}");
                $db->delete_query('petplay_species_types', "species_id = {$species_id}");
                \flash_message($lang->petplay_admin_species_updated, 'success');
            } else {
                $db->insert_query('petplay_species', $data);
                $species_id = $db->insert_id();
                \flash_message($lang->petplay_admin_species_added, 'success');
            }
            
            foreach ($types as $type_id) {
                $db->insert_query('petplay_species_types', [
                    'species_id' => $species_id,
                    'type_id' => (int)$type_id
                ]);
            }
            
            \admin_redirect($pageUrl . '&action=species');
        }
    }
    
    $page->output_header($headerText);
    $page->output_nav_tabs($sub_tabs, $activeTab);
    
    $form = new \Form($pageUrl . '&amp;action=' . ($isEdit ? 'edit_species&amp;id=' . $species_id : 'add_species'), 'post');
    
    $form_container = new \FormContainer($headerText);
    $form_container->output_row(
        $lang->petplay_admin_species_name,
        $lang->petplay_admin_species_name_description,
        $form->generate_text_box('name', $isEdit ? $species['name'] : '')
    );
    $form_container->output_row(
        $lang->petplay_admin_species_description,
        $lang->petplay_admin_species_description_desc,
        $form->generate_text_area('description', $isEdit ? $species['description'] : '')
    );
    
    $type_options = [];
    $query = $db->simple_select('petplay_types', 'id, name', '', ['order_by' => 'name']);
    while ($type = $db->fetch_array($query)) {
        $type_options[$type['id']] = $type['name'];
    }
    
    $form_container->output_row(
        $lang->petplay_admin_species_type,
        $lang->petplay_admin_species_type_description,
        $form->generate_select_box('types[]', $type_options, $current_types, ['multiple' => true, 'size' => 5])
    );
    
    $form_container->end();
    
    $buttons = [];
    $buttons[] = $form->generate_submit_button($lang->petplay_admin_species_submit);
    $form->output_submit_wrapper($buttons);
    $form->end();
}

function handleDeleteSpeciesPage($pageUrl, $page, $db, $lang, $mybb)
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
            $db->delete_query('petplay_species_types', "species_id = {$species_id}");
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