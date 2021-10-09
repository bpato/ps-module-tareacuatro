<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tareacuatro extends Module
{
    /** @var string Unique name */
    public $name = 'tareacuatro';

    /** @var string Version */
    public $version = '1.0.0';

    /** @var string author of the module */
    public $author = 'Brais Pato';

    /** @var int need_instance */
    public $need_instance = 0;

    /** @var string Admin tab corresponding to the module */
    public $tab = 'pricing_promotion';

    /** @var array filled with known compliant PS versions */
    public $ps_versions_compliancy = [
        'min' => '1.7.3.3',
        'max' => '1.7.9.99'
    ];

    /** @var array Hooks used */
    public $hooks = [
        'actionValidateOrder',
        'actionFrontControllerSetMedia',
        'displayOrderConfirmation1'
    ];

    /** Name of ModuleAdminController used for configuration */
    const MODULE_ADMIN_CONTROLLER = 'AdminTareacuatro';
    const MODULE_ADMIN_TABLE_CONTROLLER = 'AdminTareacuatrotable';

    /** Configuration variable names */
    const CONF_MAXSPEND = 'TAREA_CUATRO_MAXSPEND';
    const CONF_DISCOUNT = 'TAREA_CUATRO_DISCOUNT';

    /**
     * Constructor of module
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Modulo Tarea 4', [], 'Modules.Tareacuatro.Admin');
        $this->description = $this->trans('Crear un módulo en el que cada vez que se realice un pedido (con cualquier estado de pedido), le envíe al
        comprador un email informándole del total de dinero que lleva gastado en la tienda.', [], 'Modules.Tareacuatro.Admin');
        $this->confirmUninstall = $this->trans('¿Estás seguro de que quieres desinstalar el módulo?', array(), 'Modules.Tareacuatro.Admin');
        $this->templateFile = 'module:tareacuatro/views/templates/hook/displayOrderConfirmation1.tpl';
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install() 
            && $this->registerHook($this->hooks)
            && $this->installTab()
            && $this->installConfig()
            && $this->installDB();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTabs()
            && $this->uninstallConfiguration()
            && $this->uninstallDB();
    }

    public function installConfig() {
        return (bool) Configuration::updateValue(self::CONF_MAXSPEND, 200)
        && (bool) Configuration::updateValue(self::CONF_DISCOUNT, 20);
    }

    /**
     * @return bool
     */
    public function installTab()
    {
        $tab = new Tab();
        
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->name
        );
        $tab->active = false;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminModulesManage');
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool) $tab->delete();
        }

        return true;
    }

    /**
     * @return bool
     */
    public function uninstallConfiguration()
    {
        return true;
    }

    public function installDB()
    {
        $return = true;
        $return &= Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'tareacuatro` (
                `id_tareacuatro` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_user` INT(10) UNSIGNED NOT NULL,
                `id_cart_rule` INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id_tareacuatro`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;'
        );

        return $return;
    }

    public function uninstallDB($drop_table = true)
    {
        $ret = true;
        if ($drop_table) {
            $ret &= Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tareacuatro`');
        }

        return $ret;
    }

    /**
     * @return null
     */
    public function getContent()
    {
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink(self::MODULE_ADMIN_CONTROLLER));
        return null;
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if ($this->context->controller instanceof OrderConfirmationControllerCore) {

            $filename = '/modules/' . $this->name . '/views/js/scratch-card.js';

            if (file_exists(_PS_ROOT_DIR_ . $filename)) {
                $this->context->controller->registerJavascript(
                    'scratch-card', // Unique ID
                    $filename, // JS path
                    array(
                        'server' => 'remote', 
                        'position' => 'bottom',
                        'priority' => 1000
                    ) // Arguments
                );
            }

            $this->context->controller->registerStylesheet(
                'scratch-card-style',
                '/modules/' . $this->name . 'views/css/demo.css',
                [
                    'media' => 'all',
                    'priority' => 1000,
                ]
            );
        }
    }
    
    public function hookActionValidateOrder($args) {
        $customer = $args['customer'];
        $stats = $customer->getStats();
        //$total_spent = (float) $stats['total_orders'] + (float)$args['order']->total_paid;
        $total_spent = (float) $stats['total_orders'];
        
        if (!$this->hasVouncher($customer->id) && $total_spent > Configuration::get(self::CONF_MAXSPEND)) {

            $id_cart_rule = $this->generateVouncher($customer);
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'tareacuatro` (`id_user`, `id_cart_rule`) 
            VALUES ('.(int)$customer->id.','.(int)$id_cart_rule.')';
            Db::getInstance()->execute($sql);
            $mailargs['cartRule'] = new CartRule($id_cart_rule);
        }

        $mailargs['total_spent'] = $total_spent;
        $mailargs['id_currency'] = $args['cart']->id_currency;

        $this->sendConfirmationMail($customer, $mailargs);
    }

    private function sendConfirmationMail(Customer $customer, $args)
    {
        if ($customer->is_guest || !Configuration::get('PS_CUSTOMER_CREATION_EMAIL')) {
            return true;
        }

        if (isset($args['cartRule'])){
            $voucher_amount = Context::getContext()->getCurrentLocale()->formatPrice($args['cartRule']->reduction_amount, Currency::getIsoCodeById((int) $args['id_currency']));
            $voucher_num = $args['cartRule']->code;
            $voucher = 'Has conseguido un cupón de decuento de '.$voucher_amount.' CODIGO: '.$voucher_num;
        }
        
        return Mail::Send(
            (int)$this->context->language->id,
            'tareacuatro',
            Context::getContext()->getTranslator()->trans(
                'Welcome!',
                array(),
                'Emails.Subject'
            ),
            array(
                '{total_spent}' =>  Context::getContext()->getCurrentLocale()->formatPrice($args['total_spent'], Currency::getIsoCodeById((int) $args['id_currency'])),
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}' => $customer->email,
                '{voucher_amount}' => (isset($voucher_amount))?$voucher_amount:null,
                '{voucher_num}' => (isset($voucher_num))?$voucher_num:null,
                '{voucher}' => (isset($voucher))?$voucher:null,
            ),
            $customer->email,
            $customer->firstname.' '.$customer->lastname,
            null,
            null,
            null,
            null,
            _PS_THEME_DIR_.'modules/tareacuatro/mails/',
            null,
            (int)$this->context->shop->id
        );
    }

    public function hasVouncher($idCustomer)
    {
        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('tareacuatro', 't');
        $sql->where('t.`id_user` = ' . (int) $idCustomer);

        $result = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        return $result;
    }

    public function generateVouncher($customer)
    {
        $cartRule = new CartRule();
        $cartRule->description = Context::getContext()->getTranslator()->trans(
            'Vouncher tarea cuatro',
            [],
            'Modules.Tareacuatro.Admin'
        );

        $langIds = Language::getIDs(false);
        foreach ($langIds as $langId) {
            // Define a temporary name
            $cartRule->name[$langId] = sprintf('V0C%1$dO', 
                $customer->id
            );
        }

        // Define a temporary code
        $cartRule->code = sprintf('V0C%1$dO', $customer->id);
        $cartRule->quantity = 1;
        $cartRule->quantity_per_user = 1;

        // Specific to the customer
        $cartRule->id_customer = $customer->id;
        $now = time();
        $cartRule->date_from = date('Y-m-d H:i:s', $now);
        $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+1 year'));
        $cartRule->partial_use = 0;
        $cartRule->active = 1;

        $cartRule->reduction_amount = Configuration::get(self::CONF_DISCOUNT);
        $cartRule->reduction_tax = true;

        if (!$cartRule->add()) {
            throw new Exception('You cannot generate a voucher.');
        }

        // Update the voucher code and name
        foreach ($langIds as $langId) {
            $cartRule->name[$langId] = sprintf('V%1$dC%2$d', $cartRule->id, $customer->id);
        }

        $chars = "123456789ABCDEFGHIJKLMNPQRSTUVWXYZ";
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        $cartRule->code = $code;

        if (!$cartRule->update()) {
            throw new Exception('You cannot generate a voucher.');
        }

        return $cartRule->id;
    }

    public function getVouncherData($id_user)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from('tareacuatro', 't')
            ->where('t.id_user = '.(int)$id_user);

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        if (!$result) {
            return false;
        }

        return $result;
    }

    public function hookDisplayOrderConfirmation1($params)
    {
        $id_customer = $params["cart"]->id_customer;
        if ($this->hasVouncher($id_customer)) {
            $data = $this->getVouncherData($id_customer);
            $vouncher = new CartRule($data['id_cart_rule']);

            if (isset($vouncher)){
                $voucher_amount = Context::getContext()->getCurrentLocale()->formatPrice($vouncher->reduction_amount, Currency::getIsoCodeById((int) $params["cart"]->id_currency));
                $voucher_num = $vouncher->code;
                $voucher = 'Has conseguido un cupón de decuento de '.$voucher_amount.' CODIGO: '.$voucher_num;

                $this->smarty->assign([
                    'tareacuatro' => array(
                        'voucher_amount' => $voucher_amount,
                        'voucher_num' => $voucher_num,
                        'voucher' => $voucher
                    )
                ]);
            }
        }

        return $this->fetch($this->templateFile);
    }

}
