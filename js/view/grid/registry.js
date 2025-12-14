Ext.define('Ufolep13Volley.view.grid.registry', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.grid_registry',
    title: 'Base de registres',
    store:
        {
            type: 'registry',
            autoLoad: true,
        },
    columns: [
        {
            header: 'clé', dataIndex: 'registry_key', flex: 1, filter: {
                type: 'string',
            },
        },
        {
            header: 'valeur', dataIndex: 'registry_value', flex: 1, filter: {
                type: 'string',
            },
        },
    ],
});