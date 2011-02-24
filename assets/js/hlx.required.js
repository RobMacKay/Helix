/**
 * Required scripts for normal Helix operation
 *
 * NOTE: This file should not be modified unless you're very sure you know what
 *      you're doing. 
 *
 * LICENSE: Dual licensed under the MIT or GPL licenses.
 *
 * @author    Jason Lengstorf <jason.lengstorf@copterlabs.com>
 * @copyright 2011 Copter Labs, Inc.
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt  GPL License
 */
jQuery(function($){

    // AJAXify comment flagging
    $('.flag-comment')
        .bind('click', function(e){
                e.preventDefault();

                $.ajax({
                        "type" : "GET",
                        "url" : $(this).attr('href'),
                        "success" : function(response){
                                $.fn.thumbBox.buildModal(response, {
                                    "fullWidth" : 350
                                });
                                $(".thumbbox-main-modal *")
                                    .bind("click", function(e){
                                            e.stopPropagation();
                                        });
                            }
                    });
            });

});
