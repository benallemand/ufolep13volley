Ext.define('Ufolep13Volley.view.grid.ufolep', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.grid_ufolep',
    autoScroll: true,
    selType: 'checkboxmodel',
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        }
    ],
});