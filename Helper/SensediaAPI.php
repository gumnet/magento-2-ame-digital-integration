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

use \Ramsey\Uuid\Uuid;

class SensediaAPI extends API
{
    protected $url = "https://ame19gwci.gum.net.br:63334/transacoes/v1";

    protected $urlOrders = "ordens";

    protected $urlPayments = "pagamentos";

    public function refundOrder($ame_id, $amount)
    {
        $this->mlogger->info("AME REFUND ORDER:" . $ame_id);
        $this->mlogger->info("AME REFUND amount:" . $amount);

        $transaction_id = $this->dbAME->getTransactionIdByOrderId($ame_id);
        $this->mlogger->info("AME REFUND TRANSACTION:" . $transaction_id);

        $refund_id = Uuid::uuid4()->toString();
        while ($this->dbAME->refundIdExists($refund_id)) {
            $refund_id = Uuid::uuid4()->toString();
        }
        $this->mlogger->info("AME REFUND ID:" . $refund_id);
        $url = $this->url . "/pagamentos/" . $transaction_id;// . "/refunds/MAGENTO-" . $refund_id;
        $this->mlogger->info("AME REFUND URL:" . $url);
        $json_array['amount'] = $amount;
        $json_array['refundId'] = "MAGENTO-".$refund_id;
        $json = json_encode($json_array);
        $this->mlogger->info("AME REFUND JSON:" . $json);
        $result[0] = $this->ameRequest($url, "PUT", $json);
        $this->mlogger->info("AME REFUND Result:" . $result[0]);
        if ($this->hasError($result[0], $url, $json))
            return false;
        $result[1] = $refund_id;
        return $result;
    }
    public function cancelOrder(string $ame_id): bool
    {
        $url = $this->url . "/ordens/" . $ame_id;
        $result = $this->ameRequest($url, "DELETE", "");
        if ($this->hasError(
            $result,
            $url,
            ""
        )) {
            return false;
        }
        return true;
    }

    public function cancelTransaction(string $transaction_id): bool
    {
        if (!$transaction_id) {
            return false;
        }
        $url = $this->url . "/pagamentos/" . $transaction_id;
        $result = $this->ameRequest($url, "DELETE", "");
        if ($this->hasError(
            $result,
            $url,
            ""
        )) {
            return false;
        }
        return true;
    }

    public function ameRequest(string $url, string $method = "GET", string $json = ""): string
    {
        $this->mlogger->info("ameRequest starting...");
        if (!$client_id = $this->scopeConfig->getValue('ame/general/api_user')) {
            return "";
        }
        if (!$access_token = $this->scopeConfig->getValue('ame/general/api_password')) {
            return "";
        }
        $method = strtoupper($method);
        $this->mlogger->info("ameRequest URL:" . $url);
        $this->mlogger->info("ameRequest METHOD:" . $method);
        if ($json) {
            $this->mlogger->info("ameRequest JSON:" . $json);
        }
        $headers = [
            "Content-Type: application/json",
            "client_id: " . $client_id,
            "access_token: ". $access_token
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == "POST" || $method == "PUT") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        $this->mlogger->info("ameRequest OUTPUT:" . $result);
        $this->logger->log(curl_getinfo($ch, CURLINFO_HTTP_CODE), "header", $url, $json);
        $this->logger->log($result, "info", $url, $json);
        curl_close($ch);
        return $result;
    }
}
