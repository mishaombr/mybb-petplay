<?php

declare(strict_types=1);

namespace petplay;

class AcpPetsController extends AcpEntityManagementController
{
    protected function fetchEntities(string $tableName): array
    {
        global $db;
        
        // Join with species, capsules, and pet_owners tables to get all the data we need
        $query = $db->query("
            SELECT 
                p.*,
                s.name AS species_name,
                s.sprite_mini AS species_sprite,
                c.name AS capsule_name,
                c.sprite AS capsule_sprite,
                po.user_id,
                u.username,
                u.usergroup,
                u.displaygroup
            FROM " . TABLE_PREFIX . $tableName . " p
            LEFT JOIN " . TABLE_PREFIX . "petplay_species s ON p.species_id = s.id
            LEFT JOIN " . TABLE_PREFIX . "petplay_capsules c ON p.capsule_id = c.id
            LEFT JOIN " . TABLE_PREFIX . "petplay_pet_owners po ON p.id = po.pet_id
            LEFT JOIN " . TABLE_PREFIX . "users u ON po.user_id = u.uid
            ORDER BY p.id DESC
        ");
        
        $entities = [];
        while ($entity = $db->fetch_array($query)) {
            $entities[] = $entity;
        }
        
        return $entities;
    }
    
    protected function showList(): void
    {
        global $page, $lang, $db, $mybb;
        
        $page->output_header($lang->petplay_admin);
        $page->output_nav_tabs($GLOBALS['sub_tabs'], $this->entityName);
        
        // Show add button
        echo '<div style="margin-bottom: 10px; overflow: hidden;">
            <div class="float_left">
                <a href="' . $this->baseUrl . '&amp;option=add" class="button">
                    <i class="fa-solid fa-plus"></i> ' . $lang->{"petplay_admin_{$this->entityName}_add"} . '
                </a>
            </div>
            <div style="clear: both;"></div>
        </div>';
        
        // Get repository instance and table name
        $repository = call_user_func([$this->repositoryClass, 'with'], $db);
        $tableName = constant($this->repositoryClass . '::TABLE_NAME');
        
        // Fetch entities with any necessary joins
        $entities = $this->fetchEntities($tableName);
        
        // Create and render the list with custom renderer
        $listManager = new PetsListManager($this->entityName, $this->listColumns, $this->baseUrl, $db);
        $listManager->render($tableName, $entities);
        
        $page->output_footer();
    }
    
    protected function showAddForm(): void
    {
        global $page, $lang;
        
        $page->output_header($lang->petplay_admin);
        $page->output_nav_tabs($GLOBALS['sub_tabs'], $this->entityName);
        
        // Add required fields note above the form
        echo '<div style="margin-bottom: 10px;"><span style="color: #cc0000;">*</span> ' . $lang->petplay_admin_required_fields . '</div>';
        
        $form = new \Form($this->baseUrl . '&amp;option=save', 'post', '', true);
        
        // Create a flex container for the main form and stats
        echo '<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">';
        
        // Left column container for main form and moves
        echo '<div style="flex: 1; min-width: 500px;">';
        
        // Main form container
        $form_container = new \FormContainer($lang->{"petplay_admin_{$this->entityName}_add"});
        
        foreach ($this->formFields as $field => $config) {
            // Skip IV and EV fields as they will be handled separately
            if ($field === 'individual_values' || $field === 'effort_values') continue;
            
            // Add required field indicator
            $label = $config['label'];
            if ($config['required'] ?? false) {
                $label = '<span style="color: #cc0000;">*</span> ' . $label;
            }
            
            // Handle the owner field specially
            if ($field === 'owner_id') {
                $form_container->output_row(
                    $label,
                    $config['description'] ?? '',
                    $form->generate_text_box($field, $config['default'] ?? '', [
                        'id' => $field,
                        'required' => $config['required'] ?? false
                    ]) . ' <small>' . $lang->petplay_admin_pets_owner_help . '</small>'
                );
                continue;
            }
            
            // Use the parent class's form field rendering logic
            $this->renderFormField($form, $form_container, $field, $config, null);
        }
        
        $form_container->end();
        
        // Moves container (in the same left column)
        $this->renderMovesContainer($form, $lang->petplay_admin_pets_moves, null);
        
        echo '</div>';
        
        // Right column for stats
        echo '<div style="width: 400px;">';
        
        // Individual Values
        $this->renderStatsContainer($form, $lang->petplay_admin_pets_individual_values, 'iv', 0, 31);
        
        // Effort Values
        $this->renderStatsContainer($form, $lang->petplay_admin_pets_effort_values, 'ev', 0, 252);
        
        echo '</div>';
        
        // End flex container
        echo '</div>';
        
        $buttons[] = $form->generate_submit_button($lang->petplay_admin_save);
        $buttons[] = $form->generate_reset_button($lang->petplay_admin_cancel);
        
        $form->output_submit_wrapper($buttons);
        
        $form->end();
        
        // Add EV total validation script
        echo '<script type="text/javascript">
        document.querySelector("form").addEventListener("submit", function(e) {
            var evTotal = 0;
            var evFields = ["ev_hp", "ev_attack", "ev_defence", "ev_special_attack", "ev_special_defence", "ev_speed"];
            
            for (var i = 0; i < evFields.length; i++) {
                evTotal += parseInt(document.getElementById(evFields[i]).value || 0);
            }
            
            if (evTotal > 510) {
                e.preventDefault();
                alert("' . $lang->petplay_admin_pets_ev_total_exceeded . '");
            }
        });
        </script>';
        
        $page->output_footer();
    }
    
    protected function showEditForm(int $id): void
    {
        global $page, $lang, $db;
        
        // Make sure we have a valid ID
        if ($id <= 0) {
            flash_message($lang->petplay_admin_invalid_id, 'error');
            admin_redirect($this->baseUrl);
        }
        
        // Get the table name from the repository class
        $tableName = constant($this->repositoryClass . '::TABLE_NAME');
        
        // Fetch pet by ID with owner information
        $query = $db->query("
            SELECT 
                p.*,
                po.user_id AS owner_id,
                u.username,
                u.usergroup,
                u.displaygroup
            FROM " . TABLE_PREFIX . $tableName . " p
            LEFT JOIN " . TABLE_PREFIX . "petplay_pet_owners po ON p.id = po.pet_id
            LEFT JOIN " . TABLE_PREFIX . "users u ON po.user_id = u.uid
            WHERE p.id = " . (int)$id
        );
        
        $entity = $db->fetch_array($query);
        
        if (!$entity) {
            flash_message($lang->{"petplay_admin_{$this->entityName}_not_found"}, 'error');
            admin_redirect($this->baseUrl);
        }
        
        $page->output_header($lang->petplay_admin);
        $page->output_nav_tabs($GLOBALS['sub_tabs'], $this->entityName);
        
        // Add required fields note above the form
        echo '<div style="margin-bottom: 10px;"><span style="color: #cc0000;">*</span> ' . $lang->petplay_admin_required_fields . '</div>';
        
        $form = new \Form($this->baseUrl . '&amp;option=save&amp;id=' . $id, 'post', '', true);
        
        // Create a flex container for the main form and stats
        echo '<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">';
        
        // Left column container for main form and moves
        echo '<div style="flex: 1; min-width: 500px;">';
        
        // Main form container
        $form_container = new \FormContainer($lang->{"petplay_admin_{$this->entityName}_edit"});
        
        // Add UUID field at the top (as plain text in monospace font)
        $form_container->output_row(
            $lang->petplay_admin_pets_uuid,
            $lang->petplay_admin_pets_uuid_desc,
            '<div style="font-family: monospace; padding: 5px; background-color: #f5f5f5; border-radius: 3px;">' . 
            htmlspecialchars_uni($entity['uuid']) . 
            '</div>'
        );
        
        // Track if we've added the owner display after species
        $ownerDisplayAdded = false;
        
        foreach ($this->formFields as $field => $config) {
            // Skip IV and EV fields as they will be handled separately
            if ($field === 'individual_values' || $field === 'effort_values') continue;
            
            // Skip owner_id in edit form
            if ($field === 'owner_id') continue;
            
            // Add required field indicator
            $label = $config['label'];
            if ($config['required'] ?? false) {
                $label = '<span style="color: #cc0000;">*</span> ' . $label;
            }
            
            // Use the parent class's form field rendering logic
            $this->renderFormField($form, $form_container, $field, $config, $entity);
            
            // Add owner display after species field
            if ($field === 'species_id' && !$ownerDisplayAdded) {
                $ownerDisplayAdded = true;
                
                // Format the owner's username with proper styling
                $ownerDisplay = '';
                if (!empty($entity['username'])) {
                    $formattedUsername = format_name($entity['username'], $entity['usergroup'], $entity['displaygroup']);
                    $profileLink = build_profile_link($formattedUsername, $entity['owner_id']);
                    $ownerDisplay = $profileLink;
                } else {
                    $ownerDisplay = '<em>' . $lang->petplay_admin_pets_no_owner . '</em>';
                }
                
                $form_container->output_row(
                    $lang->petplay_admin_pets_current_owner,
                    $lang->petplay_admin_pets_current_owner_desc,
                    '<div style="padding: 5px; background-color: #f5f5f5; border-radius: 3px;">' . $ownerDisplay . '</div>'
                );
            }
        }
        
        $form_container->end();
        
        // Moves container (in the same left column)
        $this->renderMovesContainer($form, $lang->petplay_admin_pets_moves, $id);
        
        echo '</div>';
        
        // Right column for stats
        echo '<div style="width: 400px;">';
        
        // Parse existing IV and EV values
        $ivs = json_decode($entity['individual_values'] ?? '{}', true) ?: [];
        $evs = json_decode($entity['effort_values'] ?? '{}', true) ?: [];
        
        // Individual Values
        $this->renderStatsContainer($form, $lang->petplay_admin_pets_individual_values, 'iv', 0, 31, $ivs);
        
        // Effort Values
        $this->renderStatsContainer($form, $lang->petplay_admin_pets_effort_values, 'ev', 0, 252, $evs);
        
        echo '</div>';
        
        // End flex container
        echo '</div>';
        
        $buttons[] = $form->generate_submit_button($lang->petplay_admin_save);
        $buttons[] = $form->generate_reset_button($lang->petplay_admin_cancel);
        
        $form->output_submit_wrapper($buttons);
        
        $form->end();
        
        // Add EV total validation script
        echo '<script type="text/javascript">
        document.querySelector("form").addEventListener("submit", function(e) {
            var evTotal = 0;
            var evFields = ["ev_hp", "ev_attack", "ev_defence", "ev_special_attack", "ev_special_defence", "ev_speed"];
            
            for (var i = 0; i < evFields.length; i++) {
                evTotal += parseInt(document.getElementById(evFields[i]).value || 0);
            }
            
            if (evTotal > 510) {
                e.preventDefault();
                alert("' . $lang->petplay_admin_pets_ev_total_exceeded . '");
            }
        });
        </script>';
        
        $page->output_footer();
    }
    
    protected function renderStatsContainer(\Form $form, string $title, string $prefix, int $min, int $max, array $values = []): void
    {
        global $lang;
        
        $stats_container = new \FormContainer($title);
        
        $stats = [
            'hp' => $lang->petplay_admin_species_stat_hp,
            'attack' => $lang->petplay_admin_species_stat_attack,
            'defence' => $lang->petplay_admin_species_stat_defence,
            'special_attack' => $lang->petplay_admin_species_stat_sp_attack,
            'special_defence' => $lang->petplay_admin_species_stat_sp_defence,
            'speed' => $lang->petplay_admin_species_stat_speed
        ];
        
        foreach ($stats as $stat => $label) {
            $stats_container->output_row(
                $label,
                '',
                $form->generate_numeric_field("{$prefix}_{$stat}", $values[$stat] ?? 0, [
                    'id' => "{$prefix}_{$stat}",
                    'min' => $min,
                    'max' => $max,
                    'style' => 'width: 60px;',
                    'required' => true
                ])
            );
        }
        
        if ($prefix === 'ev') {
            $stats_container->output_row(
                '<strong>' . $lang->petplay_admin_pets_ev_total . '</strong>',
                '<small>' . $lang->petplay_admin_pets_ev_total_desc . '</small>',
                '<div id="ev_total_display">0</div>' .
                '<script type="text/javascript">
                function updateEVTotal() {
                    var total = 0;
                    var evFields = ["ev_hp", "ev_attack", "ev_defence", "ev_special_attack", "ev_special_defence", "ev_speed"];
                    
                    for (var i = 0; i < evFields.length; i++) {
                        total += parseInt(document.getElementById(evFields[i]).value || 0);
                    }
                    
                    var display = document.getElementById("ev_total_display");
                    display.textContent = total;
                    
                    if (total > 510) {
                        display.style.color = "#cc0000";
                    } else {
                        display.style.color = "";
                    }
                }
                
                document.addEventListener("DOMContentLoaded", function() {
                    var evFields = ["ev_hp", "ev_attack", "ev_defence", "ev_special_attack", "ev_special_defence", "ev_speed"];
                    
                    for (var i = 0; i < evFields.length; i++) {
                        document.getElementById(evFields[i]).addEventListener("input", updateEVTotal);
                    }
                    
                    updateEVTotal();
                });
                </script>'
            );
        }
        
        $stats_container->end();
    }
    
    protected function renderFormField(\Form $form, \FormContainer $form_container, string $field, array $config, ?array $entity): void
    {
        // Add required field indicator
        $label = $config['label'];
        if ($config['required'] ?? false) {
            $label = '<span style="color: #cc0000;">*</span> ' . $label;
        }
        
        $value = $entity[$field] ?? ($config['default'] ?? '');
        
        switch ($config['type']) {
            case 'text':
                $form_container->output_row(
                    $label,
                    $config['description'] ?? '',
                    $form->generate_text_box($field, $value, [
                        'id' => $field,
                        'required' => $config['required'] ?? false
                    ])
                );
                break;
            case 'textarea':
                $form_container->output_row(
                    $label,
                    $config['description'] ?? '',
                    $form->generate_text_area($field, $value, [
                        'id' => $field,
                        'required' => $config['required'] ?? false
                    ])
                );
                break;
            case 'checkbox':
                $form_container->output_row(
                    $label,
                    $config['description'] ?? '',
                    $form->generate_check_box($field, 1, '', [
                        'id' => $field,
                        'checked' => (bool)$value
                    ])
                );
                break;
            case 'select':
                $options = $config['options'] ?? [];
                if (is_callable($options)) {
                    $options = $options();
                }
                
                $form_container->output_row(
                    $label,
                    $config['description'] ?? '',
                    $form->generate_select_box($field, $options, $value, [
                        'id' => $field,
                        'required' => $config['required'] ?? false
                    ])
                );
                break;
        }
    }
    
    protected function renderMovesContainer(\Form $form, string $title, ?int $petId): void
    {
        global $lang, $db;
        
        $moves_container = new \FormContainer($title);
        
        // Get all available moves
        $query = $db->query("
            SELECT m.id, m.name, t.name AS type_name, t.colour AS type_colour
            FROM " . TABLE_PREFIX . "petplay_moves m
            LEFT JOIN " . TABLE_PREFIX . "petplay_types t ON m.type_id = t.id
            ORDER BY m.name ASC
        ");
        
        $availableMoves = [];
        while ($move = $db->fetch_array($query)) {
            $availableMoves[$move['id']] = $move['name'] . ' (' . $move['type_name'] . ')';
        }
        
        // Get current moves if editing
        $currentMoves = [];
        if ($petId) {
            $query = $db->query("
                SELECT pm.move_id, pm.slot, m.name, t.name AS type_name
                FROM " . TABLE_PREFIX . "petplay_pet_moves pm
                JOIN " . TABLE_PREFIX . "petplay_moves m ON pm.move_id = m.id
                LEFT JOIN " . TABLE_PREFIX . "petplay_types t ON m.type_id = t.id
                WHERE pm.pet_id = " . (int)$petId . "
                ORDER BY pm.slot ASC
            ");
            
            while ($move = $db->fetch_array($query)) {
                $currentMoves[$move['slot']] = $move['move_id'];
            }
        }
        
        // Create 4 move slots
        for ($slot = 1; $slot <= 4; $slot++) {
            // Manually format the slot label instead of using sprintf in the language string
            $slotLabel = sprintf($lang->petplay_admin_pets_move_slot, $slot);
            
            $moves_container->output_row(
                $slotLabel,
                '',
                $form->generate_select_box("move_slot_{$slot}", [0 => '-- ' . $lang->petplay_admin_pets_no_move . ' --'] + $availableMoves, $currentMoves[$slot] ?? 0, [
                    'id' => "move_slot_{$slot}"
                ])
            );
        }
        
        $moves_container->end();
        
        // Add client-side validation
        $html = <<<HTML
<script type="text/javascript">
$(document).ready(function() {
    // Track selected moves
    var moveSelects = $('select[name^="move_slot_"]');
    
    // Function to update disabled options
    function updateDisabledOptions() {
        var selectedValues = [];
        
        // Collect all selected values
        moveSelects.each(function() {
            var value = $(this).val();
            if (value > 0) {
                selectedValues.push(value);
            }
        });
        
        // For each select, disable options that are selected in other selects
        moveSelects.each(function() {
            var currentSelect = $(this);
            var currentValue = currentSelect.val();
            
            // Enable all options first
            currentSelect.find('option').prop('disabled', false);
            
            // Disable options that are selected in other selects
            selectedValues.forEach(function(value) {
                if (value != currentValue && value > 0) {
                    currentSelect.find('option[value="' + value + '"]').prop('disabled', true);
                }
            });
        });
    }
    
    // Update on page load
    updateDisabledOptions();
    
    // Update when any select changes
    moveSelects.on('change', updateDisabledOptions);
});
</script>
HTML;
        
        echo $html;
    }
    
    protected function saveEntity(array $input): void
    {
        global $db, $lang, $mybb;
        
        // Verify post key for security
        if (!verify_post_check($mybb->post_code)) {
            flash_message($lang->invalid_post_verify_key2, 'error');
            admin_redirect($this->baseUrl);
        }
        
        // Get the table name from the repository class
        $tableName = constant($this->repositoryClass . '::TABLE_NAME');
        
        // Determine if we're editing or adding
        $isEditing = isset($input['id']) && (int)$input['id'] > 0;
        
        // Prepare data for saving
        $data = [];
        foreach ($this->formFields as $field => $config) {
            // Skip owner_id as it will be handled separately
            if ($field === 'owner_id') continue;
            
            // Handle IV and EV fields specially
            if ($field === 'individual_values') {
                $ivs = [
                    'hp' => (int)($input['iv_hp'] ?? 0),
                    'attack' => (int)($input['iv_attack'] ?? 0),
                    'defence' => (int)($input['iv_defence'] ?? 0),
                    'special_attack' => (int)($input['iv_special_attack'] ?? 0),
                    'special_defence' => (int)($input['iv_special_defence'] ?? 0),
                    'speed' => (int)($input['iv_speed'] ?? 0)
                ];
                
                // Validate IV values
                foreach ($ivs as $stat => $value) {
                    if ($value < 0 || $value > 31) {
                        flash_message($lang->sprintf($lang->petplay_admin_pets_invalid_iv, $stat), 'error');
                        admin_redirect($this->baseUrl . '&option=' . ($isEditing ? 'edit&id=' . $input['id'] : 'add'));
                    }
                }
                
                $data[$field] = json_encode($ivs);
                continue;
            }
            
            if ($field === 'effort_values') {
                $evs = [
                    'hp' => (int)($input['ev_hp'] ?? 0),
                    'attack' => (int)($input['ev_attack'] ?? 0),
                    'defence' => (int)($input['ev_defence'] ?? 0),
                    'special_attack' => (int)($input['ev_special_attack'] ?? 0),
                    'special_defence' => (int)($input['ev_special_defence'] ?? 0),
                    'speed' => (int)($input['ev_speed'] ?? 0)
                ];
                
                // Validate EV values
                foreach ($evs as $stat => $value) {
                    if ($value < 0 || $value > 252) {
                        flash_message($lang->sprintf($lang->petplay_admin_pets_invalid_ev, $stat), 'error');
                        admin_redirect($this->baseUrl . '&option=' . ($isEditing ? 'edit&id=' . $input['id'] : 'add'));
                    }
                }
                
                // Validate total EVs
                $evTotal = array_sum($evs);
                if ($evTotal > 510) {
                    flash_message($lang->petplay_admin_pets_ev_total_exceeded, 'error');
                    admin_redirect($this->baseUrl . '&option=' . ($isEditing ? 'edit&id=' . $input['id'] : 'add'));
                }
                
                $data[$field] = json_encode($evs);
                continue;
            }
            
            if ($config['type'] === 'checkbox') {
                // Properly handle boolean values for PostgreSQL
                $data[$field] = isset($input[$field]) ? true : false;
            } else {
                // For text fields and selects, ensure they're properly handled
                $value = $input[$field] ?? ($config['default'] ?? null);
                
                // Special handling for nullable foreign keys
                if ($config['type'] === 'select' && ($value === '' || $value === '0')) {
                    $data[$field] = null;
                } else {
                    // If the value is empty and the field allows NULL, set it to NULL
                    if ($value === '' && !($config['required'] ?? false)) {
                        $data[$field] = null;
                    } else {
                        $data[$field] = $value;
                    }
                }
            }
        }
        
        // Validate required fields
        foreach ($this->formFields as $field => $config) {
            // Skip owner_id validation when editing
            if ($field === 'owner_id' && $isEditing) continue;
            
            if (($config['required'] ?? false) && 
                (
                    !isset($data[$field]) || 
                    ($data[$field] === '' && $config['type'] !== 'checkbox')
                ) &&
                $field !== 'owner_id' // Skip owner_id as it's handled separately
            ) {
                flash_message(sprintf($lang->petplay_admin_field_required, $config['label']), 'error');
                admin_redirect($this->baseUrl . '&option=' . ($isEditing ? 'edit&id=' . $input['id'] : 'add'));
            }
        }
        
        // Validate owner_id only for new pets, not when editing
        if (!$isEditing) {
            $owner_id = (int)($input['owner_id'] ?? 0);
            if ($owner_id <= 0) {
                flash_message(sprintf($lang->petplay_admin_field_required, $lang->petplay_admin_pets_owner), 'error');
                admin_redirect($this->baseUrl . '&option=add');
            }
            
            // Check if user exists
            $query = $db->simple_select('users', 'uid', 'uid = ' . $owner_id);
            if ($db->num_rows($query) == 0) {
                flash_message($lang->petplay_admin_pets_invalid_owner, 'error');
                admin_redirect($this->baseUrl . '&option=add');
            }
        }
        
        // Begin transaction
        $db->write_query('BEGIN');
        
        try {
            // Save the pet entity
            if ($isEditing) {
                // Update existing pet
                $id = (int)$input['id'];
                
                // Check if pet exists
                $query = $db->simple_select($tableName, '*', 'id = ' . $id);
                if ($db->num_rows($query) == 0) {
                    throw new \Exception($lang->{"petplay_admin_{$this->entityName}_not_found"});
                }
                
                // Build update query
                $updateData = [];
                foreach ($data as $field => $value) {
                    if ($value === null) {
                        $updateData[$field] = 'NULL';
                    } elseif (is_bool($value)) {
                        $updateData[$field] = $value ? 'TRUE' : 'FALSE';
                    } else {
                        $updateData[$field] = "'" . $db->escape_string($value) . "'";
                    }
                }
                
                // Execute update query
                $updateQuery = "UPDATE " . TABLE_PREFIX . $tableName . " SET ";
                $updateParts = [];
                foreach ($updateData as $field => $value) {
                    $updateParts[] = $field . "=" . $value;
                }
                $updateQuery .= implode(', ', $updateParts);
                $updateQuery .= " WHERE id = " . $id;
                
                $db->query($updateQuery);
                
                // We no longer update the owner when editing a pet
                // Owner transfers will be handled in a separate UI
                
                $message = $lang->{"petplay_admin_{$this->entityName}_edited"};
            } else {
                // Create new pet
                $insertFields = array_keys($data);
                $insertValues = [];
                
                foreach ($data as $value) {
                    if ($value === null) {
                        $insertValues[] = 'NULL';
                    } elseif (is_bool($value)) {
                        $insertValues[] = $value ? 'TRUE' : 'FALSE';
                    } else {
                        $insertValues[] = "'" . $db->escape_string($value) . "'";
                    }
                }
                
                $insertQuery = "INSERT INTO " . TABLE_PREFIX . $tableName . " (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertValues) . ") RETURNING id";
                $result = $db->query($insertQuery);
                $pet = $db->fetch_array($result);
                $id = $pet['id'];
                
                // Create initial ownership record
                $insertOwnerQuery = "INSERT INTO " . TABLE_PREFIX . "petplay_pet_owners 
                                    (pet_id, user_id) 
                                    VALUES (" . (int)$id . ", " . (int)$owner_id . ")";
                $db->write_query($insertOwnerQuery);

                // Record initial ownership in history
                $insertHistoryQuery = "INSERT INTO " . TABLE_PREFIX . "petplay_pet_ownership_history 
                                      (pet_id, from_user_id, to_user_id, transfer_type, notes) 
                                      VALUES (" . (int)$id . ", NULL, " . (int)$owner_id . ", 'initial', 'Initial ownership assigned by admin')";
                $db->write_query($insertHistoryQuery);
                
                $message = $lang->{"petplay_admin_{$this->entityName}_added"};
            }
            
            // Handle pet moves
            if (isset($id)) {
                // Delete existing moves for this pet
                $db->delete_query('petplay_pet_moves', 'pet_id = ' . (int)$id);
                
                // Track moves to prevent duplicates
                $selectedMoves = [];
                $usedSlots = [];
                $hasDuplicates = false;
                
                // First validate all moves
                for ($slot = 1; $slot <= 4; $slot++) {
                    $moveId = (int)($input["move_slot_{$slot}"] ?? 0);
                    
                    // Skip if no move selected
                    if ($moveId <= 0) {
                        continue;
                    }
                    
                    // Check for duplicates
                    if (in_array($moveId, $selectedMoves)) {
                        $hasDuplicates = true;
                        break;
                    }
                    
                    // Add move to tracking array
                    $selectedMoves[] = $moveId;
                    $usedSlots[] = $slot;
                }
                
                // If duplicates found, show error and redirect
                if ($hasDuplicates) {
                    flash_message($lang->petplay_admin_pets_duplicate_move, 'error');
                    admin_redirect($this->baseUrl . '&option=' . ($isEditing ? 'edit&id=' . $input['id'] : 'add'));
                }
                
                // If validation passes, insert the moves
                foreach ($selectedMoves as $index => $moveId) {
                    $slot = $usedSlots[$index];
                    $insertMoveQuery = "INSERT INTO " . TABLE_PREFIX . "petplay_pet_moves 
                                      (pet_id, move_id, slot) 
                                      VALUES (" . (int)$id . ", " . $moveId . ", " . $slot . ")";
                    $db->write_query($insertMoveQuery);
                }
            }
            
            // Commit transaction
            $db->write_query('COMMIT');
            
            flash_message($message, 'success');
            admin_redirect($this->baseUrl);
        } catch (\Exception $e) {
            // Rollback transaction on error
            $db->write_query('ROLLBACK');
            
            // Handle constraint violations
            if (strpos($e->getMessage(), 'unique_pet_move') !== false || 
                strpos($e->getMessage(), 'unique_pet_slot') !== false) {
                flash_message($lang->petplay_admin_pets_duplicate_move, 'error');
                admin_redirect($this->baseUrl . '&option=' . ($isEditing ? 'edit&id=' . $input['id'] : 'add'));
            }
            
            flash_message($e->getMessage(), 'error');
            admin_redirect($this->baseUrl . '&option=' . ($isEditing ? 'edit&id=' . $input['id'] : 'add'));
        }
    }
}

// Custom list manager for pets to handle the special rendering requirements
class PetsListManager extends ListManager
{
    protected function renderCell(string $column, array $entity): void
    {
        switch ($column) {
            case 'species':
                $this->renderSpeciesCell($entity);
                break;
                
            case 'nickname':
                $this->renderNicknameCell($entity);
                break;
                
            case 'gender':
                $this->renderGenderCell($entity);
                break;
                
            case 'owner':
                $this->renderOwnerCell($entity);
                break;
                
            case 'capsule':
                $this->renderCapsuleCell($entity);
                break;
                
            default:
                parent::renderCell($column, $entity);
        }
    }
    
    protected function renderSpeciesCell(array $entity): void
    {
        $content = '<div style="display: flex; align-items: center;">';
        
        if (!empty($entity['species_sprite'])) {
            $content .= '<img src="/' . htmlspecialchars_uni($entity['species_sprite']) . '" 
                         alt="' . htmlspecialchars_uni($entity['species_name']) . '" 
                         style="max-width: 40px; max-height: 40px; margin-right: 10px;">';
        }
        
        $content .= htmlspecialchars_uni($entity['species_name']);
        
        if ($entity['is_shiny']) {
            $content .= ' <i class="fa-solid fa-sparkles" style="color: gold;" title="Shiny"></i>';
        }
        
        $content .= '</div>';
        
        $this->table->construct_cell($content);
    }
    
    protected function renderNicknameCell(array $entity): void
    {
        if (!empty($entity['nickname'])) {
            $this->table->construct_cell(htmlspecialchars_uni($entity['nickname']));
        } else {
            $this->table->construct_cell('<em>-</em>');
        }
    }
    
    protected function renderGenderCell(array $entity): void
    {
        $gender = $entity['gender'] ?? 'unknown';
        
        switch ($gender) {
            case 'male':
                $icon = '<i class="fa-solid fa-mars" style="color: #3498db;"></i>';
                break;
            case 'female':
                $icon = '<i class="fa-solid fa-venus" style="color: #e74c3c;"></i>';
                break;
            default:
                $icon = '<i class="fa-solid fa-question" style="color: #95a5a6;"></i>';
        }
        
        $this->table->construct_cell($icon, ['class' => 'align_center']);
    }
    
    protected function renderOwnerCell(array $entity): void
    {
        global $db;
        
        if (!empty($entity['user_id'])) {
            // Get user's formatted profile link
            $user = format_name($entity['username'], $entity['usergroup'], $entity['displaygroup']);
            $profileLink = build_profile_link($user, $entity['user_id']);
            
            $this->table->construct_cell($profileLink);
        } else {
            $this->table->construct_cell('<em>' . $lang->petplay_admin_pets_no_owner . '</em>');
        }
    }
    
    protected function renderCapsuleCell(array $entity): void
    {
        if (!empty($entity['capsule_sprite'])) {
            $this->table->construct_cell(
                '<div style="text-align: center;">
                    <img src="/' . htmlspecialchars_uni($entity['capsule_sprite']) . '" 
                         alt="' . htmlspecialchars_uni($entity['capsule_name']) . '" 
                         title="' . htmlspecialchars_uni($entity['capsule_name']) . '"
                         style="max-width: 40px; max-height: 40px;">
                </div>'
            );
        } else {
            $this->table->construct_cell(
                '<div style="text-align: center;">' . 
                htmlspecialchars_uni($entity['capsule_name']) . 
                '</div>'
            );
        }
    }
    
    protected function isBooleanColumn(string $column): bool
    {
        return $column === 'is_shiny' || parent::isBooleanColumn($column);
    }
}
