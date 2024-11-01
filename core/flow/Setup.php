<?php

defined('WPINC') || header('HTTP/1.1 403') & exit;

class Shopp_SalesBinderAdminSetup
{
  public function salesbinder()
  {
    if ((!empty($_POST['save']) || !empty($_POST['sync'])) && isset($_POST['settings'])) {
      check_admin_referer('shopp-salesbinder');

      shopp_set_setting('salesbinder_subdomain', $_POST['settings']['salesbinder']['subdomain']);
      shopp_set_setting('salesbinder_api_key', $_POST['settings']['salesbinder']['api']['key']);
      shopp_set_setting('salesbinder_sync_interval', $_POST['settings']['salesbinder']['sync']['interval']);
      shopp_set_setting('salesbinder_context_account', $_POST['settings']['salesbinder']['context']['account']);
      shopp_set_setting('salesbinder_context_document', $_POST['settings']['salesbinder']['context']['document']);

      shopp_set_formsettings();

      wp_clear_scheduled_hook('shopp_salesbinder_cron');
      if (!empty($_POST['settings']['salesbinder']['sync']['interval'])) {
        wp_schedule_event(time(), $_POST['settings']['salesbinder']['sync']['interval'], 'shopp_salesbinder_cron');
      }

      if (!empty($_POST['sync'])) {
        do_action('shopp_salesbinder_cron');
      }

      $updated = __('Shopp settings saved.', 'Shopp');
    }

    include dirname(dirname(__FILE__)) . '/ui/settings/salesbinder.php';
  }
}
