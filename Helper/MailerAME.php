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

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class MailerAME extends AbstractHelper
{
    protected $_directoryList;
    protected $_scopeConfig;

    public function __construct(Context $context,
                                \Magento\Framework\Filesystem\DirectoryList $directoryList,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
                                )
    {
        $this->_directoryList = $directoryList;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    public function sendDebug($subject,$message){
        if(!$this->_scopeConfig->getValue('ame/debug/debug_email_addresses',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) return;

        $emails = $this->_scopeConfig->getValue('ame/debug/debug_email_addresses',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $emails_array = explode(",",$emails);
        foreach($emails_array as $email){
            if (\Zend_Validate::is(trim($email), 'EmailAddress')) {
                $this->mailSender(trim($email),$subject,$message);
            }
        }
    }
    public function mailSender($to,$subject,$message)
    {
        $email = $this->_scopeConfig->getValue('trans_email/ident_support/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $headers = "From: Magento Debug <".$email.">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($to,$subject,$message,$headers);
        return true;
    }
}
