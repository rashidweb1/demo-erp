<?php
defined('BASEPATH') or exit('No direct script access allowed');


/**
* Helper: send a single formatted reminder email using Perfex's emails_model
* Returns array of results per recipient.
*/
function msr_send_emails(array $staff_rows) {
$CI = &get_instance();
$CI->load->model('emails_model');


$results = [];


foreach ($staff_rows as $staff) {
// build subject and message
$subject = 'Monthly Reminder: Review your Timesheet & Records';


$message = '';
$message .= "Hello " . $staff->firstname . ",<br><br>";
$message .= "This is a monthly reminder to review and update your records:<br>";
$message .= "&nbsp;&nbsp;&nbsp;- Timesheet<br>";
$message .= "&nbsp;&nbsp;&nbsp;- Check-in & Check-out<br>";
$message .= "&nbsp;&nbsp;&nbsp;- Leaves<br>";
$message .= "&nbsp;&nbsp;&nbsp;- Overtime (OT)<br>";
$message .= "&nbsp;&nbsp;&nbsp;- Tickets (Support / Tasks)<br><br>";
$message .= "<strong>Note:</strong> After the 3rd of every month, leaves and OT requests will not be approved.<br><br>";
$message .= "Regards,<br>Admin";


// send using emails_model -> send_simple_email (Perfex standard helper)
$sent = false;


try {
    $params = [
        'email'   => $staff->email,
        'subject' => $subject,
        'message' => $message,
    ];

    // Perfex global mail function (uses SMTP)
    $sent = app_mail($params);
} catch (Exception $e) {
    $sent = false;
}



$results[] = [
'staffid' => $staff->staffid,
'email' => $staff->email,
'sent' => (bool)$sent,
];


// log activity (Perfex utilities)
if ($sent) {
log_activity('Monthly Staff Reminder sent to: ' . $staff->email);
} else {
log_activity('Monthly Staff Reminder FAILED to: ' . $staff->email);
}
}


return $results;
}