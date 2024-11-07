define([
    'jquery',
    'TikTokShop/TikTokShop/Listing/View/Grid',
    'TikTokShop/Listing/MovingFromListing',
    'TikTokShop/Listing/Wizard/Category',
    'Magento_Ui/js/modal/modal'
], function (jQuery) {

    window.TikTokShopListingViewSettingsGrid = Class.create(TikTokShopListingViewGrid, {

        // ---------------------------------------

        accountId: null,
        shopId: null,

        // ---------------------------------------

        initialize: function ($super, gridId, listingId, accountId, shopId) {
            this.accountId = accountId;
            this.shopId = shopId;

            $super(gridId, listingId);
        },

        // ---------------------------------------

        prepareActions: function ($super) {
            $super();

            this.movingHandler = new MovingFromListing(this);
            this.categoryHandler = new TikTokShopListingCategory(this);

            this.actions = Object.extend(this.actions, {
                editCategorySettingsAction: this.categoryHandler.editCategorySettings.bind(this.categoryHandler),
                movingAction: this.movingHandler.run.bind(this.movingHandler),
            });
        },

        // ---------------------------------------

        tryToMove: function (listingId) {
            this.movingHandler.submit(listingId, this.onSuccess)
        },

        onSuccess: function () {
            this.unselectAllAndReload();
        },

        // ---------------------------------------

        confirm: function (config) {
            if (config.actions && config.actions.confirm) {
                config.actions.confirm();
            }
        },

        // ---------------------------------------
    });
});
