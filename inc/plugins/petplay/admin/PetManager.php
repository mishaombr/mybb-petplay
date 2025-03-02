<?php

namespace petplay\admin;

class PetManager
{
    public static function handle($action, $pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        switch ($action) {
            case 'add_pet':
                self::handlePetForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'edit_pet':
                self::handlePetForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, true);
                break;
            case 'delete_pet':
                self::handleDeletePetPage($pageUrl, $page, $db, $lang, $mybb);
                break;
            case 'pet_history':
                self::handlePetHistoryPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'transfer_pet':
                self::handleTransferPetPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
            case 'pets':
            default:
                self::handlePetsListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb);
                break;
        }
    }

    private static function handlePetsListPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        $page->output_header($lang->petplay_admin_pets_list);
        $page->output_nav_tabs($sub_tabs, 'pets');

        // Add button above table with tighter spacing
        echo '<div style="margin-bottom: 5px;">';
        echo '<a href="' . $pageUrl . '&amp;action=add_pet" class="button"><span>+</span>' . $lang->petplay_admin_pets_add_button . '</a>';
        echo '</div>';

        $itemsNum = $db->fetch_field(
            $db->query("
                SELECT COUNT(p.id) AS n
                FROM " . TABLE_PREFIX . "petplay_pets p
            "),
            'n'
        );

        $listManager = new \petplay\ListManager([
            'mybb' => $mybb,
            'baseurl' => $pageUrl . '&amp;action=pets',
            'order_columns' => ['id', 'species_name', 'nickname', 'current_owner_name'],
            'order_dir' => 'asc',
            'items_num' => $itemsNum,
            'per_page' => 20,
        ]);

        $table = new \Table;
        $table->construct_header($listManager->link('id', $lang->petplay_admin_pets_id), ['width' => '5%']);
        $table->construct_header($listManager->link('nickname', $lang->petplay_admin_pets_nickname), ['width' => '20%']);
        $table->construct_header($listManager->link('species_name', $lang->petplay_admin_pets_species), ['width' => '20%']);
        $table->construct_header($listManager->link('current_owner_name', $lang->petplay_admin_pets_current_owner), ['width' => '20%']);
        $table->construct_header($lang->petplay_admin_pets_attributes, ['width' => '25%']);
        $table->construct_header("", ['width' => '10%']);

        $query = $db->query("
            SELECT p.*, s.name as species_name, s.mini_sprite_path, 
                   (SELECT username 
                    FROM " . TABLE_PREFIX . "petplay_pet_ownership_history ph
                    JOIN " . TABLE_PREFIX . "users u2 ON ph.user_id = u2.uid
                    WHERE ph.pet_id = p.id 
                    AND ph.is_current_owner = true
                    LIMIT 1) as current_owner_name
            FROM " . TABLE_PREFIX . "petplay_pets p
            LEFT JOIN " . TABLE_PREFIX . "petplay_species s ON p.species_id = s.id
            " . $listManager->sql()
        );

        if ($db->num_rows($query) > 0) {
            while ($pet = $db->fetch_array($query)) {
                // Build attributes string
                $attributes = [];
                if ($pet['is_shiny']) {
                    $attributes[] = $lang->petplay_admin_pets_shiny;
                }
                if ($pet['is_fainted']) {
                    $attributes[] = $lang->petplay_admin_pets_fainted;
                }
                
                $table->construct_cell($pet['id']);
                $table->construct_cell(
                    '<img src="' . $mybb->settings['bburl'] . '/' . htmlspecialchars_uni($pet['mini_sprite_path']) . '" alt="" style="vertical-align: middle;" /> ' .
                    htmlspecialchars_uni($pet['nickname'] ?: '-')
                );
                $table->construct_cell(
                    htmlspecialchars_uni($pet['species_name']) . 
                    ($pet['gender'] ? ' <span style="' . 
                        ($pet['gender'] === 'male' ? 'color: #3355ff;' : 'color: #ff4477;') . 
                        '">' . ($pet['gender'] === 'male' ? '♂' : '♀') . '</span>' : '')
                );
                $table->construct_cell(htmlspecialchars_uni($pet['current_owner_name'] ?: '-'));
                $table->construct_cell(implode(', ', $attributes));
                
                // Actions column
                $popup = new \PopupMenu("pet_{$pet['id']}", $lang->options);
                $popup->add_item(
                    $lang->edit,
                    $pageUrl . "&amp;action=edit_pet&amp;id={$pet['id']}"
                );
                $popup->add_item(
                    $lang->petplay_admin_pets_history,
                    $pageUrl . "&amp;action=pet_history&amp;id={$pet['id']}"
                );
                $popup->add_item(
                    $lang->delete,
                    $pageUrl . "&amp;action=delete_pet&amp;id={$pet['id']}"
                );
                $popup->add_item(
                    $lang->petplay_admin_pets_transfer,
                    $pageUrl . "&amp;action=transfer_pet&amp;id={$pet['id']}"
                );
                $table->construct_cell($popup->fetch());
                
                $table->construct_row();
            }
        } else {
            $table->construct_cell($lang->petplay_admin_pets_empty, ['colspan' => 6]);
            $table->construct_row();
        }

        $table->output($lang->petplay_admin_pets_list);

        $page->output_footer();
    }

    private static function handlePetForm($pageUrl, $page, $sub_tabs, $db, $lang, $mybb, $isEdit = false)
    {
        $page->output_header($isEdit ? $lang->petplay_admin_pets_edit : $lang->petplay_admin_pets_add);
        $page->output_nav_tabs($sub_tabs, 'pets');

        $pet = [];
        $pet_id = 0;

        if ($isEdit) {
            $pet_id = $mybb->get_input('id', \MyBB::INPUT_INT);
            $pet = $db->fetch_array($db->simple_select('petplay_pets', '*', "id = {$pet_id}"));
            
            if (!$pet) {
                flash_message($lang->petplay_admin_pets_invalid, 'error');
                admin_redirect($pageUrl . '&action=pets');
            }
        }

        $form = new \Form($pageUrl . '&action=' . ($isEdit ? 'edit_pet&id=' . $pet_id : 'add_pet'), 'post');
        
        echo '<div style="display: flex; gap: 20px;">';
        echo '<div style="flex: 2;">'; // Main details section takes up 2/3 of the space
        
        $form_container = new \FormContainer($isEdit ? $lang->petplay_admin_pets_edit : $lang->petplay_admin_pets_add);

        // Nickname field
        $form_container->output_row(
            $lang->petplay_admin_pets_nickname,
            $lang->petplay_admin_pets_nickname_desc,
            $form->generate_text_box('nickname', $pet['nickname'] ?? '')
        );

        // Species dropdown
        $species_options = [];
        $query = $db->simple_select('petplay_species', 'id, name', '', ['order_by' => 'name']);
        while ($species = $db->fetch_array($query)) {
            $species_options[$species['id']] = $species['name'];
        }
        $form_container->output_row(
            $lang->petplay_admin_pets_species,
            $lang->petplay_admin_pets_species_desc,
            $form->generate_select_box('species_id', $species_options, $pet['species_id'] ?? '')
        );

        // Owner field
        if (!$isEdit) {
            $form_container->output_row(
                $lang->petplay_admin_pets_original_owner . ' <em>*</em>',
                $lang->petplay_admin_pets_owner_desc,
                $form->generate_text_box('owner_id', '', ['type' => 'number'])
            );
        } else {
            $owner = $db->fetch_array($db->simple_select('users', 'username', "uid = " . (int)$pet['original_owner_id']));
            $form_container->output_row(
                $lang->petplay_admin_pets_original_owner,
                '',
                htmlspecialchars_uni($owner['username'])
            );
        }

        // Gender field
        $gender_options = [
            '' => $lang->petplay_admin_pets_gender_none,
            'male' => $lang->petplay_admin_pets_gender_male,
            'female' => $lang->petplay_admin_pets_gender_female
        ];
        $form_container->output_row(
            $lang->petplay_admin_pets_gender,
            $lang->petplay_admin_pets_gender_desc,
            $form->generate_select_box('gender', $gender_options, $pet['gender'] ?? '')
        );

        // Shiny checkbox
        $form_container->output_row(
            $lang->petplay_admin_pets_is_shiny,
            $lang->petplay_admin_pets_is_shiny_desc,
            $form->generate_check_box('is_shiny', 1, '', ['checked' => $pet['is_shiny'] ?? false])
        );

        // Nature dropdown
        $nature_options = [];
        $query = $db->simple_select('petplay_natures', 'id, name', '', ['order_by' => 'name']);
        while ($nature = $db->fetch_array($query)) {
            $nature_options[$nature['id']] = $nature['name'];
        }
        $form_container->output_row(
            $lang->petplay_admin_pets_nature,
            $lang->petplay_admin_pets_nature_desc,
            $form->generate_select_box('nature_id', $nature_options, $pet['nature_id'] ?? '')
        );

        // Ability field
        $form_container->output_row(
            $lang->petplay_admin_pets_ability,
            $lang->petplay_admin_pets_ability_desc,
            $form->generate_text_box('ability', $pet['ability'] ?? '')
        );

        // Fainted checkbox
        $form_container->output_row(
            $lang->petplay_admin_pets_is_fainted,
            $lang->petplay_admin_pets_is_fainted_desc,
            $form->generate_check_box('is_fainted', 1, '', ['checked' => $pet['is_fainted'] ?? false])
        );

        $form_container->end();
        echo '</div>';

        echo '<div style="flex: 1;">'; // IVs section takes up 1/3 of the space
        // Create form container for IVs
        $iv_container = new \FormContainer($lang->petplay_admin_pets_ivs);
        
        // Add randomize button at the top of the IVs container
        $iv_container->output_row(
            '',
            '',
            '<input type="button" value="' . $lang->petplay_admin_pets_iv_randomize . '" class="button" onclick="randomizeIVs()" style="margin-bottom: 10px;">'
        );
        
        $default_ivs = [
            'hp' => 0,
            'attack' => 0,
            'defence' => 0,
            'special_attack' => 0,
            'special_defence' => 0,
            'speed' => 0
        ];
        
        $current_ivs = $isEdit ? json_decode($pet['individual_values'], true) : $default_ivs;
        
        $iv_fields = [
            'hp' => $lang->petplay_admin_pets_iv_hp,
            'attack' => $lang->petplay_admin_pets_iv_attack,
            'defence' => $lang->petplay_admin_pets_iv_defence,
            'special_attack' => $lang->petplay_admin_pets_iv_special_attack,
            'special_defence' => $lang->petplay_admin_pets_iv_special_defence,
            'speed' => $lang->petplay_admin_pets_iv_speed
        ];
        
        foreach ($iv_fields as $iv_key => $iv_label) {
            $iv_container->output_row(
                $iv_label,
                $lang->petplay_admin_pets_iv_description,
                $form->generate_numeric_field("individual_values_{$iv_key}", $current_ivs[$iv_key], [
                    'min' => 0,
                    'max' => 31,
                    'style' => 'width: 100px'
                ])
            );
        }
        
        // Add JavaScript for randomizing IVs
        echo "<script type=\"text/javascript\">
        function randomizeIVs() {
            const ivFields = [
                'hp', 'attack', 'defence', 
                'special_attack', 'special_defence', 'speed'
            ];
            
            ivFields.forEach(field => {
                const input = document.querySelector(`input[name='individual_values_\${field}']`);
                if (input) {
                    input.value = Math.floor(Math.random() * 32); // Random number between 0 and 31
                }
            });
        }
        </script>";
        
        $iv_container->end();
        echo '</div>';
        echo '</div>';

        $buttons[] = $form->generate_submit_button($lang->petplay_admin_pets_submit);
        $form->output_submit_wrapper($buttons);
        $form->end();

        if ($mybb->request_method == 'post') {
            $errors = [];

            // Validate required fields
            if (!$isEdit) {
                $owner_id = $mybb->get_input('owner_id', \MyBB::INPUT_INT);
                if (!$owner_id) {
                    $errors[] = $lang->petplay_admin_pets_no_owner;
                } else {
                    $owner = $db->fetch_array($db->simple_select('users', '*', "uid = {$owner_id}"));
                    if (!$owner) {
                        $errors[] = $lang->petplay_admin_pets_invalid_owner;
                    }
                }
            }

            // Validate species
            $species_id = $mybb->get_input('species_id', \MyBB::INPUT_INT);
            if (!$species_id) {
                $errors[] = $lang->petplay_admin_pets_no_species;
            } else {
                $species = $db->fetch_array($db->simple_select('petplay_species', '*', "id = {$species_id}"));
                if (!$species) {
                    $errors[] = $lang->petplay_admin_pets_invalid_species;
                }
            }
            
            // Collect and validate IV values
            $individual_values = [];
            foreach (array_keys($iv_fields) as $stat_key) {
                $value = $mybb->get_input("individual_values_{$stat_key}", \MyBB::INPUT_INT);
                if ($value < 0 || $value > 31) {
                    $errors[] = $lang->sprintf($lang->petplay_admin_pets_iv_invalid, $iv_fields[$stat_key]);
                }
                $individual_values[$stat_key] = $value;
            }

            if (empty($errors)) {
                $update_data = [
                    'nickname' => $db->escape_string($mybb->get_input('nickname')),
                    'species_id' => $species_id,
                    'gender' => $db->escape_string($mybb->get_input('gender')),
                    'is_shiny' => $mybb->get_input('is_shiny', \MyBB::INPUT_INT) ? 'true' : 'false',
                    'nature_id' => $mybb->get_input('nature_id', \MyBB::INPUT_INT),
                    'ability' => $db->escape_string($mybb->get_input('ability')),
                    'is_fainted' => $mybb->get_input('is_fainted', \MyBB::INPUT_INT) ? 'true' : 'false',
                    'individual_values' => $db->escape_string(json_encode($individual_values))
                ];

                if ($isEdit) {
                    $db->update_query('petplay_pets', $update_data, "id = {$pet_id}");
                    flash_message($lang->petplay_admin_pets_updated, 'success');
                } else {
                    // Make sure we have a valid owner_id before proceeding
                    if (!$owner_id) {
                        $errors[] = $lang->petplay_admin_pets_no_owner;
                    } else {
                        $update_data['original_owner_id'] = $owner_id;
                        
                        try {
                            $db->write_query("BEGIN");
                            
                            $pet_id = $db->insert_query('petplay_pets', $update_data);
                            
                            // Create initial ownership record
                            $db->insert_query('petplay_pet_ownership_history', [
                                'pet_id' => $pet_id,
                                'user_id' => $owner_id,
                                'is_current_owner' => 'true',
                                'acquired_at' => date('Y-m-d H:i:s')
                            ]);
                            
                            $db->write_query("COMMIT");
                            flash_message($lang->petplay_admin_pets_added, 'success');
                            admin_redirect($pageUrl . '&action=pets');
                        } catch (Exception $e) {
                            $db->write_query("ROLLBACK");
                            $errors[] = $lang->petplay_admin_pets_add_error;
                        }
                    }
                }

                if (empty($errors)) {
                    admin_redirect($pageUrl . '&action=pets');
                }
            }
            
            if (!empty($errors)) {
                $page->output_inline_error($errors);
            }
        }

        $page->output_footer();
    }

    private static function handleDeletePetPage($pageUrl, $page, $db, $lang, $mybb)
    {
        $pet_id = $mybb->get_input('id', \MyBB::INPUT_INT);
        $pet = $db->fetch_array($db->simple_select('petplay_pets', '*', "id = {$pet_id}"));
        
        if (!$pet) {
            flash_message($lang->petplay_admin_pets_invalid, 'error');
            admin_redirect($pageUrl . '&action=pets');
        }

        if ($mybb->request_method == 'post') {
            $db->delete_query('petplay_pets', "id = {$pet_id}");
            flash_message($lang->petplay_admin_pets_deleted, 'success');
            admin_redirect($pageUrl . '&action=pets');
        }
        
        $page->output_header($lang->petplay_admin_pets_delete_confirm_title);
        $page->output_nav_tabs($sub_tabs, 'pets');
        
        $form = new \Form($pageUrl . "&action=delete_pet&id={$pet_id}", 'post');
        
        echo "<div class=\"confirm_action\">\n";
        echo "<p>{$lang->petplay_admin_pets_delete_confirm_message}</p>\n";
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

    private static function handlePetHistoryPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        $pet_id = $mybb->get_input('id', \MyBB::INPUT_INT);
        
        // Get pet details
        $pet = $db->fetch_array($db->query("
            SELECT p.*, s.name as species_name, s.mini_sprite_path
            FROM " . TABLE_PREFIX . "petplay_pets p
            LEFT JOIN " . TABLE_PREFIX . "petplay_species s ON p.species_id = s.id
            WHERE p.id = {$pet_id}
        "));
        
        if (!$pet) {
            flash_message($lang->petplay_admin_pets_invalid, 'error');
            admin_redirect($pageUrl . '&action=pets');
        }
        
        $page->output_header($lang->petplay_admin_pets_history_title);
        $page->output_nav_tabs($sub_tabs, 'pets');
        
        // Pet info header
        echo "<div style='margin-bottom: 10px;'>";
        echo "<img src='" . $mybb->settings['bburl'] . '/' . htmlspecialchars_uni($pet['mini_sprite_path']) . "' alt='' style='vertical-align: middle;' /> ";
        echo "<strong>" . htmlspecialchars_uni($pet['nickname'] ? $pet['nickname'] : $pet['species_name']) . "</strong>";
        echo "</div>";
        
        // Create history table
        $table = new \Table;
        $table->construct_header($lang->petplay_admin_pets_history_owner, ['width' => '40%']);
        $table->construct_header($lang->petplay_admin_pets_history_acquired, ['width' => '40%']);
        $table->construct_header($lang->petplay_admin_pets_history_current, ['width' => '20%', 'class' => 'align_center']);
        
        $query = $db->query("
            SELECT h.*, u.username
            FROM " . TABLE_PREFIX . "petplay_pet_ownership_history h
            LEFT JOIN " . TABLE_PREFIX . "users u ON h.user_id = u.uid
            WHERE h.pet_id = {$pet_id}
            ORDER BY h.acquired_at DESC
        ");
        
        if ($db->num_rows($query) > 0) {
            while ($history = $db->fetch_array($query)) {
                $table->construct_cell(htmlspecialchars_uni($history['username']));
                $table->construct_cell(my_date('relative', strtotime($history['acquired_at'])));
                $table->construct_cell(
                    $history['is_current_owner'] ? '✓' : '',
                    ['class' => 'align_center']
                );
                $table->construct_row();
            }
        } else {
            $table->construct_cell($lang->petplay_admin_pets_history_empty, ['colspan' => 3]);
            $table->construct_row();
        }
        
        $table->output($lang->petplay_admin_pets_history_description);
        
        $page->output_footer();
    }

    private static function handleTransferPetPage($pageUrl, $page, $sub_tabs, $db, $lang, $mybb)
    {
        $pet_id = $mybb->get_input('id', \MyBB::INPUT_INT);
        
        // Get pet details with current owner
        $pet = $db->fetch_array($db->query("
            SELECT p.*, s.name as species_name, s.mini_sprite_path,
                   (SELECT username 
                    FROM " . TABLE_PREFIX . "petplay_pet_ownership_history ph
                    JOIN " . TABLE_PREFIX . "users u ON ph.user_id = u.uid
                    WHERE ph.pet_id = p.id 
                    AND ph.is_current_owner = true
                    LIMIT 1) as current_owner_name,
                   (SELECT user_id
                    FROM " . TABLE_PREFIX . "petplay_pet_ownership_history ph
                    WHERE ph.pet_id = p.id 
                    AND ph.is_current_owner = true
                    LIMIT 1) as current_owner_id
            FROM " . TABLE_PREFIX . "petplay_pets p
            LEFT JOIN " . TABLE_PREFIX . "petplay_species s ON p.species_id = s.id
            WHERE p.id = {$pet_id}
        "));
        
        if (!$pet) {
            flash_message($lang->petplay_admin_pets_invalid, 'error');
            admin_redirect($pageUrl . '&action=pets');
        }
        
        if ($mybb->request_method == 'post') {
            $new_owner_id = $mybb->get_input('new_owner_id', \MyBB::INPUT_INT);
            $errors = [];
            
            // Validate new owner
            if (!$new_owner_id) {
                $errors[] = $lang->petplay_admin_pets_no_owner;
            } else {
                $new_owner = $db->fetch_array($db->simple_select('users', '*', "uid = {$new_owner_id}"));
                if (!$new_owner) {
                    $errors[] = $lang->petplay_admin_pets_invalid_owner;
                }
            }
            
            if (empty($errors)) {
                // Start transaction
                $db->write_query("BEGIN");
                try {
                    // Don't allow transferring to the current owner
                    if ($pet['current_owner_id'] == $new_owner_id) {
                        throw new Exception($lang->petplay_admin_pets_already_owner);
                    }

                    // 1. Mark the current owner as no longer the owner
                    $db->write_query("
                        UPDATE " . TABLE_PREFIX . "petplay_pet_ownership_history 
                        SET is_current_owner = 'false', 
                            released_at = '" . date('Y-m-d H:i:s') . "'
                        WHERE pet_id = {$pet_id} 
                        AND is_current_owner = 'true'
                    ");

                    // 2. Always create a new ownership record to preserve history
                    $db->insert_query('petplay_pet_ownership_history', [
                        'pet_id' => $pet_id,
                        'user_id' => $new_owner_id,
                        'is_current_owner' => 'true',
                        'acquired_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Commit transaction
                    $db->write_query("COMMIT");
                    flash_message($lang->petplay_admin_pets_transferred, 'success');
                    admin_redirect($pageUrl . '&action=pets');
                } catch (Exception $e) {
                    $db->write_query("ROLLBACK");
                    $errors[] = $e->getMessage();
                }
            }
            
            if (!empty($errors)) {
                $page->output_inline_error($errors);
            }
        }
        
        $page->output_header($lang->petplay_admin_pets_transfer_title);
        $page->output_nav_tabs($sub_tabs, 'pets');
        
        // Pet info header
        echo "<div style='margin-bottom: 10px;'>";
        echo "<img src='" . $mybb->settings['bburl'] . '/' . htmlspecialchars_uni($pet['mini_sprite_path']) . "' alt='' style='vertical-align: middle;' /> ";
        echo "<strong>" . htmlspecialchars_uni($pet['nickname'] ? $pet['nickname'] : $pet['species_name']) . "</strong>";
        echo " (Current owner: " . htmlspecialchars_uni($pet['current_owner_name']) . ")";
        echo "</div>";
        
        $form = new \Form($pageUrl . "&action=transfer_pet&id={$pet_id}", 'post');
        
        $form_container = new \FormContainer($lang->petplay_admin_pets_transfer_description);
        
        $form_container->output_row(
            $lang->petplay_admin_pets_new_owner . ' <em>*</em>',
            $lang->petplay_admin_pets_new_owner_desc,
            $form->generate_text_box('new_owner_id', '', ['type' => 'number'])
        );
        
        $form_container->end();
        
        $buttons[] = $form->generate_submit_button($lang->petplay_admin_pets_submit);
        $form->output_submit_wrapper($buttons);
        $form->end();
        
        $page->output_footer();
    }
}
