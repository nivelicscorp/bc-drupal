<?php
/**
 * Fix fullcalendar_view strlen error.
 * Options left_buttons/right_buttons can be arrays in Drupal 11.
 */
$file = '/opt/drupal/web/modules/contrib/fullcalendar_view/src/FullcalendarViewPreprocess.php';
$content = file_get_contents($file);

$content = str_replace(
  '$left_buttons = Xss::filter($options[\'left_buttons\']);',
  '$left_buttons = is_array($options[\'left_buttons\']) ? implode(\',\', $options[\'left_buttons\']) : Xss::filter($options[\'left_buttons\']);',
  $content
);

$content = str_replace(
  '$right_buttons = Xss::filter($options[\'right_buttons\']);',
  '$right_buttons = is_array($options[\'right_buttons\']) ? implode(\',\', $options[\'right_buttons\']) : Xss::filter($options[\'right_buttons\']);',
  $content
);

$content = str_replace(
  "\$slot_duration = empty(\$options['slotDuration']) ? '00:30:00' : Xss::filter(\$options['slotDuration']);",
  "\$slot_duration = empty(\$options['slotDuration']) ? '00:30:00' : (is_array(\$options['slotDuration']) ? '00:30:00' : Xss::filter(\$options['slotDuration']));",
  $content
);

file_put_contents($file, $content);
echo "Patched fullcalendar_view successfully.\n";
