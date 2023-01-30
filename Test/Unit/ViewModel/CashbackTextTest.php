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

use GumNet\AME\Model\ApiClient;
use Magento\Catalog\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use GumNet\AME\ViewModel\CashbackText;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CashbackTextTest extends TestCase
{
    private MockObject $helper;
    private MockObject $api;
    private MockObject $request;
    private MockObject $scopeConfig;
    private MockObject $product;
    private CashbackText $cashbackText;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->api = $this->createMock(ApiClient::class);
        $this->request = $this->createMock(Http::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->product = $this->createMock(Product::class);

        $this->cashbackText = new CashbackText(
            $this->helper,
            $this->api,
            $this->request,
            $this->scopeConfig
        );
    }

    public function testIsShowCashbackProduct()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(1);
        $this->assertTrue($this->cashbackText->isShowCashbackProduct());
    }
    public function testGetCashbackPercent()
    {
        $this->api->expects($this->once())
            ->method('getCashBackPercent')
            ->willReturn(10.0);
        $this->assertEquals(10, $this->cashbackText->getCashbackPercent());
    }

    public function testGetCashbackValue()
    {
        $this->request->expects($this->once())
            ->method('getFullActionName')
            ->willReturn('catalog_product_view');

        $this->helper->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->product);

        $this->product->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn(100);

        $this->api->expects($this->once())
            ->method('getCashBackPercent')
            ->willReturn(10.0);
        $this->assertEquals(10, $this->cashbackText->getCashbackValue());
    }
}
