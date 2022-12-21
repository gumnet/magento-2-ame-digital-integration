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

use GumNet\AME\Api\AmeConfigRepositoryInterface;
use GumNet\AME\Model\Config\Environment;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use GumNet\AME\Model\Values\Config;
use GumNet\AME\Model\Values\PaymentInformation;

class ApiClient
{
    public const ORDER = 'Pedido ';

    protected $url = Config::AME_API_URL;

    protected $urlOrders = "orders";

    protected $urlPayments = "payments";

    protected $urlTransaction = "";

    protected $urlCancelTransaction = "wallet/user/payments";

    protected $urlCancelEnd = "";

    protected $urlTrustWallet = "trust-wallet/v1/orders";

    protected $urlOrderTrustWallet = "transactions/v1/orders";

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GumApi
     */
    protected $gumapi;

    /**
     * @var AmeConfigRepositoryInterface
     */
    protected $ameConfigRepository;

    protected $apiType = 0;


    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param GumApi $gumApi
     * @param AmeConfigRepositoryInterface $ameConfigRepository
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        GumApi $gumApi,
        AmeConfigRepositoryInterface $ameConfigRepository
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        $this->gumapi = $gumApi;
        $this->ameConfigRepository = $ameConfigRepository;
        $this->setApiDetails();
    }

    protected function setApiDetails()
    {
        if ($this->getApiType() == Environment::ENV_SENSEDIA_VALUE) {
            $this->url = Config::SENSEDIA_API_URL;
            $this->urlOrders = 'transactions/v1/orders';
            $this->urlTransaction = 'transactions/v1';
            $this->urlPayments = 'pagamentos';
            $this->urlCancelTransaction = 'transactions/v1/payments';
            $this->urlCancelEnd = 'cancel';
        }
    }

    /**
     * @return float
     */
    public function getCashBackPercent(): float
    {
        try {
            $cashbackUpdatedAt = $this->ameConfigRepository->getByConfig(Config::CASHBACK_UPDATED_AT)->getValue();
            if (time() < $cashbackUpdatedAt + 3600) {
                return (float)$this->ameConfigRepository->getByConfig(Config::CASHBACK_PERCENT)->getValue();
            } else {
                return $this->generateCashbackFromOrder();
            }
        } catch (LocalizedException $e) {
            return 0;
        }
    }

    /**
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function generateCashbackFromOrder(): float
    {
        $url = $this->url . "/" . $this->urlOrders;
        $orderId = rand(1000, 1000000);

        $jsonArray = [
            'title' => self::ORDER . $orderId,
            'description' => self::ORDER . $orderId,
            'amount' => 10000,
            'currency' => 'BRL',
            'attributes' => [
                'transactionChangedCallbackUrl' => $this->getCallbackUrl(),
                'items' => [[
                    'description' => "Produto - SKU " . "38271686",
                    'quantity' => 1,
                    'amount' => 9800,
                ]],
                'customPayload' => [
                    'ShippingValue' => 200,
                    'shippingAddress' => [
                        'country' => 'BRA',
                        'number' => '234',
                        'city' => 'Niteroi',
                        'street' => 'Rua Presidente Backer',
                        'postalCode' => '24220-041',
                        'neighborhood' => 'Icarai',
                        'state' => 'RJ',
                    ],
                    'isFrom' => 'MAGENTO',
                ],
                'paymentOnce' => true,
                'riskHubProvider' => 'SYNC',
                'origin' => 'ECOMMERCE'
            ]
        ];

        $jsonArray['attributes']['customPayload']['billingAddress'] =
            $jsonArray['attributes']['customPayload']['shippingAddress'];

        $json = json_encode($jsonArray);
        $result = $this->ameRequest($url, "POST", $json, false);
        $resultArray = json_decode($result, true);
        if ($this->hasError($result, $url, $json)) {
            return 0;
        }
        $cashbackAmountValue = 0;
        if (is_array($resultArray) && array_key_exists('attributes', $resultArray)
            && array_key_exists('cashbackAmountValue', $resultArray['attributes'])) {
            $cashbackAmountValue = $resultArray['attributes']['cashbackAmountValue'];
        }
        $cashbackPercent = $cashbackAmountValue/100;
        $this->setCashbackPercent($cashbackPercent);
        return (float)$cashbackPercent;
    }

    /**
     * @param $cashbackPercent
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function setCashbackPercent($cashbackPercent): void
    {
        $config = $this->ameConfigRepository->getByConfig(Config::CASHBACK_UPDATED_AT);
        $config->setValue(time());
        $this->ameConfigRepository->save($config);
        $config = $this->ameConfigRepository->getByConfig(Config::CASHBACK_PERCENT);
        $config->setValue($cashbackPercent);
        $this->ameConfigRepository->save($config);
    }

    /**
     * @param string $ameId
     * @return bool
     */
    public function cancelOrder(string $ameId): bool
    {

        $url = $this->url . $this->urlOrders . $ameId;
        if ($this->getApiType() == Environment::ENV_SENSEDIA_VALUE) {
            $ameOrder = $this->consultOrder($ameId);
            $jsonArray = json_decode($ameOrder, true);
            $ownerId = $jsonArray['ownerId'];
            $url = $this->url . "/transactions/v1/customers/" . $ownerId . "/orders/" . $ameId;
        }
        $result = $this->ameRequest($url, "DELETE", "");
        if ($this->hasError($result, $url, "")) {
            return false;
        }
        return true;
    }

    /**
     * @param string $transactionId
     * @param float $amount
     * @return array
     * @throws \Exception
     */
    public function refundOrder(string $transactionId, float $amount): array
    {
        $url = $this->url . "/" . $this->urlPayments ."/" . $transactionId;

        $refundId = Uuid::uuid4()->toString();
        $url .= "/refunds/MAGENTO-" . $refundId;
        $jsonArray['amount'] = $amount;
        $json = json_encode($jsonArray);
        $method = "PUT";
        if($this->getApiType() == Environment::ENV_SENSEDIA_VALUE) {
            $method = "POST";
        }
        $result[0] = $this->ameRequest($url, $method, $json);
        $this->logger->info("AME REFUND Result:" . $result[0]);
        if ($this->hasError($result[0], $url, $json)) {
            return [];
        }
        $result[1] = $refundId;
        return $result;
    }

    /**
     * @param string $ameId
     * @return bool
     */
    public function cancelTransaction(string $transactionId): bool
    {
        $method = "PUT";
        if ($this->getApiType() == Environment::ENV_SENSEDIA_VALUE) {
            $method = "POST";
        }
        $url = $this->url . "/" . $this->urlCancelTransaction ."/" . $transactionId . "/" . $this->urlCancelEnd;

        $result = $this->ameRequest($url, $method, "");
        if ($this->hasError($result, $url, "")) {
            return false;
        }
        return true;
    }

    /**
     * @param string $ameId
     * @return string
     */
    public function consultOrder(string $ameId): string
    {
        $url = $this->url . "/" . $this->urlOrders . "/" . $ameId;
        $result = $this->ameRequest($url, "GET", "");
        if ($this->hasError($result, $url)) {
            return "";
        }
        return $result;
    }

    /**
     * @param $order
     * @return string
     * @throws NoSuchEntityException
     * @throws IntegrationException
     */
    public function createOrder($order): string
    {
        /** @var Order $order */
        $url = $this->url . "/" . $this->urlOrders;
        if ($this->trustWalletIsEnabled()) {
            $url = $this->url . "/" . $this->urlOrderTrustWallet;
        }
        $amount = (int)$order->getGrandTotal() * 100;

        $number_line = $this->scopeConfig->getValue(
            Config::ADDRESS_NUMBER,
            ScopeInterface::SCOPE_STORE
        );
        $street_line = $this->scopeConfig->getValue(
            Config::ADDRESS_STREET,
            ScopeInterface::SCOPE_STORE
        );
        $neighborhood_line = $this->scopeConfig->getValue(
            Config::ADDRESS_NEIGHBORHOOD,
            ScopeInterface::SCOPE_STORE
        );

        $jsonArray = [
            'title' => self::ORDER . $order->getIncrementId(),
            'description' => self::ORDER . $order->getIncrementId(),
            'amount' => $amount,
            'type' => 'PAYMENT',
            'currency' => 'BRL',
            'attributes' => [
                'transactionChangedCallbackUrl' => $this->getCallbackUrl(),
                'items' => $this->getItemsArray($order),
                'customPayload' => [
                    'ShippingValue' => (int)$order->getShippingAmount() * 100,
                    'shippingAddress' => [
                        'country' => 'BRA',
                        'number' => $order->getShippingAddress()->getStreet()[$number_line],
                        'street' => $order->getShippingAddress()->getStreet()[$street_line],
                        'city' => $order->getShippingAddress()->getCity(),
                        'postalCode' => $order->getShippingAddress()->getPostcode(),
                        'neighborhood' => $order->getShippingAddress()->getStreet()[$neighborhood_line],
                        'state' => $this->codigoUF($order->getShippingAddress()->getRegion()),
                    ]
                ],
                'isFrom' => 'MAGENTO',
                'paymentOnce' => true,
                'riskHubProvider' => 'SYNC',
                'origin' => 'ECOMMERCE'
            ]
        ];

        $jsonArray = $this->addTrustWalletData($jsonArray);

        $jsonArray['attributes']['customPayload']['billingAddress'] =
            $jsonArray['attributes']['customPayload']['shippingAddress'];

        $json = json_encode($jsonArray);
        $this->logger->info($json);
        $result = $this->ameRequest($url, "POST", $json);
        if (!$this->isJson($result)) {
            $this->logger->critical("AME API invalid JSON: " . $result);
            throw new IntegrationException(__('There was an error placing your order. Please contact support.'));
        }

        if ($this->hasError($result, $url, $json)) {
            return "";
        }
        $this->gumapi->createOrder($json, $result);
        return $result;
    }

    /**
     * @param $order
     * @return array
     */
    public function getItemsArray($order): array
    {
        $items = $order->getAllItems();
        $jsonArray = [];
        foreach ($items as $item) {
            if (isset($arrayItems)) {
                unset($arrayItems);
            }
            $arrayItems = [
                'description' => $item->getName() . " - SKU " . $item->getSku(),
                'quantity' => (int)$item->getQtyOrdered(),
                'amount' => (int)(($item->getRowTotal() - $item->getDiscountAmount()) * 100)
            ];
            $jsonArray[] = $arrayItems;
        }
        return $jsonArray;
    }

    /**
     * @param array $jsonArray
     * @return array
     */
    public function addTrustWalletData(array $jsonArray): array
    {
        if ($this->scopeConfig->getValue(Config::TRUST_WALLET_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $jsonArray['subType'] = "TRUST_WALLET";
            $jsonArray['attributes']['trustWallet']['enabled'] = true;
        }
        return $jsonArray;
    }

    /**
     * @param $order
     * @param array $resultArray
     * @return void
     */
    public function setAdditionalInformation(
        $payment,
        array $resultArray
    ): void {
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
        $payment->save();
    }

    /**
     * @param string $trustWalletId
     * @param float $value
     * @param string $title
     * @param string $description
     * @param array $items
     * @return string
     * @throws NoSuchEntityException
     */
    public function chargeTrustWallet(
        string $trustWalletId,
        float $value,
        string $title,
        string $description,
        array $items
    ): string {
        $url = $this->url . "/";
        $jsonArray = [
            'linkUuid' => $trustWalletId,
            'amountInCents' => (int)$value * 100,
            'title' => $title,
            'description' => $description,
            'attributes' =>
            [
                'transactionChangedCallbackUrl' => $this->getCallbackUrl(),
                'items' => $items
            ]
        ];
        return $this->ameRequest($url, "POST", json_encode($jsonArray));
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCallbackUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }

    /**
     * @param string $result
     * @param string $url
     * @param string $input
     * @return bool
     */
    public function hasError(string $result, string $url, string $input = ""): bool
    {
        if (!$this->isJson($result)) {
            return false;
        }
        $resultArray = json_decode($result, true);
        if (is_array($resultArray)) {
            if (array_key_exists("error", $resultArray)) {
                $subject = "AME Error";
                $message = "Input: " . $input . "\n";
                $message .= "Result: ".$result."\r\n\r\nurl: ".$url."\r\n\r\n";
                $this->logger->error($subject . "-" . $message);
                return true;
            }
        } else {
            $this->logger->info("ameRequest hasError:" . $result);
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

    /**
     * @param string $url
     * @param string $method
     * @param string $json
     * @param bool $enableLog
     * @return string
     * @throws NoSuchEntityException
     */
    public function ameRequest(string $url, string $method = "GET", string $json = "", bool $enableLog = true): string
    {
        if (!$token = $this->getToken()) {
            return "";
        }
        $this->logger->info("AME using token: " . $token);
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $token];

        $method = strtoupper($method);
        // Allow curl - do not use buggy Magento classes
        // phpcs:disable
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method == "POST" || $method == "PUT") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);
        // phpcs:enable
        if ($enableLog) {
            $this->logger->info("AME API URL: " . $url);
            $this->logger->info("AME API Input: " . $json);
            $this->logger->info("AME API Result: " . $result);
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function trustWalletIsEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            Config::TRUST_WALLET_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getTokenFromDb(): string
    {
        $config = $this->ameConfigRepository->getByConfig('token_expires');
        $token_expires = (int)$config->getValue();
        if (time() + Config::TOKEN_EXPIRES_SECONDS < $token_expires) {
            $ameConfig = $this->ameConfigRepository->getByConfig('token_value');
            return $ameConfig->getValue();
        }
        return "";
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
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
            $this->logger->error("AME user/pass not set. Please check Magento configuration");
            return "";
        }
        $url = $this->url . "/auth/oauth/token";
        if ($this->getApiType() === Environment::ENV_SENSEDIA_VALUE) {
            $url = $this->url . "/auth/v1/login";
        }
        // Allow curl - do not use buggy Magento classes
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

    /**
     * @return int
     */
    public function getApiType(): int
    {
        if (!$this->apiType) {
            $this->apiType = $this->scopeConfig->getValue(
                Config::ENVIRONMENT,
                ScopeInterface::SCOPE_STORE
            );
        }
        return $this->apiType;
    }

    /**
     * @param string $token
     * @param int $expiresIn
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function storeToken(string $token, int $expiresIn): void
    {
        $ameConfig = $this->ameConfigRepository->getByConfig(Config::TOKEN_VALUE);
        $ameConfig->setValue($token);
        $this->ameConfigRepository->save($ameConfig);
        $ameConfig = $this->ameConfigRepository->getByConfig(Config::TOKEN_EXPIRES);
        $ameConfig->setValue((string)$expiresIn);
        $this->ameConfigRepository->save($ameConfig);
    }

    /**
     * @param string $txtUf
     * @return string
     */
    public function codigoUF(string $txtUf): string
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
            if ($key == $txtUf) {
                $uf = $value;
                break;
            }
        }
        return $uf;
    }
}
