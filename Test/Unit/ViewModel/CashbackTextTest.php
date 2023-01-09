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
declare(strict_types=1);

namespace GumNet\AME\Test\ViewModel;

use GumNet\AME\Model\GumApi;
use GumNet\AME\Observer\CreditMemoObserver;
use GumNet\AME\Model\ApiClient;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CashbackTextTest extends TestCase
{
    private MockObject $observer;
    private MockObject $event;
    private MockObject $creditMemo;
    private MockObject $order;
    private MockObject $payment;

    private MockObject $api;
    private MockObject $gumApi;
    private CreditMemoObserver $creditMemoObserver;

    protected function setUp(): void
    {
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->creditMemo = $this->createMock(Creditmemo::class);
        $this->order = $this->createMock(Order::class);
        $this->payment = $this->createMock(OrderPaymentInterface::class);

        $this->api = $this->createMock(ApiClient::class);
        $this->gumApi = $this->createMock(GumApi::class);

        $this->creditMemoObserver = new CreditMemoObserver(
            $this->api,
            $this->gumApi
        );
    }

    public function testExecute()
    {
        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->event->expects($this->once())
            ->method('getCreditMemo')
            ->willReturn($this->creditMemo);
        $this->productModel->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn(100.0);
        $this->apiClient->expects($this->once())
            ->method('getCashbackPercent')
            ->willReturn(10.0);
        $this->assertEquals(10.0, $this->cashbackText->getCashbackValue());
    }
}
