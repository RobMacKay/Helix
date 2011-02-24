/**
 * JavaScript to be executed for all pages and all visitors
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */

// Set defaults for the thumbBox plugin
$.fn.thumbBox.defaults.lightColor = '#FEFEFE';
$.fn.thumbBox.defaults.darkColor = '#111';

jQuery(function($){

    // Technically valid workaround for target="_blank"
    $('[rel="external"]').attr('target', '_blank');

    // Load a user's Flickr stream
    $('#sidebar-flickr')
        .loadFlickr({
                "flickrID" : "29080075@N02",
                "displayNum" : 8,
                "randomize" : true,
                "callback" : function(el){
                    $(el).thumbBox({
                            "fullWidth" : "600"
                        });
                }
            });

});
