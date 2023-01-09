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

namespace GumNet\AME\Model\Values;

class PaymentInformation
{
    public const AME_ID = 'ameId';
    public const AMOUNT = 'amount';
    public const QR_CODE_LINK = 'qrCodeLink';
    public const DEEP_LINK = 'deepLink';
    public const CASHBACK_VALUE = 'cashbackAmountValue';
    public const TRANSACTION_ID = 'transaction_id';
    public const NSU = "nsu";
    public const TRUST_WALLET_UUID = 'trust_wallet_uuid';

    // old v1 tables
    public const OLD_AME_ORDER_TABLE = 'ame_order';
    public const OLD_AME_ORDER_ID = 'ame_id';
    public const OLD_AME_ORDER_CASHBACK_AMOUNT = 'cashback_amount';
    public const OLD_AME_ORDER_QR_CODE = 'qr_code_link';
    public const OLD_AME_ORDER_DEEP_LINK = 'deep_link';

    public const OLD_AME_TRANSACTION_TABLE = 'ame_transaction';
    public const OLD_AME_TRANSACTION_ORDER_ID = 'ame_order_id';
    public const OLD_AME_TRANSACTION_ID = 'ame_transaction_id';
}
