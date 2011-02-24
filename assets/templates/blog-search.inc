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
<h3>{title}</h3>
<ul id="entries">
// Entry loop
{loop}
    <li class="blog-post">
        <h2><a href="{relative_link}">{title}</a></h2>{admin}
        <a href="{relative_link}">
            <img src="{thumb}" alt="{title}" class="blog_img" />
        </a>
        {excerpt}

        <ul class="meta">
                <li class="date">Date <strong>{date}</strong></li>
                <li class="comments">
                    {comment_text}
                    <strong>
                        <a href="{relative_link}#comments">{comment_count}</a>
                    </strong>
                </li>
                <li class="tags">Category <strong>{tags}</strong></li>
        </ul><!-- end .meta -->
    </li><!-- end .blog-post -->
{/loop}

// Footer
</ul><!-- end #entries -->
{pagination}