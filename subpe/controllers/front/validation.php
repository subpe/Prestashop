  <style type="text/css">
    .loader {
    position: fixed;
    left: 0px;
    top: 0px;
    width: 100%;
    height: 100%;
    z-index: 9999;
    background: url('https://loading.io/spinners/coolors/index.palette-rotating-ring-loader.gif') 50% 50% no-repeat rgb(249,249,249);
    opacity: 1.6;
}
img.centre-image {
    top: 34%;
    left: 46%;
    position: relative;
}
h3 {
    position: relative;
    left: 38%;
    display: inline-block;
}
</style>
<div class="loader">
    <img src="https://www.subpe.com/wp-content/uploads/2018/07/logo.png" class="centre-image">
</div>  


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

/**
 * @since 1.5.0
 */
// $base=dirname( __FILE__, 3 );
// $path=$base."\lib\bppg_helper.php";
// require_once("$path");
require_once("bppg_helper.php");
class SubPeValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

   
    
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'subpe') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);

        //subpe
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $amount;
        global $cookie;
        $first_name= $cookie->customer_firstname;
        $last_name= $cookie->customer_lastname;
        $customer_name=$first_name ." ". $last_name;
        $customer_email=$cookie->email;
        $order_id=$cookie->id_cart;

        //fetching data from config file
       
        $pay_id = Configuration::get('pay_id');
        $salt = Configuration::get('merchant_salt');
        $merchant_website = Configuration::get('merchant_website');

        $industry_type = Configuration::get('industry_type');
        $transaction_request_url = Configuration::get('transaction_request_url');
        $transaction_response_url= Configuration::get('transaction_response_url');
        $callback_url =Configuration::get('callback_url');
        //$merchant_website=$merchant_website."en/module/subpe/validation";
        $merchant_website=$merchant_website."en/module/subpe/thankyou";
        //test for redirect

        @$pg_transaction = new BPPGModule;
        @$pg_transaction->setPayId("$pay_id");
        @$pg_transaction->setPgRequestUrl("$transaction_request_url");
        @$pg_transaction->setSalt("$salt");
        @$pg_transaction->setReturnUrl("$merchant_website");
        @$pg_transaction->setCurrencyCode(356);
        @$pg_transaction->setTxnType('SALE');
        @$pg_transaction->setOrderId("$order_id");
        @$pg_transaction->setCustEmail("$customer_email");
        @$pg_transaction->setCustName("$customer_name");
        @$pg_transaction->setCustPhone('Nan');
        @$pg_transaction->setAmount(("$amount")*100); // convert to Rupee from Paisa
        @$pg_transaction->setProductDesc('PrestaShop');
        //@$pg_transaction->setCustStreetAddress1($_REQUEST['CUST_STREET_ADDRESS1']);
        //@$pg_transaction->setCustCity($_REQUEST['CUST_CITY']);
        //@$pg_transaction->setCustState($_REQUEST['CUST_STATE']);
        //@$pg_transaction->setCustCountry($_REQUEST['CUST_COUNTRY']);
        //@$pg_transaction->setCustZip($_REQUEST['CUST_ZIP']);  
       // @$pg_transaction->setCustShipStreetAddress1($_REQUEST['CUST_SHIP_STREET_ADDRESS1']);
       // @$pg_transaction->setCustShipCity($_REQUEST['CUST_SHIP_CITY']);
       // @$pg_transaction->setCustShipState($_REQUEST['CUST_SHIP_STATE']);
        //@$pg_transaction->setCustShipCountry($_REQUEST['CUST_SHIP_COUNTRY']);
       // @$pg_transaction->setCustShipZip($_REQUEST['CUST_SHIP_ZIP']);
       // @$pg_transaction->setCustShipPhone($_REQUEST['CUST_SHIP_PHONE']);
       // @$pg_transaction->setCustShipName($_REQUEST['CUST_SHIP_NAME']);
        //subpe
        if (isset($_REQUEST['token'])) {
            $postdata = $pg_transaction->createTransactionRequest();
            $pg_transaction->redirectForm($postdata);
            exit();
        }                 
        
        if ($_POST['STATUS']=="Cancelled") {
            Tools::redirect(Tools::getHttpHost(true).__PS_BASE_URI__.'en/cart?action=show');
        }

        $this->setTemplate('module:subpe/views/templates/front/payment_return.tpl');


        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = array(
            '{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
            '{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
            '{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
        );

        $this->module->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
        Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }
}
