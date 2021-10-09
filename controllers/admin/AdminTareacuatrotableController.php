<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminTareacuatrotableController extends ModuleAdminController
{
    /** @var Tareacuatro $module */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'cart_rule';
        $this->className = 'Tareacuatrotable';
        $this->lang = true;
        $this->_orderWay = 'DESC';
        
        $this->meta_title = 'B';

        parent::__construct();

        $this->fields_list = [
            'id_cart_rule' => ['title' => $this->trans('ID', [], 'Admin.Global'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name' => ['title' => $this->trans('Name', [], 'Admin.Global')],
            'priority' => ['title' => $this->trans('Priority', [], 'Admin.Global'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'code' => ['title' => $this->trans('Code', [], 'Admin.Global'), 'class' => 'fixed-width-sm'],
            'quantity' => ['title' => $this->trans('Quantity', [], 'Admin.Catalog.Feature'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'date_to' => ['title' => $this->trans('Expiration date', [], 'Admin.Catalog.Feature'), 'type' => 'datetime', 'class' => 'fixed-width-lg'],
            'active' => ['title' => $this->trans('Status', [], 'Admin.Global'), 'active' => 'status', 'type' => 'bool', 'align' => 'center', 'class' => 'fixed-width-xs', 'orderby' => false],
        ];
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_quick_access'] = [
                'href' => self::$currentIndex . '&addquick_access&token=' . $this->token,
                'desc' => $this->trans('Add new quick access', [], 'Admin.Navigation.Header'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Set default toolbar title.
     */
    public function initToolbarTitle()
    {
        $this->toolbar_title[] = $this->module->displayName;
    }
}