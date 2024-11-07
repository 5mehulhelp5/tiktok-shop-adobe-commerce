define([
    'jquery',
    'TikTokShop/Plugin/Messages',
    'TikTokShop/Template/Edit',
    'TikTokShop/Common',
    'Magento_Ui/js/modal/modal'
], function (jQuery, MessagesObj) {
    window.Settings = Class.create(Common, {

        // ---------------------------------------

        initialize: function () {

            this.messageObj = Object.create(MessagesObj);
            this.messageObj.setContainer('#anchor-content');

            this.templateEdit = new TemplateEdit();

            this.initFormValidation();
        },

        // ---------------------------------------

        saveSettings: function () {
            var isFormValid = true;
            var uiTabs = jQuery.find('div.ui-tabs-panel')
            uiTabs.forEach(item => {
                var elementId = item.getAttribute('data-ui-id').split('-').pop();
                if (isFormValid) {
                    var form = jQuery(item).find('form');
                    if (form.length) {
                        if (!form.valid()) {
                            isFormValid = false;
                            return;
                        }

                        if (!TikTokShop.url.urls[elementId]) {
                            return;
                        }

                        jQuery("a[name='" + elementId + "']").removeClass('_changed _error');
                        var formData = form.serialize(true);
                        formData.tab = elementId;
                        this.submitTab(TikTokShop.url.get(elementId), formData);
                    }
                }
            })
        },

        restoreAllHelpsAndRememberedChoices: function () {
            var self = this;
            var modalDialogMessage = $('modal_interface_dialog');

            if (!modalDialogMessage) {
                modalDialogMessage = new Element('div', {
                    id: 'modal_interface_dialog'
                });
            }

            jQuery(modalDialogMessage).confirm({
                title: TikTokShop.translator.translate('Are you sure?'),
                actions: {
                    confirm: function () {

                        new Ajax.Request(TikTokShop.url.get('settings_interface/restoreRememberedChoices'), {
                            method: 'get',
                            asynchronous: true,
                            onSuccess: function (transport) {

                                BlockNoticeObj.deleteAllHashedStorage();
                                self.templateEdit.forgetSkipSaveConfirmation();

                                self.messageObj.addSuccess(
                                        TikTokShop.translator.translate('Help Blocks have been restored.')
                                );
                            }
                        });
                    }
                },
                buttons: [{
                    text: TikTokShop.translator.translate('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: TikTokShop.translator.translate('Confirm'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        },

        // ---------------------------------------

        submitTab: function (url, formData) {
            var self = this;

            new Ajax.Request(url, {
                method: 'post',
                asynchronous: false,
                parameters: formData || {},
                onSuccess: function (transport) {
                    var result = transport.responseText;

                    self.messageObj.clear();
                    if (!result.isJSON()) {
                        self.messageObj.addError(result);
                    }

                    result = JSON.parse(result);

                    if (typeof result['view_show_block_notices_mode'] !== 'undefined') {
                        BLOCK_NOTICES_SHOW = result['view_show_block_notices_mode'];
                        BlockNoticeObj.initializedBlocks = [];
                        BlockNoticeObj.init();
                    }

                    if (result.messages && Array.isArray(result.messages) && result.messages.length) {
                        self.scrollPageToTop();
                        result.messages.forEach(function (el) {
                            var key = Object.keys(el).shift();
                            self.messageObj['add' + key.capitalize()](el[key]);
                        });
                        return;
                    }

                    if (result.success) {
                        self.messageObj.addSuccess(TikTokShop.translator.translate('Settings saved'));
                    } else {
                        self.messageObj.addError(TikTokShop.translator.translate('Error'));
                    }

                }
            });
        }

        // ---------------------------------------
    });
});
