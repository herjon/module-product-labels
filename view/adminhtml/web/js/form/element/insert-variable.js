/**
 * "Insert variable" select of the label form: appends the chosen variable to the sibling "text" field.
 */
define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function (Select, registry) {
    'use strict';

    return Select.extend({
        defaults: {
            textField: '${ $.parentName }.text'
        },

        /**
         * @inheritdoc
         */
        onUpdate: function () {
            var variable = this.value();

            this._super();

            if (!variable) {
                return;
            }

            registry.get(this.textField, function (field) {
                field.value((field.value() || '') + variable);
            });
            this.value('');
        }
    });
});
