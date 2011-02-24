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

// Header
<h2>{title}</h2>

// Entry loop
{loop [
        "text_preview" : true,
        "text_preview_length" : 50
    ]}
<div class="blog-preview">
    <h3><a href="{relative_link}">{title}</a></h3>
    <a href="{relative_link}">
        <img src="{thumb}" alt="{caption}" class="entry-thumb" />
    </a>{entry}
    <p class="meta">
        Posted {date} by {author}. Tagged with {tags}.<br />
        There are {comment-count} {comment-text} on this entry.
    </p>{admin}
</div><!-- end .blog-preview -->
{/loop}

// Footer
{pagination}