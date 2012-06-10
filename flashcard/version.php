<?PHP // $Id: version.php,v 1.1 2011-10-15 12:25:59 vf Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of NEWMODULE
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2011100601;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007101550;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>