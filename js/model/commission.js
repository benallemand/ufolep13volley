Ext.define('Ufolep13Volley.model.commission', {
    extend: 'Ext.data.Model',
    fields: [
        { name: 'id_commission', type: 'int',},
        { name: 'nom', type: 'string',},
        { name: 'prenom', type: 'string',},
        { name: 'fonction', type: 'string',},
        { name: 'telephone1', type: 'string',},
        { name: 'telephone2', type: 'string',},
        { name: 'email', type: 'string',},
        { name: 'photo', type: 'string',},
        { name: 'type', type: 'string',},
        { name: 'attribution', type: 'string',},
    ]
});
