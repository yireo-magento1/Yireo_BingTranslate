/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

jQuery(function () {
    jQuery("input").each(function () {
        YireoBingTranslate.addButtonToInput(jQuery(this));
    });
});

/**
 * YireoBingTranslate class
 */
var YireoBingTranslate = {

    ajaxEntityBaseUrl: null,

    ajaxTextBaseUrl: null,

    allowedInputTypes : ['text'],

    skipInputNames : ['news_from_date', 'news_to_date', 'special_from_date', 'special_to_date',
        'price', 'special_price', 'msrp', 'custom_design_from', 'custom_design_to', 'weight',
        'simple_product_inventory_qty'],

    debug : false,

    translateText: function (html_id, from_language, to_language) {

        // Fetch the from_language and to_language if not yet set
        var new_from_language = $('bingtranslate_source_language').value;
        var new_to_language = $('bingtranslate_destination_language').value;

        if (new_from_language && new_from_language != 'auto') {
            var from_language = new_from_language;
        }

        if (new_to_language && new_to_language != 'auto') {
            var to_language = new_to_language;
        }

        var field = $(html_id);
        if (field == null || field.disabled) {
            this.doDebug('Field ' + html_id + ' disabled');
            return false;
        }

        // Skip if the languages are equal
        if (to_language == from_language) {
            return false;
        }

        var ajaxUrl = this.ajaxTextBaseUrl
                + 'string/' + field.value + '/'
                + 'from/' + from_language + '/'
                + 'to/' + to_language + '/'
            ;

        this.ajax(ajaxUrl, field);
    },

    translateAttribute: function (data_id, attribute_code, html_id, store_id, from_language, to_language) {

        // Fetch the from_language and to_language if not yet set
        var new_from_language = $('bingtranslate_source_language').value;
        var new_to_language = $('bingtranslate_destination_language').value;

        if (new_from_language && new_from_language != 'auto') {
            var from_language = new_from_language;
        }

        if (new_to_language && new_to_language != 'auto') {
            var to_language = new_to_language;
        }

        // Skip if the languages are equal
        if (to_language == from_language) {
            return false;
        }

        // Define variables
        var button = $('bingtranslate_button_' + attribute_code);
        var ajaxUrl = this.ajaxEntityBaseUrl
                + 'id/' + data_id + '/'
                + 'attribute/' + attribute_code + '/'
                + 'from/' + from_language + '/'
                + 'to/' + to_language + '/'
                + 'store/' + store_id + '/'
            ;

        // Check if the field is actually enabled
        var field = $(html_id);
        if (field == null || field.disabled) {
            button.disabled = true;
            button.className = 'disabled';
            return false;
        }

        this.ajax(ajaxUrl, field, button);

        return true;
    },

    ajax: function (ajaxUrl, field) {

        // If all is right, perform an AJAX-request
        new Ajax.Request(ajaxUrl, {
            method: 'get',
            onSuccess: function (transport) {
                var response = transport.responseText;
                if (response) {
                    json = response.evalJSON(true);

                    // Alert in case of an error
                    if (json.error) {
                        if (json.message) {
                            message = json.message;
                        } else {
                            message = json.error;
                        }
                        alert('ERROR: ' + message);

                        // Set the new field-value and disable the button
                    } else {

                        $(field).value = json.translation;

                        if (tinyMCE) {
                            var editor = tinyMCE.get(html_id);
                            if (editor) {
                                editor.setContent(json.translation);
                            }
                        }

                        if (button) {
                            button.className = 'disabled';
                            button.disabled = true;
                        }
                    }
                }
            },

            // General failure
            onFailure: function () {
                alert('Failed to contact BingTranslate')
            }
        });
    },

    addButtonToInput: function (input) {
        var inputId = input.attr('id');
        var inputName = input.attr('name');
        var inputType = input.attr('type');

        if (inputName == undefined) {
            this.doDebug('Input name undefined');
            return false;
        }

        if (inputId == undefined) {
            this.doDebug('Input ID undefined');
            return false;
        }

        if (input.attr('disabled') == 'disabled' || input.prop('readonly')) {
            //this.doDebug('Input disabled or readonly');
            //return false;
        }

        if (this.inArray(inputName, this.skipInputNames) || this.inArray(inputId, this.skipInputNames)) {
            this.doDebug('Input ' + inputName + ' in skip list');
            return true;
        }

        if (this.inArray(inputType, this.allowedInputTypes) == false) {
            this.doDebug('Input ' + inputName + ' not in allowed input types');
            return false;
        }

        var parent = input.parent();
        var html = '<div class="bingtranslate-container">'
            + input.prop('outerHTML')
            + '<a href="#" title="BingTranslate" onclick="javascript:YireoBingTranslate.translateText(\'' + inputId + '\'); return false;">'
            + '<div class="bingtranslate-icon">'
            + '&nbsp;'
            + '</div>'
            + '</a>'
            + '</div>';

        input.replaceWith(html);
        console.log(inputName + ' / ' + inputId + ' = ' + inputType);

        return true;
    },

    inArray: function (name, array) {
        var count = array.length;
        for (var i = 0; i < count; i++) {
            if (array[i] === name) {
                return true;
            }

            if ('jform_' + array[i] === name) {
                return true;
            }

            if ('params_' + array[i] === name) {
                return true;
            }
        }

        return false;
    },

    doDebug: function (string, variable) {
        if (this.debug == false) {
            return false;
        }

        console.log(string);
        if (variable) {
            console.log(variable);
        }

        return true;
    }
}