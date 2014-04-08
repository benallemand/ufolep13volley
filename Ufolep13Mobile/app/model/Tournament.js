Ext.define('Ufolep13Mobile.model.Tournament', {
    extend: 'Ext.data.Model',
    config: {
        fields: [
            {
                name: 'id',
                type: 'int'
            },
            'code_competition',
            'libelle'
        ]
    }
});
