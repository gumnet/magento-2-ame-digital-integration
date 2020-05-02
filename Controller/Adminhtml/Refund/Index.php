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

namespace GumNet\AME\Controller\Adminhtml\Refund;

use \Zend\Barcode\Barcode;

use \Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\App\Request\InvalidRequestException;
use \Magento\Framework\App\RequestInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $request;
    protected $orderRepository;
    protected $_apiAME;
    protected $_dbAME;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\App\Request\Http $request,
                                \Magento\Sales\Model\OrderRepository $orderRepository,
                                \GumNet\AME\Helper\API $apiAME,
                                \GumNet\AME\Helper\DbAME $dbAME
                                )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->_apiAME = $apiAME;
        $this->_dbAME = $dbAME;
        parent::__construct($context);
    }
    public function execute()
    {
        $id = $this->request->getParam('id');
        $valor = $this->request->getParam('valor');
        $valor = str_replace(",",".",$valor);
        $valor = $valor * 100;
        $order = $this->orderRepository->get($id);
        echo "Painel AME - reembolsar pedido<br><br>\r\n";
        echo "Pedido Magento: ".$order->getIncrementId()."<br>\r\n";
        echo "Pedido AME: ".$this->_dbAME->getAmeIdByIncrementId($order->getIncrementId())."<br>\r\n";
        echo "Valor: ".$valor."<br>";
        $refund = $this->_apiAME->refundOrder($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()),$valor);
        echo "Refund order executado<br>";
        if(!$refund){
            echo "ERROR";
            die();
        }
        $json = $refund;
        $json_array = json_decode($json,true);
        $json_string = json_encode($json_array, JSON_PRETTY_PRINT);
        echo "<br>\n";
        echo nl2br($json_string);
        echo "<br>\n";
        die();
        return;
    }
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
