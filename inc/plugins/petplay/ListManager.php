<?php

namespace petplay;

class ListManager
{
    private $mybb;
    private $baseurl;
    private $order_columns;
    private $order_dir;
    private $items_num;
    private $per_page;
    private $page;
    
    public function __construct($options)
    {
        $this->mybb = $options['mybb'];
        $this->baseurl = $options['baseurl'];
        $this->order_columns = $options['order_columns'];
        $this->order_dir = isset($options['order_dir']) ? $options['order_dir'] : 'asc';
        $this->items_num = $options['items_num'];
        $this->per_page = isset($options['per_page']) ? $options['per_page'] : 20;
        
        // Get current page
        $this->page = $this->mybb->get_input('page', \MyBB::INPUT_INT);
        if ($this->page < 1) {
            $this->page = 1;
        }
        
        // Get sort column and direction
        $this->sort_column = $this->mybb->get_input('sort');
        if (!in_array($this->sort_column, $this->order_columns)) {
            $this->sort_column = $this->order_columns[0];
        }
        
        $this->sort_dir = $this->mybb->get_input('dir');
        if ($this->sort_dir != 'desc' && $this->sort_dir != 'asc') {
            $this->sort_dir = $this->order_dir;
        }
    }
    
    public function sql()
    {
        $sql = '';
        
        // Add ORDER BY clause
        $sql .= " ORDER BY {$this->sort_column} {$this->sort_dir}";
        
        // Add LIMIT clause for pagination
        $start = ($this->page - 1) * $this->per_page;
        $sql .= " LIMIT {$this->per_page} OFFSET {$start}";
        
        return $sql;
    }
    
    public function link($column, $title)
    {
        $dir = 'asc';
        if ($this->sort_column == $column && $this->sort_dir == 'asc') {
            $dir = 'desc';
        }
        
        return "<a href=\"{$this->baseurl}&amp;sort={$column}&amp;dir={$dir}\">{$title}</a>";
    }
    
    public function pagination()
    {
        global $lang;
        
        // Make sure these language variables exist
        if (!isset($lang->multipage_pages)) {
            $lang->multipage_pages = "Pages:";
        }
        if (!isset($lang->multipage_prev)) {
            $lang->multipage_prev = "Previous";
        }
        if (!isset($lang->multipage_next)) {
            $lang->multipage_next = "Next";
        }
        if (!isset($lang->multipage_last)) {
            $lang->multipage_last = "Last";
        }
        if (!isset($lang->multipage_first)) {
            $lang->multipage_first = "First";
        }
        
        // Generate pagination
        return multipage($this->items_num, $this->per_page, $this->page, $this->baseurl . '&amp;sort=' . $this->sort_column . '&amp;dir=' . $this->sort_dir);
    }
} 