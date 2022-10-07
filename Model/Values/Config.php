<?php

namespace GumNet\AME\Model\Values;

class Config
{
    const TABLE_CORE_DATA = 'core_config_data';
    const API_USER_OLD = 'ame/general/api_user';
    const API_USER = 'payment/ame/api_user';
    const API_PASSWORD_OLD = 'ame/general/api_password';
    const API_PASSWORD = 'payment/ame/api_password';
    const ENVIRONMENT_OLD = 'ame/general/environment';
    const ENVIRONMENT = 'payment/ame/environment';
    const STATUS_CREATED_OLD = 'ame/general/order_status_created';
    const STATUS_CREATED = 'payment/ame/order_status_created';
    const STATUS_PROCESSING_OLD = 'ame/general/order_status_payment_received';
    const STATUS_PROCESSING = 'payment/ame/order_status_payment_received';
    const ADDRESS_STREET_OLD = 'ame/address/street';
    const ADDRESS_STREET = 'payment/ame/address_street';
    const ADDRESS_NUMBER_OLD = 'ame/address/number';
    const ADDRESS_NUMBER = 'payment/ame/address_number';
    const ADDRESS_NEIGHBORHOOD_OLD = 'ame/address/neighborhood';
    const ADDRESS_NEIGHBORHOOD = 'payment/ame/address_neighborhood';
    const EXHIBITION_LIST_OLD = 'ame/exhibition/show_cashback_products_list';
    const EXHIBITION_LIST = 'payment/ame/show_cashback_products_list';
}
