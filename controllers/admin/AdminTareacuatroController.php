<?php

use PrestaShop\PrestaShop\Adapter\Order\Refund\VoucherGenerator;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminTareacuatroController extends ModuleAdminController
{
    /** @var Tareacuatro $module */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'tareacuatro';
        $this->className = 'Tareacuatrotable';
        $this->lang = false;
        $this->_orderWay = 'DESC';
        
        $this->meta_title = 'B';

        parent::__construct();

        // $this->fields_list = [
        //     'id_tareacuatro' => ['title' => $this->trans('ID', [], 'Admin.Global'), 'align' => 'center', 'class' => 'fixed-width-xs'],
        //     'id_user' => ['title' => $this->trans('ID', [], 'Admin.Global'), 'align' => 'center', 'class' => 'fixed-width-xs'],
        //     'id_cart_rule' => ['title' => $this->trans('ID', [], 'Admin.Global'), 'align' => 'center', 'class' => 'fixed-width-xs'],
        // ];
        $this->fields_list = [
            'id_customer' => ['title' => $this->trans('id_customer', [], 'Modules.Tareacuatro.Admin'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'firstname' => ['title' => $this->trans('firstname', [], 'Modules.Tareacuatro.Admin'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'lastname' => ['title' => $this->trans('lastname', [], 'Modules.Tareacuatro.Admin'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'email'=> ['title' => $this->trans('email', [], 'Modules.Tareacuatro.Admin'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'code'=> ['title' => $this->trans('code', [], 'Modules.Tareacuatro.Admin'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'date_from'=> ['title' => $this->trans('date_from', [], 'Modules.Tareacuatro.Admin'), 'align' => 'center', 'class' => 'fixed-width-xs'],
        ];

        $this->fields_options = [];
        $this->fields_options[] = [
            'title' => $this->trans('Modulo Tarea Cuatro', [], 'Modules.Tareacuatro.Admin'),
            'icon' => 'icon-cogs',
            'fields' => [
                $this->module::CONF_MAXSPEND => [
                    'title' => $this->trans('Cantidad', [], 'Modules.Tareacuatro.Admin'),
                    'desc' => $this->trans('Cantidad de dinero que tiene que gastar el cliente.', [], 'Modules.Tareacuatro.Admin'),
                    'align' => 'right', 
                    'type' => 'price',
                    'currency' => true,
                    'width' => 60
                ],
                $this->module::CONF_DISCOUNT => [
                    'title' => $this->trans('Importe', [], 'Modules.Tareacuatro.Admin'),
                    'desc' => $this->trans('Importe del cupón.', [], 'Modules.Tareacuatro.Admin'),
                    'align' => 'right', 
                    'type' => 'price',
                    'currency' => true,
                    'width' => 60
                ],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
        ];
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
        $nb_items = count($this->_list);
        for ($i = 0; $i < $nb_items; ++$i) {
            $item = &$this->_list[$i];

            $query = new DbQuery();
            $query->select('c.id_customer, c.firstname, c.lastname, c.email, cr.code, cr.date_from');
            $query->from('customer', 'c');
            $query->leftJoin('cart_rule', 'cr', 'cr.id_customer = c.id_customer');
            $query->where('c.id_customer =' . (int) $item['id_user']);
            $query->where('cr.id_cart_rule =' . (int) $item['id_cart_rule']);
            $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
            if (is_array($res)) {
                $item = $res;
            }
            unset($query);
        }
    }
}