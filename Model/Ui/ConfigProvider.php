<?php

namespace GumNet\AME\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Paypal\Helper\Data as PaypalHelper;
use CyberSource\PayPal\Model\Config as GatewayConfig;

class ConfigProvider implements ConfigProviderInterface
{
    protected $assertRepository;

    protected $checkoutSession;

    protected $api;

    public function __construct(
        \Magento\Framework\View\Asset\Repository $assertRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \GumNet\AME\Helper\API $api
    ) {
        $this->assertRepository = $assertRepository;
        $this->checkoutSession = $checkoutSession;
        $this->api = $api;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {

        $quote = $this->checkoutSession->getQuote();

        $grandTotal = $quote->getGrandTotal();
        if (array_key_exists('discount', $quote->getTotals())) {
            $grandTotal = $quote->getGrandTotal() - $quote->getTotals()['discount']->getValue();
        }

        /** @var \GumNet\AME\Helper\API $cashbackPercent */
        $cashbackPercent = $this->api->getCashbackPercent();
        $cashbackValue = $grandTotal * $cashbackPercent * 0.01;

        $cashback_text = "&nbsp;&nbsp;Receba R$"
            . number_format($cashbackValue, "2", ",", ".")
            . " de volta com a AME Digital";

        $config = [
            'payment' => [
                'ame' => [
                    'logo_url' => $this->assertRepository->getUrl("GumNet_AME::images/ame-logo.png"),
                    'cashback_text' => $cashback_text
                ]
            ]
        ];

        return $config;
    }
}
