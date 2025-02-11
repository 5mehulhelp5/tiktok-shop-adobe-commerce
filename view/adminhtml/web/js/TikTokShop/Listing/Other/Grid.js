define([
    'TikTokShop/Listing/Other/Grid'
], function () {
    window.TikTokShopListingOtherGrid = Class.create(ListingOtherGrid, {

        // ---------------------------------------

        tryToMove: function (listingId) {
            this.movingHandler.submit(listingId, this.onSuccess)
        },

        onSuccess: function (wizardId) {
            var refererUrl = TikTokShop.url.get('listingWizard', {id: wizardId});

            setLocation(refererUrl);
        },

        // ---------------------------------------

        getSelectedItemsParts: function () {
            var selectedProductsArray = this.getSelectedProductsArray();

            if (this.getSelectedProductsString() == '' || selectedProductsArray.length == 0) {
                return [];
            }

            var maxProductsInPart = this.getMaxProductsInPart();

            var result = [];
            for (var i = 0; i < selectedProductsArray.length; i++) {
                if (result.length == 0 || result[result.length - 1].length == maxProductsInPart) {
                    result[result.length] = [];
                }
                result[result.length - 1][result[result.length - 1].length] = selectedProductsArray[i];
            }

            return result;
        },

        // ---------------------------------------

        getMaxProductsInPart: function () {
            return 10;
        }

        // ---------------------------------------
    });
});
