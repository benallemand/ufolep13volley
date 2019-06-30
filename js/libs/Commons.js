window.Sencha = (function () {
    var isTouch, _ref, _ref1;
    isTouch = !!((_ref = Ext.getVersion("touch")) != null ? (_ref1 = _ref.version) != null ? _ref1.match(/2\./) : void 0 : void 0);
    return {
        isTouch: isTouch,
        isExtJS: !isTouch
    };
})();
Sencha.modelCompatibility = Sencha.isExtJS ? function (x) {
    return x;
} : function (classConfig) {
    if (!classConfig.hasOwnProperty('config'))
        classConfig.config = {};
    classConfig.config['fields'] = classConfig.fields;
    delete classConfig.fields;
    return classConfig;
};
Sencha.storeCompatibility = Sencha.isExtJS ? function (x) {
    return x;
} : function (classConfig) {
    if (classConfig.hasOwnProperty('proxy')) {
        if (classConfig.proxy.hasOwnProperty('reader')) {
            if (classConfig.proxy.reader.hasOwnProperty('root')) {
                classConfig.proxy.reader.rootProperty = classConfig.proxy.reader.root;
                delete classConfig.proxy.reader.root;
            }
        }
    }
    return classConfig;
};

Ext.define('overrides.window.Window', {
    override: 'Ext.window.Window',

    initComponent: function () {
        var me = this;
        me.on('show', me.onShowRemoveUnselectable, me);
        me.callParent();
    },

    onShowRemoveUnselectable: function (grid, state, eOpts) {
        // Let user select the displayed text
        this.removeCls("x-unselectable");
    }
});