/* global jQuery, wp */

jQuery( function( $ ) {
    'use strict';

    var wc_bobospay_admin = {
        frames: [],
        init: function () {
            $('button.woocommerce_gateway_bobospay_image_upload')
                .on('click', this.onClickUploadButton);

            $('button.woocommerce_gateway_bobospay_image_remove')
                .on('click', this.removeProductImage);

            $(document.body).on('change', '#woocommerce_woocommerce_gateway_bobospay_testmode', function () {
                const test_client_id = $('#woocommerce_woocommerce_gateway_bobospay_bobospay_test_client_id').parents('tr').eq(0),
                    test_client_secret = $('#woocommerce_woocommerce_gateway_bobospay_bobospay_test_client_secret').parents('tr').eq(0),
                    live_client_id = $('#woocommerce_woocommerce_gateway_bobospay_bobospay_live_client_id').parents('tr').eq(0),
                    live_client_secret = $('#woocommerce_woocommerce_gateway_bobospay_bobospay_live_client_secret').parents('tr').eq(0);

                if ($(this).is(':checked')) {
                    test_client_id.show();
                    test_client_secret.show();
                    live_client_id.hide();
                    live_client_secret.hide();
                } else {
                    test_client_id.hide();
                    test_client_secret.hide();
                    live_client_id.show();
                    live_client_secret.show();
                }
            });

            $('#woocommerce_woocommerce_gateway_bobospay_testmode').change();
        },

        onClickUploadButton: function (event) {
            event.preventDefault();

            var data = $(event.target).data();

            // If the media frame already exists, reopen it.
            if ('undefined' !== typeof wc_bobospay_admin.frames[data.fieldId]) {
                // Open frame.
                wc_bobospay_admin.frames[data.fieldId].open();
                return false;
            }

            // Create the media frame.
            wc_bobospay_admin.frames[data.fieldId] = wp.media({
                title: data.mediaFrameTitle,
                button: {
                    text: data.mediaFrameButton
                },
                multiple: false // Set to true to allow multiple files to be selected
            });

            // When an image is selected, run a callback.
            var context = {
                fieldId: data.fieldId,
            };

            wc_bobospay_admin.frames[data.fieldId]
                .on('select', wc_bobospay_admin.onSelectAttachment, context);

            // Finally, open the modal.
            wc_bobospay_admin.frames[data.fieldId].open();
        },

        onSelectAttachment: function () {
            // We set multiple to false so only get one image from the uploader.
            const attachment = wc_bobospay_admin.frames[this.fieldId]
                .state()
                .get('selection')
                .first()
                .toJSON();

            const $field = $('#' + this.fieldId);
            const $img = $('<img />')
                .attr('src', getAttachmentUrl(attachment));

            $field.siblings('.image-preview-wrapper')
                .html($img);

            $field.val(attachment.id);
            $field.siblings('button.woocommerce_gateway_bobospay_image_remove').show();
            $field.siblings('button.woocommerce_gateway_bobospay_image_upload').hide();
        },

        removeProductImage: function (event) {
            event.preventDefault();
            const $button = $(event.target);
            const data = $button.data();
            const $field = $('#' + data.fieldId);

            //update fields
            $field.val('');
            $field.siblings('.image-preview-wrapper').html(' ');
            $button.hide();
            $field.siblings('button.woocommerce_gateway_bobospay_image_upload').show();
        },
    };

    function getAttachmentUrl(attachment) {
        if (attachment.sizes && attachment.sizes.medium) {
            return attachment.sizes.medium.url;
        }
        if (attachment.sizes && attachment.sizes.thumbnail) {
            return attachment.sizes.thumbnail.url;
        }
        return attachment.url;
    }

    wc_bobospay_admin.init();

});
