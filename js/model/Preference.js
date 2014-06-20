Ext.define('Ufolep13Volley.model.Preference', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'is_remind_matches',
            type: 'bool',
            convert : function(val) {
                if(val === 'on') {
                    return true;
                }
                return false;
            }
        }
    ]
});
