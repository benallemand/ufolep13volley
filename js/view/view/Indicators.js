var indicator_tpl = new Ext.XTemplate(
    '<div style="display: flex; flex-wrap: wrap; padding: 10px;">',
    '<tpl for=".">',
    '<div class="indicator-wrap" data-id="{id}" style="',
    'width: 140px; height: 140px; margin: 8px; padding: 12px;',
    'border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);',
    'display: flex; flex-direction: column; justify-content: space-between;',
    'cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;',
    '<tpl if="loading">background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);</tpl>',
    '<tpl if="!loading && type === \'alert\'">background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);</tpl>',
    '<tpl if="!loading && type !== \'alert\'">background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);</tpl>',
    '">',
    '<div style="text-align: center; font-size: 28px; font-weight: bold; margin-top: 10px;">',
    '<tpl if="loading">',
    '<span style="color: #999;">⏳</span>',
    '<tpl elseif="type === \'alert\'">',
    '<span style="color: #e53935;">{value}</span>',
    '<tpl else>',
    '<span style="color: #1976d2;">{value}</span>',
    '</tpl>',
    '</div>',
    '<div style="text-align: center; font-size: 11px; color: #555; line-height: 1.3; overflow: hidden; flex: 1; display: flex; align-items: center; justify-content: center;">',
    '<span>{fieldLabel}</span>',
    '</div>',
    '</div>',
    '</tpl>',
    '</div>'
);

Ext.define('Ufolep13Volley.view.view.Indicators', {
    extend: 'Ext.view.View',
    alias: 'widget.view_indicators',
    tpl: indicator_tpl,
    itemSelector: 'div.indicator-wrap',
    emptyText: 'Aucun indicateur',
    loadMask: false,

    initComponent: function () {
        var me = this;
        var indicatorType = me.indicatorType || 'info';
        me.store = Ext.create('Ext.data.Store', {
            fields: ['id', 'fieldLabel', 'type', 'value', 'details', 'loading'],
            proxy: {
                type: 'rest',
                url: 'ajax/indicators.php',
                extraParams: {mode: 'list'},
                reader: {type: 'json', root: 'results'}
            },
            filters: [{
                property: 'type',
                value: indicatorType
            }],
            listeners: {
                load: function (store, records) {
                    store.clearFilter(true);
                    store.filter('type', indicatorType);
                    Ext.each(store.getRange(), function (record) {
                        record.set('loading', true);
                        me.loadIndicatorDetail(record);
                    });
                }
            }
        });
        me.callParent(arguments);
    },

    loadIndicatorDetail: function (record) {
        var me = this;
        Ext.Ajax.request({
            url: 'ajax/indicators.php',
            method: 'GET',
            params: {mode: 'detail', id: record.get('id')},
            success: function (response) {
                var data = Ext.decode(response.responseText);
                var value = data.value || 0;
                if (value === 0) {
                    me.getStore().remove(record);
                } else {
                    record.set('loading', false);
                    record.set('value', value);
                    record.set('details', data.details || []);
                    record.commit();
                }
            },
            failure: function () {
                record.set('loading', false);
                record.set('value', -1);
                record.commit();
            }
        });
    },

    listeners: {
        itemdblclick: function (view, record, item, index, e) {
            view.showDetails(record);
        }
    },

    showDetails: function (record) {
        var detailsData = record.get('details');
        if (!detailsData || detailsData.length === 0) {
            return;
        }
        var fields = [];
        var columns = [];
        for (var k in detailsData[0]) {
            fields.push(k);
            columns.push({
                text: k,
                dataIndex: k,
                flex: 1
            });
        }
        Ext.create('Ext.window.Window', {
            title: record.get('fieldLabel'),
            height: 500,
            width: 700,
            maximizable: true,
            layout: 'fit',
            items: {
                xtype: 'grid_ufolep',
                selModel: 'rowmodel',
                store: Ext.create('Ext.data.Store', {
                    fields: fields,
                    data: {'items': detailsData},
                    proxy: {type: 'memory', reader: {type: 'json', root: 'items'}}
                }),
                columns: columns
            },
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',
                items: [{
                    text: 'Télécharger',
                    handler: function (button) {
                        button.up('window').down('grid').export(record.get('fieldLabel'));
                    }
                }]
            }]
        }).show();
    }
});
