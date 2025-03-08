<?php

declare(strict_types=1);

namespace petplay;

class AcpEntityManagementController
{
    protected string $entityName;
    protected string $repositoryClass;
    protected array $formFields = [];
    protected array $listColumns = [];
    protected string $baseUrl;
    
    public function __construct(
        string $entityName,
        string $repositoryClass,
        array $formFields,
        array $listColumns,
        string $baseUrl
    ) {
        $this->entityName = $entityName;
        $this->repositoryClass = $repositoryClass;
        $this->formFields = $formFields;
        $this->listColumns = $listColumns;
        $this->baseUrl = $baseUrl;
    }
    
    public function handleRequest(array $input): void
    {
        $option = $input['option'] ?? 'list';
        
        switch ($option) {
            case 'add':
                $this->showAddForm();
                break;
            case 'edit':
                $this->showEditForm((int)($input['id'] ?? 0));
                break;
            case 'save':
                $this->saveEntity($input);
                break;
            case 'delete':
                $this->deleteEntity((int)($input['id'] ?? 0));
                break;
            case 'list':
            default:
                $this->showList();
                break;
        }
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
        
        // Create and render the list
        $listManager = new ListManager($this->entityName, $this->listColumns, $this->baseUrl, $db);
        $listManager->render($tableName, $entities);
        
        $page->output_footer();
    }
    
    protected function fetchEntities(string $tableName): array
    {
        global $db;
        
        if ($this->entityName === 'species') {
            $query = $db->query("
                SELECT 
                    s.*, 
                    t1.name as primary_type_name, 
                    t1.colour as primary_type_colour, 
                    t1.sprite_path as primary_type_sprite,
                    t2.name as secondary_type_name, 
                    t2.colour as secondary_type_colour, 
                    t2.sprite_path as secondary_type_sprite
                FROM " . TABLE_PREFIX . $tableName . " s
                LEFT JOIN " . TABLE_PREFIX . "petplay_types t1 ON s.type_primary_id = t1.id
                LEFT JOIN " . TABLE_PREFIX . "petplay_types t2 ON s.type_secondary_id = t2.id
                ORDER BY s.name ASC
            ");
        } elseif ($this->entityName === 'moves') {
            $query = $db->query("
                SELECT 
                    m.*, 
                    t.name as type_name, 
                    t.colour as type_colour, 
                    t.sprite_path as type_sprite
                FROM " . TABLE_PREFIX . $tableName . " m
                LEFT JOIN " . TABLE_PREFIX . "petplay_types t ON m.type_id = t.id
                ORDER BY m.name ASC
            ");
        } else {
            $query = $db->simple_select($tableName, '*', '', ['order_by' => 'name']);
        }
        
        $entities = [];
        while ($entity = $db->fetch_array($query)) {
            $entities[] = $entity;
        }
        
        return $entities;
    }
    
    protected function showAddForm(): void
    {
        global $page, $lang;
        
        $page->output_header($lang->petplay_admin);
        $page->output_nav_tabs($GLOBALS['sub_tabs'], $this->entityName);
        
        // Add required fields note above the form
        echo '<div style="margin-bottom: 10px;"><span style="color: #cc0000;">*</span> ' . $lang->petplay_admin_required_fields . '</div>';
        
        $form = new \Form($this->baseUrl . '&amp;option=save', 'post', '', true);
        
        // Create a flex container
        echo '<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">';
        
        // Main container (flex-grow to take available space)
        echo '<div style="flex: 1; min-width: 500px;">';
        $form_container = new \FormContainer($lang->{"petplay_admin_{$this->entityName}_add"});
        
        foreach ($this->formFields as $field => $config) {
            // Skip base_stats as it will be handled separately
            if ($field === 'base_stats') continue;
            
            // Add required field indicator
            $label = $config['label'];
            if ($config['required'] ?? false) {
                $label = '<span style="color: #cc0000;">*</span> ' . $label;
            }
            
            switch ($config['type']) {
                case 'text':
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $form->generate_text_box($field, $config['default'] ?? '', [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'textarea':
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $form->generate_text_area($field, $config['default'] ?? '', [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'color':
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        '<input type="color" name="' . $field . '" id="' . $field . '" value="' . ($config['default'] ?? '#FFFFFF') . '"' . 
                        (($config['required'] ?? false) ? ' required="required"' : '') . 
                        ' style="width: 50px; height: 25px; padding: 0; cursor: pointer;" />'
                    );
                    break;
                case 'checkbox':
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $form->generate_check_box($field, 1, '', [
                            'id' => $field,
                            'checked' => $config['default'] ?? false
                        ])
                    );
                    break;
                case 'select':
                    $options = $config['options'] ?? [];
                    if (is_callable($options)) {
                        $options = $options();
                    }
                    
                    $default = $config['default'] ?? '';
                    if (is_callable($default)) {
                        $default = $default();
                    }
                    
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $form->generate_select_box($field, $options, $default, [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'file':
                    $previewScript = '
                    <script type="text/javascript">
                    function previewFile_' . $field . '(input) {
                        var preview = document.getElementById("preview_' . $field . '");
                        var previewContainer = document.getElementById("preview_container_' . $field . '");
                        var file = input.files[0];
                        var reader = new FileReader();
                        
                        reader.onloadend = function() {
                            preview.src = reader.result;
                            previewContainer.style.display = "block";
                        }
                        
                        if (file) {
                            reader.readAsDataURL(file);
                        } else {
                            preview.src = "";
                            previewContainer.style.display = "none";
                        }
                    }
                    
                    function removeFile_' . $field . '() {
                        // Clear the file input
                        document.getElementById("' . $field . '").value = "";
                        
                        // Hide the preview
                        document.getElementById("preview_container_' . $field . '").style.display = "none";
                        
                        // Add a hidden input to signal file removal
                        document.getElementById("remove_' . $field . '").value = "1";
                        
                        return false;
                    }
                    </script>';
                    
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $previewScript . '
                        <input type="file" name="' . $field . '" id="' . $field . '" 
                            onchange="previewFile_' . $field . '(this)" 
                            ' . (($config['required'] ?? false) ? 'required="required"' : '') . '>
                        <input type="hidden" name="remove_' . $field . '" id="remove_' . $field . '" value="0">
                        
                        <div id="preview_container_' . $field . '" style="margin-top: 10px; display: none;">
                            <div style="display: flex; align-items: center;">
                                <div style="margin-right: 10px;">
                                    <img id="preview_' . $field . '" src="" alt="Preview" style="max-width: 100px; max-height: 100px;">
                                </div>
                                <div>
                                    <a href="#" onclick="return removeFile_' . $field . '();" style="color: #cc0000;">
                                        <i class="fa-solid fa-trash-can"></i> ' . $lang->petplay_admin_remove_file . '
                                    </a>
                                </div>
                            </div>
                        </div>'
                    );
                    break;
            }
        }
        
        $form_container->end();
        echo '</div>';
        
        // Stats container (fixed width)
        if (isset($this->formFields['base_stats'])) {
            echo '<div style="width: 400px;">';
            $stats_container = new \FormContainer($lang->petplay_admin_species_base_stats);
            
            $stats = [
                'hp' => $lang->petplay_admin_species_stat_hp,
                'attack' => $lang->petplay_admin_species_stat_attack,
                'defence' => $lang->petplay_admin_species_stat_defence,
                'sp_attack' => $lang->petplay_admin_species_stat_sp_attack,
                'sp_defence' => $lang->petplay_admin_species_stat_sp_defence,
                'speed' => $lang->petplay_admin_species_stat_speed
            ];
            
            foreach ($stats as $stat => $label) {
                $stats_container->output_row(
                    $label,
                    '',
                    $form->generate_numeric_field("stats[{$stat}]", 100, [
                        'id' => "stat_{$stat}",
                        'min' => 1,
                        'max' => 255,
                        'style' => 'width: 60px;',
                        'required' => true
                    ])
                );
            }
            
            $stats_container->end();
            echo '</div>';
        }
        
        // End flex container
        echo '</div>';
        
        $buttons[] = $form->generate_submit_button($lang->petplay_admin_save);
        $buttons[] = $form->generate_reset_button($lang->petplay_admin_cancel);
        
        $form->output_submit_wrapper($buttons);
        
        $form->end();
        
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
        
        // Get repository instance
        $repository = call_user_func([$this->repositoryClass, 'with'], $db);
        
        // Get the table name from the repository class
        $tableName = constant($this->repositoryClass . '::TABLE_NAME');
        
        // Fetch entity by ID using direct query
        $query = $db->simple_select($tableName, '*', 'id = ' . (int)$id);
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
        
        // Create a flex container
        echo '<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">';
        
        // Main container (flex-grow to take available space)
        echo '<div style="flex: 1; min-width: 500px;">';
        $form_container = new \FormContainer($lang->{"petplay_admin_{$this->entityName}_edit"});
        
        foreach ($this->formFields as $field => $config) {
            // Skip base_stats as it will be handled separately
            if ($field === 'base_stats') continue;
            
            // Add required field indicator
            $label = $config['label'];
            if ($config['required'] ?? false) {
                $label = '<span style="color: #cc0000;">*</span> ' . $label;
            }
            
            switch ($config['type']) {
                case 'text':
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $form->generate_text_box($field, $entity[$field] ?? ($config['default'] ?? ''), [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'textarea':
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $form->generate_text_area($field, $entity[$field] ?? ($config['default'] ?? ''), [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'color':
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        '<input type="color" name="' . $field . '" id="' . $field . '" value="' . 
                        ($entity[$field] ?? ($config['default'] ?? '#FFFFFF')) . '"' . 
                        (($config['required'] ?? false) ? ' required="required"' : '') . 
                        ' style="width: 50px; height: 25px; padding: 0; cursor: pointer;" />'
                    );
                    break;
                case 'checkbox':
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $form->generate_check_box($field, 1, '', [
                            'id' => $field,
                            'checked' => (bool)($entity[$field] ?? ($config['default'] ?? false))
                        ])
                    );
                    break;
                case 'select':
                    $options = $config['options'] ?? [];
                    // If options is a callable, execute it to get the actual options
                    if (is_callable($options)) {
                        $options = $options();
                    }
                    
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $form->generate_select_box($field, $options, $entity[$field] ?? ($config['default'] ?? ''), [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'file':
                    $previewScript = '
                    <script type="text/javascript">
                    function previewFile_' . $field . '(input) {
                        var preview = document.getElementById("preview_' . $field . '");
                        var previewContainer = document.getElementById("preview_container_' . $field . '");
                        var file = input.files[0];
                        var reader = new FileReader();
                        
                        reader.onloadend = function() {
                            preview.src = reader.result;
                            previewContainer.style.display = "block";
                        }
                        
                        if (file) {
                            reader.readAsDataURL(file);
                        } else {
                            preview.src = "";
                            previewContainer.style.display = "none";
                        }
                    }
                    
                    function removeFile_' . $field . '() {
                        // Clear the file input
                        document.getElementById("' . $field . '").value = "";
                        
                        // Hide the preview
                        document.getElementById("preview_container_' . $field . '").style.display = "none";
                        
                        // Hide the current file preview if it exists
                        var currentPreview = document.getElementById("current_' . $field . '");
                        if (currentPreview) {
                            currentPreview.style.display = "none";
                        }
                        
                        // Add a hidden input to signal file removal
                        document.getElementById("remove_' . $field . '").value = "1";
                        
                        return false;
                    }
                    </script>';
                    
                    $currentPreview = '';
                    if (!empty($entity[$field])) {
                        $currentPreview = '<div id="current_' . $field . '" style="margin-top: 10px; display: flex; align-items: center;">
                            <div style="margin-right: 10px;">
                                <img src="/' . htmlspecialchars_uni($entity[$field]) . '" alt="Preview" style="max-width: 100px; max-height: 100px;">
                            </div>
                            <div>
                                <small>' . $lang->petplay_admin_current_file . '</small><br>
                                <a href="#" onclick="return removeFile_' . $field . '();" style="color: #cc0000;">
                                    <i class="fa-solid fa-trash-can"></i> ' . $lang->petplay_admin_remove_file . '
                                </a>
                            </div>
                        </div>';
                    }
                    
                    $form_container->output_row(
                        $label,
                        $config['description'] ?? '',
                        $previewScript . '
                        <input type="file" name="' . $field . '" id="' . $field . '" 
                            onchange="previewFile_' . $field . '(this)" 
                            ' . (($config['required'] ?? false) ? 'required="required"' : '') . '>
                        <input type="hidden" name="remove_' . $field . '" id="remove_' . $field . '" value="0">
                        
                        <div id="preview_container_' . $field . '" style="margin-top: 10px; display: none;">
                            <div style="display: flex; align-items: center;">
                                <div style="margin-right: 10px;">
                                    <img id="preview_' . $field . '" src="" alt="Preview" style="max-width: 100px; max-height: 100px;">
                                </div>
                                <div>
                                    <a href="#" onclick="return removeFile_' . $field . '();" style="color: #cc0000;">
                                        <i class="fa-solid fa-trash-can"></i> ' . $lang->petplay_admin_remove_file . '
                                    </a>
                                </div>
                            </div>
                        </div>' . 
                        $currentPreview
                    );
                    break;
            }
        }
        
        $form_container->end();
        echo '</div>';
        
        // Stats container (fixed width)
        if (isset($this->formFields['base_stats'])) {
            echo '<div style="width: 400px;">';
            $stats_container = new \FormContainer($lang->petplay_admin_species_base_stats);
            
            $stats = [
                'hp' => $lang->petplay_admin_species_stat_hp,
                'attack' => $lang->petplay_admin_species_stat_attack,
                'defence' => $lang->petplay_admin_species_stat_defence,
                'sp_attack' => $lang->petplay_admin_species_stat_sp_attack,
                'sp_defence' => $lang->petplay_admin_species_stat_sp_defence,
                'speed' => $lang->petplay_admin_species_stat_speed
            ];
            
            // Parse existing base_stats JSON if it exists
            $existing_stats = [];
            if (!empty($entity['base_stats'])) {
                $existing_stats = json_decode($entity['base_stats'], true) ?? [];
            }
            
            foreach ($stats as $stat => $label) {
                $stats_container->output_row(
                    $label,
                    '',
                    $form->generate_numeric_field("stats[{$stat}]", $existing_stats[$stat] ?? 100, [
                        'id' => "stat_{$stat}",
                        'min' => 1,
                        'max' => 255,
                        'style' => 'width: 60px;',
                        'required' => true
                    ])
                );
            }
            
            $stats_container->end();
            echo '</div>';
        }
        
        // End flex container
        echo '</div>';
        
        $buttons[] = $form->generate_submit_button($lang->petplay_admin_save);
        $buttons[] = $form->generate_reset_button($lang->petplay_admin_cancel);
        
        $form->output_submit_wrapper($buttons);
        
        $form->end();
        
        $page->output_footer();
    }
    
    protected function saveEntity(array $input): void
    {
        global $db, $lang, $mybb;
        
        // Verify post key for security
        if (!verify_post_check($mybb->post_code)) {
            flash_message($lang->invalid_post_verify_key2, 'error');
            admin_redirect($this->baseUrl);
        }
        
        // Get repository instance
        $repository = call_user_func([$this->repositoryClass, 'with'], $db);
        
        // Get the table name from the repository class
        $tableName = constant($this->repositoryClass . '::TABLE_NAME');
        
        // Prepare data for saving
        $data = [];
        foreach ($this->formFields as $field => $config) {
            if ($config['type'] === 'checkbox') {
                // Properly handle boolean values for PostgreSQL
                $data[$field] = isset($input[$field]) ? true : false;
            } elseif ($config['type'] === 'file') {
                // Handle file uploads
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    // Create upload directory if it doesn't exist
                    $uploadDir = MYBB_ROOT . ($config['upload_dir'] ?? 'uploads/petplay/');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Get file extension
                    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                    
                    // Generate unique filename using:
                    // - timestamp
                    // - entity type (species, types, etc)
                    // - field name (sprite, sprite_shiny, etc)
                    // - random string
                    $uniqueId = bin2hex(random_bytes(8)); // 16 characters of randomness
                    $fileName = sprintf(
                        '%d_%s_%s_%s.%s',
                        time(),
                        $this->entityName,
                        $field,
                        $uniqueId,
                        $extension
                    );
                    
                    $fullPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $fullPath)) {
                        $data[$field] = ($config['upload_dir'] ?? 'uploads/petplay/') . $fileName;
                    }
                } elseif (isset($input['id'])) {
                    // Check if the user wants to remove the file
                    if (isset($input['remove_' . $field]) && $input['remove_' . $field] == 1) {
                        // Set the field to NULL to remove the file reference
                        $data[$field] = null;
                        
                        /*
                        $query = $db->simple_select($tableName, $field, 'id = ' . (int)$input['id']);
                        $existingFile = $db->fetch_field($query, $field);
                        if ($existingFile && file_exists(MYBB_ROOT . $existingFile)) {
                            @unlink(MYBB_ROOT . $existingFile);
                        }
                        */
                    } else {
                        // If editing and no new file uploaded and not removing, keep existing file
                        $query = $db->simple_select($tableName, $field, 'id = ' . (int)$input['id']);
                        $existingValue = $db->fetch_field($query, $field);
                        if ($existingValue) {
                            $data[$field] = $existingValue;
                        }
                    }
                    continue;
                }
            } else {
                // For text fields and selects, ensure they're properly handled
                $value = $input[$field] ?? ($config['default'] ?? null);
                
                // Special handling for nullable foreign keys (like secondary type)
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
        
        // Handle base_stats separately if it exists in formFields
        if (isset($this->formFields['base_stats']) && isset($input['stats'])) {
            $stats = [
                'hp' => (int)($input['stats']['hp'] ?? 100),
                'attack' => (int)($input['stats']['attack'] ?? 100),
                'defence' => (int)($input['stats']['defence'] ?? 100),
                'sp_attack' => (int)($input['stats']['sp_attack'] ?? 100),
                'sp_defence' => (int)($input['stats']['sp_defence'] ?? 100),
                'speed' => (int)($input['stats']['speed'] ?? 100)
            ];
            
            // Validate stat values
            foreach ($stats as $stat => $value) {
                if ($value < 1 || $value > 255) {
                    flash_message($lang->sprintf($lang->petplay_admin_invalid_stat_value, $stat), 'error');
                    admin_redirect($this->baseUrl . '&option=' . (isset($input['id']) ? 'edit&id=' . $input['id'] : 'add'));
                }
            }
            
            // Add stats to data array as JSON
            $data['base_stats'] = json_encode($stats);
        }
        
        // Validate required fields
        foreach ($this->formFields as $field => $config) {
            if (($config['required'] ?? false) && 
                (
                    !isset($data[$field]) || 
                    ($data[$field] === '' && $config['type'] !== 'checkbox')
                )
            ) {
                // Fix the error message format
                flash_message(sprintf($lang->petplay_admin_field_required, $config['label']), 'error');
                admin_redirect($this->baseUrl . '&option=' . (isset($input['id']) ? 'edit&id=' . $input['id'] : 'add'));
            }
        }
        
        // Save the entity
        if (isset($input['id']) && (int)$input['id'] > 0) {
            // Update existing entity using direct query
            $id = (int)$input['id'];
            
            // Check if entity exists
            $query = $db->simple_select($tableName, '*', 'id = ' . $id);
            if ($db->num_rows($query) == 0) {
                flash_message($lang->{"petplay_admin_{$this->entityName}_not_found"}, 'error');
                admin_redirect($this->baseUrl);
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
            
            flash_message($lang->{"petplay_admin_{$this->entityName}_edited"}, 'success');
        } else {
            // Create new entity using direct query
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
            
            $insertQuery = "INSERT INTO " . TABLE_PREFIX . $tableName . " (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertValues) . ")";
            $db->query($insertQuery);
            
            flash_message($lang->{"petplay_admin_{$this->entityName}_added"}, 'success');
        }
        
        admin_redirect($this->baseUrl);
    }
    
    protected function deleteEntity(int $id): void
    {
        global $db, $lang, $mybb;
        
        // Make sure we have a valid ID
        if ($id <= 0) {
            flash_message($lang->petplay_admin_invalid_id, 'error');
            admin_redirect($this->baseUrl);
        }
        
        // Verify post key for security
        if (!isset($mybb->input['my_post_key']) || !verify_post_check($mybb->input['my_post_key'])) {
            flash_message($lang->invalid_post_verify_key2, 'error');
            admin_redirect($this->baseUrl);
        }
        
        // Get repository instance
        $repository = call_user_func([$this->repositoryClass, 'with'], $db);
        
        // Get the table name from the repository class
        $tableName = constant($this->repositoryClass . '::TABLE_NAME');
        
        // Check if entity exists
        $query = $db->simple_select($tableName, '*', 'id = ' . (int)$id);
        
        if ($db->num_rows($query) == 0) {
            flash_message($lang->{"petplay_admin_{$this->entityName}_not_found"}, 'error');
            admin_redirect($this->baseUrl);
        }
        
        // Delete the entity
        $db->delete_query($tableName, 'id = ' . (int)$id);
        
        flash_message($lang->{"petplay_admin_{$this->entityName}_deleted"}, 'success');
        admin_redirect($this->baseUrl);
    }
} 
