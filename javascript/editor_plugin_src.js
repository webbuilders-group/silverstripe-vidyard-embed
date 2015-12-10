(function() {
    var each=tinymce.each;

    tinymce.create('tinymce.plugins.Vidyard', {
        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @returns Name/value array containing information about the plugin.
         * @type Array 
         */
        getInfo: function() {
            return {
                    longname: 'Vidyard Embed SilverStripe',
                    author: 'Ed Chipman',
                    authorurl: 'http://webbuildersgroup.com/',
                    infourl: 'http://webbuildersgroup.com/',
                    version: "1.0"
                };
        },
        
        init: function(ed, url) {
            ed.onSaveContent.add(function(ed, o) {
                var content=jQuery(o.content);
                content.find('.ss-htmleditorfield-file.vidyard').each(function() {
                    var el=jQuery(this);
                    var shortCode='[vidyard width="'+el.attr('width')+'"'
                                       +' height="'+el.attr('height')+'"'
                                       +' class="'+el.data('cssclass')+'"'
                                       +' thumbnail="'+el.data('thumbnail')+'"'
                                       +']'+el.data('url')
                                       +'[/vidyard]';
                    el.replaceWith(shortCode);
                });
                
                o.content=jQuery('<div />').append(content).html();
            });
            
            var shortTagRegex=/(.?)\[vidyard(.*?)\](.+?)\[\/\s*vidyard\s*\](.?)/gi;
            ed.onBeforeSetContent.add(function(ed, o) {
                var matches=null, content=o.content;
                var prefix, suffix, attributes, attributeString, url;
                var attrs, attr;
                var imgEl;
                
                // Match various parts of the embed tag
                while((matches=shortTagRegex.exec(content))) {
                    prefix=matches[1];
                    suffix=matches[4];
                    if(prefix==='[' && suffix===']') {
                        continue;
                    }
                    
                    attributes={};
                    // Remove quotation marks and trim.
                    attributeString=matches[2].replace(/['"]/g, '').replace(/(^\s+|\s+$)/g, '');
                    
                    // Extract the attributes and values into a key-value array (or key-key if no value is set)
                    attrs=attributeString.split(/\s+/);
                    for(attribute in attrs) {
                        attr=attrs[attribute].split('=');
                        if(attr.length == 1) {
                            attributes[attr[0]]=attr[0];
                        } else {
                            attributes[attr[0]]=attr[1];
                        }
                    }
                    
                    // Build HTML element from embed attributes.
                    attributes.cssclass=attributes['class'];
                    url=matches[3];
                    imgEl=jQuery('<img/>').attr({
                                                'src': attributes['thumbnail'],
                                                'width': attributes['width'],
                                                'height': attributes['height'],
                                                'class': attributes['cssclass'],
                                                'data-url': url
                                            }).addClass('ss-htmleditorfield-file vidyard');
                    
                    jQuery.each(attributes, function (key, value) {
                        imgEl.attr('data-'+key, value);
                    });
                    
                    content=content.replace(matches[0], prefix+(jQuery('<div/>').append(imgEl).html())+suffix);
                }
                
                o.content=content;
            });
        }
    });
    
    // Adds the plugin class to the list of available TinyMCE plugins
    tinymce.PluginManager.add("vidyard", tinymce.plugins.Vidyard);
})();
