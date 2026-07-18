// js/educator-review.js

$(document).ready(function () {

    // Handle both Approve and Disapprove buttons
    $(document).on('click', '.approve-btn, .disapprove-btn', function (e) {
        e.preventDefault();

        const $btn  = $(this);
        const $form = $btn.closest('.review-form');       // our form
        const status = $btn.val();                        // "approved" or "disapproved"

        if ($form.length === 0) {
            alert('Form not found for this question.');
            return;
        }

        // Build data: serialize form fields + add status manually
        let dataToSend = $form.serialize();
        dataToSend += '&status=' + encodeURIComponent(status);

        $.ajax({
            url: $form.attr('action'),               // review-question.php
            method: $form.attr('method') || 'POST',  // POST
            data: dataToSend,
            success: function (response) {
                // PHP page should echo "true" if operation successful
                if (typeof response === 'string' && response.trim() === 'true') {
                    // remove the row that contains this form
                    $form.closest('tr').remove();
                } else {
                    alert('Failed to update the question. Server said: ' + response);
                }
            },
            error: function () {
                alert('Server error. Please try again.');
            }
        });
    });

});
