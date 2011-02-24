/**
 * LoadFlickr - A jQuery Plugin to load images from a given Flickr user into an
 *      unordered list
 *
 * Demo and Documentation at http://ennuidesign.com/projects/loadflickr
 *
 * @version 1.0.0
 * @author Jason Lengstorf <jason.lengstorf@copterlabs.com>
 * @copyright 2010 Copter Labs, Inc.
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
(function($){

    $.fn.loadFlickr = function(options)
    {
        var o = $.extend($.fn.loadFlickr.defaults, options);

        return this.each(function(){
            var thisCache = this,
                suffix = '_m',
                query_string,load_uri;

            if( o.fullFeedUri!==null )
            {
                var feed_array = o.fullFeedUri.split('?');
                load_uri = feed_array[0];
                query_string = feed_array[1];

                if( query_string.indexOf('&format=json&jsoncallback=?')===-1 )
                {
                    query_string += '&format=json&jsoncallback=?';
                }
            }
            else
            {
                load_uri = "http://api.flickr.com/services/feeds/photos_public.gne";
                query_string = "id="+o.flickrID+"&lang=en-us&format=json&jsoncallback=?";
            }

            // Request the JSON and process it
            $.ajax({
                type:'GET',
                url:load_uri,
                data:query_string,
                success:function(feed) {
                    // Create an empty array to store images
                    var thumbs = [];

                    // Shuffle the array if the option is enabled
                    if( o.randomize===true )
                    {
                        feed.items.shuffle();
                    }

                    // Make thumbnails if requested
                    if( o.makeThumb===true )
                    {
                        suffix = '_s';
                    }

                    // Loop through the items
                    for(var i=0, l=feed.items.length; i<l; ++i)
                    {
                        // Manipulate the image to get thumb and medium sizes
                        var title = feed.items[i].title,
                            hide = i>=o.displayNum ? ' style="display: none;"' : '',
                            markup = feed.items[i].media.m.replace(
                                    /^(.*?)_m\.jpg$/,
                                    '<li' + hide
                                        + '><a href="$1.jpg">'
                                        + '<img src="$1' + suffix + '.jpg" alt="'
                                        + title + '" /></a></li>'
                                );

                        // Add the new element to the array
                        thumbs.push(markup);
                    }

                    // Display the thumbnails on the page
                    $(thisCache).html(thumbs.join(''));

                    // Fire the callback
                    o.callback(thisCache);
                },
                dataType:'jsonp'
            });
        });
    };

    // Add a shuffle function to the Array prototype
    Array.prototype.shuffle = function() {
            var len = this.length,
                i = len;
             while( i-- )
             {
                var p = parseInt(Math.random()*len),
                    t = this[i];

                this[i] = this[p];
                this[p] = t;
            }
        };

    $.fn.loadFlickr.defaults = {
            "flickrID" : "29080075@N02",
            "fullFeedUri" : null,
            "displayNum" : 9,
            "randomize" : false,
            "makeThumb" : true,
            "callback" : function(el){}
        };

})(jQuery)
