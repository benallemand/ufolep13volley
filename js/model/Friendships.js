Ext.define('Ufolep13Volley.model.Friendships', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'id_club_1',
            type: 'int'
        },
        'nom_club_1',
        {
            name: 'id_club_2',
            type: 'int'
        },
        'nom_club_2'
    ]
}));
