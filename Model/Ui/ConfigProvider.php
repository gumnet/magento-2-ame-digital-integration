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
    const IN_CONTEXT_BUTTON_ID = 'paypal-express-in-context-button';

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var PaypalHelper
     */
    protected $paypalHelper;

    /**
     * @var string[]
     */
    protected $methodCodes = [
        GatewayConfig::CODE,
        GatewayConfig::CODE_CREDIT
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;

    /**
     * @param ResolverInterface $localeResolver
     * @param CurrentCustomer $currentCustomer
     * @param PaypalHelper $paypalHelper
     * @param PaymentHelper $paymentHelper
     * @param UrlInterface $urlBuilder
     * @param GatewayConfig $gatewayConfig
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        ResolverInterface $localeResolver,
        CurrentCustomer $currentCustomer,
        PaypalHelper $paypalHelper,
        PaymentHelper $paymentHelper,
        UrlInterface $urlBuilder,
        GatewayConfig $gatewayConfig
    ) {
        $this->localeResolver = $localeResolver;
        $this->gatewayConfig = $gatewayConfig;
        $this->currentCustomer = $currentCustomer;
        $this->paypalHelper = $paypalHelper;
        $this->paymentHelper = $paymentHelper;
        $this->urlBuilder = $urlBuilder;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $locale = $this->localeResolver->getLocale();

        $config = [
            'payment' => [
                GatewayConfig::CODE => [
                    'paymentAcceptanceMarkHref' => $this->gatewayConfig->getPaymentMarkWhatIsPaypalUrl(
                        $this->localeResolver
                    ),
                    'paymentAcceptanceMarkSrc' => $this->gatewayConfig->getPaymentMarkImageUrl(),
                    'isContextCheckout' => false,
                    'inContextConfig' => [],
                    'creditTitle' => $this->gatewayConfig->getCreditTitle(),
                    'isVaultEnabled' => $this->gatewayConfig->isVaultEnabled(),
                    'vaultCode' => GatewayConfig::CODE_VAULT
                ]
            ]
        ];

        if ($this->gatewayConfig->isInContext()) {
            $config['payment'][GatewayConfig::CODE]['isContextCheckout'] = true;
            $config['payment'][GatewayConfig::CODE]['inContextConfig'] = [
                'inContextId' => self::IN_CONTEXT_BUTTON_ID,
                'merchantId' => $this->gatewayConfig->getPayPalMerchantId(),
                'path' => $this->urlBuilder->getUrl('cybersourcepaypal/express/gettoken', ['_secure' => true]),
                'clientConfig' => [
                    'environment' => ($this->gatewayConfig->getEnvironment() == 'sandbox' ? 'sandbox' : 'production'),
                    'locale' => $locale,
                    'button' => [
                        self::IN_CONTEXT_BUTTON_ID
                    ]
                ],
            ];
        }

        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment'][GatewayConfig::CODE]['redirectUrl'][$code] = $this->getMethodRedirectUrl($code);
            }
        }

        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     * @return mixed
     */
    protected function getMethodRedirectUrl($code)
    {
        return $this->methods[$code]->getCheckoutRedirectUrl();
    }
}
