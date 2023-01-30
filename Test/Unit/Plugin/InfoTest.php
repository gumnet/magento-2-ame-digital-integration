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

namespace GumNet\AME\Test\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use GumNet\AME\Plugin\Order\Info;

class InfoTest extends TestCase
{
    private MockObject $orderRepository;
    private MockObject $request;
    private MockObject $order;
    private MockObject $payment;
    private MockObject $origInfo;

    private Info $info;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->request = $this->createMock(Http::class);
        $this->order = $this->createMock(Order::class);
        $this->payment = $this->createMock(Order\Payment::class);
        $this->origInfo = $this->createMock(\Magento\Sales\Block\Order\Info::class);

        $this->info = new Info(
            $this->orderRepository,
            $this->request,
        );
    }

    public function testAfterGetPaymentInfoHtml()
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn('1');

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->payment);
        $this->payment->expects($this->once())
            ->method('getMethod')
            ->willReturn('ame');

        $this->payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn('URL');

        $result = "<br><img src='URL' alt='qrcode'>";

        $this->assertEquals($result, $this->info->afterGetPaymentInfoHtml($this->origInfo, ""));
    }
}
