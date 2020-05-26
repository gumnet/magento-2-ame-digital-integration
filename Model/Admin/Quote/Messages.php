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

namespace GumNet\AME\Model\Admin\Quote;

use Magento\Security\Model\ResourceModel\AdminSessionInfo\Collection;
use Magento\Backend\Model\UrlInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Notification\MessageInterface;

class Messages implements MessageInterface
{
    protected $backendUrl;
    private $adminSessionInfoCollection;
    protected $authSession;
    protected $_moduleLIst;

    public function __construct(
        Collection $adminSessionInfoCollection,
        UrlInterface $backendUrl,
        Session $authSession,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        $this->authSession = $authSession;
        $this->backendUrl = $backendUrl;
        $this->adminSessionInfoCollection = $adminSessionInfoCollection;
        $this->_moduleLIst = $moduleList;
    }
    public function getText()
    {
        $message = __('Módulo AME - nova versão identificada - '.$this->getLatestVersion().'<br>
        Contato: suporte@gum.net.br');
        return $message;
    }
    public function getIdentity()
    {
        return md5('GUMNET_AME' . $this->authSession->getUser()->getLogdate());
    }
    public function isDisplayed()
    {
        $current_version = $this->getCurrentVersion();
        $latest_version = $this->getLatestVersion();
        if(version_compare($current_version,$latest_version,"<")) {
            return true;
        }
        else return false;
    }
    public function getSeverity()
    {
        return \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE;
    }
    public function getCurrentVersion(){
        return $this->_moduleLIst->getOne('GumNet_AME')['setup_version'];
    }
    public function getLatestVersion(){
        return file_get_contents('https://apiame.gum.net.br/latestversion.txt');
    }
}
