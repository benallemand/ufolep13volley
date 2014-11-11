Ext.define('Ufolep13Volley.view.site.TitlePanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.titlePanel',
    border: false,
    html: Ext.is.Phone ? "<div id='titre_mobile'>" + title + "</div>" : "<div id='titre'>" + title + "</div>"
});
