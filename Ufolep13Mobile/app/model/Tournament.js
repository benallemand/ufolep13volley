Ext.define('Ufolep13.model.Tournament', {
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
