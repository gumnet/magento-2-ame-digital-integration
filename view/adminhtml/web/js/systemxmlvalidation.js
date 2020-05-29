require([
        'jquery',
        'mage/translate',
        'jquery/validate'],
    function($){
        $.validator.addMethod(
            'validate-number-0-100', function (v) {
                return (v <= 100 && v >= 0);
            }, $.mage.__('Value must be between 0 and 100'));
        $.validator.addMethod(
            'validate-uuid', function (v) {
                var pattern = new RegExp('^[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}$','i')
                return (pattern.test(v));
            }, $.mage.__('Value must be a valid UUID'));

    }
);
