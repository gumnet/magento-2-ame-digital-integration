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
namespace GumNet\AME\Model\Ui;

use GumNet\AME\Model\ApiClient;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Asset\Repository;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Repository
     */
    protected $assertRepository;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ApiClient
     */
    protected $api;

    /**
     * @param Repository $assertRepository
     * @param Session $checkoutSession
     * @param ApiClient $api
     */
    public function __construct(
        Repository $assertRepository,
        Session $checkoutSession,
        ApiClient $api
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

        /** @var ApiClient $cashbackPercent */
        $cashbackPercent = $this->api->getCashbackPercent();
        $cashbackValue = $grandTotal * $cashbackPercent * 0.01;

        $cashbackText = "&nbsp;&nbsp;Receba R$"
            . number_format($cashbackValue, "2", ",", ".")
            . " de volta com a AME Digital";

        return [
            'payment' => [
                'ame' => [
                    'logo_url' => $this->assertRepository->getUrl("GumNet_AME::images/ame-logo.png"),
                    'cashback_text' => $cashbackText
                ]
            ]
        ];
    }
}
