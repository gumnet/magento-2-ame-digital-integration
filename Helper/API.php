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

namespace GumNet\AME\Helper;

use GumNet\AME\Api\AmeConfigRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use \Ramsey\Uuid\Uuid;
use GumNet\AME\Model\Values\Config;
use GumNet\AME\Model\Values\PaymentInformation;

class API
{
    const ORDER = "Pedido ";
    protected $mlogger;

    protected $scopeConfig;

    protected $storeManager;

    protected $dbAME;

    protected $gumapi;

    protected $ameConfigRepository;

    protected $curl;

//    protected = "https://api.dev.amedigital.com/api";
//    protected = "https://api.hml.amedigital.com/api";

    protected $url = "https://ame19gwci.gum.net.br:63333/api";

    protected $urlOrders = "orders";

    protected $urlPayments = "payments";


    /**
     * @param LoggerInterface $mlogger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param DbAME $dbAME
     * @param GumApi $gumApi
     * @param AmeConfigRepositoryInterface $ameConfigRepository
     */
    public function __construct(
        LoggerInterface $mlogger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        DbAME $dbAME,
        GumApi $gumApi,
        AmeConfigRepositoryInterface $ameConfigRepository
    ) {
        $this->mlogger = $mlogger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->dbAME = $dbAME;

        $this->gumapi = $gumApi;
        $this->ameConfigRepository = $ameConfigRepository;
    }

    /**
     * @return float
     * @throws NoSuchEntityException
     */
    public function getCashBackPercent(): float
    {
        $cashback_updated_at = $this->ameConfigRepository->getByConfig('cashback_updated_at')->getValue();
        if (time() < $cashback_updated_at + 3600) {
            return (float)$this->ameConfigRepository->getByConfig('cashback_percent')->getValue();
        } else {
            return $this->generateCashbackFromOrder();
        }
    }

    /**
     * @return float
     * @throws NoSuchEntityException
     */
    public function generateCashbackFromOrder(): float
    {
        $url = $this->url . "/" . $this->urlOrders;
        $orderId = rand(1000, 1000000);
        $jsonArray['title'] = self::ORDER . $orderId;
        $jsonArray['description'] = self::ORDER . $orderId;
        $jsonArray['amount'] = 10000;
        $jsonArray['currency'] = "BRL";
        $jsonArray['attributes']['transactionChangedCallbackUrl'] = $this->getCallbackUrl();
        $jsonArray['attributes']['items'] = [];

        $arrayItems['description'] = "Produto - SKU " . "38271686";
        $arrayItems['quantity'] = 1;
        $arrayItems['amount'] = 9800;
        $jsonArray['attributes']['items'][] = $arrayItems;
        $jsonArray['attributes']['customPayload']['ShippingValue'] = 200;
        $jsonArray['attributes']['customPayload']['shippingAddress']['country'] = "BRA";
        $jsonArray['attributes']['customPayload']['shippingAddress']['number'] = "234";
        $jsonArray['attributes']['customPayload']['shippingAddress']['city'] = "Niteroi";
        $jsonArray['attributes']['customPayload']['shippingAddress']['street'] = "Rua Presidente Backer";
        $jsonArray['attributes']['customPayload']['shippingAddress']['postalCode'] = "24220-041";
        $jsonArray['attributes']['customPayload']['shippingAddress']['neighborhood'] = "Icarai";
        $jsonArray['attributes']['customPayload']['shippingAddress']['state'] = "RJ";
        $jsonArray['attributes']['customPayload']['billingAddress'] =
            $jsonArray['attributes']['customPayload']['shippingAddress'];
        $jsonArray['attributes']['customPayload']['isFrom'] = "MAGENTO";
        $jsonArray['attributes']['paymentOnce'] = true;
        $jsonArray['attributes']['riskHubProvider'] = "SYNC";
        $jsonArray['attributes']['origin'] = "ECOMMERCE";
        $json = json_encode($jsonArray);
        $result = $this->ameRequest($url, "POST", $json);
        $resultArray = json_decode($result, true);
        if ($this->hasError($result, $url, $json)) {
            return 0;
        }
        $cashbackAmountValue = 0;
        if (array_key_exists('cashbackAmountValue', $resultArray['attributes'])) {
            $cashbackAmountValue = $resultArray['attributes']['cashbackAmountValue'];
        }
        $cashback_percent = $cashbackAmountValue/100;
        $this->setCashbackPercent($cashback_percent);
        return (float)$cashback_percent;
    }

    /**
     * @param $cashback_percent
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function setCashbackPercent($cashback_percent): void
    {
        $config = $this->ameConfigRepository->getByConfig('cashback_updated_at');
        $config->setValue(time());
        $this->ameConfigRepository->save($config);
        $config = $this->ameConfigRepository->getByConfig('cashback_percent');
        $config->setValue($cashback_percent);
        $this->ameConfigRepository->save($config);
    }

    public function refundOrder(string $ame_id, float $amount): array
    {
        $transactionId = $this->dbAME->getTransactionIdByOrderId($ame_id);

        $refundId = Uuid::uuid4()->toString();
        while ($this->dbAME->refundIdExists($refundId)) {
            $refundId = Uuid::uuid4()->toString();
        }
        $this->mlogger->info("AME REFUND ID:" . $refundId);
        $url = $this->url . "/" . $this->urlPayments ."/" . $transactionId . "/refunds/MAGENTO-" . $refundId;

        $jsonArray['amount'] = $amount;
        $json = json_encode($jsonArray);
        $result[0] = $this->ameRequest($url, "PUT", $json);
        $this->mlogger->info("AME REFUND Result:" . $result[0]);
        if ($this->hasError($result[0], $url, $json)) {
            return [];
        }
        $result[1] = $refundId;
        return $result;
    }

    /**
     * @param string $ame_id
     * @return bool
     */
    public function cancelOrder(string $ame_id): bool
    {
        $transactionId = $this->dbAME->getTransactionIdByOrderId($ame_id);
        if (!$transactionId) {
            return false;
        }
        $url = $this->url . "/wallet/user/payments/" . $transactionId . "/cancel";
        $result = $this->ameRequest($url, "PUT", "");
        if ($this->hasError($result, $url, "")) {
            return false;
        }
        return true;
    }

    public function consultOrder(string $ame_id): string
    {
        $url = $this->url . "/" . $this->urlOrders . "/" . $ame_id;
        $result = $this->ameRequest($url, "GET", "");
        if ($this->hasError($result, $url)) {
            return "";
        }
        return $result;
    }

    public function captureOrder(string $ame_id): ?array
    {
        $ame_transaction_id = $this->dbAME->getTransactionIdByOrderId($ame_id);
        $url = $this->url . "/wallet/user/payments/" . $ame_transaction_id . "/capture";
        $result = $this->ameRequest($url, "PUT", "");
        if ($this->hasError($result, $url)) {
            return null;
        }
        return json_decode($result, true);
    }

    public function createOrder($order): string
    {
        /** @var Order $order */
        $this->mlogger->info("Create AME Order");
        $url = $this->url . "/" . $this->urlOrders;
        $amount = (int)$order->getGrandTotal() * 100;

        $jsonArray['title'] = self::ORDER . $order->getIncrementId();
        $jsonArray['description'] = self::ORDER . $order->getIncrementId();
        $jsonArray['amount'] = $amount;
        $jsonArray['currency'] = "BRL";
        $jsonArray['attributes']['transactionChangedCallbackUrl'] = $this->getCallbackUrl();
        $jsonArray['attributes']['items'] = [];

        $items = $order->getAllItems();
        $total_discount = 0;
        foreach ($items as $item) {
            if (isset($arrayItems)) {
                unset($arrayItems);
            }
            $arrayItems['description'] = $item->getName() . " - SKU " . $item->getSku();
            $arrayItems['quantity'] = (int)$item->getQtyOrdered();
            $arrayItems['amount'] = (int)(($item->getRowTotal() - $item->getDiscountAmount()) * 100);
            $total_discount = $total_discount + abs($item->getDiscountAmount());
            $jsonArray['attributes']['items'][] = $arrayItems;
        }

        $jsonArray['attributes']['customPayload']['ShippingValue'] = (int)$order->getShippingAmount() * 100;
        $jsonArray['attributes']['customPayload']['shippingAddress']['country'] = "BRA";

        $number_line = $this->scopeConfig->getValue(
            Config::ADDRESS_NUMBER,
            ScopeInterface::SCOPE_STORE
        );
        $jsonArray['attributes']['customPayload']['shippingAddress']['number'] =
            $order->getShippingAddress()->getStreet()[$number_line];

        $jsonArray['attributes']['customPayload']['shippingAddress']['city'] = $order->getShippingAddress()->getCity();

        $street_line = $this->scopeConfig->getValue(
            Config::ADDRESS_STREET,
            ScopeInterface::SCOPE_STORE
        );
        $jsonArray['attributes']['customPayload']['shippingAddress']['street'] =
            $order->getShippingAddress()->getStreet()[$street_line];

        $jsonArray['attributes']['customPayload']['shippingAddress']['postalCode'] =
            $order->getShippingAddress()->getPostcode();

        $neighborhood_line = $this->scopeConfig->getValue(
            Config::ADDRESS_NEIGHBORHOOD,
            ScopeInterface::SCOPE_STORE
        );
        $jsonArray['attributes']['customPayload']['shippingAddress']['neighborhood'] =
            $order->getShippingAddress()->getStreet()[$neighborhood_line];

        $jsonArray['attributes']['customPayload']['shippingAddress']['state'] =
            $this->codigoUF($order->getShippingAddress()->getRegion());

        $jsonArray['attributes']['customPayload']['billingAddress'] =
            $jsonArray['attributes']['customPayload']['shippingAddress'];
        $jsonArray['attributes']['customPayload']['isFrom'] = "MAGENTO";
        $jsonArray['attributes']['paymentOnce'] = true;
        $jsonArray['attributes']['riskHubProvider'] = "SYNC";
        $jsonArray['attributes']['origin'] = "ECOMMERCE";

        $json = json_encode($jsonArray);
        $this->mlogger->info($json);
        $result = $this->ameRequest($url, "POST", $json);

        if ($this->hasError($result, $url, $json)) {
            return "";
        }
        $this->gumapi->createOrder($json, $result);
        $resultArray = json_decode($result, true);

        $payment = $order->getPayment();
        $this->setAdditionalInformation($payment, $resultArray);
        $payment->save();

        return $result;
    }

    /**
     * @param $order
     * @param array $resultArray
     * @return void
     */
    public function setAdditionalInformation(
        OrderInterface $order,
        array $resultArray
    ): void {
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(PaymentInformation::AME_ID, $resultArray['id']);
        $payment->setAdditionalInformation(PaymentInformation::AMOUNT, $resultArray['amount']);
        $payment->setAdditionalInformation(PaymentInformation::QR_CODE_LINK, $resultArray['qrCodeLink']);
        $payment->setAdditionalInformation(PaymentInformation::DEEP_LINK, $resultArray['deepLink']);
        if (array_key_exists('cashbackAmountValue', $resultArray['attributes'])) {
            $payment->setAdditionalInformation(
                PaymentInformation::CASHBACK_VALUE,
                $resultArray['attributes']['cashbackAmountValue']
            );
        }
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCallbackUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }

    public function hasError(string $result, string $url, string $input = ""): bool
    {
        if (!$this->isJson($result)) {
            return false;
        }
        $resultArray = json_decode($result, true);
        if (is_array($resultArray)) {
            if (array_key_exists("error", $resultArray)) {
                $subject = "AME Error";
                $message = "Result: ".$result."\r\n\r\nurl: ".$url."\r\n\r\n";
                $this->mlogger->error($subject . "-" . $message);
                return true;
            }
        } else {
            $this->mlogger->info("ameRequest hasError:" . $result);
            return true;
        }
        return false;
    }

    /**
     * @param string $string
     * @return bool
     */
    public function isJson(string $string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @return string
     */
    public function getStoreName(): string
    {
        return $this->scopeConfig->getValue('ame/general/store_name', ScopeInterface::SCOPE_STORE);
    }

    public function ameRequest(string $url, string $method = "GET", string $json = ""): string
    {
        $this->mlogger->info("ameRequest starting...");
        $token = $this->getToken();
        if (!$token) {
            return "";
        }

        $method = strtoupper($method);
        // phpcs:disable
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $token];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        if ($method == "POST" || $method == "PUT") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);
        // phpcs:enable
        return $result;
    }

    public function getTokenFromDb(): string
    {
        $config = $this->ameConfigRepository->getByConfig('token_expires');
        $token_expires = (int)$config->getValue();
        if (time() + 600 < $token_expires) {
            $ameConfig = $this->ameConfigRepository->getByConfig('token_value');
            return $ameConfig->getValue();
        }
        return "";
    }

    public function getToken(): string
    {
        // check if existing token will be expired within 10 minutes
        if ($token = $this->getTokenFromDb()) {
            return $token;
        }
        // get user & pass from core_config_data
        $username = $this->scopeConfig->getValue(
            Config::API_USER,
            ScopeInterface::SCOPE_STORE
        );
        $password = $this->scopeConfig->getValue(
            Config::API_PASSWORD,
            ScopeInterface::SCOPE_STORE
        );
        if (!$username || !$password) {
            $this->mlogger->error("AME user/pass not set. Please check Magento configuration");
            return "";
        }
        $url = $this->url . "/auth/oauth/token";
        // phpcs:disable
        $ch = curl_init();
        $post = "grant_type=client_credentials";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $header = ['Content-Type: application/x-www-form-urlencoded'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($ch);
        if ($this->hasError($result, $url, $post)) {
            curl_close($ch);
            // Known issue - invert username/password
            $userTmp = $username;
            $username = $password;
            $password = $userTmp;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $result = curl_exec($ch);
            // phpcs:disable
            if ($this->hasError($result, $url, $post)) {
                return "";
            }
        }
        $resultArray = json_decode($result, true);
        if (!array_key_exists('access_token', $resultArray)) {
            return "";
        }

        $expires_in = time() + (int)$resultArray['expires_in'];
        $this->storeToken($resultArray['access_token'], $expires_in);
        return $resultArray['access_token'];
    }

    public function storeToken(string $token, int $expires_in): void
    {
        $ameConfig = $this->ameConfigRepository->getByConfig('token_value');
        $ameConfig->setValue($token);
        $this->ameConfigRepository->save($ameConfig);
        $ameConfig = $this->ameConfigRepository->getByConfig('token_expires');
        $ameConfig->setValue($expires_in);
        $this->ameConfigRepository->save($ameConfig);
    }

    /**
     * @param string $txt_uf
     * @return string
     */
    public function codigoUF(string $txt_uf): string
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
