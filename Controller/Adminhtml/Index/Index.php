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

namespace GumNet\AME\Controller\Adminhtml\Index;

use \Zend\Barcode\Barcode;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $request;
    protected $orderRepository;
    protected $_apiAME;
    protected $_dbAME;
    protected $_formKey;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\App\Request\Http $request,
                                \Magento\Sales\Model\OrderRepository $orderRepository,
                                \GumNet\AME\Helper\API $apiAME,
                                \GumNet\AME\Helper\DbAME $dbAME,
                                \Magento\Framework\Data\Form\FormKey $formKey
                                )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->_apiAME = $apiAME;
        $this->_dbAME = $dbAME;
        $this->_formKey = $formKey;
        parent::__construct($context);
    }
    public function execute()
    {
//        if(!$this->_auth->isLoggedIn()) die('unauthorized');

        $id = $this->request->getParam('id');
        $order = $this->orderRepository->get($id);
        echo "Painel AME <br><br>\r";
        echo "Pedido Magento: ".$order->getIncrementId()."<br>\r";
        echo "Pedido AME: ".$this->_dbAME->getAmeIdByIncrementId($order->getIncrementId())."<br>\r";
        $url = "../../../../";
        echo "<a href='".$url."capture/index/id/".$id."/'>Capturar</a> | ";
        echo "<a href='".$url."cancel/index/id/".$id."/'>Cancelar</a> | ";
        echo "<form method='post' action='".$url."refund/index/id/".$id."/'>";
        echo "<input type='text' name='valor'><input type='submit' value='Reembolsar'>Reembolsar";
        ?>
        <input name="form_key" type="hidden" value="<?php echo $this->getFormKey(); ?>">
        <?php
        echo "</form>";
        echo "<br>\n";
        $json = $this->_apiAME->consultOrder($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()));
        $json_array = json_decode($json,true);
        $json_string = json_encode($json_array, JSON_PRETTY_PRINT);
        echo "<br>\n";
        echo nl2br($json_string);
        echo "<br>\n";
        die();
        return;
    }
    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
    }
}
