<?php 
class BhartiPayThankyouModuleFrontController extends ModuleFrontController
{
  
 public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'bhartipay') {
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
        //bhartipay
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
        //$merchant_website=$merchant_website."en/module/bhartipay/validation";
        $merchant_website=$merchant_website."en/module/bhartipay/thankyou";
        //test for redirect
                
        
        if ($_POST['STATUS']=="Cancelled") {
            Tools::redirect(Tools::getHttpHost(true).__PS_BASE_URI__.'en/cart?action=show');
        }
       // $this->setTemplate('module:bhartipay/views/templates/front/payment_return.tpl');
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
 ?>
