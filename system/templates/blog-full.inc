/*
 * Preview for the Blog class
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author      Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright   2010 Ennui Design
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 * @see         Blog class <ennui-cms/inc/class.blog.inc.php>
 *
 * Available header template tags:
 *  title           Page title displayed above
 *
 * Available entry template tags:
 *  site-url        The base URL for the site (i.e. http://yoursite.com/)
 *  page            Page name (for URLs)
 *  title           The title of the entry
 *  body            The body of the entry
 *  tags            Tags for the entry
 *  category-name   The name of the category
 *  admin           Administrative options for the entry
 *
 * Available footer template tags:
 *  pagination      Controls to navigate between pages of entry previews
 */

// Entry loop
{loop}
<h2><a href="/{page}/{url}">{title}</a></h2>
<div class="entry-image">
    <img src="{image-url}" alt="{title}" class="entry-main-image" />
    <p class="cap">{imgcap}</p>
</div>
{body}
{admin}<p class="meta">
    Posted {date} by {author}. Tagged with {tags}.<br />

    <!-- sharing options from http://addtoany.com -->
    <a class="a2a_dd" href="http://www.addtoany.com/share_save">
        <img src="http://static.addtoany.com/buttons/share_save_171_16.png"
             width="171" height="16" border="0" class="a2a"
             alt="Share/Bookmark" />
    </a>
    <script type="text/javascript">
        a2a_linkname="{title}";
        a2a_linkurl="{site-url}{page}/{url}";
    </script>
    <script type="text/javascript"
            src="http://static.addtoany.com/menu/page.js"></script>
    <!-- end sharing options -->

</p>
{comments}
{/loop}

// Footer
<a href="{backlink}">&laquo; back to previous page</a>