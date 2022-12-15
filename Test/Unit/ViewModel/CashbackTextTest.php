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

use GumNet\AME\ViewModel\CashbackText;
use GumNet\AME\Model\ApiClient;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CashbackTextTest extends TestCase
{
    private MockObject $registry;
    private MockObject $apiClient;
    private MockObject $request;
    private MockObject $scopeConfig;
    private MockObject $productModel;
    private CashbackText $cashbackText;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->request = $this->createMock(Http::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->productModel = $this->createMock(Product::class);

        $this->cashbackText = new CashbackText(
            $this->registry,
            $this->apiClient,
            $this->request,
            $this->scopeConfig
        );
    }

    public function testIsShowCashbackProductsListEnabled()
    {
        $this->scopeConfig->method('getValue')
            ->willReturn('1');
        $this->assertSame(true, $this->cashbackText->isShowCashbackProductsListEnabled());
    }

    public function testGetCashbackPercent()
    {
        $this->apiClient->expects($this->once())
            ->method('getCashbackPercent')
            ->willReturn(10.0);
        $this->assertSame(10.0, $this->cashbackText->getCashbackPercent());
    }

    public function testGetCashbackValue()
    {
        $this->request->expects($this->once())
            ->method('getFullActionName')
            ->willReturn('catalog_product_view');
        $this->registry->expects($this->once())
            ->method('registry')
            ->willReturn($this->productModel);
        $this->productModel->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn(100.0);
        $this->apiClient->expects($this->once())
            ->method('getCashbackPercent')
            ->willReturn(10.0);
        $this->assertEquals(10.0, $this->cashbackText->getCashbackValue());
    }
}
