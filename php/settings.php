<?php
require_once(WP_PLUGIN_DIR . THIS_PLUGIN_NAME .'/php/helpers.php');

//Settings Page ===========================================================================

function fac_settings() {
	global $wpdb;
	$settings = FACs::get_options();
	$email_id = FACs::$pica_email;
	$image_res_id =  FACs::$pica_img_import_res;
	
	if ($_POST['ps_action']  == 'save')
	{
		$settings[$email_id] = $_POST[$email_id];
		$settings[$image_res_id] = $_POST[$image_res_id];
		FACs::update_options($settings);
	}
	
	$settings = FACs::get_options();

	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br></div>
		<h2>' <?= FACs::$plugin_title ?> ' Settings</h2>
		<form action="" method="post" class="themeform">
			<input type="hidden" id="ps_action" name="ps_action" value="save" />
			<table class="form-table">
				<tbody>
					<tr>
						<th>Picasa Account Email</th>
						<td>
							<input name="<?=$email_id?>" type="text" value="<?=$settings[$email_id]?>" class="regular-text code" />
							<div class="description">Used for Picasa <?= FACs::$connected_title .' import; Only public '. strtolower(FACs::$connected_title_plural) ?> from Picasa will be available for import.</div>
						</td>
					</tr>
					<tr>
						<th>Picasa Image Import Resolution</th>
						<td>
						
						<?= FAHelper::generateSelect($image_res_id, FACs::$pica_img_resolutions, $settings[$image_res_id]) ?>
						
							<div class="description">Choose what resolution images from Picasa are to be imported with.</div>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button-primary" value="Save Settings" />
			</p>
		</form>
	</div>
	<?
}

?>