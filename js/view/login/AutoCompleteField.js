Ext.define('Ufolep13Volley.view.login.AutoCompleteField', {
    extend: 'Ext.form.field.Text',
    alias : 'widget.actextfield',
    initComponent: function() {
        Ext.each(this.fieldSubTpl, function(oneTpl, idx, allItems) {
            if (Ext.isString(oneTpl)) {
                allItems[idx] = oneTpl.replace('autocomplete="off"', 'autocomplete="on"');
            }
        });
        this.callParent(arguments);
    }
});