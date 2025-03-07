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
        
        echo '<div style="margin-bottom: 10px; overflow: hidden;">
            <div class="float_left">
                <a href="' . $this->baseUrl . '&amp;option=add" class="button">
                    <i class="fa-solid fa-plus"></i> ' . $lang->{"petplay_admin_{$this->entityName}_add"} . '
                </a>
            </div>
            <div style="clear: both;"></div>
        </div>';
        
        // Create table for listing entities
        $table = new \Table();
        
        // Set up table headers
        foreach ($this->listColumns as $column => $config) {
            $table->construct_header($config['label'], ['width' => $config['width']]);
        }
        
        // Get repository instance
        $repository = call_user_func([$this->repositoryClass, 'with'], $db);
        
        // Fetch all entities
        $query = $repository->get();
        
        while ($entity = $db->fetch_array($query)) {
            foreach ($this->listColumns as $column => $config) {
                if ($column === 'actions') {
                    // Actions column
                    $table->construct_cell(
                        '<a href="' . $this->baseUrl . '&amp;option=edit&amp;id=' . (int)$entity['id'] . '" class="button">
                            <i class="fa-solid fa-edit"></i>
                        </a>
                        <a href="' . $this->baseUrl . '&amp;option=delete&amp;id=' . (int)$entity['id'] . '&amp;my_post_key=' . $mybb->post_code . '" class="button" onclick="return confirm(\'' . $lang->{"petplay_admin_{$this->entityName}_delete_confirm"} . '\');">
                            <i class="fa-solid fa-trash"></i>
                        </a>',
                        ['class' => 'align_center']
                    );
                } elseif ($column === 'colour' || $column === 'color') {
                    // Special handling for color column
                    $table->construct_cell(
                        '<div style="display: flex; align-items: center;">
                            <div style="width: 20px; height: 20px; background-color: ' . htmlspecialchars_uni($entity[$column]) . '; margin-right: 10px; border: 1px solid #ccc;"></div>
                            ' . htmlspecialchars_uni($entity[$column]) . '
                        </div>'
                    );
                } elseif ($column === 'is_default' || substr($column, 0, 3) === 'is_') {
                    // Special handling for boolean column
                    $icon = $entity[$column] ? 'fa-solid fa-check text-success' : 'fa-solid fa-xmark text-danger';
                    $table->construct_cell('<i class="' . $icon . '"></i>', ['class' => 'align_center']);
                } else {
                    $table->construct_cell(htmlspecialchars_uni($entity[$column]));
                }
            }
            
            $table->construct_row();
        }
        
        if ($table->num_rows() == 0) {
            $table->construct_cell($lang->{'petplay_admin_no_' . $this->entityName}, ['colspan' => count($this->listColumns)]);
            $table->construct_row();
        }
        
        $table->output($lang->{'petplay_admin_' . $this->entityName});
        
        $page->output_footer();
    }
    
    protected function showAddForm(): void
    {
        global $page, $lang;
        
        $page->output_header($lang->petplay_admin);
        $page->output_nav_tabs($GLOBALS['sub_tabs'], $this->entityName);
        
        $form = new \Form($this->baseUrl . '&amp;option=save', 'post');
        
        $form_container = new \FormContainer($lang->{"petplay_admin_{$this->entityName}_add"});
        
        foreach ($this->formFields as $field => $config) {
            switch ($config['type']) {
                case 'text':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_text_box($field, $config['default'] ?? '', [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'textarea':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_text_area($field, $config['default'] ?? '', [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'color':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        '<input type="color" name="' . $field . '" id="' . $field . '" value="' . ($config['default'] ?? '#FFFFFF') . '"' . 
                        (($config['required'] ?? false) ? ' required="required"' : '') . 
                        ' style="width: 50px; height: 25px; padding: 0; cursor: pointer;" />'
                    );
                    break;
                case 'checkbox':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_check_box($field, 1, '', [
                            'id' => $field,
                            'checked' => $config['default'] ?? false
                        ])
                    );
                    break;
                case 'select':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_select_box($field, $config['options'] ?? [], $config['default'] ?? '', [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'file':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_file_upload_box($field, [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
            }
        }
        
        $form_container->end();
        
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
        
        $form = new \Form($this->baseUrl . '&amp;option=save&amp;id=' . $id, 'post');
        
        $form_container = new \FormContainer($lang->{"petplay_admin_{$this->entityName}_edit"});
        
        foreach ($this->formFields as $field => $config) {
            switch ($config['type']) {
                case 'text':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_text_box($field, $entity[$field] ?? ($config['default'] ?? ''), [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'textarea':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_text_area($field, $entity[$field] ?? ($config['default'] ?? ''), [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'color':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        '<input type="color" name="' . $field . '" id="' . $field . '" value="' . 
                        ($entity[$field] ?? ($config['default'] ?? '#FFFFFF')) . '"' . 
                        (($config['required'] ?? false) ? ' required="required"' : '') . 
                        ' style="width: 50px; height: 25px; padding: 0; cursor: pointer;" />'
                    );
                    break;
                case 'checkbox':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_check_box($field, 1, '', [
                            'id' => $field,
                            'checked' => (bool)($entity[$field] ?? ($config['default'] ?? false))
                        ])
                    );
                    break;
                case 'select':
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_select_box($field, $config['options'] ?? [], $entity[$field] ?? ($config['default'] ?? ''), [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ])
                    );
                    break;
                case 'file':
                    $currentFile = $entity[$field] ? '<br><small>' . $lang->petplay_admin_current_file . ': ' . htmlspecialchars_uni($entity[$field]) . '</small>' : '';
                    $form_container->output_row(
                        $config['label'],
                        $config['description'] ?? '',
                        $form->generate_file_upload_box($field, [
                            'id' => $field,
                            'required' => $config['required'] ?? false
                        ]) . $currentFile
                    );
                    break;
            }
        }
        
        $form_container->end();
        
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
                    // Process file upload
                    $uploadDir = MYBB_ROOT . 'uploads/petplay/';
                    $fileName = time() . '_' . basename($_FILES[$field]['name']);
                    
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $fileName)) {
                        $data[$field] = 'uploads/petplay/' . $fileName;
                    }
                } elseif (isset($input['id'])) {
                    // If editing and no new file uploaded, keep existing file
                    continue;
                }
            } else {
                // For text fields, ensure they're properly escaped
                $value = $input[$field] ?? ($config['default'] ?? null);
                
                // If the value is empty and the field allows NULL, set it to NULL
                if ($value === '' && !($config['required'] ?? false)) {
                    $data[$field] = null;
                } else {
                    $data[$field] = $value;
                }
            }
        }
        
        // Validate required fields
        foreach ($this->formFields as $field => $config) {
            if (($config['required'] ?? false) && 
                (
                    !isset($data[$field]) || 
                    ($data[$field] === '' && $config['type'] !== 'checkbox')
                )
            ) {
                flash_message($lang->sprintf($lang->petplay_admin_field_required, $config['label']), 'error');
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
