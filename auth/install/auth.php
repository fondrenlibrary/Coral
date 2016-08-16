<?php
function register_auth_provider()
{
	$protected_module_data = [
		"config_file_path" => "auth/admin/configuration.ini"
	];
	$MODULE_VARS = [
		"uid" => "auth",
		"translatable_title" => _("Authentication Module"),
	];
	return array_merge( $MODULE_VARS,[
		"bundle" => function($version) use ($MODULE_VARS, $protected_module_data) {
			switch ($version) {
				case Installer::VERSION_STRING_INSTALL:
					return [
						"dependencies_array" => [ "db_tools", "have_read_write_access_to_config", "have_default_db_user", "have_default_coral_admin_user" ],
						"sharedInfo" => [
							"database" => [
								"title" => _("Auth Database"),
								"default_value" => "coral_auth"
							],
							"config_file" => [
								"path" => $protected_module_data{"config_file_path"},
							]
						],
						"function" => function($shared_module_info) use ($MODULE_VARS, $protected_module_data) {
							$return = new stdClass();
							$return->yield = new stdClass();
							$return->yield->messages = [];
							$return->yield->title = _("Auth Module Installation");
							$return->success = true;

							// Check that the database exists
							// We assume success - if not, it should have been handled in have_database_access
							$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
							$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

							$result = $shared_module_info["provided"]["check_db"]($MODULE_VARS["uid"], $dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "User", $MODULE_VARS["translatable_title"]);
							if ($result)
								return $result;


							// Process sql files
							$sql_files_to_process = ["auth/install/test_create.sql", "auth/install/create_tables_data.sql"];
							$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
							if (!$ret["success"])
							{
								$return->success = false;
								$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
								return $return;
							}


							$ldap_session_var_by_reference = &$_SESSION[$MODULE_VARS["uid"]]["ldap"];

							// Grab post vars
							if (isset($_POST['session_timeout']))
								$_SESSION[$MODULE_VARS["uid"]]["session_timeout"] = $_POST['session_timeout'];
							if (isset($_POST['ldap_enabled']))
								$_SESSION[$MODULE_VARS["uid"]]["ldap"]["ldap_enabled"] = $_POST['ldap_enabled'] == 1 ? "Y" : "N";
							$ldap_post_vars = [
								"host" => "ldap_host",
								"port" => "ldap_port",
								"search_key" => "ldap_search_key",
								"base_dn" => "ldap_base_dn",
								"bindAccount" => "ldap_bind_account",
								"fname" => "ldap_fname_field",
								"lname" => "ldap_lname_field",
								"bindPass" => "ldap_bind_password",
								"bindPassConfirm" => "ldap_confirm_bind_password"
							];
							foreach ($ldap_post_vars as $key => $value) {
								if (isset($_POST[$value]))
								{
									$ldap_session_var_by_reference[$key] = $_POST[$value];
								}
								else
								{
									$ldap_session_var_by_reference[$key] = !empty($ldap_session_var_by_reference[$key]) ? $ldap_session_var_by_reference[$key] : null;
								}
							}

							$ldap_fields = [
								[
									"key" => "ldap_host",
									"type" => "text",
									"title" => _("Host"),
									"default_value" => isset($ldap_session_var_by_reference["host"]) ? $ldap_session_var_by_reference["host"] : ""
								],[
									"key" => "ldap_port",
									"type" => "text",
									"title" => _("Port"),
									"default_value" => isset($ldap_session_var_by_reference["port"]) ? $ldap_session_var_by_reference["port"] : ""
								],

								[
									"key" => "ldap_base_dn",
									"type" => "text",
									"title" => _("Base DN"),
									"default_value" => isset($ldap_session_var_by_reference["base_dn"]) ? $ldap_session_var_by_reference["base_dn"] : ""
								],[
									"key" => "ldap_bind_account",
									"type" => "text",
									"title" => _("Bind Account"),
									"default_value" => isset($ldap_session_var_by_reference["bindAccount"]) ? $ldap_session_var_by_reference["bindAccount"] : ""
								],

								[
									"key" => "ldap_bind_password",
									"type" => "password",
									"title" => _("Bind Password"),
									"default_value" => isset($ldap_session_var_by_reference["bindPass"]) ? $ldap_session_var_by_reference["bindPass"] : ""
								],[
									"key" => "ldap_confirm_bind_password",
									"type" => "password",
									"title" => _("Confirm Bind Password"),
									"default_value" => isset($ldap_session_var_by_reference["bindPassConfirm"]) ? $ldap_session_var_by_reference["bindPassConfirm"] : ""
								],
								// TODO: We don't use fname & lname in the config file but we are providing ldap details for other modules.
								// Because fname and lname are ldap fields they do get dumped out into the config file.
								// Probably should move ldap fname/lname stuff to auth (other modules care too much about this)
								[
									"key" => "ldap_fname_field",
									"type" => "text",
									"title" => _("First Name"),
									"default_value" => isset($ldap_session_var_by_reference["fname"]) ? $ldap_session_var_by_reference["fname"] : ""
								],[
									"key" => "ldap_lname_field",
									"type" => "text",
									"title" => _("Last Name"),
									"default_value" => isset($ldap_session_var_by_reference["lname"]) ? $ldap_session_var_by_reference["lname"] : ""
								],
								// search key at the end to have natural pairs line up better
								[
									"key" => "ldap_search_key",
									"type" => "text",
									"title" => _("Search Key"),
									"default_value" => isset($ldap_session_var_by_reference["search_key"]) ? $ldap_session_var_by_reference["search_key"] : ""
								]
							];
							require_once "install/templates/auth_module_template.php";
							$session_timeout_default = isset($_SESSION[$MODULE_VARS["uid"]]["session_timeout"]) ? $_SESSION[$MODULE_VARS["uid"]]["session_timeout"] : 3600;
							$ldap_enabled_default = isset($ldap_session_var_by_reference["ldap_enabled"]) && $ldap_session_var_by_reference["ldap_enabled"] == "Y";
							$return->yield->body = auth_module_template($session_timeout_default, $ldap_enabled_default, $ldap_fields);
							if (empty($_POST['ldap_enabled']))
							{
								if (empty($ldap_session_var_by_reference["ldap_enabled"]))
								{
									//We set the return body just before entering the if so we can return now
									$return->success = false;
									return $return;
								}
							}
							if ($ldap_session_var_by_reference["ldap_enabled"] == 'Y')
							{
								if (empty($ldap_session_var_by_reference["bindPass"]))
								{
									$return->yield->messages[] = _("Your Bind Password is empty.");
									$return->success = false;
								}
								else if ($ldap_session_var_by_reference["bindPass"] != $ldap_session_var_by_reference["bindPassConfirm"])
								{
									$return->yield->messages[] = _("Your Bind Passwords do not match.");
									$return->success = false;
								}
								if (empty($ldap_session_var_by_reference['host']))
								{
									$return->yield->messages[] = _("LDAP Host is required for LDAP");
									$return->success = false;
								}
								if (empty($ldap_session_var_by_reference['search_key']))
								{
									$return->yield->messages[] = _("LDAP Search Key is required for LDAP");
									$return->success = false;
								}
								if (empty($ldap_session_var_by_reference['base_dn']))
								{
									$return->yield->messages[] = _("LDAP Base DN is required for LDAP");
									$return->success = false;
								}
							}

							if (!$return->success)
								return $return;

							$ldap_enabled = isset($ldap_session_var_by_reference["ldap_enabled"]) && $ldap_session_var_by_reference["ldap_enabled"] == 'Y';
							if (!$ldap_enabled)
							{
								// We need a password for the default admin account
								if (isset($_POST["coral_default_admin_password"]))
									$_SESSION[$MODULE_VARS["uid"]]["coral_default_admin_password"] = $_POST["coral_default_admin_password"];

								$pwd_is_set = isset($_SESSION[$MODULE_VARS["uid"]]["coral_default_admin_password"]);
								$pwd_is_valid = true;
								if ($pwd_is_set)
								{
									$temp_pwd = $_SESSION[$MODULE_VARS["uid"]]["coral_default_admin_password"];
									if (strlen($temp_pwd) < 8)
									{
										$pwd_is_valid = false;
										$return->yield->messages[] = _("Sorry but your password needs to be at least 8 characters long");
									}
								}
								if (!$pwd_is_set || !$pwd_is_valid)
								{
									$field = [
										"uid" => "coral_default_admin_password",
										"title" => _("Default Admin Password"),
										"default_value" => isset($_SESSION[$MODULE_VARS["uid"]]["coral_default_admin_password"]) ? $_SESSION[$MODULE_VARS["uid"]]["coral_default_admin_password"] : ""
									];
									require_once "install/templates/text_field_template.php";
									$instruction = sprintf(_("You have chosen not to use LDAP. In order for the auth module to authenticate your admin user (you chose: '<b>%s</b>') you will need to provide a default password."), $shared_module_info["have_default_coral_admin_user"]["default_user"]);
									$return->yield->body = text_field_template($field, $instruction);
									$return->success = false;
									return $return;
								}
								else
								{
									// TODO: check that we only need to populate auth's db if ldap is disabled!
									if (!(!empty($_SESSION[$MODULE_VARS["uid"]]["default_user_created"]) && $_SESSION[$MODULE_VARS["uid"]]["default_user_created"]))
									{
										$random_string = function($length)
										{
											// The weird maths here is because of bytes to base64 encoding
											// $length + 2 is because the last two characters are predictable in strings where length % 3 != 0
											// (so the predictable characters are pushed out of our range)
											$str_to_ret = base64_encode(openssl_random_pseudo_bytes(3*(($length+2)/4)));
											return substr($str_to_ret, 0, $length);
										};

										$admin_username = $shared_module_info["have_default_coral_admin_user"]["default_user"];
										$random_prefix =$random_string(45);
										$hashed_password = hash('sha512', $random_prefix . $_SESSION[$MODULE_VARS["uid"]]["coral_default_admin_password"]);
										$random_prefix = $dbconnection->escapeString($random_prefix);
										$admin_username = $dbconnection->escapeString($admin_username);
										$createDefaultAdmin = "INSERT INTO `User` VALUES ('$admin_username', '$hashed_password', '$random_prefix', 'Y');";
										// This should be successful because our database check passed (it will throw an error otherwise and we will know about it)
										$result = $dbconnection->processQuery($createDefaultAdmin);
										// An error would be thrown here if the insert were not successful
										$_SESSION[$MODULE_VARS["uid"]]["default_user_created"] = true;
									}
								}
							}

							// Share data for other modules
							$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "ldap_enabled", $ldap_enabled);
							if ($ldap_enabled)
							{
								$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "host", 			$ldap_session_var_by_reference["host"]);
								$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "port", 			$ldap_session_var_by_reference["port"]);
								$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "search_key", 		$ldap_session_var_by_reference["search_key"]);
								$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "base_dn", 		$ldap_session_var_by_reference["base_dn"]);
								$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "bindAccount", 	$ldap_session_var_by_reference["bindAccount"]);
								$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "fname", 			$ldap_session_var_by_reference["fname"]);
								$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "lname", 			$ldap_session_var_by_reference["lname"]);
								$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "bindPass", 		$ldap_session_var_by_reference["bindPass"]);
							}

							// Write the config file
							$configFile = $protected_module_data["config_file_path"];
							$iniData = array();
							$iniData["settings"] = [
								"timeout" => $_SESSION[$MODULE_VARS["uid"]]["session_timeout"]
							];
							$iniData["database"] = [
								"type" => "mysql",
								"host" => Config::dbInfo("host"),
								"name" => $this_db_name,
								"username" => $shared_module_info["have_default_db_user"]["username"],
								"password" => $shared_module_info["have_default_db_user"]["password"]
							];
							$iniData["ldap"] = $ldap_session_var_by_reference;

							$shared_module_info["provided"]["write_config_file"]($configFile, $iniData);

							$return->yield->completionMessages[] = _("Set up your <span class='highlight'>.htaccess</span> file");
							$return->yield->completionMessages[] = _("Remove the <span class='highlight'>/auth/install/</span> directory for security purposes");
							$return->yield->completionMessages[] = _("Set up your users on the <a href='auth/admin.php'>admin screen</a>.  You may log in initially with coral/admin.");

							$return->success = true;
							return $return;
						}
					];

				default:
					return null;
			}
		}
	]);
}
