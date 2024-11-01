<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<div class="icon32"></div>

	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

	<form name="settings" id="salesbinder" action="<?php //echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-salesbinder'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="salesbinder_subdomain">Subdomain</label></th>
				<td>
				  <input type="text" id="salesbinder_subdomain" name="settings[salesbinder][subdomain]" value="<?php echo esc_attr(shopp_setting('salesbinder_subdomain')); ?>"> .salesbinder.com
				</td>
			</tr>
      <tr>
        <th scope="row" valign="top"><label for="salesbinder_api_key">API Key</label></th>
        <td>
          <input type="password" id="salesbinder_api_key" name="settings[salesbinder][api][key]" value="<?php echo esc_attr(shopp_setting('salesbinder_api_key')); ?>" size="30">
          <br>
          You can generate a new API Key by going into your "Profile" once logged into SalesBinder. You’ll find a button that says "Generate New API Key". More information about generating your API Key can be found in our <a href="http://www.salesbinder.com/kb/generating-your-api-key/" target="_blank">Knowledge Base</a>.
        </td>
      </tr>
			<tr>
				<th scope="row" valign="top"><label for="salesbinder_context_account">Account Context</label></th>
				<td>
					<select id="salesbinder_context_account" name="settings[salesbinder][context][account]">
						<?php echo Shopp::menuoptions(array(2 => 'Customer', 8 => 'Prospect'), shopp_setting('salesbinder_context_account'), true); ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="salesbinder_context_document">Document Context</label></th>
				<td>
					<select id="salesbinder_context_document" name="settings[salesbinder][context][document]">
						<?php echo Shopp::menuoptions(array(4 => 'Estimate', 5 => 'Invoice', 11 => 'Purchase Order'), shopp_setting('salesbinder_context_document'), true); ?>
					</select>
				</td>
			</tr>
      <tr>
        <th scope="row" valign="top"><label for="salesbinder_sync_interval">Sync Interval</label></th>
        <td>
          <select id="salesbinder_sync_interval" name="settings[salesbinder][sync][interval]">
            <option value="">Disabled</option>
            <?php

            $intervals = array();
            foreach (wp_get_schedules() as $value => $schedule) {
              if (in_array($value, array('hourly', 'twicedaily', 'daily')) || !empty($schedule['shopp_salesbinder'])) {
                $intervals[$value] = $schedule['display'];
              }
            }

            ?>
            <?php echo Shopp::menuoptions($intervals, shopp_setting('salesbinder_sync_interval'), true); ?>
          </select>
        </td>
    </table>

    <p class="submit"><input type="submit" class="button-primary" name="save" value="Save Changes" />	<input type="submit" name="sync" value="Save Changes and Force Sync" class="button-secondary"></p>
  </form>
</div>
