<div id="comments">

    <h2> Comments for This Entry </h2>

    <ol>
{loop}
        <li id="comment-{comment_id}">
            <!-- AVATAR -->
            {gravatar}

            <!-- COMMENT -->
            <p class="comment">
                {comment}
            </p>
            <!-- COMMENT DATE -->
            <p class="comment_meta">
                Posted by {linked_name} at {date}
                [<a href="#comment-{comment_id}">permalink</a> |
                <a href="{form_action}?page=comments&action=comment-flag&comment_id={comment_id}"
                   class="flag-comment">flag as inappropriate</a>]
            </p>
        </li>
{/loop}
    </ol>

    {comment_form}

</div><!-- end #comments -->
