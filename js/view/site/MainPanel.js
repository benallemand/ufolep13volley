Ext.define('Ufolep13Volley.view.site.MainPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.mainPanel',
    layout: Ext.is.Phone ? 'accordion' : 'anchor',
    autoScroll: true,
    border: false,
    margin: Ext.is.Phone ? 0 : '0 50 0 50',
    items: [
        {
            xtype: 'LastResultsGrid',
            maxHeight: 480
        },
        {
            xtype: 'LastPostsGrid',
            maxHeight: 480
        },
        {
            xtype: 'WebSitesGrid',
            maxHeight: 480
        }
    ],
    dockedItems: [
        Ext.is.Phone ? null : {
            height: 150,
            dock: 'top',
            border: false,
            layout: 'border',
            items: [
                {
                    region: 'west',
                    width: 300,
                    margin: 20,
                    border: false,
                    layout: 'border',
                    items: [
                        {
                            flex: 2,
                            region: 'north',
                            xtype: 'banner'
                        },
                        {
                            flex: 1,
                            region: 'center',
                            xtype: 'image',
                            src: './images/JeuAvantEnjeu.jpg'
                        }
                    ]
                },
                Ext.isIE9m ? null : {
                    region: 'center',
                    flex: 1,
                    xtype: 'panel',
                    html: "<div id='my-slideshow'><div class='swiper-container'><div class='swiper-wrapper'/></div></div></div>",
                    listeners: {
                        render: function () {
                            var mySwiper = new Swiper('.swiper-container', {
                                effect: 'coverflow',
                                grabCursor: true,
                                centeredSlides: true,
                                slidesPerView: 4,
                                coverflow: {
                                    rotate: 50,
                                    stretch: 0,
                                    depth: 100,
                                    modifier: 1,
                                    slideShadows: true
                                },
                                autoplay: 1500,
                                autoplayDisableOnInteraction: false
                            });
                            mySwiper.stopAutoplay();
                            mySwiper.removeAllSlides();
                            var storeImages = Ext.data.StoreManager.lookup('Images');
                            storeImages.load(function (records) {
                                Ext.each(records, function (record) {
                                    mySwiper.appendSlide("<div class='swiper-slide' style='background-image:url(" + record.get('src') + ")'></div>");
                                });
                                mySwiper.update();
                                mySwiper.startAutoplay();
                            });
                        }
                    }
                }
            ]
        },
        {
            dock: 'top',
            xtype: 'headerPanel'
        },
        {
            xtype: 'toolbar',
            dock: 'bottom',
            border: false,
            items: [
                '->',
                {
                    xtype: 'tbtext',
                    text: 'UFOLEP 13 VOLLEY (c) 2015-2016',
                    style: {
                        color: '#0099CC',
                        fontWeight: 'bold'
                    }
                },
                '|',
                {
                    xtype: 'tbtext',
                    id: 'textShowLastCommit',
                    text: ''
                },
                '->',
                {
                    xtype: 'button',
                    glyph: 'xf003@FontAwesome',
                    text: 'Contact',
                    scale: 'medium',
                    href: 'mailto:benallemand@gmail.com'
                }
            ]
        }
    ]
});
        