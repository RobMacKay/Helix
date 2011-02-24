<?php

// Make sure the form submission is valid
if( Form::is_form_submission_valid()===TRUE )
{
    echo Form::handle_form_submission();
    exit;
}

// If no conditions have been met, something fishy is going on
else
{
    // Throw an exception and die
    ECMS_Error::log_exception(
            new Exception("An unknown error has occurred.\n")
        );
}
