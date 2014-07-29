Ext.define('Ufolep13Volley.view.site.CommissionPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.commissionPanel',
    layout: {
        type: 'vbox',
        align: 'center'
    },
    autoScroll: true,
    bodyStyle: 'background-color: #C9D7E5;',
    items: {
        layout: 'border',
        width: 1280,
        height: 2048,
        items: [
            {
                region: 'north',
                xtype: 'headerPanel'
            },
            {
                region: 'north',
                xtype: 'titlePanel'
            },
            {
                region: 'center',
                flex: 1,
                layout: 'fit',
                items: {
                    xtype: 'dataview',
                    autoScroll: true,
                    tpl: new Ext.XTemplate(
                            '<H2 align="center">Les personnes membres de la CTSD 13 Volley-Ball ainsi que les personnes qui aident le font à titre de bénévolat.</H2>',
                            '<div id="commission">',
                            '<h1>Membres CTSD</h1>',
                            '<table>',
                            '<tpl for=".">',
                            '<tpl if="type == \'membre\'">',
                            '<tr>',
                            '<div class="membre">',
                            '<td>',
                            '<div class="details">',
                            '<h1>{prenom} {nom}</h1>',
                            '<ul>',
                            '<li class="fonction">{fonction}</li>',
                            '<li><span>{telephone1}</span></li>',
                            '<li><span>{telephone2}</span></li>',
                            '<li><span><a href="mailto:{email}" target="_blank">{email}</a></span></li>',
                            '</ul>',
                            '</div>',
                            '</td>',
                            '<td>',
                            '<div class="photo"><img src="{photo}" width="100px" height="auto"></div>',
                            '</td>',
                            '</div>',
                            '</tr>',
                            '</tpl>',
                            '</tpl>',
                            '</table>',
                            '<h1>Supports</h1>',
                            '<table>',
                            '<tpl for=".">',
                            '<tpl if="type == \'support\'">',
                            '<tr>',
                            '<div class="membre">',
                            '<td>',
                            '<div class="details">',
                            '<h1>{prenom} {nom}</h1>',
                            '<ul>',
                            '<li class="fonction">{fonction}</li>',
                            '<li><span>{telephone1}</span></li>',
                            '<li><span>{telephone2}</span></li>',
                            '<li><span><a href="mailto:{email}" target="_blank">{email}</a></span></li>',
                            '</ul>',
                            '</div>',
                            '</td>',
                            '<td>',
                            '<div class="photo"><img src="{photo}" width="100px" height="auto"></div>',
                            '</td>',
                            '</div>',
                            '</tr>',
                            '</tpl>',
                            '</tpl>',
                            '</table>',
                            '</div>'
                            ),
                    itemSelector: 'div.membre',
                    store: 'Commission'
                }
            }
        ]}
});
