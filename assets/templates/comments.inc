<div id="comments">

    <h2> Comments for This Entry </h2>

    <ol>
{loop}
        <li id="comment-{comment_id}">

            <div class="commenter-info">
                {gravatar}
                <p class="comment-meta">
                    <strong>{linked_name}</strong>
                    {date}
                </p><!-- end .comment-meta -->
            </div><!-- end .commenter-info -->

            <p class="comment-links">
                <a href="#comment-{comment_id}">permalink</a> |
                <a href="{form_action}?page=comments&action=comment-flag&comment_id={comment_id}"
                   class="flag-comment">flag</a>
            </p><!-- end .comment-links -->

            <p class="comment">
                {comment}
            </p>{admin}{threaded_replies}
        </li>
{/loop}
    </ol>

    {comment_form}

</div><!-- end #comments -->
