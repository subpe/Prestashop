<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SubPe extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'subpe';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'By Rohit Kr Singh';
        $this->controllers = array('validation');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Subpe Payment Gateway');
        $this->description = $this->l('If you are looking for a reliable online payment partner, head towards SubPe, we offer 100+ Payment options. Easy to integrate with your website with tons of ready-to-go plugins for major open source ecommerce.');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn') || 
            !Configuration::updateValue('pay_id', 'pay id')||
            !Configuration::updateValue('marchant_salt', 'salt') ||
            !Configuration::updateValue('merchant_website', 'merchant website')||
            !Configuration::updateValue('industry_type', 'industry type')||
            !Configuration::updateValue('transaction_request_url', 'transanction request url')||
            !Configuration::updateValue('transaction_response_url', 'transaction response url')||
            !Configuration::updateValue('callback_url', 'callback url')


    ) {
            return false;
        }
        return true;
    }


    public function uninstall()
    {
        if (!Configuration::deleteByName("pay_MERCHANT_ID") OR 
            !Configuration::deleteByName("pay_MERCHANT_KEY") OR 
            !Configuration::deleteByName("pay_TRANSACTION_STATUS_URL") OR 
            !Configuration::deleteByName("pay_GATEWAY_URL") OR 
            !Configuration::deleteByName("pay_MERCHANT_INDUSTRY_TYPE") OR 
            !Configuration::deleteByName("pay_MERCHANT_CHANNEL_ID") OR 
            !Configuration::deleteByName("pay_MERCHANT_WEBSITE") OR 
            !Configuration::deleteByName("pay_CALLBACK_URL_STATUS") OR 
            !Configuration::deleteByName("pay_CALLBACK_URL") OR 
        
            !parent::uninstall()) {
            return false;
        }
        return true;
    }

    public function getContent()
        {
            $output = null;

            if (Tools::isSubmit('submit'.$this->name)) {
                $pay_module = strval(Tools::getValue('pay_id'));
                $pay_module_salt = strval(Tools::getValue('merchant_salt'));
                 $pay_module_website = strval(Tools::getValue('merchant_website'));

                 $pay_module_industry_type = strval(Tools::getValue('industry_type'));
                 $pay_module_transaction_request_url = strval(Tools::getValue('transaction_request_url'));
                 $pay_module_transaction_response_url = strval(Tools::getValue('transaction_response_url'));
                 $pay_module_callback_url = strval(Tools::getValue('callback_url'));


                if (
                    !$pay_module ||
                    empty($pay_module) ||
                    !Validate::isGenericName($pay_module)
                ) {
                    $output .= $this->displayError($this->l('Invalid Configuration value'));
                } else {
                    Configuration::updateValue('pay_id', $pay_module);
                    Configuration::updateValue('merchant_salt', $pay_module_salt);
                    Configuration::updateValue('merchant_website', $pay_module_website);
                    
                    Configuration::updateValue('industry_type', $pay_module_industry_type);
                    Configuration::updateValue('transaction_request_url', $pay_module_transaction_request_url);
                    Configuration::updateValue('transaction_response_url', $pay_module_transaction_response_url);
                    Configuration::updateValue('callback_url', $pay_module_callback_url);
                    $output .= $this->displayConfirmation($this->l('Settings updated'));
                }
            }

            return $output.$this->displayForm();
        }

                // admin settings

    public function displayForm()
{
    // Get default language
    $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

    // Init Fields form array
    $fieldsForm[0]['form'] = [
        'legend' => [
            'title' => $this->l('Settings'),
        ],
        'input' => [
            [
                'type' => 'text',
                'label' => $this->l('Merchant PAY ID'),
                'name' => 'pay_id',
                'size' => 20,
                'required' => true
            ],
            [
                'type' => 'text',
                'label' => $this->l('Merchant SALT'),
                'name' => 'merchant_salt',
                'size' => 20,
                'required' => true
            ],
            [
                'type' => 'text',
                'label' => $this->l('Website'),
                'name' => 'merchant_website',
                'size' => 20,
                'required' => true
            ],
            [
                'type' => 'text',
                'label' => $this->l('Industry Type'),
                'name' => 'industry_type',
                'size' => 20,
                'required' => true
            ],

            [
                'type' => 'text',
                'label' => $this->l('Transaction Request Url'),
                'name' => 'transaction_request_url',
                'size' => 20,
                'required' => true
            ],
      

        ],
        'submit' => [
            'title' => $this->l('Save'),
            'class' => 'btn btn-default pull-right'
        ]
    ];

    $helper = new HelperForm();

    // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

    // Language
    $helper->default_form_language = $defaultLang;
    $helper->allow_employee_form_lang = $defaultLang;

    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit'.$this->name;
    $helper->toolbar_btn = [
        'save' => [
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ],
        'back' => [
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        ]
    ];

    // Load current value
    $helper->fields_value['pay_id'] = Configuration::get('pay_id');
    $helper->fields_value['merchant_salt'] = Configuration::get('merchant_salt');
    $helper->fields_value['merchant_website'] = Configuration::get('merchant_website');
    $helper->fields_value['industry_type'] = Configuration::get('industry_type');
    $helper->fields_value['transaction_request_url'] = Configuration::get('transaction_request_url');
    $helper->fields_value['transaction_response_url'] = Configuration::get('transaction_response_url');
    $helper->fields_value['callback_url'] = Configuration::get('callback_url');
    return $helper->generateForm($fieldsForm);
}



    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [
            $this->getExternalPaymentOption(),
        ];
      
        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getExternalPaymentOption()
    {
        //Generate a random string.
         $token = openssl_random_pseudo_bytes(16);
        //Convert the binary data into hexadecimal representation.
         $token = bin2hex($token);


        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l('Pay By SubPe'))
                       ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                       ->setInputs([
                            'token' => [
                                'name' =>'token',
                                'type' =>'hidden',
                                'value' =>"$token",
                            ],
                        ]);
                      
        return $externalOption;
    }


    protected function generateForm()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = sprintf("%02d", $i);
        }

        $years = [];
        for ($i = 0; $i <= 10; $i++) {
            $years[] = date('Y', strtotime('+'.$i.' years'));
        }

        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
            'months' => $months,
            'years' => $years,
        ]);

        return $this->context->smarty->fetch('module:subpe/views/templates/front/payment_form.tpl');
    }
}
