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

class Config
{
    // core_config_data values
    public const ACTIVE = 'payment/ame/active';
    public const TABLE_CORE_DATA = 'core_config_data';
    public const API_USER_OLD = 'ame/general/api_user';
    public const API_USER = 'payment/ame/api_user';
    public const API_PASSWORD_OLD = 'ame/general/api_password';
    public const API_PASSWORD = 'payment/ame/api_password';
    public const ENVIRONMENT_OLD = 'ame/general/environment';
    public const ENVIRONMENT = 'payment/ame/environment';
    public const STATUS_CREATED_OLD = 'ame/general/order_status_created';
    public const STATUS_CREATED = 'payment/ame/order_status_created';
    public const STATUS_PROCESSING_OLD = 'ame/general/order_status_payment_received';
    public const STATUS_PROCESSING = 'payment/ame/order_status_payment_received';
    public const ADDRESS_STREET_OLD = 'ame/address/street';
    public const ADDRESS_STREET = 'payment/ame/address_street';
    public const ADDRESS_NUMBER_OLD = 'ame/address/number';
    public const ADDRESS_NUMBER = 'payment/ame/address_number';
    public const ADDRESS_NEIGHBORHOOD_OLD = 'ame/address/neighborhood';
    public const ADDRESS_NEIGHBORHOOD = 'payment/ame/address_neighborhood';
    public const EXHIBITION_LIST_OLD = 'ame/exhibition/show_cashback_products_list';
    public const EXHIBITION_LIST = 'payment/ame/show_cashback_products_list';
    public const TRUST_WALLET_ENABLED = 'payment/ame/trust_wallet_enabled';

    // Environments

    // ame_config values
    public const TOKEN_VALUE = 'token_value';
    public const TOKEN_EXPIRES = 'token_expires';

    // hardcoded values
    public const AME_API_URL = "https://ame19gwci.gum.net.br:63333/api";
    public const SENSEDIA_API_URL = "https://ame19gwci.gum.net.br:63334/transacoes/v1";
    public const SENSEDIA_API_DEV_URL = "http://api-amedigital.sensedia.com/hml/transacoes/v1";

    public const SENSEDIA_TRUST_WALLET_URL = "https://ame19gwci.gum.net.br:63334/cobranca-confiavel/v1";

    public const SENSEDIA_TRUST_WALLET_DEV_URL = "http://api-amedigital.sensedia.com/hml/cobranca-confiavel/v1";

    // Stored for dev purposes only
    //    public const AME_API_URL = "https://api.dev.amedigital.com/api";
    //    public const AME_API_URL = "https://api.hml.amedigital.com/api";
}
