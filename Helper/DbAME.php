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

class DbAME {

    protected $_connection;
    protected $_mlogger;

    public function __construct(\Magento\Framework\App\ResourceConnection $resource,
                                \Psr\Log\LoggerInterface $mlogger
                                )
    {
        $this->_connection = $resource->getConnection();
        $this->_mlogger = $mlogger;
    }
    public function setTransactionUpdated($ame_transaction_id){
        $sql = "UPDATE ame_transaction SET updated_at = NOW() WHERE ame_transaction_id = '".$ame_transaction_id."'";
        $rs_query = $this->_connection->query($sql);
    }
    public function setCaptured($ame_transaction_id,$ame_capture_id){
        $sql = "UPDATE ame_transaction SET capture_ok = 1 WHERE ame_transaction_id = '".$ame_transaction_id."'";
        $rs_query = $this->_connection->query($sql);
        $sql = "UPDATE ame_transaction SET ame_capture_id = '".$ame_capture_id."' WHERE ame_transaction_id = '".$ame_transaction_id."'";
        $rs_query = $this->_connection->query($sql);
    }
    public function getRefundedSumByTransactionId($ame_transaction_id){
        $sql = "SELECT SUM(amount) soma FROM ame_refund WHERE ame_transaction_id = '".$ame_transaction_id."'";
        $soma = $this->_connection->fetchOne($sql);
        return $soma;
    }
    public function getAmeOrderIdByTransactionId($ame_transaction_id){
        $sql = "SELECT ame_order_id FROM ame_transaction WHERE ame_transaction_id = '".$ame_transaction_id."'";
        return $this->_connection->fetchOne($sql);
    }
    public function getCallback2Hash(){
        $sql = "SELECT ame_value FROM ame_config WHERE ame_option = 'callback2_hash'";
        return $this->_connection->fetchOne($sql);
    }
    public function setCaptured2($ame_transaction_id){
        $sql = "UPDATE ame_transaction SET update_ok = 1 WHERE ame_transaction_id = '".$ame_transaction_id."'";
        $rs_query = $this->_connection->query($sql);
    }
    public function insertRefund($ame_order_id,$refund_id,$operation_id,$amount,$status){
        $transaction_id = $this->getTransactionIdByOrderId($ame_order_id);
        $sql = "INSERT INTO ame_refund (ame_transaction_id,refund_id,operation_id,amount,status,created_at,refunded_at)
                VALUES ('".$transaction_id."','".$refund_id."','".$operation_id."',".$amount.",'".$status."',NOW(),NOW())";
        $this->_connection->query($sql);
        return true;
    }
    public function refundIdExists($refund_id){
        $sql = "SELECT refund_id FROM ame_refund WHERE refund_id = '".$refund_id."'";
        $result = $this->_connection->fetchOne($sql);
        if($result){
            return true;
        }
        else{
            return false;
        }
    }
    public function insertOrder($order,$result_array){
        if(array_key_exists('cashbackAmountValue',$result_array['attributes'])){
            $cashbackAmountValue = $result_array['attributes']['cashbackAmountValue'];
        }
        else{
            $cashbackAmountValue = 0;
        }
        $sql = "INSERT INTO ame_order (increment_id,ame_id,amount,cashback_amount,
                       qr_code_link,deep_link)
                VALUES ('" . $order->getIncrementId() . "','" . $result_array['id'] . "',
                        " . $result_array['amount'] . ",
                        " . $cashbackAmountValue . ",
                        '" . $result_array['qrCodeLink'] . "',
                        '" . $result_array['deepLink'] . "')";
        $this->_connection->query($sql);
    }
    public function updateToken($expires_in,$token){
        $sql = "UPDATE ame_config SET ame_value = '" . $expires_in . "'WHERE ame_option = 'token_expires'";
        $this->_connection->query($sql);
        $sql = "UPDATE ame_config SET  ame_value = '" . $token . "' WHERE ame_option = 'token_value'";
        $this->_connection->query($sql);
    }
    public function getToken(){
        $sql = "SELECT ame_value FROM ame_config WHERE ame_option = 'token_expires'";
        $token_expires = $this->_connection->fetchOne($sql);
        $sql = "SELECT ame_value FROM ame_config WHERE ame_option = 'token_value'";
        if (time() + 600 < $token_expires) {
            $token = $this->_connection->fetchOne($sql);
            $this->_mlogger->info("ameRequest getToken returns: " . $token);
            return $token;
        }
        return false;
    }
    public function getOrderAmount($ame_order_id){
        $sql = "SELECT amount FROM ame_order WHERE ame_id = '".$ame_order_id."'";
        return $this->_connection->fetchOne($sql);
    }
    public function getTransactionAmount($ame_transaction_id){
        $sql = "SELECT amount FROM ame_transaction WHERE ame_transaction_id = '".$ame_transaction_id."'";
        return $this->_connection->fetchOne($sql);
    }
    public function getTransactionIdByOrderId($ame_order_id){
        $sql = "SELECT ame_transaction_id FROM ame_transaction WHERE ame_order_id = '".$ame_order_id."'";
        return $this->_connection->fetchOne($sql);
    }
    public function insertTransactionSplits($transaction_array){
        $splits = $transaction_array['splits'];
        $array_keys = array('id','date','amount','status','cashType');
        foreach($splits as $split) {
            $sql = file_get_contents(__DIR__ . "/SQL/inserttransactionsplit.sql");
            if(array_key_exists('id',$transaction_array)) {
                $sql = str_replace('[AME_TRANSACTION_ID]', $transaction_array['id'], $sql);
            }
            if(array_key_exists('id',$split)) {
                $sql = str_replace('[AME_TRANSACTION_SPLIT_ID]', $split['id'], $sql);
            }
            if(array_key_exists('date',$split)) {
                $sql = str_replace('[AME_TRANSACTION_SPLIT_DATE]', json_encode($split['date']), $sql);
            }
            if(array_key_exists('amount',$split)) {
                $sql = str_replace('[AMOUNT]', $split['amount'], $sql);
            }
            if(array_key_exists('status',$split)) {
                $sql = str_replace('[STATUS]', $split['status'], $sql);
            }
            if(array_key_exists('cashType',$split)) {
                $sql = str_replace('[CASH_TYPE]', $split['cashType'], $sql);
            }
            $others = [];
            foreach($split as $key => $value){
                if(!in_array($key,$array_keys)){
                    $others[$key] = $value;
                }
            }
            $others_json = json_encode($others);
            $sql = str_replace('[OTHERS]', $others_json, $sql);
            $this->_connection->query($sql);
        }
        return true;
    }
    public function insertTransaction($transaction_array){
        $sql = file_get_contents(__DIR__ . "/SQL/inserttransaction.sql");
        $sql = str_replace('[AME_ORDER_ID]',$transaction_array['attributes']['orderId'],$sql);
        $sql = str_replace('[AME_TRANSACTION_ID]',$transaction_array['id'],$sql);
        $sql = str_replace('[AMOUNT]',$transaction_array['amount'],$sql);
        $sql = str_replace('[STATUS]',$transaction_array['status'],$sql);
        $sql = str_replace('[OPERATION_TYPE]',$transaction_array['operationType'],$sql);
        $this->_connection->query($sql);
        $this->insertTransactionSplits($transaction_array);
        return true;
    }
    public function getAmeIdByIncrementId($incrementId){
        $sql = "SELECT ame_id FROM ame_order WHERE increment_id = '".$incrementId."'";
        return $this->_connection->fetchOne($sql);
    }
    public function setCanceled($ame_transaction_id){
        $sql = "UPDATE ame_transaction SET capture_ok = 2 WHERE ame_transaction_id = '".$ame_transaction_id."'";
        $this->_connection->query($sql);
    }
    public function getFirstPendingCaptureTransactions($num){
        $sql = file_get_contents("SQL/getfirstpendingcapturetransactions.sql");
        $sql = str_replace("[LIMIT]",$num,$sql);
        return $this->_connection->fetchAssoc($sql);
    }
    public function getFirstPendingTransactions($num){
        $sql = file_get_contents("SQL/getfirstpendingtransactions.sql");
        $sql = str_replace("[LIMIT]",$num,$sql);
        return $this->_connection->fetchAssoc($sql);
    }
    public function isCaptured($ame_transaction_id){
        $sql = "SELECT capture_ok FROM ame_transaction WHERE ame_transaction_id = '".$ame_transaction_id."'";
        return $this->_connection->fetchOne($sql);
    }
    public function insertCallback($json){
        $sql = "INSERT INTO ame_callback (json,created_at) VALUES ('".$json."',NOW())";
        $this->_connection->query($sql);
    }
    public function getOrderIncrementId($ameid){
        $sql = "SELECT increment_id FROM ame_order WHERE ame_id = '".$ameid."'";
        return $this->_connection->fetchOne($sql);
    }
}

