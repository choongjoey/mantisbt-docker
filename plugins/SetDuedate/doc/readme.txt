This plugin sets the due date based on the priority of the issue if the due date field
left empty or the issue would be due when the issue is submitted (this could happen
rarely if the user selects the current date as a due date, but doesnt set the hh:mm
properly)

If the priority is not defined in the SetDueDate.php and the Config_edit.php then the
default value is applied.

If you have other priorites than the default, or the numeric values assigned to these
priorites are different than the default then open you config_inc.php file and search
for the following: "$g_priority_enum_string" and modify the SetDueDate.php and the 
Config_edit.php according to your setup.

If you want to force these settings for all the reported issues then simply revoke users
to access the due date field by than add the following line to your config_inc.php:
$g_due_date_update_threshold = NOBODY;

If lets say you want developers to set their own due dates, but you want your reporters
to forced use the the default ones you can use this:
$g_due_date_update_threshold = DEVELOPER;

As of version 3, it is also possible to set the number of days by project/category.
In case this is used, it will override the priority settings.
This is only available for defined categories defined per project.

You can use my Reminder plugin to send emails as and when the duedate is gettuing near.

Cas Nuy / Istvan Baktai
01/03/2011