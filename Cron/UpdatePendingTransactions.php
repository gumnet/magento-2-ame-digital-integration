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

namespace GumNet\Tracking\Cron;

class UpdatePendingTransactions
{
    protected $_mlogger;
    protected $_order;
    protected $_dbAME;
    protected $_gumApi;
    protected $_storeManager;

    public function __construct(
        \Psr\Log\LoggerInterface $mlogger,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \GumNet\AME\Helper\DbAME $dbAME,
        \GumNet\AME\Helper\GumApi $gumApi,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_mlogger = $mlogger;
        $this->_order = $order;
        $this->_dbAME = $dbAME;
        $this->_gumApi = $gumApi;
        $this->_storeManager = $storeManager;
    }
    public function execute()
    {
        // Temporarily disabled
        $num = 10;
//        $transactions = $this->_dbAME->getFirstPendingCaptureTransactions($num);
//        foreach($transactions as $transaction){
//            $hash = $this->_dbAME->getCallback2Hash();
//            $url = $this->getCallbackUrl() . '/step2/index/hash/' . $hash . '/id/' . $ame_transaction_id;
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//            $result = curl_exec($ch);
//        }

        // WORK BELOW
/*
        $transactions = $this->_dbAME->getFirstPendingTransactions($num);
        foreach($transactions as $transaction){
            $capture = $this->_gumApi->captureTransaction($transaction['ame_order_id'],$transaction['ame_transaction_id'],$transaction['amount']);
            if($capture) $this->_dbAME->setCaptured2($transaction['ame_transaction_id']);
            $this->_dbAME->setTransactionUpdated($transaction['ame_transaction_id']);
        }
*/
    }
    public function getCallbackUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }
}
