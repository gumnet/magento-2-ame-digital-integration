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

namespace GumNet\AME\Test\Unit\Observer;

use GumNet\AME\Model\AME;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use GumNet\AME\Observer\ObserverForDisabledFrontendPg;

class ObserverForDisabledFrontendPgTest extends TestCase
{
    private MockObject $observer;
    private DataObject $event;
    private MockObject $scopeConfig;
    private MockObject $ame;
    private DataObject $result;
    private ObserverForDisabledFrontendPg $observerForDisabledFrontendPg;

    protected function setUp(): void
    {
        $this->observer = $this->createMock(Observer::class);
        $this->event = new DataObject();
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->ame = $this->createMock(AME::class);
        $this->result = new DataObject();

        $this->observerForDisabledFrontendPg = new ObserverForDisabledFrontendPg(
            $this->scopeConfig
        );
    }

    public function testExecute()
    {
        $this->observer->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->event->setData('result', $this->result);
        $this->event->setData('method_instance', $this->ame);
        $this->ame->expects($this->once())
            ->method('getCode')
            ->willReturn('ame');
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn('0');
        $this->observerForDisabledFrontendPg->execute($this->observer);
        $this->assertEquals(false, $this->result->getData('is_available'));
    }
}
