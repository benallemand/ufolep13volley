Ext.define('Ufolep13Volley.view.grid.ufolep', {
    extend: 'Ext.ux.ExportableGrid',
    alias: 'widget.grid_ufolep',
    autoScroll: true,
    selModel: 'checkboxmodel',
    plugins: 'gridfilters',
    viewConfig: {
        enableTextSelection: true
    },
    features: [
        {
            ftype: 'grouping',
            groupHeaderTpl: '{name}'
        }
    ],
    listeners: {
        added: function(grid) {
            grid.addDocked({
                xtype: 'toolbar',
                dock: 'top',
                items: [
                    'FILTRES',
                    {
                        xtype: 'tbseparator'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Recherche',
                        listeners: {
                            change: function(textfield, searchText) {
                                var searchTerms = searchText.split(',').map(searchTerm => searchTerm.trim());
                                var store = textfield.up('grid').getStore();
                                store.removeFilter('searchInGrid');
                                if (Ext.isEmpty(searchText)) {
                                    textfield.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                                    return;
                                }
                                var model = store.first();
                                if (!model) {
                                    textfield.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                                    return;
                                }
                                store.filter({
                                    id: 'searchInGrid', filterFn: function (item) {
                                        var fields = model.getFields();
                                        var queribleFields = [];
                                        Ext.each(fields, function (field) {
                                            if (field.getType() === 'string' || field.getType() === 'auto') {
                                                Ext.Array.push(queribleFields, field.getName());
                                            }
                                        });
                                        var found = false;
                                        Ext.each(searchTerms, function (searchTerm) {
                                            var regExp = new RegExp(searchTerm, "i");
                                            Ext.each(queribleFields, function (queribleField) {
                                                if (!item.get(queribleField)) {
                                                    return true;
                                                }
                                                if (regExp.test(item.get(queribleField))) {
                                                    found = true;
                                                    return false;
                                                }
                                            });
                                            return !found;
                                        });
                                        return found;
                                    }
                                });
                                textfield.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                            }
                        }
                    },
                    {
                        xtype: 'displayfield',
                        fieldLabel: 'Total',
                        action: 'displayFilteredCount'
                    }
                ]
            })
        }
    }
});