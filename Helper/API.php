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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use \Ramsey\Uuid\Uuid;
use GumNet\AME\Model\Values\Config;
use GumNet\AME\Model\Values\PaymentInformation;

class API
{
    public const URL = "https://ame19gwci.gum.net.br:63333/api";
// Do not remove the following lines - used for development
//    public const URL = "https://api.dev.amedigital.com/api";
//    public const URL = "https://api.hml.amedigital.com/api";

    protected $url;

    protected $mlogger;

    protected $scopeConfig;

    protected $storeManager;

    protected $dbAME;

    protected $gumapi;

    protected $ameConfigRepository;

    public function __construct(
        \Psr\Log\LoggerInterface $mlogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \GumNet\AME\Helper\DbAME $dbAME,
        \GumNet\AME\Helper\GumApi $gumApi,
        \GumNet\AME\Api\AmeConfigRepositoryInterface $ameConfigRepository
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
        $url = self::URL . "/orders";
        $pedido = rand(1000, 1000000);
        $json_array['title'] = "Pedido " . $pedido;
        $json_array['description'] = "Pedido " . $pedido;
        $json_array['amount'] = 10000;
        $json_array['currency'] = "BRL";
        $json_array['attributes']['transactionChangedCallbackUrl'] = $this->getCallbackUrl();
        $json_array['attributes']['items'] = [];

        $array_items['description'] = "Produto - SKU " . "38271686";
        $array_items['quantity'] = 1;
        $array_items['amount'] = 9800;
        array_push($json_array['attributes']['items'], $array_items);
        $json_array['attributes']['customPayload']['ShippingValue'] = 200;
        $json_array['attributes']['customPayload']['shippingAddress']['country'] = "BRA";
        $json_array['attributes']['customPayload']['shippingAddress']['number'] = "234";
        $json_array['attributes']['customPayload']['shippingAddress']['city'] = "Niteroi";
        $json_array['attributes']['customPayload']['shippingAddress']['street'] = "Rua Presidente Backer";
        $json_array['attributes']['customPayload']['shippingAddress']['postalCode'] = "24220-041";
        $json_array['attributes']['customPayload']['shippingAddress']['neighborhood'] = "Icarai";
        $json_array['attributes']['customPayload']['shippingAddress']['state'] = "RJ";
        $json_array['attributes']['customPayload']['billingAddress'] = $json_array['attributes']['customPayload']['shippingAddress'];
        $json_array['attributes']['customPayload']['isFrom'] = "MAGENTO";
        $json_array['attributes']['paymentOnce'] = true;
        $json_array['attributes']['riskHubProvider'] = "SYNC";
        $json_array['attributes']['origin'] = "ECOMMERCE";
        $json = json_encode($json_array);
        $result = $this->ameRequest($url, "POST", $json);
        $result_array = json_decode($result, true);
        if ($this->hasError($result, $url, $json)) {
            return 0;
        }
        $cashbackAmountValue = 0;
        if (array_key_exists('cashbackAmountValue', $result_array['attributes'])) {
            $cashbackAmountValue = $result_array['attributes']['cashbackAmountValue'];
        }
        $cashback_percent = $cashbackAmountValue/100;
        $this->setCashbackPercent($cashback_percent);
        return (float)$cashback_percent;
    }

    protected function setCashbackPercent($cashback_percent)
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
        $transaction_id = $this->dbAME->getTransactionIdByOrderId($ame_id);

        $refund_id = Uuid::uuid4()->toString();
        while ($this->dbAME->refundIdExists($refund_id)) {
            $refund_id = Uuid::uuid4()->toString();
        }
        $this->mlogger->info("AME REFUND ID:" . $refund_id);
        $url = self::URL . "/payments/" . $transaction_id . "/refunds/MAGENTO-" . $refund_id;

        $json_array['amount'] = $amount;
        $json = json_encode($json_array);
        $result[0] = $this->ameRequest($url, "PUT", $json);
        $this->mlogger->info("AME REFUND Result:" . $result[0]);
        if ($this->hasError($result[0], $url, $json)) {
            return "";
        }
        $result[1] = $refund_id;
        return $result;
    }

    /**
     * @param string $ame_id
     * @return bool
     */
    public function cancelOrder(string $ame_id): bool
    {
        $transaction_id = $this->dbAME->getTransactionIdByOrderId($ame_id);
        if (!$transaction_id) {
            return false;
        }
        $url = self::URL . "/wallet/user/payments/" . $transaction_id . "/cancel";
        $result = $this->ameRequest($url, "PUT", "");
        if ($this->hasError($result, $url, "")) {
            return false;
        }
        return true;
    }

    public function consultOrder(string $ame_id): string
    {
        $url = self::URL . "/orders/" . $ame_id;
        $result = $this->ameRequest($url, "GET", "");
        if ($this->hasError($result, $url)) {
            return "";
        }
        return $result;
    }
    public function captureOrder(string $ame_id): ?array
    {
        $ame_transaction_id = $this->dbAME->getTransactionIdByOrderId($ame_id);
        $url = self::URL . "/wallet/user/payments/" . $ame_transaction_id . "/capture";
        $result = $this->ameRequest($url, "PUT", "");
        if ($this->hasError($result, $url)) {
            return null;
        }
        return json_decode($result, true);
    }

    public function createOrder($order): string
    {
        /** @var \Magento\Sales\Model\Order $order */
        $this->mlogger->info("Create AME Order");
        $url = self::URL . "/orders";

        $shippingAmount = $order->getShippingAmount();
        $amount = (int)$order->getGrandTotal() * 100;

        $json_array['title'] = "Pedido " . $order->getIncrementId();
        $json_array['description'] = "Pedido " . $order->getIncrementId();
        $json_array['amount'] = $amount;
        $json_array['currency'] = "BRL";
        $json_array['attributes']['transactionChangedCallbackUrl'] = $this->getCallbackUrl();
        $json_array['attributes']['items'] = [];

        $items = $order->getAllItems();
        $total_discount = 0;
        foreach ($items as $item) {
            if (isset($array_items)) {
                unset($array_items);
            }
            $array_items['description'] = $item->getName() . " - SKU " . $item->getSku();
            $array_items['quantity'] = (int)$item->getQtyOrdered();
            $array_items['amount'] = (int)(($item->getRowTotal() - $item->getDiscountAmount()) * 100);
            $total_discount = $total_discount + abs($item->getDiscountAmount());
            $json_array['attributes']['items'][] = $array_items;
        }

        $json_array['attributes']['customPayload']['ShippingValue'] = (int)$order->getShippingAmount() * 100;
        $json_array['attributes']['customPayload']['shippingAddress']['country'] = "BRA";

        $number_line = $this->scopeConfig->getValue(
            Config::ADDRESS_NUMBER,
            ScopeInterface::SCOPE_STORE
        );
        $json_array['attributes']['customPayload']['shippingAddress']['number'] =
            $order->getShippingAddress()->getStreet()[$number_line];

        $json_array['attributes']['customPayload']['shippingAddress']['city'] = $order->getShippingAddress()->getCity();

        $street_line = $this->scopeConfig->getValue(
            Config::ADDRESS_STREET,
            ScopeInterface::SCOPE_STORE
        );
        $json_array['attributes']['customPayload']['shippingAddress']['street'] =
            $order->getShippingAddress()->getStreet()[$street_line];

        $json_array['attributes']['customPayload']['shippingAddress']['postalCode'] =
            $order->getShippingAddress()->getPostcode();

        $neighborhood_line = $this->scopeConfig->getValue(
            Config::ADDRESS_NEIGHBORHOOD,
            ScopeInterface::SCOPE_STORE
        );
        $json_array['attributes']['customPayload']['shippingAddress']['neighborhood'] =
            $order->getShippingAddress()->getStreet()[$neighborhood_line];

        $json_array['attributes']['customPayload']['shippingAddress']['state'] =
            $this->codigoUF($order->getShippingAddress()->getRegion());

        $json_array['attributes']['customPayload']['billingAddress'] =
            $json_array['attributes']['customPayload']['shippingAddress'];
        $json_array['attributes']['customPayload']['isFrom'] = "MAGENTO";
        $json_array['attributes']['paymentOnce'] = true;
        $json_array['attributes']['riskHubProvider'] = "SYNC";
        $json_array['attributes']['origin'] = "ECOMMERCE";

        $json = json_encode($json_array);
        $this->mlogger->info($json);
        $result = $this->ameRequest($url, "POST", $json);

        if ($this->hasError($result, $url, $json)) {
            return "";
        }
        $this->gumapi->createOrder($json, $result);
        $result_array = json_decode($result, true);

        $payment = $order->getPayment();
        $payment->setAdditionalInformation(PaymentInformation::AME_ID, $result_array['id']);
        $payment->setAdditionalInformation(PaymentInformation::AMOUNT, $result_array['amount']);
        $payment->setAdditionalInformation(PaymentInformation::QR_CODE_LINK, $result_array['qrCodeLink']);
        $payment->setAdditionalInformation(PaymentInformation::DEEP_LINK, $result_array['deepLink']);
        $payment->save();

        return $result;
    }

    /**
     * @param $order
     * @param array $result_array
     * @return void
     */
    public function setAdditionalInformation(
        \Magento\Sales\Api\Data\OrderInterface $order,
        array $result_array
    ): void {
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(PaymentInformation::AME_ID, $result_array['id']);
        $payment->setAdditionalInformation(PaymentInformation::AMOUNT, $result_array['amount']);
        $payment->setAdditionalInformation(PaymentInformation::QR_CODE_LINK, $result_array['qrCodeLink']);
        $payment->setAdditionalInformation(PaymentInformation::DEEP_LINK, $result_array['deepLink']);
        $cashbackAmountValue = 0;
        if (array_key_exists('cashbackAmountValue', $result_array['attributes'])) {
            $payment->setAdditionalInformation(
                PaymentInformation::CASHBACK_VALUE,
                $result_array['attributes']['cashbackAmountValue']
            );
        }
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCallbackUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }

    public function hasError(string $result, string $url, string $input = ""): bool
    {
        $result_array = json_decode($result, true);
        if (is_array($result_array)) {
            if (array_key_exists("error", $result_array)) {
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

    public function getStoreName(): string
    {
        return $this->scopeConfig->getValue('ame/general/store_name', ScopeInterface::SCOPE_STORE);
    }

    public function ameRequest(string $url, string $method = "GET", string $json = ""): string
    {
        $this->mlogger->info("ameRequest starting...");
        $_token = $this->getToken();
        if (!$_token) {
            return "";
        }
        $method = strtoupper($method);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $_token];
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
            'ame/general/api_user',
            ScopeInterface::SCOPE_STORE
        );
        $password = $this->scopeConfig->getValue(
            'ame/general/api_password',
            ScopeInterface::SCOPE_STORE
        );
        if (!$username || !$password) {
            $this->mlogger->info("AME user/pass not set.");
            return "";
        }
        $url = self::URL . "/auth/oauth/token";
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
            if ($this->hasError($result, $url, $post)) {
                return "";
            }
        }
        $result_array = json_decode($result, true);
        if (!array_key_exists('access_token', $result_array)) {
            return "";
        }

        $expires_in = time() + (int)$result_array['expires_in'];
        $this->storeToken($result_array['access_token'], $expires_in);
        return $result_array['access_token'];
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
