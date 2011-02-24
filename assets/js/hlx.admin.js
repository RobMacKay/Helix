/**
 * Scripts that allow for AJAX editing
 *
 * LICENSE: Dual licensed under the MIT or GPL licenses.
 *
 * @author    Jason Lengstorf <jason.lengstorf@copterlabs.com>
 * @copyright 2011 Copter Labs, Inc.
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt  GPL License
 */
jQuery(function($){

    $.ajaxSetup({
            "type" : "POST",
            "url" : Helix_Edit.processfile,
            "error" : function(e){return false;}
        });

    // Handler for edit button
    $(".hlx-edit")
            .live("click", function(event){
                    event.preventDefault();

                    var url_array = $(this).attr("href").split('/'),
                        page = url_array[1],
                        id = url_array[3]!=undefined ? url_array[3] : '';

                    Helix_Edit.show(page, 'entry-edit', id);
                });

    // For direct load of the editing form, activate TinyMCE
    Helix_Edit.tinymce_init();

    // Handler to auto-generate slugs
    $("input#title")
            .live("keydown,blur", function(){
                    $("input#slug").val(Helix_Edit.make_slug($(this).val()));
                });

});

/**
 * A quick object to house all of the Helix AJAX editing controls
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author Jason Lengstorf <jason.lengstorf@copterlabs.com>
 * @copyright 2011 Copter Labs, Inc.
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
var Helix_Edit = {

    /**
     * Current session token
     */
    token : $("meta[name='hlx-token']").attr("content"),

    /**
     * Location of the TinyMCE file
     */
    tinymcefile : '/assets/js/tiny_mce/tiny_mce.js',

    /**
     * Location of the file that handles form submissions
     */
    processfile : '/assets/inc/process.php',

    /**
     * Displays the entry editing/creation form in a modal window
     *
     * @param {String} page   The current page being edited
     * @param {String} action The action to take upon form submission
     * @param {String} id     The entry ID if an entry is being edited
     * @return void
     */
    show : function( page, action, id )
    {
        var params = "page=" + page
                    + "&action=" + action
                    + "&entry_id=" + id
                    + "&token=" + this.token;

        $.ajax({
            data : params,
            success : function(response)
            {
                // Build a modal window w/the form
                $.fn.thumbBox.buildModal(response, {
                        "fullWidth" : 650
                    });

                // Prevent unwanted window closure
                $(".thumbbox-main-modal *")
                    .bind("click", function(event){
                            event.stopPropagation();
                        });

                // Show TinyMCE editing controls
                setTimeout("Helix_Edit.tinymce_init();", 500);
            }
        });
    },

    /**
     * Initializes TinyMCE
     *
     * @return void
     */
    tinymce_init : function(  )
    {
        $('textarea#entry').tinymce({
                script_url : this.tinymcefile,
                theme : "advanced",
                plugins : "safari,iespell,inlinepopups,"
                        + "spellchecker,paste,advimage,media",
                theme_advanced_blockformats : "p,h2,h3,h4,blockquote,code",
                style_formats : [
                    {title : 'H2 Title', block : 'h2'},
                    {title : 'H3 Title', block : 'h3'}
                ],
                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "left",
                theme_advanced_buttons1 : "pasteword,|,bold,italic,underline,"
                    + "blockquote,|,justifyleft,justifycenter,justifyright,|,"
                    + "bullist,numlist,outdent,indent,|,link,unlink,image,"
                    + "media,code,|,forecolor,backcolor,|,formatselect",
                theme_advanced_buttons2 : "",
                theme_advanced_buttons3 : "",
                relative_urls : false
            });
    },

    /**
     * Reorders an entry in the database
     *
     * @param {String}  page        The page on which the entry is posted
     * @param {Int}     pos         The current entry position
     * @param {String}  direction   The direction to move the entry
     * @param {Int}     id          The ID of the entry
     */
    reorder : function( page, pos, direction, id )
    {
        var params = "page=" + page
                    + "&action=reorderEntry&pos=" + pos
                    + "&id=" + id
                    + "&direction=" + direction;

        $.ajax({
            data : params,
            success : function()
            {
                document.location = "/"+page;
            }
        });
    },

    /**
     * Creates an SEO-friendly slug from the entered title
     *
     * @param  {String} str The string to be converted to a slug
     * @return {String}     The generated slug
     */
    make_slug : function( str )
    {
        return str.replace(/[^\w\s]+/g, '').replace(/\s+/g, '-').toLowerCase();
    }

};
