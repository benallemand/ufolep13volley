Ext.define('Ufolep13Volley.view.grid.commission', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.grid_commission',
    title: 'Commission',
    features: [
        {
            ftype: 'grouping',
            groupHeaderTpl: '{name}'
        }
    ],
    store:
        {
            type: 'commission',
            autoLoad: true,
        },
    selType: 'rowmodel',
    autoScroll: true,
    columns: [
        {header: 'nom', dataIndex: 'nom', flex: 1,},
        {header: 'prenom', dataIndex: 'prenom', flex: 1,},
        {header: 'fonction', dataIndex: 'fonction', flex: 1, cellWrap: true,},
        {header: 'telephone1', dataIndex: 'telephone1', flex: 1,},
        {header: 'telephone2', dataIndex: 'telephone2', flex: 1,},
        {header: 'email', dataIndex: 'email', flex: 1,},
        {header: 'photo', dataIndex: 'photo', flex: 1,},
        {header: 'type', dataIndex: 'type', flex: 1,},
        {header: 'attribution', dataIndex: 'attribution', flex: 1,},
    ],
});