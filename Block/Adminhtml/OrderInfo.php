<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace GumNet\AME\Block\Adminhtml;

use Magento\Framework\Exception\NoSuchEntityException;

class OrderInfo extends \Magento\Framework\View\Element\Template
{
    protected $_metadataElementFactory;
    protected $_api;
    protected $_dbAME;
    protected $_request;
    protected $_orderRepository;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        \GumNet\AME\Helper\API $api,
        \GumNet\AME\Helper\DbAME $dbAME,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->_metadataElementFactory = $elementFactory;
        $this->_api = $api;
        $this->_dbAME = $dbAME;
        $this->_request = $request;
        $this->_orderRepository = $orderRepository;
        $this->_isScopePrivate = true;
        parent::__construct($context, $registry, $adminHelper, $data);
    }
    protected function _beforeToHtml()
    {
        if (!$this->getParentBlock()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please correct the parent block for this block.')
            );
        }
        $this->_isScopePrivate = true;
        $this->setOrder($this->_orderRepository
            ->get($this->_request->getParam('id')));
    }
    public function getId(){
        return $this->_request->getParam('id');
    }
    public function getAmeOrderId(){
        return $this->_dbAME->getAmeIdByIncrementId($this->getOrder()->getIncrementId());
    }
    public function getViewUrl($orderId)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
    }
    public function isSingleStoreMode()
    {
        return $this->_storeManager->isSingleStoreMode();
    }
    public function getCreatedAtStoreDate($store, $createdAt)
    {
        return $this->_localeDate->scopeDate($store, $createdAt, true);
    }
    public function getTimezoneForStore($store)
    {
        return $this->_localeDate->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
    }
    public function getOrderAdminDate($createdAt)
    {
        return $this->_localeDate->date(new \DateTime($createdAt));
    }
}
