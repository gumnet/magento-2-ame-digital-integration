<?php
/**
 * @author Gustavo Ulyssea - gustavo.ulyssea@gmail.com
 * @copyright Copyright (c) 2020-2021 GumNet (https://gum.net.br)
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

class GumApi
{
    protected $_storeManager;
    protected $_scopeConfig;
    protected $url;
    protected $moduleList;
    protected $productMetadata;

    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Framework\Module\ModuleList $moduleList,
                                \Magento\Framework\App\ProductMetadataInterface $productMetadata
    )
    {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->url = "https://apiame.gum.net.br";
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
    }
    public function refundTransaction($ame_transaction_id,$ame_refund_id,$amount)
    {
        $result = $ame_refund_id . "|" . $amount;
        return $this->gumRequest("refundtransaction",$result,$ame_transaction_id);
    }
    public function queueTransactionError($json)
    {
        $this->apiGumCallback("/api/ame/transactionerror/","POST",$json);
    }

    public function queueTransaction($json)
    {
        $this->apiGumCallback("/api/ame/transaction/","POST",$json);
    }

    public function captureTransaction($ame_transaction_id,$ame_order_id,$amount)
    {
        $result = $ame_transaction_id . "|".$amount;
        return $this->gumRequest("capturetransaction",$result,$ame_order_id);
    }
    public function createOrder($input,$result)
    {
        $input_array = json_decode($input,true);
        $input1['amount'] = $input_array['amount'];
        $json_input = json_encode($input1);
        $result_array = json_decode($result,true);
        $result1['id'] = $result_array['id'];
        $result1['amount'] = $result_array['amount'];
        $json_result = json_encode($result1);

        $this->gumRequest("createorder",$json_result,$json_input);
        return true;
    }
    public function apiGumCallback($url, $method = "GET", $json = ""){
        $url = $this->url . $url;
        $ch = curl_init();

        $json_array['environment'] = $this->getEnvironment();
        $json_array['siteurl'] = $this->_storeManager->getStore()->getBaseUrl();
        $json_array['username'] = $this->_scopeConfig->getValue('ame/general/api_user', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $json_array['password'] = $this->_scopeConfig->getValue('ame/general/api_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $json_array['magentoversion'] = $this->productMetadata->getVersion();
        $json_array['moduleversion'] = $this->moduleList->getOne('GumNet_AME')['setup_version'];

        if (!$this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ||$this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 3) {
            $json_array['api'] = "sensedia";
        }
        else $json_array['api'] = "ame";

        $json_array['callback'] = $json;
        $json_array['hash'] = "E2F49DA5F963DAE26F07E778FB4B9301B051AEEA6E8E08D788163023876BC14E";
        $json = json_encode($json_array,JSON_PRETTY_PRINT);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $re = curl_exec($ch);
        curl_close($ch);
        return $re;
    }
    public function gumRequest($action,$result,$input=""){
        $ch = curl_init();
        $environment = $this->getEnvironment();
        $post['environment'] = $environment;
        $post['siteurl'] = $this->_storeManager->getStore()->getBaseUrl();
        $post['input'] = $input;
        $post['result'] = $result;
        $post['action'] = $action;
        $post['hash'] = "E2F49DA5F963DAE26F07E778FB4B9301B051AEEA6E8E08D788163023876BC14E";

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $re = curl_exec($ch);
        $http_code = curl_getinfo ($ch, CURLINFO_HTTP_CODE );
        curl_close($ch);
        if($http_code=="200") {
            return true;
        }
        else{
            return false;
        }
    }
    public function getEnvironment(){
//        $environment = "";
//        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 0) {
//            $environment = "dev";
//        }
//        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1) {
//            $environment = "hml";
//        }
//        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 2) {
//            $environment = "prod";
//        }
        return "prod";
    }
}


