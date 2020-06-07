<?php
/**
 * @author Gustavo Ulyssea - gustavo.ulyssea@gmail.com
 * @copyright Copyright (c) 2020 GumNet (https://gum.net.br)
 * @package GumNet AME
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY GUM Net (https://gum.net.br). AND CONTRIBUTORS
 * ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE FOUNDATION OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace GumNet\AME\Helper;

use \Ramsey\Uuid\Uuid;

class API
{
    protected $url;
    protected $_logger;
    protected $_mlogger;
    protected $_connection;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_dbAME;
    protected $_email;
    protected $_gumapi;
    protected $_proxyUrl;
    protected $_invalidProxies;

    public function __construct(\GumNet\AME\Helper\LoggerAME $logger,
                                \Psr\Log\LoggerInterface $mlogger,
                                \Magento\Framework\App\ResourceConnection $resource,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
                                \GumNet\AME\Helper\DbAME $dbAME,
                                \GumNet\AME\Helper\MailerAME $email,
                                \GumNet\AME\Helper\GumApi $gumApi,
                                \GumNet\AME\Helper\Mlogger $nmlogger
    )
    {
        $this->url = "https://api.hml.amedigital.com/api";
        $this->_logger = $logger;
        $this->_mlogger = $mlogger;
        $this->_connection = $resource->getConnection();
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_dbAME = $dbAME;

        if(!$this->_scopeConfig->getValue('ame/general/debug_log', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)){
            $this->_mlogger = $nmlogger;
        }
        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 0) {
            $this->url = "https://api.dev.amedigital.com/api";
        }
        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1) {
            $this->url = "https://api.hml.amedigital.com/api";
//            $this->url = "https://ame19gwci.gum.net.br:63333/api";
        }
        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 2) {
            $this->url = "https://api.amedigital.com/api";
        }
        $this->_email = $email;
        $this->_gumapi = $gumApi;
        $this->_proxyUrl = "";
        $this->_invalidProxies = [];
    }
    public function getProxy($force_update=0){
        if(!$force_update&&$this->_proxyUrl) return $this->_proxyUrl;
        $valid_proxy=0;
        while(!$valid_proxy){
            $proxy = rand(0,count($this->proxyList()-1));
            $valid_proxy=1;
            foreach($this->_invalidProxies as $invalidProxy){

            }
        }
    }
    public function proxyList(){
        $list[0]="ameproxy0.gum.net.br";
        $list[1]="ameproxy1.gum.net.br";
        $list[2]="ameproxy2.gum.net.br";
//        $list[3]="ameproxy3.gum.net.br";
    }
    public function refundOrder($ame_id, $amount)
    {
        $this->_mlogger->info("AME REFUND ORDER:" . $ame_id);
        $this->_mlogger->info("AME REFUND amount:" . $amount);

        $transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_id);
        $this->_mlogger->info("AME REFUND TRANSACTION:" . $transaction_id);

        $refund_id = Uuid::uuid4()->toString();
        while($this->_dbAME->refundIdExists($refund_id)){
            $refund_id = Uuid::uuid4()->toString();
        }
        $this->_mlogger->info("AME REFUND ID:" . $refund_id);
        $url = $this->url . "/payments/" . $transaction_id . "/refunds/" . $refund_id;
        $this->_mlogger->info("AME REFUND URL:" . $url);
//        echo $url;
        $json_array['amount'] = $amount;
        $json = json_encode($json_array);
        $this->_mlogger->info("AME REFUND JSON:" . $json);
        $result[0] = $this->ameRequest($url, "PUT", $json);
//        echo $result[0];
        $this->_mlogger->info("AME REFUND Result:" . $result[0]);
        if ($this->hasError($result[0], $url, $json)) return false;
        $result[1] = $refund_id;
        return $result;
    }
    public function cancelOrder($ame_id)
    {
        $transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_id);
        if (!$transaction_id) {
//            echo "Transaction ID not found";
            return false;
        }
        $url = $this->url . "/wallet/user/payments/" . $transaction_id . "/cancel";
        $result = $this->ameRequest($url, "PUT", "");
        if ($this->hasError($result, $url, "")) return false;
        return true;
    }
    public function consultOrder($ame_id)
    {
        $url = $this->url . "/orders/" . $ame_id;
        $result = $this->ameRequest($url, "GET", "");
        if ($this->hasError($result, $url)) return false;
        return $result;
    }
    public function captureOrder($ame_id)
    {
        $ame_transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_id);
        $url = $this->url . "/wallet/user/payments/" . $ame_transaction_id . "/capture";
        $result = $this->ameRequest($url, "PUT", "");
        if ($this->hasError($result, $url)) return false;
        $result_array = json_decode($result, true);

        return $result_array;
    }
    public function createOrder($order)
    {
        $url = $this->url . "/orders";

        $shippingAmount = $order->getShippingAmount();
        $productsAmount = $order->getGrandTotal() - $shippingAmount;
        $amount = intval($order->getGrandTotal() * 100);
        $cashbackAmountValue = intval($this->getCashbackPercent() * $amount * 0.01);

        $json_array['title'] = "GumNet Pedido " . $order->getIncrementId();
        $json_array['description'] = "Pedido " . $order->getIncrementId();
        $json_array['amount'] = $amount;
        $json_array['currency'] = "BRL";
        $json_array['attributes']['cashbackamountvalue'] = $cashbackAmountValue;
        $json_array['attributes']['transactionChangedCallbackUrl'] = $this->getCallbackUrl();
        $json_array['attributes']['items'] = [];

        $items = $order->getAllItems();
        $amount = 0;
        $total_discount = 0;
        foreach ($items as $item) {
            if (isset($array_items)) unset($array_items);
            $array_items['description'] = $item->getName() . " - SKU " . $item->getSku();
            $array_items['quantity'] = intval($item->getQtyOrdered());
            $array_items['amount'] = intval(($item->getRowTotal() - $item->getDiscountAmount()) * 100);
            $products_amount = $amount + $array_items['amount'];
            $total_discount = $total_discount + abs($item->getDiscountAmount());
            array_push($json_array['attributes']['items'], $array_items);
        }
        if($total_discount){
//            $amount = intval($products_amount + $shippingAmount * 100);
//            $json_array['amount'] = $amount;
            $cashbackAmountValue = intval($this->getCashbackPercent() * $products_amount * 0.01);
            $json_array['attributes']['cashbackamountvalue'] = $cashbackAmountValue;
        }

        $json_array['attributes']['customPayload']['ShippingValue'] = intval($order->getShippingAmount() * 100);
        $json_array['attributes']['customPayload']['shippingAddress']['country'] = "BRA";

        $number_line = $this->_scopeConfig->getValue('ame/address/number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $json_array['attributes']['customPayload']['shippingAddress']['number'] = $order->getShippingAddress()->getStreet()[$number_line];

        $json_array['attributes']['customPayload']['shippingAddress']['city'] = $order->getShippingAddress()->getCity();

        $street_line = $this->_scopeConfig->getValue('ame/address/street', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $json_array['attributes']['customPayload']['shippingAddress']['street'] = $order->getShippingAddress()->getStreet()[$street_line];

        $json_array['attributes']['customPayload']['shippingAddress']['postalCode'] = $order->getShippingAddress()->getPostcode();

        $neighborhood_line = $this->_scopeConfig->getValue('ame/address/neighborhood', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $json_array['attributes']['customPayload']['shippingAddress']['neighborhood'] = $order->getShippingAddress()->getStreet()[$neighborhood_line];

        $json_array['attributes']['customPayload']['shippingAddress']['state'] = $this->codigoUF($order->getShippingAddress()->getRegion());

        $json_array['attributes']['customPayload']['billingAddress'] = $json_array['attributes']['customPayload']['shippingAddress'];
        $json_array['attributes']['paymentOnce'] = true;
        $json_array['attributes']['riskHubProvider'] = "SYNC";
        $json_array['attributes']['origin'] = "ECOMMERCE";

        $json = json_encode($json_array);
        $result = $this->ameRequest($url, "POST", $json);

        if ($this->hasError($result, $url, $json)) return false;
        $this->_gumapi->createOrder($json,$result);
        $this->_logger->log($result, "info", $url, $json);
        $result_array = json_decode($result, true);

        $this->_dbAME->insertOrder($order,$result_array);

        $this->_logger->log($result, "info", $url, $json);
        return $result;
    }
    public function getCallbackUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }
    public function hasError($result, $url, $input = "")
    {
        $result_array = json_decode($result, true);
        if (is_array($result_array)) {
            if (array_key_exists("error", $result_array)) {
                $this->_logger->log($result, "error", $url, $input);
                $subject = "AME Error";
                $message = "Result: ".$result."\r\n\r\nurl: ".$url."\r\n\r\n";
                if($input){
                    $message = $message . "Input: ".$input;
                }
                $this->_email->sendDebug($subject,$message);
                return true;
            }
        } else {
            $this->_mlogger->info("ameRequest hasError:" . $result);
            return true;
        }
        return false;
    }
    public function getCashbackPercent()
    {
        return $this->_scopeConfig->getValue('ame/general/cashback_value', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getStoreName()
    {
        return $this->_scopeConfig->getValue('ame/general/store_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function ameRequest($url, $method = "GET", $json = "")
    {
        $this->_mlogger->info("ameRequest starting...");
        $_token = $this->getToken();
        if (!$_token) return false;
        $method = strtoupper($method);
        $this->_mlogger->info("ameRequest URL:" . $url);
        $this->_mlogger->info("ameRequest METHOD:" . $method);
        if ($json) {
            $this->_mlogger->info("ameRequest JSON:" . $json);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . $_token));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        if ($method == "POST" || $method == "PUT") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        $this->_mlogger->info("ameRequest OUTPUT:" . $result);
        $this->_logger->log(curl_getinfo($ch, CURLINFO_HTTP_CODE), "header", $url, $json);
        $this->_logger->log($result, "info", $url, $json);
        curl_close($ch);
        return $result;
    }
    public function getToken()
    {
        $this->_mlogger->info("ameRequest getToken starting...");
        // check if existing token will be expired within 10 minutes
        if($token = $this->_dbAME->getToken()){
            return $token;
        }
        // get user & pass from core_config_data
        $username = $this->_scopeConfig->getValue('ame/general/api_user', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $password = $this->_scopeConfig->getValue('ame/general/api_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$username || !$password) {
            $this->_logger->log("user/pass not found on db", "error", "-", "-");
            return false;
        }
        $url = $this->url . "/auth/oauth/token";
        $ch = curl_init();
        $post = "grant_type=client_credentials";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
        ));
        $result = curl_exec($ch);
        if ($this->hasError($result, $url, $post)) return false;
        $result_array = json_decode($result, true);
        $this->_logger->log($result, "info", $url, $username . ":" . $password);

        $expires_in = (int)time() + intval($result_array['expires_in']);
        $this->_dbAME->updateToken($expires_in,$result_array['access_token']);
        return $result_array['access_token'];
    }
    public function codigoUF($txt_uf)
    {
        $array_ufs = array("Rondônia" => "RO",
            "Acre" => "AC",
            "Amazonas" => "AM",
            "Roraima" => "RR",
            "Pará" => "PA",
            "Amapá" => "AP",
            "Tocantins" => "TO",
            "Maranhão" => "MA",
            "Piauí" => "PI",
            "Ceará" => "CE",
            "Rio Grande do Norte" => "RN",
            "Paraíba" => "PB",
            "Pernambuco" => "PE",
            "Alagoas" => "AL",
            "Sergipe" => "SE",
            "Bahia" => "BA",
            "Minas Gerais" => "MG",
            "Espírito Santo" => "ES",
            "Rio de Janeiro" => "RJ",
            "São Paulo" => "SP",
            "Paraná" => "PR",
            "Santa Catarina" => "SC",
            "Rio Grande do Sul (*)" => "RS",
            "Mato Grosso do Sul" => "MS",
            "Mato Grosso" => "MT",
            "Goiás" => "GO",
            "Distrito Federal" => "DF");
        $uf = "RJ";
        foreach ($array_ufs as $key => $value) {
            if ($key == $txt_uf) {
                $uf = $value;
                break;
            }
        }
        return $uf;
    }
}
