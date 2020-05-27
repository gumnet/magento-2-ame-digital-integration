require([
        'jquery',
        'mage/translate',
        'jquery/validate'],
    function($){
        $.validator.addMethod(
            'validate-number-0-100', function (v) {
                return (v <= 100 && v >= 0);
            }, $.mage.__('Value must be between 0 and 100'));
    }
);
