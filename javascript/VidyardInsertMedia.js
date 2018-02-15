(function($) {
    $.entwine('ss', function($) {
        /**
         * Extends the add url button for vidyard
         */
        $('form.htmleditorfield-form.htmleditorfield-mediaform .add-url.add-vidyard').entwine({
            getURLField: function() {
                return this.closest('.CompositeField').find('input.remoteurl');
            },

            onclick: function(e) {
                var urlField=this.getURLField(), container=this.closest('.CompositeField'), form=this.closest('form');

                if (urlField.validate()) {
                    container.addClass('loading');
                    form.showVidyardView('http://' + urlField.val()).done(function() {
                        container.removeClass('loading');
                    });
                    form.redraw();
                }

                return false;
            }
        });
        
        /**
         * Extends the media form to add vidyard support
         */
        $('form.htmleditorfield-mediaform').entwine({
            showVidyardView: function(url) {
                var self=this, params={VidyardURL: url};
    
                var item=$('<div class="ss-htmleditorfield-file loading" />');
                this.find('.content-edit').prepend(item);
                
                var dfr=$.Deferred();
                
                $.ajax({
                    url: $.path.addSearchParams(this.attr('action').replace(/MediaForm/, 'viewvidyard'), params),
                    success: function(html, status, xhr) {
                        var newItem=$(html).filter('.ss-htmleditorfield-file');
                        item.replaceWith(newItem);
                        self.redraw();
                        dfr.resolve(newItem);
                    },
                    error: function() {
                        item.remove();
                        dfr.reject();
                    }
                });
                
                return dfr.promise();
            },
            updateFromEditor: function() {
                var node=this.getSelection();
                
                if(node.is('img') && node.hasClass('vidyard')) {
                    var self=this;
                    this.showVidyardView(node.data('url') || node.attr('src')).done(function(filefield) {
                        filefield.updateFromNode(node);
                        self.toggleCloseButton();
                        self.redraw();
                    });
                    
                    this.redraw();
                }else {
                    this._super();
                }
            }
        });
        
        /**
         * Insert an vidyard object tag into the content. Requires the 'media' plugin for serialization of tags into <img> placeholders.
         */
        $('form.htmleditorfield-mediaform .ss-htmleditorfield-file.embed.vidyard').entwine({
            getExtraData: function() {
                var data=this._super();
                
                return $.extend({
                                'lightbox':(this.find(':input[name=UseLightbox]').is(':checked') ? 'true':'false')
                            }, data);
            },
            getHTML: function() {
                var el,
                attrs=this.getAttributes(),
                extraData=this.getExtraData(),
                // imgEl=$('<img id="_ss_tmp_img" />');
                imgEl=$('<img />').attr(attrs).addClass('ss-htmleditorfield-file vidyard');
                
                $.each(extraData, function (key, value) {
                    imgEl.attr('data-' + key, value);
                });
                
                if(extraData.CaptionText) {
                    el=$('<div style="width: ' + attrs['width'] + 'px;" class="captionImage ' + attrs['class'] + '"><p class="caption">' + extraData.CaptionText + '</p></div>').prepend(imgEl);
                } else {
                    el=imgEl;
                }
                
                return $('<div />').append(el).html(); // Little hack to get outerHTML string
            },
            /**
             * Insert updated HTML content into the rich text editor
             */
            insertHTML: function(ed) {
                // Insert content
                ed.replaceContent(this.getHTML());
            },
            updateFromNode: function(node) {
                this._super(node);
                
                this.find(':input[name=UseLightbox]').prop('checked', (node.data('lightbox')==true));
            }
        });
    });
})(jQuery);