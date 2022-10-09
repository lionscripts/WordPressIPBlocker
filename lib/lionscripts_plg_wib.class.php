<?php

if(!class_exists('lionscripts_plg_wib'))
{
	class lionscripts_plg_wib extends lionscripts_plg
	{
		public function __construct($plg_dir)
		{
			global $LIONSCRIPTS, $wpdb;
			
			$this->plg_name 				= 'IP Address Blocker';
			$this->plg_description 			= '';
			$this->plg_version 				= '11.1.1';
			$this->plg_hook_version 		= '1';
			$this->plg_identifier 			= 'WIB';
			$this->plg_db_version 			= '17';
			$this->plg_table['ip']			= $wpdb->prefix.strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'_'.str_replace(' ', '_', strtolower($this->plg_name));
			$this->plg_table['options']		= $wpdb->prefix.$this->plg_table['ip'].'_options';
			$this->plg_db_var['show_to_banned_user'] = strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'_'.str_replace(' ', '_', strtolower($this->plg_name)).'_show_to_banned_user';
			$this->plg_db_var['display_attr'] = strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'_'.str_replace(' ', '_', strtolower($this->plg_name)).'_display_attr';
			
			$this->plg_name_2 				= str_replace('Address ', '', $this->plg_name);
			$this->plg_url_val 				= str_replace(' ', '-', strtolower($this->plg_name));
			$this->plg_referer 				= '?utm_source=wib_lite_version&utm_content='.$this->plg_version;
			$this->plg_product_url 			= LIONSCRIPTS_HOME_PAGE_URL.'product/wordpress-'.$this->plg_url_val.'-pro';
			$this->plg_name_pro 			= $this->plg_name_2.' Pro';
			$this->plg_heading 				= $this->plg_name;
			$this->plg_short_name 			= $this->plg_name;
			$this->plg_ad_ver				= rand(1, 2);
	
			$this->site_admin_url_val 		= strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'-'.$plg_dir;
			$this->site_admin_url 			= get_admin_url().'admin.php?page='.$this->site_admin_url_val;
			$this->site_admin_dashboard_url = get_admin_url().'admin.php?page='.strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'-dashboard';
			$this->site_base				= array('dir'=>ABSPATH, 'www'=>get_bloginfo('wpurl'));
			$this->plg_base 				= array('dir'=>$this->site_base['dir'].'wp-content'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$plg_dir.DIRECTORY_SEPARATOR, 'www'=>$this->site_base['www']."/wp-content/plugins/".$plg_dir.'/');
			$this->plg_assets 				= array('dir'=>$this->plg_base['dir'].'assets'.DIRECTORY_SEPARATOR, 'www'=>$this->plg_base['www'].'assets/');
			$this->plg_css 					= array('dir'=>$this->plg_assets['dir'].'css'.DIRECTORY_SEPARATOR, 'www'=>$this->plg_assets['www'].'css/');
			$this->plg_images 				= array('dir'=>$this->plg_assets['dir'].'images'.DIRECTORY_SEPARATOR, 'www'=>$this->plg_assets['www'].'images/');
			$this->plg_javascript 			= array('dir'=>$this->plg_assets['dir'].'js'.DIRECTORY_SEPARATOR, 'www'=>$this->plg_assets['www'].'js/');
			$this->plg_others 				= array('dir'=>$this->plg_assets['dir'].'others'.DIRECTORY_SEPARATOR, 'www'=>$this->plg_assets['www'].'others/');
			$this->plg_attr					= '<font style="font-size:12px;"><center>IP Blocking Protection is enabled by <a href="'.$this->plg_product_url.'" target="_blank">'.$this->plg_name.'</a> from <a href="'.LIONSCRIPTS_HOME_PAGE_URL.$this->plg_referer.'" target="_blank">'.LIONSCRIPTS_SITE_NAME.'</a>.</center></font>';
			
			$this->plg_redirect_const 		= strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'_'.strtolower($this->plg_identifier)."_activate_redirect";
			$this->plg_db_version_const 	= strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'_'.strtolower($this->plg_identifier)."_db_version";
	
			$plugin_file = $this->plg_url_val.'/'.$this->plg_url_val.'.php';
			$action = sanitize_text_field(@$_GET['action']);

			add_action( 'admin_menu', array($this, strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'_admin_menu') );
	
			register_activation_hook($this->plg_base['dir'].$plg_dir.'.php', array($this, 'install'));
			register_deactivation_hook($this->plg_base['dir'].$plg_dir.'.php', array($this, 'deactivate'));
			register_uninstall_hook($this->plg_base['dir'].$plg_dir.'.php', strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'_'.strtolower($this->plg_identifier).'_uninstall');
			
			add_action('admin_init', array($this, 'admin_settings_page'));
			// add_action('wp_footer', array($this, 'attr_display'));
			add_action('plugins_loaded', array($this, 'upgrade'));
			
			add_filter("plugin_action_links_".$plugin_file, array($this, 'settings_link'), 10, 2);

			add_action('admin_enqueue_scripts', array($this, 'print_admin_styles_scripts'));
			add_action('admin_print_scripts', array($this, 'admin_inl_js'));

			if(isset($action) && ($action == 'download_csv'))
			{
				add_action('admin_init', array($this, 'export_csv'));
			}
		}

		public function export_csv($format)
		{
			global $LIONSCRIPTS, $wpdb;

			if( !current_user_can( 'manage_options' ) ){ return false; }

			if( !is_admin() ){ return false; }

			$blocked_ips_list = $this->get_all_blocked_ips();

			$format = sanitize_text_field($_GET['format']);
			if(($format == 'wib_pro'))
			{
				$ips = ($wpdb->get_results("SELECT * FROM ".$this->plg_table['ip']));

				$config = array('Do-After-Block'=>get_option($this->plg_db_var['show_to_banned_user']));

				if(!headers_sent())
				{
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename=IP_Address_Blocker_Lite_Bkp_'.(date('d_M_Y')).'_In_WordPress_IP_Blocker_Pro_Format'.'.dat');
					header('Pragma: no-cache');
				}

				echo base64_encode(json_encode(array('config'=>$config, 'list_data'=>$ips)));
				exit;
			}
			else
			{
				$header = 'IP Address,Added On Date';
				$rows = '';

				if(isset($blocked_ips_list) && !empty($blocked_ips_list))
				{
					foreach($blocked_ips_list as $key=>$value)
					{
						$rows .= $value->ip.','.$value->dt_added."\n";
					}
				}

				if(!headers_sent())
				{
					header('Content-Type: application/csv');
					header('Content-Disposition: attachment; filename=IP_Address_Blocker_Lite_Bkp_'.(date('d_M_Y')).'_In_Normal_CSV_Format'.'.csv');
					header('Pragma: no-cache');
				}

				echo $header."\n".$rows;
				exit;
			}
		}

		public function print_admin_styles_scripts()
		{
			wp_enqueue_style('lionscripts_css', $this->plg_css['www'].'style.css');
		}
		
		public function get_configuration()
		{
			global $LIONSCRIPTS;
			$LIONSCRIPTS[$this->plg_identifier]['show_blank_page_to_banned_user'] = get_option($this->plg_db_var['show_to_banned_user']); 
			$LIONSCRIPTS[$this->plg_identifier]['show_'.strtolower($this->plg_identifier).'_attribution'] = get_option($this->plg_db_var['display_attr']);
		}
		
		public function save_configuration($data)
		{
			global $LIONSCRIPTS;
			update_option( $this->plg_db_var['show_to_banned_user'], ((isset($data['show_blank_page_to_banned_user']) && ($data['show_blank_page_to_banned_user'])) ? $data['show_blank_page_to_banned_user'] : '0') );
			update_option( $this->plg_db_var['display_attr'], ((isset($data['show_'.strtolower($this->plg_identifier).'_attribution']) && ($data['show_'.strtolower($this->plg_identifier).'_attribution'])) ? $data['show_'.strtolower($this->plg_identifier).'_attribution'] : '0') );
			$this->get_configuration();
		}
		
		public function install($update_db_version=false)
		{
			global $wpdb;

			$current_db_version = get_option($this->plg_db_version_const);

			$sql = "CREATE TABLE ".$this->plg_table['ip']." (
						id int(12) NOT NULL AUTO_INCREMENT, 
						ip VARCHAR(255) DEFAULT '0.0.0.0' NOT NULL, 
						dt_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, 
						PRIMARY KEY (id), 
						UNIQUE KEY ip (ip), 
						KEY dt_added (dt_added)
					);";
			require_once(ABSPATH.'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			if($update_db_version)
				update_option($this->plg_db_version_const, $this->plg_db_version);

			if(!isset($current_db_version) || empty($current_db_version))
				add_option($this->plg_db_version_const, $this->plg_db_version);
		} 
		
		public function upgrade()
		{
			$current_db_version = get_option($this->plg_db_version_const);

			if($current_db_version != $this->plg_db_version)
			{
				$this->install($update_db_version);
			}
		}
		
		public function deactivate()
		{
			delete_option($this->plg_redirect_const);
		} 
		
		public function settings_link($links)
		{
			$settings_link = '<a href="'.$this->site_admin_url.'">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

		public function admin_settings_page()
		{
			if (get_option($this->plg_redirect_const, false)) 
			{
				delete_option($this->plg_redirect_const);
				wp_redirect($this->site_admin_url);
			}
		}
		
		public function add_ip_to_db($ip, $date_time='')
		{
			global $wpdb;

			if(empty($date_time))
				$date_time = date('Y-m-d H:i:s');

			$rows_affected = $wpdb->insert( $this->plg_table['ip'], array('ip'=>$ip, 'dt_added'=> $date_time) );
		}
		
		public function prepare_all_blocked_ips()
		{
			global $wpdb;
			$result = $wpdb->get_results("SELECT * FROM ".$this->plg_table['ip']);
			return $result;
		}
		
		public function get_all_blocked_ips()
		{
			$blocked_ips_data = $this->prepare_all_blocked_ips();
			
			if(isset($blocked_ips_data) && !empty($blocked_ips_data))
			{
				return $blocked_ips_data;
			}
			else
				return false;
		}
		
		public function delete_ip($id)
		{
			global $wpdb;
			$wpdb->query("DELETE FROM ".$this->plg_table['ip']." WHERE id = '".$id."'");
		}
		
		public function lionscripts_admin_menu()
		{
			$this->show_lionscripts_menu();
			add_submenu_page( strtolower(LIONSCRIPTS_SITE_NAME_SHORT), $this->plg_short_name, $this->plg_name, 'activate_plugins', $this->site_admin_url_val, array($this, 'lionscripts_plg_f') );
		}
		
		public function show_lionscripts_menu()
		{
			global $menu;
			$lionscripts_menu_available = false;
			
			foreach($menu as $item)
			{
				if( strtolower($item[0]) == strtolower(LIONSCRIPTS_SITE_NAME_SHORT))
					return $lionscripts_menu_available = true;
			}
			
			if($lionscripts_menu_available == false)
			{
				add_menu_page(LIONSCRIPTS_SITE_NAME_SHORT, LIONSCRIPTS_SITE_NAME_SHORT, 'level_8', strtolower(LIONSCRIPTS_SITE_NAME_SHORT), strtolower(LIONSCRIPTS_SITE_NAME_SHORT), $this->plg_images['www'].'ls-icon-16.png');
	
				add_submenu_page( 
					strtolower(LIONSCRIPTS_SITE_NAME_SHORT) 
					, LIONSCRIPTS_SITE_NAME_SHORT.' Dashboard' 
					, 'Dashboard'
					, 'activate_plugins'
					, strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'-dashboard'
					, array($this, strtolower(LIONSCRIPTS_SITE_NAME_SHORT).'_dashboard')
				);
			
				remove_submenu_page( strtolower(LIONSCRIPTS_SITE_NAME_SHORT), strtolower(LIONSCRIPTS_SITE_NAME_SHORT) );
			}
		}
	
		public function lionscripts_dashboard()
		{
			global $LIONSCRIPTS;

			$this->use_thickbox();
			$referer = '?utm_source=lionscripts-dashboard-wibl-wp-pl';
			$tab = sanitize_text_field($_GET['tab']);
			?>
			<div class="wrap">
				<div class="ls-icon-32">
					<br />
				</div>
				<h2 class="nav-tab-wrapper">
					<a href="<?php echo LIONSCRIPTS_HOME_PAGE_URL.$referer; ?>" target="_blank"><?php echo LIONSCRIPTS_SITE_NAME; ?></a>
					<a href="<?php echo $this->site_admin_dashboard_url; ?>" class="nav-tab <?php echo ( (!isset($tab) || (trim($tab) == '')) ? 'nav-tab-active' : '' ); ?>">Dashboard</a>
					<a href="<?php echo LIONSCRIPTS_HOME_PAGE_URL.$referer; ?>" target="_blank" class="nav-tab">LionScripts' Website</a>
					<a href="<?php echo LIONSCRIPTS_SUPPORT_PAGE_URL.$referer; ?>" target="_blank" class="nav-tab">Technical Support</a>
				</h2>
				<div class="tab_container">
					<div style="width:49%;" class="fluid_widget_container">
						<div class="postbox" id="about_lionscripts">
							<h3><span>About Us</span></h3>
							<div class="inside">
								<div class="">
									<?php
									ksort($LIONSCRIPTS['ABOUT_US']);
									$LIONSCRIPTS['N_ABOUT_US'] = end($LIONSCRIPTS['ABOUT_US']);
									echo $LIONSCRIPTS['N_ABOUT_US'];
									?>
								</div>
							</div>
						</div>
					</div>
					<div style="width:49%;margin-left:1%;" class="fluid_widget_container">
						<div class="postbox" id="more_from_lionscripts">
							<h3><span>Products from our house</span></h3>
							<div class="inside">
								<div class="">
									<p>
										<?php
										ksort($LIONSCRIPTS['WP_PRODUCTS']);
										$LIONSCRIPTS['ALL_WP_PRODUCTS'] = end($LIONSCRIPTS['WP_PRODUCTS']);
										?>
										<ul class="bullet inside">
											<?php
											foreach($LIONSCRIPTS['ALL_WP_PRODUCTS'] as $product_data)
											{
												?>
												<!--<li><a class="thickbox" title="<?php echo $product_data['name']; ?>" href="plugin-install.php?tab=plugin-information&plugin=<?php echo $product_data['wp_url_var']; ?>&TB_iframe=true&width=640&height=500"><?php echo $product_data['name']; ?></a></li>-->
												<li><a target="_blank" title="<?php echo $product_data['name']; ?>" href="<?php echo $product_data['url'].$referer; ?>"><?php echo $product_data['name']; ?></a></li>
												<?php
											}
											?>
										</ul>
									</p>
								</div>
							</div>
						</div>
					</div>
					<div class="cl"></div>
					<div style="width:49%;" class="fluid_widget_container">
						<div class="postbox" id="more_from_lionscripts">
							<h3><span>Questions and Support</span></h3>
							<div class="inside">
								<div class="">
									<p>
										<?php echo LIONSCRIPTS_SITE_NAME; ?> provides 24x7 support for all its products and services. So in terms of service, you don't need to worry about the techincal support. 
									</p>
									<p>
										If you have any concern or issue regarding any of our software, please visit <a href="<?php echo LIONSCRIPTS_SUPPORT_PAGE_URL.'ask'.$referer; ?>" target="_blank"><?php echo preg_replace('/\/|http\:/i', '', LIONSCRIPTS_SUPPORT_PAGE_URL); ?>/ask</a> and provide complete details of your issue.
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		
		public function lionscripts_plg_f()
		{
			global $LIONSCRIPTS;

			$this->use_thickbox();
			$block_type = sanitize_text_field($_GET['block_type']);
			$delete_ip = sanitize_text_field($_GET['delete_ip']);

			if($_POST)
			{
				$blocked_ips_list = $this->get_all_blocked_ips();
				
				if(isset($block_type) && ($block_type == 'upload'))
				{
					$uploaded_ips_csv = $this->wib_uploader('input_ips_csv_upload_lite');
					if(isset($uploaded_ips_csv) && !empty($uploaded_ips_csv))
					{
						$handle = fopen($uploaded_ips_csv['file_full_path'], "r");
						while (($data = fgetcsv($handle)) !== FALSE)
						{
							if($data[0] != 'IP Address')
							{
								$csv_ip_address = sanitize_text_field($data[0]);
								$csv_added_date = sanitize_text_field($data[1]);

								if(filter_var($csv_ip_address, FILTER_VALIDATE_IP))
									$save_ip_by_csv[] = $this->add_ip_to_db($csv_ip_address, $csv_added_date);
							}
						}
					}
				}
				
				if(!empty($_POST['new-ip-1']))
				{
					$ip_address = sanitize_text_field($_POST['new-ip-1']); 
				}
				else if(!empty($_POST['new-ip-2']))
				{
					$ip_address = sanitize_text_field($_POST['new-ip-2']);
				}

				if(isset($ip_address) && !empty($ip_address) && !filter_var($ip_address, FILTER_VALIDATE_IP))
				{
					$response = '<center><b><font class="error">Incorrect IP Address Provided. Please add a valid IP address to be blocked.</font></b></center>';
				}
				else
				{
					if(isset($save_ip_by_csv) && !empty($save_ip_by_csv))
					{
						$response = '<center><b><font class="success">IPs CSV has been successfully uploaded</font></b></center>';
					}
					else if(isset($ip_address))
					{
						if((!is_array($blocked_ips_list)) || !(in_array($ip_address, $blocked_ips_list)))
						{
							$added = $this->add_ip_to_db($ip_address);
							$response = '<center><b><font class="success">Provided IP Address has been added successfully</font></b></center>'; 
						}
						else
							$response = '<center><b><font class="error">Provided IP Address is already there in the Blocking List</font></b></center>';
					}
					else
					{
						$response = '<center><b><font class="success">Settings has been successfully updated</font></b></center>';
					}

					if(isset($block_type) && ($block_type == 'configuration'))
						$this->save_configuration($_POST);
					else
						$this->get_configuration();
				}
			}
			else
				$this->get_configuration();
			
			if(isset($delete_ip) && !empty($delete_ip))
			{
				$this->delete_ip($delete_ip);
				$response = '<center><b><font class="error">Provided IP Address has been successfully deleted from the Blocking List</font></b></center>';
			}
			$blocked_ips_list = $this->get_all_blocked_ips();
			?>
			
			<div class="wrap">
				<div class="icon-32">
					<br />
				</div>
				<h2><?php echo $this->plg_heading; ?> - Settings</h2>
				<div class="content_left">
					<div id="lionscripts_plg_settings">
						Plugin Version: <b><font class="version"><?php echo $this->plg_version; ?></font> <font class="lite_version">[Lite Version]</font></b>
						&nbsp;|&nbsp;
						<b><a href="<?php echo $this->plg_product_url; ?>" target="_blank" title="Purchase <?php echo $this->plg_name_pro; ?>">Purchase <?php echo $this->plg_name_pro; ?> ? </a></b>
						&nbsp;|&nbsp;
						<b><a href="<?php echo $this->plg_product_url; ?>" target="_blank" title="Visit plugin page to get more info or to buy the <?php echo $this->plg_name_pro; ?>">Visit Plugin Page</a></b>
						&nbsp;|&nbsp;
						<b><a href="<?php echo LIONSCRIPTS_HOME_PAGE_URL.$this->plg_referer; ?>" target="_blank" title="Visit our official website">Official Website</a></b>
						&nbsp;|&nbsp;
						<b><a href="<?php echo LIONSCRIPTS_SUPPORT_PAGE_URL.$this->plg_referer; ?>" target="_blank" title="Get the technical support">Technical Support</a></b>
						<br /><br />
						
						<b>Your current IP Address is </b><span style="color:#F00;font-weight:bold;font-size:18px;"><?php echo LIONSCRIPTS_CURRENT_USER_IP; ?></span> , <b><font title="You will be unable to view your site if you block your own IP Address.">Please do not block your own IP.</font></b>
						
						<br /><br />
						<?php 
						if(isset($response))
						{
							echo $response.'<br />';
						}
						?>
						<form action="admin.php?page=<?php echo $this->site_admin_url_val; ?>&block_type=configuration" method="post">
							<p>
								<input type="checkbox" name="show_blank_page_to_banned_user" id="show_blank_page_to_banned_user" onClick="this.form.submit()" value="1" <?php if($LIONSCRIPTS[$this->plg_identifier]['show_blank_page_to_banned_user'] == 1) { echo('checked="checked"'); } ?> />
								<label for="show_blank_page_to_banned_user"> Display blank page to the Banned User</label>
							</p>
							<br />
							<input type="hidden" name="submit_form" value="submit_form" />
						</form>
		
						<div id="wib_blocking_option">
							<label><input type="radio" name="ip_blocking_type" id="ip_blocking_type_manual" checked /> Block IPs Manually</label>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<label><input type="radio" name="ip_blocking_type" id="ip_blocking_type_upload" /> Upload IP Addresses (CSV Format)</label>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<label><input type="radio" name="ip_blocking_type" id="ip_blocking_type_download" /> Download Blocked IP Addresses</label>
						</div>
		
						<div id="manual_ip_block_wib_lite">
							<form action="admin.php?page=<?php echo $this->site_admin_url_val; ?>" method="post">
								<p>
									Add New IP: 
									<input type="text" pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}$" name="new-ip-1" id="new-ip-1" style="width:37%" value="" />
									<input type="submit" class="button-secondary" value="Add" />
									<!--<input type="button" class="button-secondary" value="Download CSV" />-->
									<input type="submit" class="button-primary right" value="Save Changes" />
								</p>
								<table class="widefat">
									<thead>
										<tr>
											<th style="width: 20px;">#</th>
											<th style="width: 110px;text-align: center;">IP Address</th>
											<th style="width: 110px;text-align: center;">Added on</th>
											<th style="width: 20px;text-align: center;">Delete</th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<th>#</th>
											<th style="text-align: center;">IP Address</th>
											<th style="text-align: center;">Added on</th>
											<th style="text-align: center;">Delete</th>
										</tr>
									</tfoot>
									<tbody>
										<?php 
										if(isset($blocked_ips_list) && !empty($blocked_ips_list))
										{
											$i=1;
											foreach($blocked_ips_list as $key=>$ip_data)
											{
												?>
												<tr id="blocked_ip_<?php echo $ip_data->id; ?>" class="blocked_ips_data">
													<td><?php echo $i; ?></td>
													<td class="tcenter"><?php echo $ip_data->ip; ?></td>
													<td class="tcenter"><?php echo $ip_data->dt_added; ?></td>
													<td class="tcenter"><a title="Delete IP <?php echo $ip_data->ip; ?>" href="admin.php?page=<?php echo $this->site_admin_url_val; ?>&delete_ip=<?php echo $ip_data->id; ?>" onClick="return confirm('Are you sure you want to delete the IP Address <?php echo $ip_data->ip; ?> from the Blocking List?');"><img src="<?php echo $this->plg_images['www']."icon-delete-16.png"; ?>"/></a></td>
												</tr>
												<?php 
												$i++;
											}
										}
										else
										{
										?>
											<tr id="no_saved_data">
												<td>&nbsp;</td>
												<td class="tcenter">No saved data Exists.</td>
												<td>&nbsp;</td>
											</tr>
										<?php
										}
										?>
									</tbody>
								</table>
								<p>
									Add New IP: <input type="text" name="new-ip-2" id="new-ip-2" pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}$" style="width:37%" value="" />
									<input type="submit" class="button-secondary" value="Add" />
									<input type="submit" class="button-primary right" value="Save Changes" />
								</p>
							</form>
						</div>
						
						<div id="upload_ips_wib_lite">
							<br />
							<form id="csv_ip_adder" name="csv_ip_adder" method="post" action="admin.php?page=<?php echo $this->site_admin_url_val; ?>&block_type=upload" method="post" enctype="multipart/form-data">
								<p>
									<input type="file" accept=".csv" name="input_ips_csv_upload_lite" id="input_ips_csv_upload_lite" required />
									&nbsp;&nbsp;
									<input id="submit" name="submit" type="submit" class="button-primary" value="Upload CSV" />
									&nbsp;&nbsp;&nbsp;&nbsp;
									( <a href="<?php echo $this->plg_others['www']; ?>sample-ips-upload-lite-version.csv">Download Sample CSV</a> )
								</p>
								<?php wp_nonce_field( 'lionscripts-upload-ips-csv', 'nonce' ); ?>
							</form>
						</div>
		
						<div id="download_ips_wib_lite">
							<br />
							<p>
								<a href="<?php echo 'admin.php?page='.$this->site_admin_url_val.'&action=download_csv&format=wib_pro'; ?>" class="button-primary">Download Complete Backup (<b>IP Blocker Pro</b> Format)</a>
								&nbsp;&nbsp;&nbsp;&nbsp;
								<a href="<?php echo 'admin.php?page='.$this->site_admin_url_val.'&action=download_csv&format=wib_lite'; ?>" class="button-secondary">Download in Excel/CSV (<b>IP Blocker Lite</b>) Format</a>
							</p>
						</div>
						
						<br />
						<p>
							<a href="<?php echo $this->plg_product_url; ?>" target="_blank"><b>See the difference</b> between <b>Lite</b> and <b>Professional</b> Versions of <b><?php echo $this->plg_name_2; ?></b>.</a>
						</p>
						<div class="lionscripts_plg_footer">
							<p>
								<small>For all kind of Inquiries and Support, please visit at <a href="<?php echo LIONSCRIPTS_SUPPORT_PAGE_URL.'ask'.$this->plg_referer; ?>&utm_medium=ask" target="_blank"><?php echo preg_replace('/\/|http\:/i', '', LIONSCRIPTS_SUPPORT_PAGE_URL); ?>/ask</a>.</small>
							</p>
							<p>
								<ul class="socialicons color">
									<li><a href="<?php echo LIONSCRIPTS_FACEBOOK_LINK; ?>" target="_blank" class="facebook"></a></li>
									<li><a href="<?php echo LIONSCRIPTS_HOME_PAGE_URL; ?>" target="_blank" class="rss"></a></li>
								</ul>
								<div class="cl"></div>
							</p>
						</div>
					</div>
				</div>
				
				<div id="<?php echo str_replace(' ', '_', strtolower($this->plg_name)); ?>_right_container" class="content_right">
					<a href="<?php echo $this->plg_product_url; ?>" target="_blank"><img src="<?php echo $this->plg_images['www']."pro-".$this->plg_ad_ver.".png"; ?>" border="0" /></a>
				</div>
			</div>
			<?php
		}

		public function admin_inl_js()
		{
			$script = "<script type='text/javascript'>";
			$script .= "document.addEventListener('DOMContentLoaded', function(event) {\n";
			$script .= "jQuery('#new-ip-1').focus();jQuery('#download_ips_wib_lite').hide();jQuery('#upload_ips_wib_lite').hide();";
			$script .= "jQuery('#ip_blocking_type_manual').click(function(e) {";
			$script .= "	jQuery('#manual_ip_block_wib_lite').show();jQuery('#upload_ips_wib_lite').hide();jQuery('#download_ips_wib_lite').hide();";
			$script .= "});";
			$script .= "jQuery('#ip_blocking_type_upload').click(function(e) {";
			$script .= "	jQuery('#manual_ip_block_wib_lite').hide();jQuery('#upload_ips_wib_lite').show();jQuery('#download_ips_wib_lite').hide();";
			$script .= "});";
			$script .= "jQuery('#ip_blocking_type_download').click(function(e) {";
			$script .= "	jQuery('#manual_ip_block_wib_lite').hide();jQuery('#upload_ips_wib_lite').hide();jQuery('#download_ips_wib_lite').show();";
			$script .= "});";
			$script .= "});";
			$script .= "</script>";

			echo $script;
		}
			
		public function block_user_ip()
		{
			$blocked_ips_list = $this->get_all_blocked_ips();
			return $blocked_ips_list;
		}
		
		public function check_blocked_ip()
		{
			$blocked_ips_list = $this->get_all_blocked_ips();

			if(isset($blocked_ips_list) && !empty($blocked_ips_list) && is_object($blocked_ips_list[0]))
			{
				$blocked_ips_data_arr = [];
				foreach($blocked_ips_list as $key=>$value)
					$blocked_ips_list_arr[] = $value->ip;
			}

			$user_ip = LIONSCRIPTS_CURRENT_USER_IP;

			if(is_array($blocked_ips_list_arr) && in_array($user_ip, $blocked_ips_list_arr))
				return 1;
			else
				return 0;
		}
		
		public function attr_display($return=false)
		{
			global $LIONSCRIPTS;
			$this->get_configuration();
			
			$display_attr = (($LIONSCRIPTS[$this->plg_identifier]['show_'.strtolower($this->plg_identifier).'_attribution'] == 1) ? $this->plg_attr : '');
			
			if($return == true)
				return $display_attr;
			else
				echo $display_attr;
		}
		
		public function plugin_is_active($plugin_var)
		{
			return in_array( $plugin_var. '/' .$plugin_var. '.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
		}
		
		public function wib_uploader($f_name)
		{
			$verify = wp_verify_nonce($_POST['nonce'], 'lionscripts-upload-ips-csv');

			if(!$verify)
				exit("<br />There was some error! Please retry!");

			$file_name = sanitize_file_name($_FILES[$f_name]["name"]);
			$allowed =  array('csv');

			$ext = pathinfo($file_name, PATHINFO_EXTENSION);

			if(!in_array($ext, $allowed))
			{
				echo "<br />The file uploaded must be in CSV format! Please retry.";
				exit;
			}

			$upload_dir = wp_upload_dir();
			$upload_dir_path = $upload_dir['path'];
			if( isset($_FILES[$f_name]) && ($_FILES[$f_name] != '') && !($_FILES[$f_name]["error"] > 0) )
			{
				move_uploaded_file($_FILES[$f_name]["tmp_name"], $upload_dir_path.'/'.$file_name);
				$uploaded['file_name'] = $file_name;
				$uploaded['file_folder_name'] = $upload_dir_path;
				$uploaded['file_full_path'] = $upload_dir_path."/".$file_name;
				return $uploaded;
			}
			else
				return false;
		}
		
	}
}

