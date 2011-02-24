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
<h2>{title}</h2>{admin}

<p class="image-display">
    <img src="{image}"
         alt="{caption}" />
    <span>{caption}</span>
</p>

{entry}

<ul class="meta">
        <li class="date">Date <strong>{date}</strong></li>
        <li class="comments">
            {comment_text} <strong>{comment_count}</strong>
        </li>
        <li class="tags">Category <strong>{tags}</strong></li>
</ul><!-- end .meta -->

{comments}
{/loop}

// Footer
<a href="{backlink}">&laquo; back to previous page</a>