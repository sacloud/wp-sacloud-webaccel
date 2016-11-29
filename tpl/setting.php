<div id="sacloud-webaccel-flash" class="notice">
		<p></p>
		<?php if($messages): ?>
				<?php foreach($messages as $msg): ?>
						<p><?php echo $msg; ?></p>
				<?php endforeach; ?>
		<?php endif; ?>
</div>

<h2><?php _e('Setting SakuraCloud WebAccelerator', "wp-sacloud-webaccel"); ?></h2>
<p><?php _e("Please Input the API tokens for the SakuraCloud. No account? Let's ", 'wp-sacloud-webaccel'); ?><a href="<?php _e('https://secure.sakura.ad.jp/signup3/member-register/input.html', 'wp-sacloud-webaccel'); ?>" target="_blank" ><?php _e('signup', 'wp-sacloud-webaccel'); ?></a></p>

<form method="post" action="options.php">
		<?php settings_fields('sacloud-webaccel-options'); ?>
		<?php do_settings_sections('sacloud-webaccel-options'); ?>

		<h3><?php _e('API Key settings', 'wp-sacloud-webaccel'); ?></h3>
		<table>
				<tr>
						<th><?php _e('SakuraCloud API AccessKey', 'wp-sacloud-webaccel') ?>:</th>
						<td>
								<input id="sacloud-webaccel-api-key" name="sacloud-webaccel-options[api-key]" type="text"
												size="15" value="<?php echo esc_attr(
																				sacloud_webaccel_get_option('api-key')
																				 ); ?>" class="regular-text code"/>

						</td>
				</tr>
				<tr>
						<th><?php _e('SakuraCloud API Secret', 'wp-sacloud-webaccel') ?>:</th>
						<td>
								<input id="sacloud-webaccel-api-secret" name="sacloud-webaccel-options[api-secret]" type="text"
												size="15" value="<?php echo esc_attr(
																				sacloud_webaccel_get_option('api-secret')
																				 ); ?>"  class="regular-text code"/>

						</td>
				</tr>

				<tr>
						<td colspan="2" style="padding-top: 1em">
                <input type="button" name="test" id="submit" class="button button-secondary"
                        value="<?php _e('Check the connection', 'wp-sacloud-webaccel'); ?>"
												onclick="sacloudojs_connect_test()"/>
						</td>
				</tr>
		</table>

		<h3><?php _e('Cache settings', 'wp-sacloud-webaccel'); ?></h3>
		<table>
			<tr>
				<th><?php _e('Cache s-maxage', 'wp-sacloud-webaccel') ?>:</th>
				<td>
					<input id="sacloud-webaccel-maxage" name="sacloud-webaccel-options[maxage]" type="text"
						   size="5" value="<?php echo esc_attr(
						sacloud_webaccel_get_option('maxage')
					); ?>"  class="regular-text code" style="width:5em;"/>

				</td>
			</tr>

			<tr>
				<th><?php _e('Page', 'wp-sacloud-webaccel') ?>:</th>
				<td >
					<input id="sacloud-webaccel-enable-page" type="checkbox" name="sacloud-webaccel-options[enable-page]"
						   value="1" <?php checked(sacloud_webaccel_get_option('enable-page'),1); ?> />
					<label for="sacloud-webaccel-enable-page"><?php _e('Cache Page', 'wp-sacloud-webaccel'); ?></label>

				</td>
			</tr>

			<tr>
				<th><?php _e('Post', 'wp-sacloud-webaccel') ?>:</th>
				<td >
					<input id="sacloud-webaccel-enable-post" type="checkbox" name="sacloud-webaccel-options[enable-post]"
						   value="1" <?php checked(sacloud_webaccel_get_option('enable-post'),1); ?> />
					<label for="sacloud-webaccel-enable-post"><?php _e('Cache Post', 'wp-sacloud-webaccel'); ?></label>

				</td>
			</tr>


			<tr>
				<th><?php _e('Archive', 'wp-sacloud-webaccel') ?>:</th>
				<td >
					<input id="sacloud-webaccel-enable-archive" type="checkbox" name="sacloud-webaccel-options[enable-archive]"
						   value="1" <?php checked(sacloud_webaccel_get_option('enable-archive'),1); ?> />
					<label for="sacloud-webaccel-enable-archive"><?php _e('Cache Archive', 'wp-sacloud-webaccel'); ?></label>
					(<?php _e('date,category,tags,author,custom taxonomies' , 'wp-sacloud-webaccel')?>)
				</td>
			</tr>

			<tr>
				<th><?php _e('Media', 'wp-sacloud-webaccel') ?>:</th>
				<td >
					<input id="sacloud-webaccel-enable-media" type="checkbox" name="sacloud-webaccel-options[enable-media]"
						   value="1" <?php checked(sacloud_webaccel_get_option('enable-media'),1); ?> />
					<label for="sacloud-webaccel-enable-media"><?php _e('Cache Media', 'wp-sacloud-webaccel'); ?></label>
					(<?php _e('use .htaccess', 'wp-sacloud-webaccel')?> )
				</td>
			</tr>

		</table>

	<h3><?php _e('Domain settings', 'wp-sacloud-webaccel'); ?></h3>
	<p class="description">
		<?php _e('When you enable the SubDomain , URL rewriting of the mediafile is enabled', 'wp-sacloud-webaccel'); ?>
	</p>

	<table>
		<tr>
			<th><?php _e('SubDomain', 'wp-sacloud-webaccel') ?>:</th>
			<td >
				<input id="sacloud-webaccel-use-subdomain" type="checkbox" name="sacloud-webaccel-options[use-subdomain]"
					   value="1" <?php checked(sacloud_webaccel_get_option('use-subdomain'),1); ?> />
				<label for="sacloud-webaccel-use-subdomain"><?php _e('Use SubDomain for MediaFile', 'wp-sacloud-webaccel'); ?></label>
			</td>
		</tr>
		<tr>
			<th><?php _e('SSL', 'wp-sacloud-webaccel') ?>:</th>
			<td >
				<input id="sacloud-webaccel-subdomain-ssl" type="checkbox" name="sacloud-webaccel-options[subdomain-ssl]"
					   value="1" <?php checked(sacloud_webaccel_get_option('subdomain-ssl'),1); ?> />
				<label for="sacloud-webaccel-subdomain-ssl"><?php _e('Use SSL', 'wp-sacloud-webaccel'); ?></label>

			</td>
		</tr>
		<tr>
			<th><?php _e('SubDomain Name', 'wp-sacloud-webaccel') ?>:</th>
			<td>
				<input id="sacloud-webaccel-subdomain-name" name="sacloud-webaccel-options[subdomain-name]" type="text"
					   size="5" value="<?php echo esc_attr(
					sacloud_webaccel_get_option('subdomain-name')
				); ?>"  class="regular-text code" style="width:8em;"/>.user.webaccel.jp

			</td>
		</tr>
	</table>

	<table>
		<tr>
			<td colspan="2">
				<?php submit_button(); ?>
			</td>
		</tr>
	</table>

	<h3><?php _e('Debug', 'wp-sacloud-webaccel'); ?></h3>
	<table>
		<tr>
			<th><?php _e('Log', 'wp-sacloud-webaccel') ?>:</th>
			<td >
				<input id="sacloud-webaccel-enable-log" type="checkbox" name="sacloud-webaccel-options[enable-log]"
					   value="1" <?php checked(sacloud_webaccel_get_option('enable-log'),1); ?> />
				<label for="sacloud-webaccel-enable-log"><?php _e('Output DebugLog', 'wp-sacloud-webaccel'); ?></label>
				(<?php _e('Need to set `WP_DEBUG`', 'wp-sacloud-webaccel') ?>)
			</td>
		</tr>
	</table>


</form>
