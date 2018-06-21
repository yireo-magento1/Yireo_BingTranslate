/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * YireoBingTranslate class
 */
var YireoBingTranslate;

(function($) {
    YireoBingTranslate = {

        ajaxEntityBaseUrl: '',

        ajaxTextBaseUrl: '',

        allowedInputTypes: ['text'],

        skipInputNames: ['news_from_date', 'news_to_date', 'special_from_date', 'special_to_date',
            'price', 'special_price', 'msrp', 'custom_design_from', 'custom_design_to', 'weight',
            'simple_product_inventory_qty', 'attribute_code'],

        skipInputClasses: ['validate-digits'],

        debug: true,

        translateText: function (anchor, from_language, to_language) {

            from_language = this.getFromLanguage(from_language);
            to_language = this.getToLanguage(to_language);

            var $field = jQuery(anchor).parent().children('input');

            if (!$field.length || $field.disabled) {
                this.doDebug('Field ' + anchor + ' disabled');
                return false;
            }

            // Skip if the languages are equal
            if (to_language === from_language) {
                this.doDebug('Languages are equal: ' + to_language + ' == ' + from_language);
                return false;
            }

            var ajaxUrl = this.getAjaxTextUrl($field, from_language, to_language);

            this.ajax(ajaxUrl, $field);
        },

        translateAttribute: function (data_id, attribute_code, html_id, store_id, from_language, to_language) {

            from_language = this.getFromLanguage(from_language);
            to_language = this.getToLanguage(to_language);

            // Skip if the languages are equal
            if (to_language === from_language) {
                this.doDebug('Languages are equal: ' + to_language + ' == ' + from_language);
                return false;
            }

            // Define variables
            var $button = $('bingtranslate_button_' + attribute_code);
            var ajaxUrl = this.getAjaxEntityUrl(data_id, attribute_code, store_id, from_language, to_language);

            // Check if the field is actually enabled
            var $field = $('#' + html_id);
            if (!$field.length > 0 || $field.disabled) {
                if ($button.length) {
                    $button.disabled = true;
                    $button.className = 'disabled';
                }

                this.doDebug('No field with ID "' + html_id + '"');
                return false;
            }

            this.ajax(ajaxUrl, $field, $button);

            return true;
        },

        getAjaxTextUrl: function($field, from_language, to_language) {
            return this.ajaxTextBaseUrl
                + 'string/' + $field.val() + '/'
                + 'from/' + from_language + '/'
                + 'to/' + to_language + '/'
            ;
        },

        getAjaxEntityUrl: function(data_id, attribute_code, store_id, from_language, to_language) {
            return this.ajaxEntityBaseUrl
                + 'id/' + data_id + '/'
                + 'attribute/' + attribute_code + '/'
                + 'from/' + from_language + '/'
                + 'to/' + to_language + '/'
                + 'store/' + store_id + '/'
            ;
        },

        getFromLanguage: function (defaultLanguage) {
            var $bingTranslateSourceLanguage = $('#bingtranslate_source_language');
            var new_from_language = $bingTranslateSourceLanguage.val();

            if (new_from_language !== undefined && new_from_language !== 'auto') {
                return new_from_language;
            }

            if (defaultLanguage !== undefined) {
                return defaultLanguage;
            }

            throw 'Source language is not defined';
        },

        getToLanguage: function (defaultLanguage) {
            var $bingTranslateDestinationLanguage = $('#bingtranslate_destination_language');
            var new_to_language = $bingTranslateDestinationLanguage.val();

            if (new_to_language !== undefined && new_to_language !== 'auto') {
                return new_to_language;
            }

            if (defaultLanguage !== undefined) {
                return defaultLanguage;
            }

            throw 'Destination language is not defined';
        },

        ajax: function (ajaxUrl, $field, $button) {

            // If all is right, perform an AJAX-request
            new Ajax.Request(ajaxUrl, {
                method: 'get',
                onSuccess: function (transport) {
                    var response = transport.responseText;
                    var message;

                    if (response) {
                        var json = response.evalJSON(true);

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
                            if (!$field instanceof jQuery) {
                                $field = $($field);
                            }

                            if ($field.length) {
                                $field.val(json.translation);
                                $field.parent().removeClass('active');
                            }

                            if (tinyMCE) {
                                var editor = tinyMCE.get(html_id);
                                if (editor) {
                                    editor.setContent(json.translation);
                                }
                            }

                            if ($button) {
                                $button.className = 'disabled';
                                $button.disabled = true;
                            }
                        }
                    }
                },

                // General failure
                onFailure: function () {
                    throw 'Failed to contact BingTranslate';
                }
            });
        },

        addButtonToInput: function ($input) {
            var inputId = $input.attr('id');
            var inputName = $input.attr('name');
            var inputType = $input.attr('type');
            var inputClass = $input.attr('class');

            if (inputId === undefined) {
                return false;
            }

            if (inputName === undefined) {
                return false;
            }

            if ($input.attr('disabled') === 'disabled' || $input.prop('readonly')) {
                this.doDebug('Input disabled or readonly');
                return false;
            }

            if (this.inArray(inputName, this.skipInputNames) || this.inArray(inputId, this.skipInputNames)) {
                this.doDebug('Input ' + inputName + ' in skip list');
                return true;
            }

            if (inputClass !== undefined) {
                for (var i = 0; i < this.skipInputClasses.length; i++) {
                    if (inputClass.indexOf(this.skipInputClasses[i]) > -1) {
                        this.doDebug('Input class ' + inputClass + ' in skip list');
                        return true;
                    }
                }
            }

            if (this.inArray(inputType, this.allowedInputTypes) === false) {
                this.doDebug('Input ' + inputName + ' not in allowed input types');
                return false;
            }

            var widgetId = $input.attr('id') + '_btwidget';
            $input.replaceWith(this.getHtmlWidget($input.prop('outerHTML'), widgetId));

            var $widget = $('#' + widgetId);
            $widget.click(function() {
                $(this).parent().addClass('active');
            });

            $input = $('#' + inputId);
            $input.focus(function() {
                $(this).parent().addClass('active');
            });

            $input.blur(function() {
                $(this).parent().removeClass('active');
            });

            $input.change(function() {
                if ($input.val()) {
                    $widget.show();
                } else {
                    $widget.hide();
                }
            });

            if ($input.val()) {
                $widget.show();
            } else {
                $widget.hide();
            }

            return true;
        },
        
        getHtmlWidget: function(fieldHtml, widgetId) {

            return '<div class="bingtranslate-container">'
                + fieldHtml
                + '<a href="#" id="' + widgetId + '" onclick="YireoBingTranslate.translateText(this); return false;">'
                + '<div class="bingtranslate-icon">'
                + '&nbsp;'
                + '</div>'
                + '</a>'
                + '</div>';
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
            if (this.debug === false) {
                return false;
            }

            console.log(string);
            if (variable) {
                console.log(variable);
            }

            return true;
        }
    };

    $(function () {
        $("input").each(function () {
            YireoBingTranslate.addButtonToInput($(this));
        });
    });
})(jQuery);
