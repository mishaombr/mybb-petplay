<?php

declare(strict_types=1);

namespace petplay;

class ListManager
{
    protected string $entityName;
    protected array $listColumns;
    protected string $baseUrl;
    protected \Table $table;
    protected \DB_Base $db;
    
    public function __construct(string $entityName, array $listColumns, string $baseUrl, \DB_Base $db)
    {
        $this->entityName = $entityName;
        $this->listColumns = $listColumns;
        $this->baseUrl = $baseUrl;
        $this->db = $db;
        $this->table = new \Table();
    }
    
    public function render(string $tableName, array $entities): void
    {
        $this->setupHeaders();
        $this->renderEntities($entities);
        $this->handleEmptyState();
        $this->output();
    }
    
    protected function setupHeaders(): void
    {
        foreach ($this->listColumns as $column => $config) {
            $this->table->construct_header($config['label'], ['width' => $config['width']]);
        }
    }
    
    protected function renderEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            foreach ($this->listColumns as $column => $config) {
                $this->renderCell($column, $entity);
            }
            $this->table->construct_row();
        }
    }
    
    protected function renderCell(string $column, array $entity): void
    {
        switch (true) {
            case $this->isSpriteColumn($column):
                $this->renderSpriteCell($entity[$column]);
                break;
                
            case $this->isTypeColumn($column):
                $this->renderTypeCell($column, $entity);
                break;
                
            case $column === 'actions':
                $this->renderActionsCell($entity);
                break;
                
            case $this->isColorColumn($column):
                $this->renderColorCell($entity[$column]);
                break;
                
            case $this->isBooleanColumn($column):
                $this->renderBooleanCell($entity[$column]);
                break;
                
            default:
                $this->renderDefaultCell($entity[$column]);
        }
    }
    
    protected function isSpriteColumn(string $column): bool
    {
        return in_array($column, ['sprite_path', 'sprite_mini', 'sprite']);
    }
    
    protected function renderSpriteCell(?string $spritePath): void
    {
        if (!empty($spritePath)) {
            $this->table->construct_cell(
                '<div style="text-align: center;">
                    <img src="/' . htmlspecialchars_uni($spritePath) . '" 
                         alt="Sprite" 
                         style="max-width: 50px; max-height: 50px;">
                </div>'
            );
        } else {
            $this->table->construct_cell(
                '<div style="text-align: center;">-</div>'
            );
        }
    }
    
    protected function isTypeColumn(string $column): bool
    {
        return ($this->entityName === 'species' && 
               in_array($column, ['type_primary_id', 'type_secondary_id'])) ||
               ($this->entityName === 'moves' && $column === 'type_id');
    }
    
    protected function renderTypeCell(string $column, array $entity): void
    {
        if ($this->entityName === 'species') {
            $isSecondary = $column === 'type_secondary_id';
            $prefix = $isSecondary ? 'secondary' : 'primary';
            $typeName = $entity["{$prefix}_type_name"] ?? null;
            $typeColor = $entity["{$prefix}_type_colour"] ?? '#FFFFFF';
            $typeSprite = $entity["{$prefix}_type_sprite"] ?? null;
        } else { // moves
            $typeName = $entity["type_name"] ?? null;
            $typeColor = $entity["type_colour"] ?? '#FFFFFF';
            $typeSprite = $entity["type_sprite"] ?? null;
        }
        
        if ($typeName) {
            if ($typeSprite) {
                $content = '<div style="display: flex; align-items: center; justify-content: center;">
                    <img src="/' . htmlspecialchars_uni($typeSprite) . '" 
                         alt="' . htmlspecialchars_uni($typeName) . '" 
                         title="' . htmlspecialchars_uni($typeName) . '"
                         style="max-width: 50px; max-height: 50px;">
                </div>';
            } else {
                $content = '<div style="text-align: center;">
                    <span style="color: ' . htmlspecialchars_uni($typeColor) . '; font-weight: bold;">
                        ' . htmlspecialchars_uni($typeName) . '
                    </span>
                </div>';
            }
            $this->table->construct_cell($content);
        } else {
            $this->table->construct_cell(
                '<div style="text-align: center;">
                    <em>-</em>
                </div>'
            );
        }
    }
    
    protected function renderActionsCell(array $entity): void
    {
        global $mybb, $lang;
        
        $this->table->construct_cell(
            '<a href="' . $this->baseUrl . '&amp;option=edit&amp;id=' . (int)$entity['id'] . '" class="button">
                <i class="fa-solid fa-edit"></i>
            </a>
            <a href="' . $this->baseUrl . '&amp;option=delete&amp;id=' . (int)$entity['id'] . '&amp;my_post_key=' . $mybb->post_code . '" class="button" onclick="return confirm(\'' . $lang->{"petplay_admin_{$this->entityName}_delete_confirm"} . '\');">
                <i class="fa-solid fa-trash"></i>
            </a>',
            ['class' => 'align_center']
        );
    }
    
    protected function isColorColumn(string $column): bool
    {
        return in_array($column, ['colour', 'color']);
    }
    
    protected function renderColorCell(string $color): void
    {
        $this->table->construct_cell(
            '<div style="display: flex; align-items: center;">
                <div style="width: 20px; height: 20px; background-color: ' . htmlspecialchars_uni($color) . '; margin-right: 10px; border: 1px solid #ccc;"></div>
                ' . htmlspecialchars_uni($color) . '
            </div>'
        );
    }
    
    protected function isBooleanColumn(string $column): bool
    {
        return $column === 'is_default' || substr($column, 0, 3) === 'is_';
    }
    
    protected function renderBooleanCell(bool $value): void
    {
        $icon = $value ? 'fa-solid fa-check text-success' : 'fa-solid fa-xmark text-danger';
        $this->table->construct_cell('<i class="' . $icon . '"></i>', ['class' => 'align_center']);
    }
    
    protected function renderDefaultCell($value): void
    {
        $this->table->construct_cell(htmlspecialchars_uni($value));
    }
    
    protected function handleEmptyState(): void
    {
        global $lang;
        
        if ($this->table->num_rows() == 0) {
            $this->table->construct_cell(
                $lang->{'petplay_admin_no_' . $this->entityName}, 
                ['colspan' => count($this->listColumns)]
            );
            $this->table->construct_row();
        }
    }
    
    protected function output(): void
    {
        global $lang;
        $this->table->output($lang->{'petplay_admin_' . $this->entityName});
    }
}
