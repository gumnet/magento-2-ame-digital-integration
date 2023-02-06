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

namespace GumNet\AME\Test\Unit\Model\Resolver;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Config\Element\Field;
use GumNet\AME\Model\Resolver\GetAdditionalInformation;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class GetAdditionalInformationTest extends TestCase
{
    private MockObject $orderFactory;
    private MockObject $order;
    private MockObject $payment;
    private MockObject $field;
    private MockObject $resolveInfo;

    private GetAdditionalInformation $getAdditionalInformation;

    protected function setUp(): void
    {
        $this->orderFactory = $this->createMock(OrderFactory::class);
        $this->order = $this->createMock(Order::class);
        $this->payment = $this->createMock(Order\Payment::class);
        $this->field = $this->createMock(Field::class);
        $this->resolveInfo = $this->createMock(ResolveInfo::class);

        $this->getAdditionalInformation = new GetAdditionalInformation(
            $this->orderFactory
        );
    }

    public function testAfterGetPaymentInfoHtml()
    {
        $this->orderFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('loadByIncrementId')
            ->willReturnSelf();

        $this->order->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->payment);

        $additionalInformation = "{\"test\": 123}";
        $this->payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->willReturn($additionalInformation);

        $value = ['increment_id' => 1];

        $result = $this->getAdditionalInformation->resolve(
            $this->field,
            null,
            $this->resolveInfo,
            $value
        );

        $this->assertEquals($additionalInformation, $result);
    }
}
