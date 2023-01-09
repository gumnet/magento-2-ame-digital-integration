<?php
/**
 * @author Gustavo Ulyssea - gustavo.ulyssea@gmail.com
 * @copyright Copyright (c) 2020-2022 GumNet (https://gum.net.br)
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

namespace GumNet\AME\Model;

use GumNet\AME\Model\Config\Environment;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Model\ScopeInterface;
use GumNet\AME\Model\Values\Config;
use Magento\Store\Model\StoreManagerInterface;

class GumApi
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    protected $url ="https://apiame.gum.net.br";

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ModuleList $moduleList
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ModuleList $moduleList,
        ProductMetadataInterface $productMetadata
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param string $ame_transaction_id
     * @param string $ame_refund_id
     * @param $amount
     * @return bool
     * @throws NoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function refundTransaction(string $ame_transaction_id, string $ame_refund_id, $amount): bool
    {
        $result = $ame_refund_id . "|" . $amount;
        return $this->gumRequest("refundtransaction", $result, $ame_transaction_id);
    }

    /**
     * @param string $json
     * @return void
     * @throws NoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function queueTransactionError(string $json)
    {
        $this->apiGumCallback("/api/ame/transactionerror/", "POST", $json);
    }

    /**
     * @param string $json
     * @return void
     * @throws NoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function queueTransaction(string $json): void
    {
        $this->apiGumCallback("/api/ame/transaction/", "POST", $json);
    }

    /**
     * @param string $ame_transaction_id
     * @param string $ame_order_id
     * @param $amount
     * @return bool
     * @throws NoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function captureTransaction(string $ame_transaction_id, string $ame_order_id, $amount): bool
    {
        $result = $ame_transaction_id . "|".$amount;
        return $this->gumRequest("capturetransaction", $result, $ame_order_id);
    }

    /**
     * @param $input
     * @param $result
     * @return bool
     * @throws NoSuchEntityException
     */
    public function createOrder($input, $result): bool
    {
        $input_array = json_decode($input, true);
        $input1['amount'] = $input_array['amount'];
        $json_input = json_encode($input1);
        $result_array = json_decode($result, true);
        $result1['id'] = $result_array['id'];
        $result1['amount'] = $result_array['amount'];
        $json_result = json_encode($result1);

        $this->gumRequest("createorder", $json_result, $json_input);
        return true;
    }

    /**
     * @param string $url
     * @param string $method
     * @param string $json
     * @return string
     * @throws NoSuchEntityException
     */
    public function apiGumCallback(string $url, string $method = "GET", string $json = ""): string
    {
        $url = $this->url . $url;

        $json_array['environment'] = $this->getEnvironment();
        $json_array['siteurl'] = $this->storeManager->getStore()->getBaseUrl();
        $json_array['username'] = $this->scopeConfig->getValue(Config::API_USER, ScopeInterface::SCOPE_STORE);
        $json_array['password'] = $this->scopeConfig->getValue(Config::API_PASSWORD, ScopeInterface::SCOPE_STORE);
        $json_array['magentoversion'] = $this->productMetadata->getVersion();
        $json_array['moduleversion'] = $this->moduleList->getOne('GumNet_AME')['setup_version'];
        $json_array['api'] = "ame";
        if ($this->scopeConfig->getValue('ame/general/environment', ScopeInterface::SCOPE_STORE) == 3) {
            $json_array['api'] = "sensedia";
        }
        $json_array['callback'] = $json;
        $json_array['hash'] = "E2F49DA5F963DAE26F07E778FB4B9301B051AEEA6E8E08D788163023876BC14E";
        $json = json_encode($json_array, JSON_PRETTY_PRINT);
        // Allow curl - do not use buggy Magento classes
        // phpcs:disable
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $re = curl_exec($ch);
        curl_close($ch);
        // phpcs:enable
        return $re;
    }

    /**
     * @param string $action
     * @param string $result
     * @param string $input
     * @return bool
     * @throws NoSuchEntityException
     */
    public function gumRequest(string $action, string $result, string $input = ""): bool
    {
        $environment = $this->getEnvironment();
        $post['environment'] = $environment;
        $post['siteurl'] = $this->storeManager->getStore()->getBaseUrl();
        $post['input'] = $input;
        $post['result'] = $result;
        $post['action'] = $action;
        $post['hash'] = "E2F49DA5F963DAE26F07E778FB4B9301B051AEEA6E8E08D788163023876BC14E";
        // phpcs:disable
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_exec($ch);
        $http_code = curl_getinfo ($ch,  CURLINFO_HTTP_CODE);
        curl_close($ch);
        // phpcs:enable
        if ($http_code == "200") {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        $envInt = $this->scopeConfig->getValue(Config::ENVIRONMENT, ScopeInterface::SCOPE_STORE);
        return $envInt === Environment::ENV_SENSEDIA_VALUE ? "prod" : "sensedia";
    }
}


