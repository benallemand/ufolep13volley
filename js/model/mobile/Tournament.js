Ext.define('Ufolep13Volley.model.mobile.Tournament', {
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
