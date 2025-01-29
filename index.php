    <?php	 

		//~ URL:			https://www.tiny-server.com/
		//~ Email:			Ron de Jong <ronuitzaandam@gmail.com>
		//~ Author:			Tiny Server, Ron de Jong, The Netherlands

		//~ Description:	Password Manager with SQLite DB and 100% Custom Fields
		//~ Dependencies:	A good Web Server such as Apache2 with PHP7.4 or higher
		//~ PHP Extensions:	standard tokenizer Core date openssl hash pcre mbstring

		//~ License:		Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International Public License (CC BY-NC-ND 4.0) 2023

		require("fortune/fortune.php");

        define('DEMO', 0);

		$date = date("Y-m-d_H-i-s");
        $isodate = date("Y-m-d");
		$isotime = date("H-i-s");

		//~ Only relevant when used in combination with Tiny Server
        $tiny_homedir = "/home/tiny/";
        $tiny_scripts = "/home/tiny/Scripts/";
        $tiny_services = "/home/tiny/Services/";
		$tiny_setup_wrapper = "${tiny_services}by-name/apache2/apps/setup/www/tiny-setup";

        $tiny_pass = "tinypass";
        $tiny_pass_db = "${tiny_pass}.db";
        $tiny_pass_export_file = "${date}_${tiny_pass}_export";
        $non_tiny_pass_export_file = "${date}_non_ ${tiny_pass}_export";
        $tiny_pass_path = dirname($_SERVER['SCRIPT_FILENAME']);
		$tiny_pass_db_file = $tiny_pass_path.DIRECTORY_SEPARATOR.$tiny_pass_db;
        $tiny_pass_dsn = "sqlite:" . $tiny_pass_db_file;
		$logfile = "${tiny_pass}_index.php_${date}.log";

        $tiny_apache2 = "/etc/apache2/";

		$tiny_debug = 0;
		
        $supplier_domain = "tiny-server.com";
        $customer_domain = exec("awk -F\".\" 'END { print $(NF-1)\".\"\$NF}' /etc/mailname");
        $web_desktpdomlnk = "https://${supplier_domain}/desktop/";

        $logdata = "";

		//~ Only relevant when used in combination with Tiny Server
        $tiny_license_dir = "/var/tmp/";
        $tiny_license_file = "tiny-license.txt";
        $tiny_license_path = "${tiny_license_dir}${tiny_license_file}";
        $tiny_license_content = exec("cat ${tiny_license_path} 2>/dev/null");
						
		$user_auth_hash_type 	= "sha256"; 			// Stored hash to authenticate user logins -      dataflow: [original users password] -> [sha256] -> [users.pass_hash] 
		$user_encr_hash_type 	= "sha512"; 			// Memory hash to en/de-crypt fields.field_value  dataflow: [original login password] -> [hidden param] -> [sha512] -> [encrypt_data] -> [fields.field_value]
		$user_orig_hash_type 	= "sha256"; 			// Stored hash type ioriginal value in table.field => fields.field_value      to verify decryption

		$user_encr_ciph_type 	= "aes-256-ctr"; 		// Cipher type to en/de-crypt fields.field_value "openssl_get_cipher_methods();" dataflow: [original login password] -> [sha512] -> [encrypt_data] -> [fields.field_value]
		$ivlen					= openssl_cipher_iv_length($user_encr_ciph_type);
		$user_encr_init_vect 	= "1234567890123456";	// En/de-crypt initialization vector NOT BEING USED
		$user_encr_hash_key 	= "1234567890123456";	// En/de-crypt initialization vector NOT BEING USED 
		$user_encr_ciph_opts 	= 0;
		$user_stnd_pass_word 	= "123tinyfree";
		
		$logo_bg = "img/logo_bg.jpg";
		$menu_bg = "img/menu_bg.jpg";
		$login_bg = "img/login_bg.jpg";
		$default_bg = "img/default_bg.jpg";
		        
		//~ REQUEST_SCHEME = 	"https"
		//~ SCRIPT_URI = 		"https://www.tiny-server.com/tiny/pass/index.php")    
        //~ HTTP_HOST = 		"www.tiny-server.com"
        //~ SERVER_NAME = 		"www.tiny-server.com"

        //~ define('TINY_PASS_URI', " . $_SERVER['SCRIPT_URI']. ");
        //~ define('TINY_PASS_URI', $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST']. "/");
        //~ define('LOGOUT_ACTION', "window.location.replace('" . TINY_PASS_URI . "');");

        define('LOGOUT_ACTION', "");
        
        //~ These kinds of history manipulations no longer work
        //~ define('LOGOUT_ACTION', "deleteAllHistory();");
        //~ define('LOGOUT_ACTION', "browser.history.deleteAll();");
        //~ define('LOGOUT_ACTION', "window.location.replace(\"https://developer.mozilla.org/en-US/docs/Web/API/Location.reload\",);");
        //~ define('LOGOUT_ACTION', "window.location.replace('https://developer.mozilla.org/');");
        
        

        //~ define('TINY_PASS_URI', " . $_SERVER['SCRIPT_URI']. ");
        //~ define('TINY_PASS_URI', $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST']. "/");
        //~ define('LOGOUT_ACTION', "browser.history.deleteUrl({ url: " . TINY_PASS_URI . " })");

		//~ foreach ($_SERVER as $key => $value) { echo $key . " = " . $value. "<br>\n"; } exit();


//~ ----------------------------------------------------------------------------
//~ 							CSV Export Constants
//~ ----------------------------------------------------------------------------


		const EXPORT_FILE_TYPE = "text/csv";

		const EXPORT_CSV_FORMATS = 
									[
										"Edge" => 		
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1" 	=> [ "name",		"url",			"username",		"password" 	],						// Read name field
															"field_names2" 	=> [ "Name",		"URL",			"Username",		"Password" 	],						// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password" 	], 						// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ -1,			0,				1,				2 			],						// -1, nr (-1 = ignore field)
														],
										"Edge_2" => 		
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1" 	=> [ "name",		"url",			"username",		"password" 	],						// Read name field
															"field_names2" 	=> [ "name",		"url",			"username",		"password" 	],						// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password" 	], 						// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3 			], 						// -1, nr (-1 = ignore field)
														],
										"1Password" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1" 	=> [ "title",		"url",			"username",		"password" 	],						// Read name field
															"field_names2" 	=> [ "Title",		"URL",			"Username",		"Password" 	],						// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password" 	], 						// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ -1,			0,				1,				2 			], 						// -1, nr (-1 = ignore field)
														],
										"1Password_2" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1" 	=> [ "title",		"url",			"username",		"password" 	],						// Read name field
															"field_names2" 	=> [ "title",		"url",			"username",		"password" 	],						// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password" 	], 						// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3 			], 						// -1, nr (-1 = ignore field)
														],
										"Chrome" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1"	=> [ "name",		"url",			"username",		"password",		"note"		],		// Read name field
															"field_names2"	=> [ "Name",		"URL",			"Username",		"Password",		"Note"		],		// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password",		"textarea" 	], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ -1,			0,				1,				2,				3 			], 		// -1, nr (-1 = ignore field)
														],
										"Chrome_2" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1"	=> [ "name",		"url",			"username",		"password",		"note"		],		// Read name field
															"field_names2"	=> [ "name",		"url",			"username",		"password",		"note"		],		// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password",		"textarea" 	], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3,				4 			], 		// -1, nr (-1 = ignore field)
														],
										"KeePass" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1" 	=> [ "Account",		"Login Name",	"Password",		"Web Site",		"Comments" 	],		// Read name field
															"field_names2" 	=> [ "Account",		"Login",		"Password",		"URL",			"Comments" 	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"password",		"url",			"textarea" 	], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ -1,			1,				2,				0,				3 			], 		// -1, nr (-1 = ignore field)
														],
										"KeePass_2" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1" 	=> [ "Account",		"Login Name",	"Password",		"Web Site",		"Comments" 	],		// Read name field
															"field_names2" 	=> [ "Account",		"Login Name",	"Password",		"Web Site",		"Comments" 	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"password",		"url",			"textarea" 	], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3,				4 			], 		// -1, nr (-1 = ignore field)
														],
										"TinyPass" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> 1,
															"field_names1"  => [ "SecretName",	"GroupName",	"FieldOrder",	"FieldName",	"FieldType",		"FieldValue"	],		// Read name field
															"field_names2"  => [ "SecretName",	"GroupName",	"FieldOrder",	"FieldName",	"FieldType",		"FieldValue"	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"integer",		"text",			"text",				"text"			], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,			1,					2, 				3,				4,					5				], 		// -1, nr (-1 = ignore field)
														],
										"Dashlane" => 	
														[
															"secret_name" 	=> 1,
															"secret_group" 	=> -1,
															"field_names1"  => [ "Type",		"name",			"url",			"username",		"password",			"note",			"totp"	],		// Read name field
															"field_names2"  => [ "Type",		"Name",			"URL",			"Username",		"Password",			"Note",			"totp"	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"url",			"username",		"password",			"note",			"totp"	], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				-1,				1, 				2,				3,					4,				-1		], 		// -1, nr (-1 = ignore field)
														],
										"Dashlane_2" => 	
														[
															"secret_name" 	=> 1,
															"secret_group" 	=> -1,
															"field_names1"  => [ "Type",		"name",			"url",			"username",		"password",			"note",			"totp"	],		// Read name field
															"field_names2"  => [ "Type",		"Name",			"URL",			"Username",		"Password",			"Note",			"totp"	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"url",			"username",		"password",			"note",			"totp"	], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2, 				3,				4,					5,				6		], 		// -1, nr (-1 = ignore field)
														],
										"1Password2" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1" 	=> [ "Title",		"Url",			"Username",		"Password",		"OTPAuth",		"Favorite",		"Archived",		"Tags",		"Notes" 	],						// Read name field
															"field_names2" 	=> [ "Title",		"URL",			"Username",		"Password",		"OTPAuth",		"Favorite",		"Archived",		"Tags",		"Notes"  	],						// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password",		"text",			"text",			"text",			"text",		"textarea"	], 						// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ -1,			0,				1,				2,				-1,				-1,				-1,				1,			3 			], 						// -1, nr (-1 = ignore field)
														],
										"1Password2_2" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1" 	=> [ "Title",		"Url",			"Username",		"Password",		"OTPAuth",		"Favorite",		"Archived",		"Tags",		"Notes" 	],						// Read name field
															"field_names2" 	=> [ "Title",		"URL",			"Username",		"Password",		"OTPAuth",		"Favorite",		"Archived",		"Tags",		"Notes"  	],						// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password",		"text",			"text",			"text",			"text",		"textarea"	], 						// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3,				4,				5,				6,				7,			8 			], 						// -1, nr (-1 = ignore field)
														],
										"Firefox" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1"	=> [ "url",			"username",		"password",		"httpRealm",	"formActionOrigin",	"guid",				"timeCreated",	"timeLastUsed",		"timePasswordChanged"	],		// Read name field
															"field_names2"	=> [ "URL",			"Username",		"Password",		"httpRealm",	"formActionOrigin",	"guid",				"timeCreated",	"timeLastUsed",		"timePasswordChanged"	],		// Write name field
															"field_types" 	=> [ "url",			"text",			"password",		"url",			"url",				"text",				"text",			"text",				"text"					], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				-1,				-1,					-1,					-1,				-1,					-1						], 		// -1, nr (-1 = ignore field)
														],
										"Firefox_2" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> -1,
															"field_names1"	=> [ "url",			"username",		"password",		"httpRealm",	"formActionOrigin",	"guid",				"timeCreated",	"timeLastUsed",		"timePasswordChanged"	],		// Read name field
															"field_names2"	=> [ "URL",			"Username",		"Password",		"httpRealm",	"formActionOrigin",	"guid",				"timeCreated",	"timeLastUsed",		"timePasswordChanged"	],		// Write name field
															"field_types" 	=> [ "url",			"text",			"password",		"url",			"url",				"text",				"text",			"text",				"text"					], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3,				4,					5,					6,				7,					8						], 		// -1, nr (-1 = ignore field)
														],
										"Bitwarden" => 	
														[
															"secret_name" 	=> 3,
															"secret_group" 	=> 0,
															"field_names1"	=> [ "folder",		"favorite",		"type",			"name",			"notes",			"fields",			"reprompt",		"login_uri",		"login_username",		"login_password",			"login_totp"	],		// Read name field
															"field_names2"	=> [ "folder",		"favorite",		"type",			"Name",			"Notes",			"fields",			"reprompt",		"URI",				"Username",				"Password",					"login_totp"	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"text",			"text",			"textarea",			"text",				"text",			"url",				"text",					"password",					"text"			], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ -1,			-1,				-1,				-1,				4,					-1,					-1,				1,					2,						3,							-1,				], 		// -1, nr (-1 = ignore field)
														],
										"Bitwarden_2" => 	
														[
															"secret_name" 	=> 3,
															"secret_group" 	=> 0,
															"field_names1"	=> [ "folder",		"favorite",		"type",			"name",			"notes",			"fields",			"reprompt",		"login_uri",		"login_username",		"login_password",			"login_totp"	],		// Read name field
															"field_names2"	=> [ "folder",		"favorite",		"type",			"name",			"notes",			"fields",			"reprompt",		"login_uri",		"login_username",		"login_password",			"login_totp"	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"text",			"text",			"textarea",			"text",				"text",			"url",				"text",					"login_password",			"text"			], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3,				4,					5,					6,				7,					8,						9,							10,				], 		// -1, nr (-1 = ignore field)
														],
										"TeamPass" => 	
														[
															"secret_name" 	=> 1,
															"secret_group" 	=> 11,
															"field_names1"  => [ "id",			"label",		"description",	"pw",			"login",			"restricted_to",	"perso",		"url",				"email",				"kb",						"tag",			"folder"	],		// Read name field
															"field_names2"  => [ "Id",			"Label",		"Note",			"Pass",			"Login",			"restricted_to",	"perso",		"URL",				"Email",				"kb",						"Tag",			"Folder"	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"textarea",		"password",		"text",				"text",				"text",			"url",				"email",				"text",						"text",			"text"		], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ -1,				-1,			4,				2,				1,					-1,					-1,				0,					3,						-1,							-1,				-1			], 		// -1, nr (-1 = ignore field)
														],
										"TeamPass_2" => 	
														[
															"secret_name" 	=> 1,
															"secret_group" 	=> 11,
															"field_names1"  => [ "id",			"label",		"description",	"pw",			"login",			"restricted_to",	"perso",		"url",				"email",				"kb",						"tag",			"folder"	],		// Read name field
															"field_names2"  => [ "id",			"label",		"description",	"pw",			"login",			"restricted_to",	"perso",		"url",				"email",				"kb",						"tag",			"folder"	],		// Write name field
															"field_types" 	=> [ "text",		"text",			"textarea",		"password",		"text",				"text",				"text",			"url",				"email",				"text",						"text",			"text"		], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3,				4,					5,					6,				7,					8,						9,							10,				11			], 		// -1, nr (-1 = ignore field)
														],
										"NordPass" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> 10,
															"field_names1"	=> [ "name",		"url",			"username",		"password",		"note",				"cardholdername",	"cardnumber",	"cvc",				"expirydate",			"zipcode",	"folder",		"full_name",	"phone_number",	"email",	"address1",	"address2",	"city",	"country",	"state"	],		// Read name field
															"field_names2"	=> [ "Name",		"URL",			"Username",		"Password",		"Note",				"CardHoldeName",	"CardNumber",	"CVC",				"ExpiryDate",			"Zipcode",	"Folder",		"Full_name",	"Phone_number",	"Email",	"Address1",	"Address2",	"City",	"Country",	"State"	],		// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password",		"textarea",			"text",				"text",			"text",				"text",					"text",		"text",			"text",			"text",			"email",	"text",		"text",		"text",	"text",		"text"	], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ -1,			0,				1,				2,				3,					-1,					-1,				-1,					-1,						-1,			-1,				-1,				-1,				-1,			-1,			-1,			-1,		-1,			-1		], 		// -1, nr (-1 = ignore field)
														],
										"NordPass_2" => 	
														[
															"secret_name" 	=> 0,
															"secret_group" 	=> 10,
															"field_names1"	=> [ "name",		"url",			"username",		"password",		"note",				"cardholdername",	"cardnumber",	"cvc",				"expirydate",			"zipcode",	"folder",		"full_name",	"phone_number",	"email",	"address1",	"address2",	"city",	"country",	"state"	],		// Read name field
															"field_names2"	=> [ "name",		"url",			"username",		"password",		"note",				"cardholdername",	"cardnumber",	"cvc",				"expirydate",			"zipcode",	"folder",		"full_name",	"phone_number",	"email",	"address1",	"address2",	"city",	"country",	"state"	],		// Write name field
															"field_types" 	=> [ "text",		"url",			"text",			"password",		"textarea",			"text",				"text",			"text",				"text",					"text",		"text",			"text",			"text",			"email",	"text",		"text",		"text",	"text",		"text"	], 		// URL, Email, Pass, Text, Textarea
															"field_orders" 	=> [ 0,				1,				2,				3,				4,					5,					6,				7,					8,						9,			10,				11,				12,				13,			14,			15,			16,		17,			18		], 		// -1, nr (-1 = ignore field)
														],
									];

		
		$cvs_keys = array_keys(EXPORT_CSV_FORMATS);
		$cvs_field_lengths = array(); foreach (array_keys(EXPORT_CSV_FORMATS) as $key => $value) { $cvs_field_lengths[] = count(array_values(EXPORT_CSV_FORMATS[$value]["field_names1"])); }
		$cvs_key_field_lengths = array_combine($cvs_keys, $cvs_field_lengths);
		$csv_fields_desc = ""; foreach ($cvs_key_field_lengths as $key => $value) { $csv_fields_desc .= $key . "[" . $value . "] "; }
		$csv_formats_desc = ""; foreach ($cvs_key_field_lengths as $key => $value) { $csv_formats_desc .= "[". $key . "]&nbsp;"; } $csv_formats_desc .= "CSV file\n\n[Format]: field1,field2,field3,field4...";
		$csv_format_input_fields = ""; foreach ($cvs_keys as $key) { $csv_format_input_fields .= "[" . $key . "]:&nbsp;"; foreach (array_values(EXPORT_CSV_FORMATS[$key]["field_names1"]) as $field) { $csv_format_input_fields .= $field . ","; } $csv_format_input_fields .= "\n"; }
		//~ $csv_format_input_fields = ""; foreach ($cvs_keys as $key) { $csv_format_input_fields .= "[" . $key . "]:&nbsp;"; for ($row = 0; $row < count(array_values(EXPORT_CSV_FORMATS[$key]["field_names1"])); $row++) { if ( EXPORT_CSV_FORMATS[$key]["field_orders"][$row] > -1 ) { $csv_format_input_fields .= "<b>". EXPORT_CSV_FORMATS[$key]["field_names2"][$row] . "</b>, "; } else { $csv_format_input_fields .= "". EXPORT_CSV_FORMATS[$key]["field_names2"][$row] . ", "; } } $csv_format_input_fields .= "\n"; }
		//~ $csv_format_input_fields = ""; foreach ($cvs_keys as $key) { $csv_format_input_fields .= "[" . $key . "]:&nbsp;"; for ($row = 0; $row < count(array_values(EXPORT_CSV_FORMATS[$key]["field_names1"])); $row++) { if ( EXPORT_CSV_FORMATS[$key]["field_orders"][$row] > -1 ) { $csv_format_input_fields .= "<" . EXPORT_CSV_FORMATS[$key]["field_names2"][$row] . ">, "; } else { $csv_format_input_fields .= "[". EXPORT_CSV_FORMATS[$key]["field_names2"][$row] . "], "; } } $csv_format_input_fields .= "\n"; }
				
		define('EXPORT_CSV_KEYS', $cvs_keys);
				
		define('EXPORT_CSV_FORMATS_FIELD_LENGTHS', $cvs_key_field_lengths);
		define('EXPORT_CSV_FORMATS_FIELD_DESC', $csv_fields_desc);
		// \nImporting from a Tiny Pass formatted CSV file all fields are imported\n\n
		// \nImporting from a Tiny Pass formatted CSV file all fields are imported\n\nImport from\n\n" . $csv_formats_desc . "\n\n" . 
		define('IMPORT_CSV_FORMATS_LIMITED_DESC',  		"Import *ALL* fields from Tiny Pass CSV file\nImport SOME fields from NON Tiny Pass CSV file\n\nChoose this option when you don't want all these useless NON Tiny Pass Format specific fields and you're not interested in exporting back to the original NON Tiny Pass format\n\nSupporting the following field formats:\n\n" . $csv_format_input_fields );
		define('IMPORT_CSV_FORMATS_ORIGINAL_DESC', 		"Import *ALL* fields from NON Tiny Pass CSV file\nImport *ALL* fields from Tiny Pass CSV file\n\nChoose this option when you want to keep all the NON Tiny Pass specific fields because later you want to export back to the original NON Tiny Pass Format\n\nSupporting the following field formats:\n\n" . $csv_format_input_fields );
		
		define('EXPORT_CSV_FORMATS_TINYPASS_DESC',  	"Export selected secrets to Tiny Pass formatted CSV file\nUse this export if it needs to be imported by Tiny Pass" );
		define('EXPORT_CSV_FORMATS_NON_TINYPASS_DESC', 	"Export selected secrets to NON Tiny Pass formatted CSV file\nThis export works when [ Import *ALL* fields from NON Tiny Pass CSV file ] was used\n\nUse this export if it needs to be imported by the original Password Manager again" );
		
		$cvs_keys = null; $cvs_field_lengths = null; $cvs_key_field_lengths = null; $csv_desc = null; $csv_format_input_fields = null;

		//~ echo "Keys:\n\n"; 				print_r(array_keys(EXPORT_CSV_FORMATS)); 								echo "\n<br>\n<br>";
		//~ foreach (array_keys(EXPORT_CSV_FORMATS) as $format)
		//~ {
			//~ echo "Name, Group:\n\n"; 		echo EXPORT_CSV_FORMATS[$format]["secret_name"] . "," . EXPORT_CSV_FORMATS[$format]["secret_group"]; 		echo "\n<br>";
			//~ echo "Values: "; 			print_r(array_values(EXPORT_CSV_FORMATS[$format]["field_names1"])); 		echo "\n<br>";
			//~ echo "Values: "; 			print_r(array_values(EXPORT_CSV_FORMATS[$format]["field_types"])); 		echo "\n<br>";
			//~ echo "Values: "; 			print_r(array_values(EXPORT_CSV_FORMATS[$format]["field_orders"])); 	echo "\n<br>\n<br>";
		//~ }

		//~ echo "Name, Group:\n\n"; 		echo EXPORT_CSV_FORMATS["NordPass"]["secret_name"] . "," . EXPORT_CSV_FORMATS["NordPass"]["secret_group"]; 		echo "\n<br>";
		//~ echo "FieldType[1]: "; 			echo EXPORT_CSV_FORMATS["NordPass"]["field_types"][1]; 		echo "\n<br>";
		//~ echo "Values TinyPass: "; 	print_r(array_values(EXPORT_CSV_FORMATS["TinyPass"]["field_names1"])); 					echo "\n<br>";
		//~ echo "Lengths  TinyPass: ";	print_r(EXPORT_CSV_FORMATS_FIELD_LENGTHS["TinyPass"]); 					echo "\n<br>";
		//~ echo "Lengths: ";			print_r(array_values(EXPORT_CSV_FORMATS_FIELD_LENGTHS)); 				echo "\n<br>";
		//~ echo "Desc: " . 			EXPORT_CSV_FORMATS_FIELD_DESC . "\n\n";
		
		//~ exit;

		if ( isset($_SERVER['HTTP_HOST']) ) { define('TINY_PASS_HOST', $_SERVER['HTTP_HOST']); } else { define('TINY_PASS_HOST', ""); }
		
        //~ $local_setup_version = exec("cat ${tiny_services}by-name/apache2/apps/$tiny_pass/version.txt;");

		define('TINY_PASS_VERSION', 1);
		define('TINY_PASS_UPGRADE', 0);
		define('TINY_PASS_UPDATE', 0);
		define('TINY_PASS_VERSION_DESC', TINY_PASS_VERSION . "." . TINY_PASS_UPGRADE . "." . TINY_PASS_UPDATE);

//~ ============================================================================

		function encrypt_password($pass, $data) // data(orig) -> cipher -> base64
		{
			$key 		= hash($GLOBALS["user_encr_hash_type"], $pass);
			$iv			= substr($key,0,$GLOBALS["ivlen"]);			
			$cipher 	= openssl_encrypt($data, $GLOBALS["user_encr_ciph_type"], $key, $GLOBALS["user_encr_ciph_opts"], $iv);
			$cipher_b64 = base64_encode($cipher);
			
			return $cipher_b64;
		}

		function decrypt_password($pass, $data) // data(base64) -> cipher -> data(orig)
		{
			$key 		= hash($GLOBALS["user_encr_hash_type"], $pass);			
			$iv			= substr($key,0,$GLOBALS["ivlen"]);			
			$cipher 	= base64_decode($data);
			$orig 		= openssl_decrypt($cipher, $GLOBALS["user_encr_ciph_type"], $key, $GLOBALS["user_encr_ciph_opts"], $iv);
			return $orig;
		}


		//~ ====================================================================
		//~ 					Internal Database Functions
		//~ ====================================================================




		function createTables($pdo, $form, $button, $target)
		{
			$pdo->exec('
CREATE TABLE IF NOT EXISTS roles
(
	role_id INTEGER PRIMARY KEY NOT NULL,
	role_name VARCHAR (255) NOT NULL
)
					');

			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "exec(..)", $button, $target);

//~ Add initial records ----

			$stmt = $pdo->prepare('SELECT * FROM roles WHERE role_name like :role1 OR role_name like :role2;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare1(..)", $button, $target);
			$stmt->execute([':role1' => "Admin", ':role2' => "User"]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute1(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			
			if ( count($object_array) == 0)
			{
				//~ New Role
				$pdo->exec('INSERT INTO roles(role_name) VALUES("Admin"),("User");');
				$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "exec(..)", $button, $target);
			}

//~ ----------------------------------------------------------------------------

			$pdo->exec('
CREATE TABLE IF NOT EXISTS groups
(
	group_id INTEGER PRIMARY KEY NOT NULL,
	user_id INTEGER NOT NULL,
	group_name VARCHAR (255) NOT NULL,
	FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE
);
					');
								
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "exec(..)", $button, $target);

//~ ----------------------------------------------------------------------------

			$pdo->exec('
CREATE TABLE IF NOT EXISTS users
(
	user_id INTEGER PRIMARY KEY NOT NULL,
	role_id INTEGER NOT NULL,
	user_name VARCHAR (255) NOT NULL,
	hash_type VARCHAR (255) NOT NULL,
	pass_hash VARCHAR (255) NOT NULL,
	FOREIGN KEY (role_id) REFERENCES roles (role_id) ON UPDATE CASCADE ON DELETE RESTRICT
);
					');
								
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "exec(..)", $button, $target);

//~ ----------------------------------------------------------------------------

			$pdo->exec('
CREATE TABLE IF NOT EXISTS secrets
(
	secret_id INTEGER PRIMARY KEY NOT NULL,
	user_id INTEGER NOT NULL,
	group_id INTEGER,
	secret_date INTEGER NOT NULL,
	secret_name VARCHAR (255) NOT NULL,
	FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (group_id) REFERENCES groups (group_id) ON UPDATE CASCADE ON DELETE SET NULL
);
					');
								
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "exec(..)", $button, $target);

//~ -----------------------------------------------------------

			$pdo->exec('
CREATE TABLE IF NOT EXISTS fields
(
	field_id INTEGER PRIMARY KEY NOT NULL,
	user_id INTEGER NOT NULL,
	secret_id INTEGER NOT NULL,
	field_ordr INTEGER NOT NULL,
	field_name VARCHAR (255) NOT NULL,
	field_type INTEGER (255) NOT NULL,
	field_value VARCHAR (255),
	field_hash VARCHAR (255),
	FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE
	FOREIGN KEY (secret_id) REFERENCES secrets (secret_id) ON UPDATE CASCADE ON DELETE CASCADE
);
					');
								
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "exec(..)", $button, $target);
			
		}


		function createUsers($pdo, $form, $button, $target)
		{
			$user_name = "admin";	$qty = 10;	$pass_word = $GLOBALS["user_stnd_pass_word"];	if ( ! userExists($pdo, $user_name, $form, $button, $target) )	{ insertUser($pdo, 1, $user_name,	$pass_word, $form, $button, $target); create_test_secrets($user_name, $pass_word, $qty); }
			$user_name = "tiny";	$qty = 11;	$pass_word = $GLOBALS["user_stnd_pass_word"]; 	if ( ! userExists($pdo, $user_name, $form, $button, $target) ) 	{ insertUser($pdo, 2, $user_name, 	$pass_word,	$form, $button, $target); create_test_secrets($user_name, $pass_word, $qty); }
		}

		//~ ====================================================================
		//~ 					External Database Functions
		//~ ====================================================================



		function connectDB($form, $button, $target)
		{
			$pdo = new PDO($GLOBALS["tiny_pass_dsn"]);
			$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			
			//~ $tables = getTables($pdo,$form,$button,$target);			
			//~ if ( count($tables) == 0) {	createTables($pdo, $form, $button, $target); }
			
			createTables($pdo, $form, $button, $target);
			createUsers($pdo, $form, $button, $target);
			
			return $pdo;
		}


		function getTables($pdo, $form, $button, $target)
		{
			$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name;");
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "exec(..)", $button, $target);
			$tables = []; while ($record = $stmt->fetch(\PDO::FETCH_ASSOC)) { $tables[] = $record['name']; }
			return $tables;
		}


		$type2num_arr = array("url" => 0, "email" => 1, "password" => 2, "text" => 3, "textarea" => 4);
		$num2type_arr = array(0 => "url", 1 => "email", 2 => "password", 3 => "text", 4 => "textarea");

		function getType2Num($type)	{ return $type2num_arr[type]; }
		function getNum2Type($num)	{ return $tnum2ype_arr[num]; }

//~ ----------------------------------------------------------------------------		

        function web_log()
        {
            global $logdata;
            $loglines = preg_split("/(\r\n|\n|\r)/",$logdata);
            foreach ($loglines as &$logline) { error_log($logline, 0); }
        }

        function buf_log($data)
        {
            echo $data;
            global $logdata;
            if ( strlen($logdata) < 300 )
            {
                $logdata .= $data;
            }
            else
            {
                $logdata .= $data;
                web_log();
                $logdata = "";
            }
        }

		function message($message, $form, $instruction, $button, $target)
		{
			echo "<div class=\"eyesloaded\"></div>";

			echo "<p style=\"position: fixed; top: 10%; left: 85%; transform: translateX(-50%); text-shadow: 0px 0px 5px rgba(30,30,5,0.8); font-size: x-large; white-space: nowrap;\">T E R M I N A L</p>";
			echo "<div style=\"position: fixed; top: 85%; left: 85%; transform: translateX(-50%); z-index: 100;\"><table style=\"width:100%; text-align: center; border: 0px solid;\"><tr><td><form class=\"info\" action=\"$target\" method=\"post\" autocomplete=\"off\"><button class=\"bigbutton\" style=\"font-size: large; white-space: nowrap;\" type=\"submit\">$button</button></form></td></tr></table></div>";

			echo "<div style=\"width: 100vw; position: stycky; top: 25%; margin-left: 50%; transform: translateX(-50%); z-index: 0; overflow-y: auto;\" >";
			echo "$message";
			//~ echo "<p style=\"text-align:center;\">Form [ $form ]</p>";

			echo "<script type='text/javascript'> document.getElementById(\"loader\").style.visibility = \"visible\"; document.getElementById(\"loader\").style.opacity = \"1.0\"; var setup_img_el = document.getElementById(\"loader\"); setup_img_el.remove(); </script>";
		}

		function messageRaw($message)
		{
			print "$message";
		}

		function dboHeader($form, $instruction, $button, $target)
		{
			if( $errorArray[0] != "00000" || strlen($errorArray[1]) > 0 || strlen($errorArray[2]) > 0)
			{
				$tp_login_uname = htmlspecialchars($_POST['tp_login_uname'], ENT_QUOTES, 'UTF-8');
				$tp_login_pword = htmlspecialchars($_POST['tp_login_pword'], ENT_QUOTES, 'UTF-8');

				echo "<div style=\"position: fixed; left: 84%; top: 50%;\" class=\"diceloaded\"></div>";

				echo "<p style=\"position: fixed; top: 10%; left: 85%; transform: translateX(-50%); text-shadow: 0px 0px 5px rgba(30,30,5,0.8); font-size: x-large; white-space: nowrap;\">DB Error</p>";
				echo "<div style=\"position: fixed; top: 85%; left: 85%; transform: translateX(-50%); z-index: 100;\"><table style=\"width:100%; text-align: center; border: 0px solid;\"><tr><td>";
				
				echo "<form class=\"info\" action=\"$target\" method=\"post\" autocomplete=\"off\">";
				echo "<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
				echo "<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
				echo "<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";
				//~ echo "<button class=\"bigbutton\" style=\"font-size: large; white-space: nowrap;\" type=\"submit\">$button</button>";
				echo "</form>";
				
				echo "</td></tr></table></div>";
				echo "<div style=\"width: 100vw; position: stycky; top: 25%; margin-left: 50%; transform: translateX(-50%); z-index: 0; overflow-y: auto;\" ><p style=\"text-align:center;\">Form [ $form ]</p><p><br /></p><p style=\"text-align:center;\"><span style=\"color: red;\">Error Instruction:</span> $instruction</p>";
			}
		}

		function dboError($errorArray, $header, $form, $instruction, $button, $target)
		{
			if( $errorArray[0] != "00000" || strlen($errorArray[1]) > 0 || strlen($errorArray[2]) > 0)
			{
				if ( $header == "header" ) { send_html_header(""); dboHeader($form, $instruction, $button, $target); }
				echo "<table style=\"text-align: center; font-size: large; width:100%\" class=\"t_able _table-bordered\">";
				echo "	<tbody>";

				$errorCounter = 0;

				$errorField = array("SQLState","Error code","Error mess");
				foreach ($errorArray as $error)
				{
					echo "<tr><td style=\"text-align:center; color: red;\">$errorField[$errorCounter]</td><td style=\"text-align:center; color: white;\"><a style=\"_color: white\"class=\"_border\" href=\"https://en.wikipedia.org/wiki/SQLSTATE\" target=\"_blank\">$error</a></td></tr>";
					$errorCounter++;
				}

				echo "	</tbody>";
				echo "</table>";
				exit;
			}
		}



		//~ ====================================================================
		//~ 						Database Functions
		//~ ====================================================================



		function printHeader($message, $form, $button, $target)
		{
			send_html_header("");
			echo "<div class=\"eyesloaded\"></div>";

			echo "<p style=\"position: fixed; top: 10%; left: 85%; transform: translateX(-50%); text-shadow: 0px 0px 5px rgba(30,30,5,0.8); font-size: x-large; white-space: nowrap;\">T E R M I N A L</p>";
			echo "<div style=\"position: fixed; top: 85%; left: 85%; transform: translateX(-50%); z-index: 100;\"><table style=\"width:100%; text-align: center; border: 0px solid;\"><tr><td><form class=\"info\" action=\"$target\" method=\"post\" autocomplete=\"off\"><button class=\"bigbutton\" style=\"font-size: large; white-space: nowrap;\" type=\"submit\">$button</button></form></td></tr></table></div>";

			echo "<div style=\"width: 100vw; position: stycky; top: 25%; margin-left: 50%; transform: translateX(-50%); z-index: 0;\" >";
			echo "<h1 style=\"text-align:center;\">$message</h1>";
			echo "<p style=\"text-align:center;\">Form [ $form ]</p>";
			//~ echo "<p style=\"text-align:center;\">TinyPass</p>";

			echo "<script type='text/javascript'> document.getElementById(\"loader\").style.visibility = \"visible\"; document.getElementById(\"loader\").style.opacity = \"1.0\"; var setup_img_el = document.getElementById(\"loader\"); setup_img_el.remove(); </script>";
		}

		function printFooter()
		{
			echo "<script type='text/javascript'> document.getElementById(\"loader\").style.visibility = \"visible\"; document.getElementById(\"loader\").style.opacity = \"1.0\"; var setup_img_el = document.getElementById(\"loader\"); setup_img_el.remove(); </script>";
		}


//~ ============================================================================

		function authenticateUser($user_name, $pass_word, $form, $button, $target)
		{
			$pdo = connectDB($form, $button, $target);
			//~ echo "\$pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS): [" . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "]\n"; // not supported attribute

			$pass_hash = hash($GLOBALS["user_auth_hash_type"], "$pass_word");
			
			$stmt = $pdo->prepare('SELECT * FROM users WHERE user_name = :user_name AND pass_hash = :pass_hash;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare1(..)", $button, $target);
			$stmt->execute([':user_name' => $user_name, ':pass_hash' => $pass_hash]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute1(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }			

			//~ echo "\$GLOBALS[\"user_auth_hash_type\"]: ". 		$GLOBALS["user_auth_hash_type"] . "\n";
			//~ echo "Password: " . 								$pass_word . "\n";
			//~ echo "Login-PassHash: " . 							$pass_hash . "\n";
			//~ echo "DB----PassHash: ". 							$object_array[0]->pass_hash . "\n";			
			
			if ( count($object_array) > 0) 	{ return true; }
			else 							{ return false; }
		}

		function userExists($pdo, $user_name, $form, $button, $target)
		{
			$stmt = $pdo->prepare('SELECT * FROM users WHERE user_name = :user_name;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare1(..)", $button, $target);
			$stmt->execute([':user_name' => $user_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute1(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			
			if ( count($object_array) > 0) { return true; } else { return false; }
		}

		function insertUser($pdo, $role_id, $user_name, $pass_word, $form, $button, $target)
		{
			if ( userExists($pdo, $user_name, $form, $button, $target) )	{ dboError(array("-","-","User: \"$user_name\" exists!"), "header", $form, "insertUser(\"$user_name\")", $button, $target); }

			$hash_type = $GLOBALS["user_auth_hash_type"];
			$pass_hash = hash($GLOBALS["user_auth_hash_type"], $pass_word);
			
			//~ New User

			$stmt = $pdo->prepare('INSERT INTO users(role_id, user_name, hash_type, pass_hash) VALUES(:role_id,:user_name,:hash_type,:pass_hash);');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare2(..)", $button, $target);
			$stmt->execute([':role_id' => $role_id, ':user_name' => $user_name, ':hash_type' => $hash_type, ':pass_hash' => $pass_hash]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute2(..)", $button, $target);

			return $pdo->lastInsertId();
		}

		function updateUser($pdo, $role_id, $user_id, $user_name, $form, $button, $target)
		{			
			//~ Update User

			//~ $stmt = $pdo->prepare('UPDATE secrets SET group_id = :group_id, secret_date = :secret_date, secret_name = :secret_name WHERE secret_id = :secret_id;');

			$stmt = $pdo->prepare('
UPDATE users SET role_id = :role_id, user_name = :user_name
WHERE user_id = :user_id;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare2(..)", $button, $target);
			$result = $stmt->execute([':role_id' => $role_id, ':user_id' => $user_id, ':user_name' => $user_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute2(..)", $button, $target);

			return $result;
		}

		function updateUserCredentials($pdo, $role_id, $user_id, $user_name, $pass_word, $form, $button, $target)
		{
			$hash_type = $GLOBALS["user_auth_hash_type"];
			$pass_hash = hash($GLOBALS["user_auth_hash_type"], $pass_word);
			
			//~ Update User

			//~ $stmt = $pdo->prepare('UPDATE secrets SET group_id = :group_id, secret_date = :secret_date, secret_name = :secret_name WHERE secret_id = :secret_id;');

			$stmt = $pdo->prepare('
UPDATE users SET role_id = :role_id, user_name = :user_name, hash_type = :hash_type, pass_hash = :pass_hash
WHERE user_id = :user_id;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare2(..)", $button, $target);
			$result = $stmt->execute([':role_id' => $role_id, ':user_id' => $user_id, ':user_name' => $user_name, ':hash_type' => $hash_type, ':pass_hash' => $pass_hash]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute2(..)", $button, $target);

			return $result;
		}

		function userIsAdmin($user_name, $form, $button, $target)
		{
			$pdo = connectDB($form, $button, $target);

			$role_id = getRoleIdByUserName($pdo, $user_name, $form, $button, $target);

			$stmt = $pdo->prepare('
SELECT role_name
FROM roles
WHERE role_id = :role_id;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':role_id' => $role_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			
			if ( $object_array[0]->role_name == "Admin" ) { return true; } else { return false; }
		}

		function selectUsers($pdo, $form, $button, $target)
		{
		//~ user_id INTEGER PRIMARY KEY NOT NULL,
		//~ role_id INTEGER NOT NULL,
		//~ user_name VARCHAR (255) NOT NULL,
		//~ hash_type VARCHAR (255) NOT NULL,
		//~ pass_hash VARCHAR (255) NOT NULL,

			$stmt = $pdo->prepare('
SELECT *
FROM users
ORDER BY user_name;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute();
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		function selectUserById($pdo, $user_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('
SELECT *
FROM users
WHERE user_id = :user_id;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		//~ user_id INTEGER PRIMARY KEY NOT NULL,
		//~ role_id INTEGER NOT NULL,
		//~ user_name VARCHAR (255) NOT NULL,
		//~ hash_type VARCHAR (255) NOT NULL,
		//~ pass_hash VARCHAR (255) NOT NULL,

		function searchUsersByName($pdo, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form, $button, $target) // $primar_column_order_fld = "left_column" or "right_column", $primar_column_order_dir = "ASC"or "DESC"
		{
			$stmt = $pdo->prepare("
SELECT users.user_id, users.role_id, users.user_name, users.hash_type, users.pass_hash, COUNT(secrets.user_id) AS user_secrets
FROM users
LEFT JOIN secrets ON users.user_id = secrets.user_id
WHERE users.user_name like :search_name_filter
AND COALESCE(secrets.secret_name,'') like :search_group_filter
GROUP BY users.user_id
ORDER BY ${primar_column_order_fld} ${primar_column_order_dir}, ${second_column_order_fld} ${second_column_order_dir};
								  ");

			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "query(..)", $button, $target);
			$stmt->execute([
								':search_name_filter' => "%" . $search_name_filter . "%"
								,':search_group_filter' => "%" . $search_group_filter . "%"
							]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
			
			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		function getUserIdByUserName($user_name, $form, $button, $target)
		{
			$pdo = connectDB($form, $button, $target);			

			$stmt = $pdo->prepare('SELECT user_id FROM users WHERE user_name = :user_name;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':user_name' => $user_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array[0]->user_id;
		}

		function getUserNameByUserId($pdo, $user_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('SELECT user_name FROM users WHERE user_id = :user_id;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array[0]->user_name;
		}

		function getRoleIdByUserName($pdo, $user_name, $form, $button, $target)
		{
			$stmt = $pdo->prepare('SELECT role_id FROM users WHERE user_name = :user_name;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':user_name' => $user_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array[0]->role_id;
		}

		function printUsers($users)
		{
			echo "<table style=\"text-align: center; font-size: large; width:100%\" class=\"t_able _table-bordered\">";
			echo "	<thead>";
			echo "			<tr><th colspan=5>Users</th></tr>";
			echo "			<tr><th>User Id</th><th>Role Id</th><th>User Name</th><th>Pass Hash</th></tr>";
			echo "	</thead>";
			echo "	<tbody>";
			foreach ($users as $user)
			{
				echo "		<tr><td>$user->user_id</td><td>$user->role_id</td><td>$user->user_name</td><td>$user->pass_hash</td></tr>";
			}
			echo "	</tbody>";
			echo "</table>";
		}

		function deleteUserByUserId($pdo, $user_id, $form, $button, $target)
		{
			$user_name = getUserNameByUserId($pdo, $user_id, $form, $button, $target);
			if ( $user_name === "admin" || $user_name === "tiny" )
			{
				dboError(array("-","-","Forbidden to delete user: " . $user_name), "header", $form, "deleteUserByUserId(\"$user_name\")", "[ ◀ OK ▶ ]","index.php");
			}
			else
			{
				deleteSecretByUserId($pdo, $user_id, $form, $button, $target);
				
				$stmt = $pdo->prepare('DELETE FROM users WHERE user_id = :user_id;');
				$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare('DELETE FROM users..)", $button, $target);
				$result = $stmt->execute([':user_id' => $user_id]);
				$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute([':user_id' => \$user_id])", $button, $target);
				return $result;
			}
		}

//~ ----------------------------------------------------------------------------


//~ ----------------------------------------------------------------------------

		function insertRole($pdo, $role_name, $form, $button, $target)
		{
			//~ Stop if role_name exists
						
			$stmt = $pdo->prepare('SELECT * FROM roles WHERE role_name = :role_name;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare1(..)", $button, $target);
			$stmt->execute([':role_name' => $role_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute1(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }			
			if ( count($object_array) > 0) { dboError($err, "header", $form, "insertRole(\"$role_name\")", $button, $target); dboError(array("-","-","Roles: \"$role_name\" exists"), "header", $form, "execute1(..)", $button, $target); }
			
			//~ New Role
			$stmt = $pdo->prepare('INSERT INTO roles(role_name) VALUES(:role_name);');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare2(..)", $button, $target);
			$stmt->execute([':role_name' => $role_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute2(..)", $button, $target);
			return $pdo->lastInsertId();
		}

		function selectRoles($pdo, $role_name, $form, $button, $target)
		{
			$stmt = $pdo->prepare('SELECT * FROM roles WHERE role_name like :role_name;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':role_name' => "%" . $role_name . "%"]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }			
			return $object_array;
		}

		//~ Table roles
		//~ role_id INTEGER PRIMARY KEY NOT NULL,
		//~ role_name VARCHAR (255) NOT NULL

		//~ Table users
		//~ user_id INTEGER PRIMARY KEY NOT NULL,
		//~ role_id INTEGER NOT NULL,
		//~ user_name VARCHAR (255) NOT NULL,
		//~ hash_type VARCHAR (255) NOT NULL,
		//~ pass_hash VARCHAR (255) NOT NULL,

		function getRoleNameByUserName($pdo, $user_name, $form, $button, $target)
		{
			$role_id = getRoleIdByUserName($pdo, $user_name, $form, $button, $target);

			$stmt = $pdo->prepare('
SELECT role_name
FROM roles
WHERE role_id = :role_id;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':role_id' => $role_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array[0]->role_name;
		}

		function getRoleIdByRoleName($pdo, $role_name, $form, $button, $target)
		{
			$stmt = $pdo->prepare('SELECT role_id FROM roles WHERE role_name = :role_name;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':role_name' => $role_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array[0]->role_id;
		}

		function printRoles($roles)
		{
			echo "<table style=\"text-align: center; font-size: large; width:100%\" class=\"t_able _table-bordered\">";
			echo "	<thead>";
			echo "			<tr><th colspan=5>Roles</th></tr>";
			echo "			<tr><th>Role Id</th><th>Role Name</th></tr>";
			echo "	</thead>";
			echo "	<tbody>";
			foreach ($roles as $role)
			{
				echo "		<tr><td>$role->role_id</td><td>$role->role_name</td></tr>";
			}
			echo "	</tbody>";
			echo "</table>";
		}

		function updateRole($pdo, $fromRoleName, $toRoleName, $form, $button, $target)
		{			
			$stmt = $pdo->prepare('UPDATE roles SET role_name = :toRoleName WHERE role_name = :fromRoleName;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$result = $stmt->execute([':fromRoleName' => $fromRoleName, ':toRoleName' => $toRoleName]); $errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
			return $result;
		}

		function deleteRole($pdo, $role_name, $form, $button, $target)
		{
			$roles = selectRoles($pdo, $role_name, $form, "[ ◀ OK ▶ ]", "index.php");
			//~ printHeader("Delete role: \"$role_name\"", $form, "[ ◀ OK ▶ ]", "index.php");
			//~ printRoles($roles);

			if ( count($roles) == 0) { dboError(array("-","-","Role: \"$role_name\" not found"), "header", $form, "deleteRole(\"$role_name\")", $button, $target); }
			else
			{
				$stmt = $pdo->prepare('DELETE FROM roles WHERE role_name = :role_name;');			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare('DELETE FROM roles..)", $button, $target);
				$stmt->execute([':role_name' => $role_name]);											$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute([':role_name' => \$role_name])", $button, $target);
				
				$roles = selectRoles($pdo, $role_name, $form, "[ ◀ OK ▶ ]", "index.php");
				printHeader("Delete role: \"$role_name\"", $form, "[ ◀ OK ▶ ]", "index.php");
				printRoles($roles);
			}
		}

//~ ----------------------------------------------------------------------------

			//~ $pdo->exec('CREATE TABLE IF NOT EXISTS secrets
								//~ (
									//~ secret_id INTEGER PRIMARY KEY NOT NULL,
									//~ user_id INTEGER NOT NULL,
									//~ group_id INTEGER,
									//~ secret_date INTEGER NOT NULL,
									//~ secret_name VARCHAR (255) NOT NULL
								//~ )');

		function insertSecret($pdo, $user_id, $group_id, $secret_name, $form, $button, $target)
		{
			$stmt = $pdo->prepare('INSERT INTO secrets(user_id, group_id, secret_date, secret_name) VALUES(:user_id,:group_id,:secret_date,:secret_name);');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare2(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id, ':group_id' => $group_id, ':secret_date' => time(), ':secret_name' => $secret_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute2(..)", $button, $target);
			return $pdo->lastInsertId();
		}

		function updateSecret($pdo, $secret_id, $group_id, $secret_name, $form, $button, $target)
		{			
			//~ Update Secret
			$stmt = $pdo->prepare('UPDATE secrets SET group_id = :group_id, secret_date = :secret_date, secret_name = :secret_name WHERE secret_id = :secret_id;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':secret_id' => $secret_id, ':group_id' => $group_id, ':secret_date' => time(), ':secret_name' => $secret_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
		}

		function deleteSecretById($pdo, $secret_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('DELETE FROM secrets WHERE secret_id = :secret_id;');			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare('DELETE FROM secrets..)", $button, $target);
			$stmt->execute([':secret_id' => $secret_id]);										$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute([':secret_id' => \$secret_id])", $button, $target);

			$stmt = $pdo->prepare('DELETE FROM fields WHERE secret_id = :secret_id;');			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare2('DELETE FROM fields..)", $button, $target);
			$stmt->execute([':secret_id' => $secret_id]);										$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute2([':secret_id' => \$secret_id])", $button, $target);			
		}

		function deleteSecretByUserId($pdo, $user_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('DELETE FROM secrets WHERE user_id = :user_id;');				$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare('DELETE FROM secrets..)", $button, $target);
			$stmt->execute([':user_id' => $user_id]);											$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute([':secret_id' => \$secret_id])", $button, $target);

			$stmt = $pdo->prepare('DELETE FROM fields WHERE user_id = :user_id;');				$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare2('DELETE FROM fields..)", $button, $target);
			$stmt->execute([':user_id' => $user_id]);										$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute2([':secret_id' => \$secret_id])", $button, $target);			
		}

		function selectSecretById($pdo, $secret_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('
SELECT secrets.secret_id, secrets.secret_name, groups.group_name
FROM secrets
LEFT JOIN groups ON secrets.group_id = groups.group_id
WHERE secret_id = :secret_id;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':secret_id' => $secret_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		function selectSecretsByName($pdo, $user_name, $search_name_filter, $search_group_filter, $form, $button, $target)
		{
			$user_id = getUserIdByUserNameByUserName($user_name,$form, $button, $target);
			
									//~ secret_id INTEGER PRIMARY KEY NOT NULL,
									//~ user_id INTEGER NOT NULL,
									//~ group_id INTEGER,
									//~ secret_date INTEGER NOT NULL,
									//~ secret_name VARCHAR (255) NOT NULL

			$stmt = $pdo->prepare("
SELECT secrets.secret_id, secrets.secret_name, groups.group_name
FROM secrets
LEFT JOIN groups ON secrets.group_id = groups.group_id
WHERE secrets.user_id = :user_id
AND secrets.secret_name like :search_name_filter
AND COALESCE(groups.group_name,'') like :search_group_filter;
								");
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id, ':search_name_filter' => "%" . $search_name_filter . "%", ':search_group_filter' => "%" . $search_group_filter . "%"]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}


		//~ secret_id INTEGER PRIMARY KEY NOT NULL,
		//~ user_id INTEGER NOT NULL,
		//~ group_id INTEGER,
		//~ secret_date INTEGER NOT NULL,
		//~ secret_name VARCHAR (255) NOT NULL,

		//~ field_id INTEGER PRIMARY KEY NOT NULL,
		//~ secret_id INTEGER NOT NULL,
		//~ field_ordr INTEGER NOT NULL,
		//~ field_name VARCHAR (255) NOT NULL,
		//~ field_type INTEGER (255) NOT NULL,
		//~ field_value VARCHAR (255) NOT NULL,
		//~ field_hash VARCHAR (255) NOT NULL,

		function searchSecretsByName($pdo, $user_name, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form, $button, $target) // $primar_column_order_fld = "left_column" or "right_column", $primar_column_order_dir = "ASC"or "DESC"
		{
			$user_id = getUserIdByUserName($user_name, $form, $button, $target);
						
					$stmt = $pdo->prepare("
SELECT secrets.secret_id, secrets.secret_name, groups.group_id, groups.group_name
FROM secrets
LEFT JOIN fields ON secrets.secret_id = fields.secret_id
LEFT JOIN groups ON secrets.group_id = groups.group_id
WHERE secrets.user_id = :user_id
AND secrets.secret_name like :search_name_filter
AND COALESCE(groups.group_name,'') like :search_group_filter
OR secrets.user_id = :user_id
AND fields.field_type != 'password'
AND fields.field_value like :search_name_filter
AND COALESCE(groups.group_name,'') like :search_group_filter
OR secrets.user_id = :user_id
AND fields.field_name like :search_name_filter
AND COALESCE(groups.group_name,'') like :search_group_filter
GROUP BY secrets.secret_id
ORDER BY ${primar_column_order_fld} ${primar_column_order_dir}, ${second_column_order_fld} ${second_column_order_dir};
										  ");
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "query(..)", $button, $target);
			$stmt->execute([
								':user_id' => $user_id
								,':search_name_filter' => "%" . $search_name_filter . "%"
								,':search_group_filter' => "%" . $search_group_filter . "%"
							]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
			
			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		function printSecrets($secrets)
		{
			echo "<table style=\"color: white; text-align: center; font-size: large; width:100%\" class=\"t_able _table-bordered\">";
			echo "	<thead>";
			echo "			<tr><th colspan=5>Secrets</th></tr>";
			echo "			<tr><th>Secret_Id</th><th>Folder_Id</th><th>Secret_Date Id</th><th>Secret_Name</th><th>Group_Name</th></tr>";
			echo "	</thead>";
			echo "	<tbody>";
			foreach ($secrets as $secret)
			{
				echo "		<tr><td>$secret->secret_id</td><td>$secret->user_id</td><td nowrap>".date("Y-m-d H:i:s",$secret->secret_date)."</td><td>$secret->secret_name</td><td>$secret->group_name</td></tr>";
			}
			echo "	</tbody>";
			echo "</table>";
		}

//~ ----------------------------------

		function insertGroup($pdo, $user_id, $group_name, $form, $button, $target)
		{
			//~ Stop if group_name exists
			
									//~ group_id INTEGER PRIMARY KEY NOT NULL,
									//~ user_id INTEGER NOT NULL,
									//~ group_name VARCHAR (255) NOT NULL

			$stmt = $pdo->prepare('
SELECT *
FROM groups
WHERE user_id = :user_id
AND group_name = :group_name;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare1(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id, ':group_name' => $group_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute1(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			
			$groupId = 0;
			
			if ( count($object_array) == 0)
			{
				//~ New Group
				$stmt = $pdo->prepare('
										INSERT INTO groups(user_id, group_name) VALUES(:user_id, :group_name);
									');
				$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare2(..)", $button, $target);
				$stmt->execute([':user_id' => $user_id, ':group_name' => $group_name]);
				$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute2(..)", $button, $target);
				
				$groupId = $pdo->lastInsertId();
			}
			else
			{
				//~ dboError($err, "header", $form, "insertGroup(\"$group_name\")", $button, $target); dboError(array("-","-","Roles: \"$role_name\" exists"), "header", $form, "execute1(..)", $button, $target);
				$groupId = getGroupId($pdo, $user_id, $group_name, $form, $button, $target);
			}
			
			return $groupId;
		}

		function selectGroupById($pdo, $group_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('
SELECT *
FROM groups
WHERE group_id = :group_id;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':group_id' => $group_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		function getGroupId($pdo, $user_id, $group_name, $form, $button, $target)
		{
			$stmt = $pdo->prepare('
SELECT group_id
FROM groups
WHERE user_id = :user_id
AND group_name = :group_name;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id, ':group_name' => $group_name]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);

			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array[0]->group_id;
		}

		function selectGroups($pdo, $user_name, $form, $button, $target)
		{			
			$user_id = getUserIdByUserName($user_name, $form, $button, $target);
			
			//~ group_id INTEGER PRIMARY KEY NOT NULL,
			//~ user_id INTEGER NOT NULL,
			//~ group_name VARCHAR (255) NOT NULL

			$stmt = $pdo->prepare('
SELECT group_name
FROM groups
WHERE user_id = :user_id
ORDER BY group_name;
								  ');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "query(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
			
			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		function printGroups($groups)
		{
			echo "<table style=\"text-align: center; font-size: large; width:100%\" class=\"t_able _table-bordered\">";
			echo "	<thead>";
			echo "			<tr><th colspan=5>Groups</th></tr>";
			echo "			<tr><th>Group Id</th><th>Group Name</th></tr>";
			echo "	</thead>";
			echo "	<tbody>";
			foreach ($groups as $group)
			{
				echo "		<tr><td>$group->group_id</td><td>$group->group_name</td></tr>";
			}
			echo "	</tbody>";
			echo "</table>";
		}

		function updateGroup($pdo, $group_id, $group_name, $form, $button, $target)
		{			
			//~ Update Secret
			$stmt = $pdo->prepare('
UPDATE groups
SET group_name = :group_name
WHERE group_id = :group_id;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':group_name' => $group_name, ':group_id' => $group_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
		}

		function deleteGroupById($pdo, $group_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('DELETE FROM groups WHERE group_id = :group_id;');			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare('DELETE FROM groups..)", $button, $target);
			$stmt->execute([':group_id' => $group_id]);											$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute([':group_id' => \$group_id])", $button, $target);
		}


//~ ============================================================================


		//~ group_id INTEGER PRIMARY KEY NOT NULL,
		//~ user_id INTEGER NOT NULL,
		//~ group_name VARCHAR (255) NOT NULL,

		//~ secret_id INTEGER PRIMARY KEY NOT NULL,
		//~ user_id INTEGER NOT NULL,
		//~ group_id INTEGER,
		//~ secret_date INTEGER NOT NULL,
		//~ secret_name VARCHAR (255) NOT NULL,

		function searchGroupsByName($pdo, $user_name,$search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form, $button, $target) // $primar_column_order_fld = "left_column" or "right_column", $primar_column_order_dir = "ASC"or "DESC"
		{
			$user_id = getUserIdByUserName($user_name, $form, $button, $target);
						
			$stmt = $pdo->prepare("
SELECT groups.group_id, groups.group_name, COUNT(secrets.group_id) AS group_secrets
FROM groups
LEFT JOIN secrets ON groups.group_id = secrets.group_id
WHERE groups.user_id = :user_id
AND groups.group_name like :search_name_filter
AND COALESCE(groups.group_name,'') like :search_group_filter
GROUP BY groups.group_id
ORDER BY ${primar_column_order_fld} ${primar_column_order_dir}, ${second_column_order_fld} ${second_column_order_dir};
									");
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "query(..)", $button, $target);
			$stmt->execute([
								':user_id' => $user_id
								,':search_name_filter' => "%" . $search_name_filter . "%"
								,':search_group_filter' => "%" . $search_group_filter . "%"
							]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
			
			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}


//~ ============================================================================


		function printSecretsFields($secretsfields)
		{
			echo "<table style=\"color: white; text-align: center; font-size: large; width:100%\" class=\"t_able _table-bordered\">";
			echo "	<thead>";
			echo "			<tr><th colspan=5>Secrets Fields</th></tr>";
			echo "			<tr><th>Secret_Name</th><th>Field Name</th><th>Field Type</th><th>Field ValueName</th></tr>";
			echo "	</thead>";
			echo "	<tbody>";
			foreach ($secretsfields as $secretsfield)
			{
				echo "		<tr><td>$secretsfield->secret_name</td><td>$secretsfield->group_name</td><td>$secretsfield->field_name</td><td>$secretsfield->field_type</td><td>$secretsfield->field_value</td></tr>";
			}
			echo "	</tbody>";
			echo "</table>";
		}

//~ ----------------------------------------------------------------------------

		//~ field_id INTEGER PRIMARY KEY NOT NULL,
		//~ secret_id INTEGER NOT NULL,
		//~ field_ordr INTEGER NOT NULL,
		//~ field_name VARCHAR (255) NOT NULL,
		//~ field_type INTEGER (255) NOT NULL,
		//~ field_value VARCHAR (255) NOT NULL,
		//~ field_hash VARCHAR (255) NOT NULL,

		function insertField($pdo, $user_id, $secret_id, $field_ordr, $field_name, $field_type, $field_value, $field_hash, $form, $button, $target)
		{			
			$stmt = $pdo->prepare('INSERT INTO fields(user_id,secret_id,field_ordr,field_name,field_type,field_value,field_hash) VALUES(:user_id,:secret_id,:field_ordr,:field_name,:field_type,:field_value,:field_hash);');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id,':secret_id' => $secret_id,':field_ordr' => $field_ordr,':field_name' => $field_name,':field_type' => $field_type,':field_value' => $field_value,':field_hash' => $field_hash]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
			return $pdo->lastInsertId();
		}

		function selectFieldsBySecretId($pdo, $secret_id, $form, $button, $target)
		{			
			$stmt = $pdo->prepare('SELECT * FROM fields WHERE secret_id = :secret_id ORDER BY field_ordr ASC;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "query(..)", $button, $target);
			$stmt->execute([':secret_id' => $secret_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
			
			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		function selectSecretPasswordFieldsByUserId($pdo, $user_id, $form, $button, $target)
		{			
			$stmt = $pdo->prepare('
SELECT fields.*
FROM fields
LEFT JOIN secrets ON secrets.secret_id = fields.secret_id
LEFT JOIN users ON secrets.user_id = users.user_id
WHERE secrets.user_id = :user_id
AND secrets.secret_id = fields.secret_id
AND fields.field_type = "password"
ORDER BY secrets.secret_name ASC;
								');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "query(..)", $button, $target);
			$stmt->execute([':user_id' => $user_id]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
			
			$object_array = []; while ($record = $stmt->fetchObject()) { $object_array[] = $record; }
			return $object_array;
		}

		function updateField($pdo, $field_id, $field_ordr, $field_name, $field_value, $field_hash, $form, $button, $target)
		{			
			//~ New Secret
			$stmt = $pdo->prepare('UPDATE fields SET field_ordr = :field_ordr, field_name = :field_name, field_value = :field_value, field_hash = :field_hash WHERE field_id = :field_id;');
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare(..)", $button, $target);
			$stmt->execute([':field_id' => $field_id, ':field_ordr' => $field_ordr, ':field_name' => $field_name, ':field_value' => $field_value, ':field_hash' => $field_hash ]);
			$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute(..)", $button, $target);
		}

		function deleteField($pdo, $field_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('DELETE FROM fields WHERE field_id = :field_id;');		$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare('DELETE FROM fields..)", $button, $target);
			$stmt->execute([':field_id' => $field_id]);								$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute([':field_id' => \$field_id])", $button, $target);			
		}

		function deleteFieldsByUserId($pdo, $user_id, $form, $button, $target)
		{
			$stmt = $pdo->prepare('DELETE FROM fields WHERE user_id = :user_id;');	$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "prepare('DELETE FROM fields..)", $button, $target);
			$stmt->execute([':user_id' => $user_id]);								$errorArray = $pdo->errorInfo(); dboError($errorArray, "header", $form, "execute([':field_id' => \$field_id])", $button, $target);			
		}



//~ ============================================================================
						//~ End External Database Functions
//~ ============================================================================

		function random_str( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
		{
			$str = '';
			$max = mb_strlen($keyspace, '8bit') - 1;
			if ($max < 1) { throw new Exception('$keyspace must be at least two characters long'); }
			for ($i = 0; $i < $length; ++$i) { $str .= $keyspace[random_int(0, $max)]; }
			return $str;
		}

		function create_test_secrets($user_name, $pass_word, $num)
		{
			$form = "create_secret";
			//~ require("fortune/fortune.php");
			$fortune = new Fortune;
			$fortune_dir = "/usr/share/games/fortunes/";
			$fortune_file = "/usr/share/games/fortunes/anarchism.dat";
			$dict_file = "/usr/share/dict/words";
			//~ $fortune->quoteFromDir($fortune_dir);
			//~ $fortune->getRandomQuote($fortune_file);


			$groups_array = array(
									"Arthur Aardvark"
									,"Bugs Bunny"
									,"Charlie Brown"
									,"Donald Duck"
									,"Eric Cartman"
									,"Fred Flintstone"
									,"Garfield"
									,"Homer Simpson"
									,"Inspector Gadget"
									,"Jake the Dog"
									,"King Louie"
									,"Lola Bunny"
									,"Mickey Mouse"
									,"Nickelodeon"
									,"Optimus Prime"
									,"Pink Panther"
									,"Quasimodo"
									,"Road Runner"
									,"Scooby Doo"
									,"Tom Cat"
									,"Ursula"
									,"Velma Dinkley"
									,"Wall-E"
									,"Xavier"
									,"Yogi Bear"
									,"Zelda"
								 );
			$words_array = explode(PHP_EOL, preg_replace("~[^a-z0-9\n\r:]~i", "", strtolower(file_get_contents($dict_file))));
			//~ array_slice($words_array, 0, $num);

			$secrets = (array) null; 
						
			//~ echo "<script type='text/javascript'>setProgressBar('progressbar',75,100);</script>";
			//~ echo "<script type='text/javascript'>var node = document.getElementById('progressbar'); node.style.display = 'inline'; node.value = 75; node.max = 100;</script>";
			
			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");

			$user_id = getUserIdByUserName($user_name, $form, "[ ◀ OK ▶ ]", "index.php");

			//~ Generating Test Secret
			for ($c = 1; $c <= $num; $c++)
			{
				//~ Define secret name & fields
				$secret_name = $words_array[array_rand($words_array)] . ".com";
				$group_name = $groups_array[array_rand($groups_array)];

				//~ Insert group
				$group_id = insertGroup($pdo, $user_id, $group_name, $form,"[ ◀ OK ▶ ]","index.php");

				//~ Insert secret name & fields
				$lastInsertSecretId = insertSecret($pdo, $user_id, $group_id, $secret_name, $form, "[ ◀ OK ▶ ]", "index.php");			

				//~ Multidimensional Fields Array
				$fields = array (
								  array("URL",		"url",		"https://www." . $secret_name, 																										""						),
								  array("Login",	"email",	$words_array[array_rand($words_array)] . "@" . $words_array[array_rand($words_array)] . ".com",										""						),
								  array("Pass",		"password",	random_str(rand(6, 12)),																											random_str(rand(6, 12))	),
								  array("Text",		"text",		rtrim(htmlspecialchars($fortune->getRandomQuote($fortune_file), ENT_QUOTES, 'UTF-8')),												""						),
								  array("Note",		"textarea",	rtrim(htmlspecialchars($fortune->getRandomQuote($fortune_file), ENT_QUOTES, 'UTF-8') . $fortune->getRandomQuote($fortune_file)),	""						)
								);
												
				for ($row = 0; $row < count($fields); $row++)
				{
					if 		( $fields[$row][1] === "password" ) 	{ insertField($pdo, $user_id, $lastInsertSecretId, $row, $fields[$row][0], $fields[$row][1], encrypt_password($pass_word, $fields[$row][2]), 	hash($GLOBALS["user_encr_hash_type"], $fields[$row][2]), 	$form, "[ ◀ OK ▶ ]","index.php"); }
					else 											{ insertField($pdo, $user_id, $lastInsertSecretId, $row, $fields[$row][0], $fields[$row][1], $fields[$row][2], 								"", 															$form, "[ ◀ OK ▶ ]","index.php"); }
				}
								
				//~ echo "<script type='text/javascript'>setProgressBar('progressbar',75,100);</script>";
				//~ echo "<script type='text/javascript'>var node = document.getElementById('progressbar'); node.style.display = 'inline'; node.value = 75; node.max = 100;</script>";
			}
		}




//~ ============================================================================
//~ 								Web Pages
//~ ============================================================================




        function send_html_header($identifier)
        {
			echo "$identifier";

			print <<< EOF
			<!DOCTYPE html>
			<html>
				<head>
					<meta charset="utf-8">
					<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
					<title>TinyPass Password Manager</title>
					<link rel="icon" type="image/x-icon" href="img/favicon.ico">
					
					<meta name="description" content="PHP Password Manager with 100% Custom Fields and a built-in SQLite Database">
					<meta name="application-name" content="TinyPass">
					<meta name="keywords" content="Password Manager, Tiny Server, Passwords, Password Safe, password fatigue, cyber-security">
					<meta name="author" content="Ron de Jong">
					<meta name="viewport" content="width=device-width, initial-scale=1">
					<meta name="referrer" content="no-referrer" />

					<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
					<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
					<link rel="stylesheet" type="text/css" href="css/fontAwesome.css">
					<link rel="stylesheet" type="text/css" href="css/light-box.css">
					<link rel="stylesheet" type="text/css" href="css/owl-carousel.css">
					<link rel="stylesheet" type="text/css" href="css/templatemo-style.css">
					<link rel="stylesheet" type="text/css" href="css/normalize.css" />
					<link rel="stylesheet" type="text/css" href="css/demo.css" />
					<!-- <link rel="stylesheet" type="text/css" href="css/tiny-server.css" /> -->
					
					<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">

					<script src="js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script>
<!--
					<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
					<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.js"></script>
-->
					<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
					
<!-- ----------------------------------------------------------------------- -->
<!--                         JAVASCRIPT FUNCTIONS                            -->
<!--                        Dynamic Fields Support                           -->
<!-- ----------------------------------------------------------------------- -->

					<script type='text/javascript'>

						var fieldsCounter = 0;
						const moveRowFunctions = [];
						const showPassFunctions = [];
						const copyValuFunctions = [];
						const deleNodeFunctions = [];
						var set_timeout;

						//~ JS-Function that returns a JS-Function
						function getMoveRowFunction(id)
						{
							return function ()
							{
								const node = document.getElementById(id);
								var row = $(this).closest('tr');
								if ($(this).hasClass('up')) { row.prev().before(row); } else { row.next().after(row); }
							}
						}

						function openURL(url)
						{
							window.open(url, "_blank");
						}

						function showPass(id)
						{
							var node = document.getElementById(id);
							if (node.type === "password")
							{
								//~ node.style.color = "black"; 
								//~ node.style.backgroundColor = "white";
								//~ node.style.fontSize = "x-large";
								node.type = "text";
							}
							else
							{
								//~ node.style.color = "lightgrey";
								//~ node.style.backgroundColor = "transparent";
								//~ node.style.fontSize = "large";
								node.type = "password";
							}
						}

						//~ JS-Function that returns a JS-Function
						function getShowPassFunction(id)
						{
							return function ()
							{
								var node = document.getElementById(id); if (node.type === "password") { node.type = "text"; } else { node.type = "password"; }
							}
						}

						function clearClipboard()
						{
							navigator.clipboard.writeText("");
							set_timeout = null;
						}

						function copyValue(id)
						{
							if (set_timeout !== 'undefined') { clearTimeout(set_timeout); }
							var node = document.getElementById(id);				
							navigator.clipboard.writeText(node.value);
							set_timeout = setTimeout(clearClipboard, 10000);
						}

						//~ JS-Function that returns a JS-Function
						function getCopyValueFunction(id)
						{
							return function ()
							{
								if (set_timeout !== 'undefined') { clearTimeout(set_timeout); }
								var node = document.getElementById(id);				
								navigator.clipboard.writeText(node.value);
								set_timeout = setTimeout(clearClipboard, 10000);
							}
						}

						//~ JS-Function that returns a delete node JS-Function
						function deleteNode(id)
						{
							id.remove();
						}

						//~ JS-Function that returns a delete node JS-Function
						function getDeleteNodeFunction(fieldsCounter,fdid,form,table_row)
						{
							return function ()
							{
								table_row.remove();
								var form_node = document.getElementById(form);

								var inputfunc = document.createElement("input");	inputfunc.type = "hidden";	inputfunc.name = "textfieldfunc" + fieldsCounter;	inputfunc.id = "textfieldfunc" + fieldsCounter;	inputfunc.value = "DELETE";	inputfunc.readonly = true; 	inputfunc.required = true;
								var inputfdid = document.createElement("input");	inputfdid.type = "hidden";	inputfdid.name = "textfieldfdid" + fieldsCounter;	inputfdid.id = "textfieldfdid" + fieldsCounter;	inputfdid.value = fdid; 	inputfdid.readonly = true; 	inputfdid.required = true;

								form_node.appendChild(inputfunc);
								form_node.appendChild(inputfdid);
							}
						}

						//~ JavaScript function that adds formfields with field-type parameters to html table node
						//~ Parameters: func is SQL function - fdid parses field_id for SQL command UPDATE & DELETE - node is HTML input tag - type is HTML input type
						
						function addField(func, fdid, form, node, data_type, name_value, data_value)
						{
							var AddSecretTable = document.getElementById("AddSecretTable");
							var AddSecretTableBody = document.getElementById("AddSecretTableBody");

							var space = document.createTextNode(" ");
							var tablerow = document.createElement("tr"); 	tablerow.classList.add('header'); 
																			tablerow.id = "tablerow" + fieldsCounter;
																			tablerow.style.height = "1rem";
																			tablerow.style.borderLeft = "1px solid rgba(50,50,50)";
																			tablerow.style.borderRight = "1px solid rgba(50,50,50)";
																			tablerow.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
																			
																			tablerow.setAttribute('draggable', false);
																			tablerow.setAttribute('ondragstart', 'start()');
																			tablerow.setAttribute('ondragover', 'dragover()');
							
							//~ Create table delimiter cells
							var upupcell = document.createElement("td"); 	upupcell.style.width = "2.0rem"; upupcell.style.textAlign = "center";
							var downcell = document.createElement("td"); 	downcell.style.width = "2.0rem"; downcell.style.textAlign = "center";
																										
																			moveRowFunctions[fieldsCounter] = getMoveRowFunction("textfieldrow" + fieldsCounter);

																			var inputupup = document.createElement("input");	inputupup.style.width = "2rem"; inputupup.style.fontSize = "medium"; inputupup.classList.add('up'); inputupup.style.border = "none"; inputupup.classList.add('dark_grey_white_button'); inputupup.type = "button";	inputupup.id = "textfieldrow" + fieldsCounter; inputupup.onclick = moveRowFunctions[fieldsCounter]; inputupup.value = "\u{2191}"; inputupup.title = "Move row up";
																			var inputdown = document.createElement("input");	inputdown.style.width = "2rem"; inputdown.style.fontSize = "medium"; inputdown.classList.add('dn'); inputdown.style.border = "none"; inputdown.classList.add('dark_grey_white_button'); inputdown.type = "button";	inputdown.id = "textfieldrow" + fieldsCounter; inputdown.onclick = moveRowFunctions[fieldsCounter]; inputdown.value = "\u{2193}"; inputdown.title = "Move row down";

							var funccell = document.createElement("td");
							var fdidcell = document.createElement("td");
							var namecell = document.createElement("td"); 	// namecell.style.width = "clamp(7rem, 8rem, 20rem)";
							var typecell = document.createElement("td");
							var valucell = document.createElement("td"); 	valucell.colspan = 2;
							var showcell = document.createElement("td"); 	showcell.style.width = "3rem"; showcell.style.textAlign = "center"; showcell.setAttribute('draggable', false); 
							var copycell = document.createElement("td");	copycell.style.width = "3rem"; copycell.style.textAlign = "center"; copycell.setAttribute('draggable', false); 
							var delecell = document.createElement("td");	delecell.style.width = "3rem"; delecell.style.textAlign = "center"; delecell.setAttribute('draggable', false); 
							
							//~ ------------------------------------------------
							
							//~ Parameters (func, fdid, ...., ....) func parses SQL Function - fdid parses correlated field_id

																var inputfunc = document.createElement("input");										 									inputfunc.type = "hidden";										inputfunc.name = "textfieldfunc" + fieldsCounter;	inputfunc.id = "textfieldfunc" + fieldsCounter;																						inputfunc.value = func; inputfunc.readonly = true; 	inputfunc.required = true;
																var inputfdid = document.createElement("input");										 									inputfdid.type = "hidden";										inputfdid.name = "textfieldfdid" + fieldsCounter;	inputfdid.id = "textfieldfdid" + fieldsCounter;																						inputfdid.value = fdid; inputfdid.readonly = true; 	inputfdid.required = true;
							
							//~ The following three fields (name, data_type, data_value) describe each field that is added to the Secret
							
							//~ Create Name Field
							if ( data_type === 'password' ) { 	var inputname = document.createElement("input");	inputname.style.fontSize = "medium"; inputname.classList.add('tfield'); inputname.type = "text";		inputname.style.width = "100%"; inputname.name = "textfieldname" + fieldsCounter;	inputname.id = "textfieldname" + fieldsCounter;	inputname.placeholder = "Name"; 	inputname.title = "Name of " + data_type + " field"; inputname.value = name_value; 	inputname.readonly = false; inputname.required = true; }
							else 							{ 	var inputname = document.createElement("input");	inputname.style.fontSize = "medium"; inputname.classList.add('tfield'); inputname.type = "text";		inputname.style.width = "100%"; inputname.name = "textfieldname" + fieldsCounter;	inputname.id = "textfieldname" + fieldsCounter;	inputname.placeholder = "Name"; 	inputname.title = "Name of " + data_type + " field"; inputname.value = name_value; 	inputname.readonly = false; inputname.required = true; }
							
							//~ Create Type Field
							if ( data_type === 'password' ) { 	var inputtype = document.createElement("input");										 									inputtype.type = "hidden";										inputtype.name = "textfieldtype" + fieldsCounter;	inputtype.id = "textfieldtype" + fieldsCounter;																						inputtype.value = data_type; 		inputtype.readonly = true; 	inputtype.required = true; }
							else 							{ 	var inputtype = document.createElement("input");										 									inputtype.type = "hidden";										inputtype.name = "textfieldtype" + fieldsCounter;	inputtype.id = "textfieldtype" + fieldsCounter;																						inputtype.value = data_type; 		inputtype.readonly = true; 	inputtype.required = true; }
							
							//~ Create Value Field
							if ( data_type === 'password' ) { 	var inputvalu = document.createElement(node);		inputvalu.style.fontSize = "medium"; inputvalu.classList.add('pfield'); inputvalu.type = data_type;			inputvalu.style.width = "100%"; inputvalu.name = "textfieldvalu" + fieldsCounter;	inputvalu.id = "textfieldvalu" + fieldsCounter;	inputvalu.placeholder = "Value"; 	inputvalu.title = "a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?";	inputvalu.value = data_value; 	inputvalu.setAttribute("pattern", "[a-zA-Z0-9 \\\[\\\!\\\@\\\#\\\$\\\%\\\^\\\&\\\*\\\(\\\)_\\\+\\\-\\\=\\\{\\\}\\\[\\\]\\\;\\\:\\\<\\\>\\\,\\\.\\\/\\\?]+");	inputvalu.readonly = false; }
							else 							{ 	var inputvalu = document.createElement(node);		inputvalu.style.fontSize = "medium"; inputvalu.classList.add('tfield'); inputvalu.type = data_type;			inputvalu.style.width = "100%"; inputvalu.name = "textfieldvalu" + fieldsCounter;	inputvalu.id = "textfieldvalu" + fieldsCounter;	inputvalu.placeholder = "Value"; 	inputvalu.title = "Value of " + data_type + " field";	inputvalu.value = data_value; 																																									inputvalu.readonly = false; }

							//~ ------------------------------------------------

							if ( data_type === 'url' ) 		{
																inputvalu.value = data_value;
																var anchortxt = document.createTextNode("\u{1F517}");
																var inputlink = document.createElement('input'); 	inputlink.style.fontSize = "medium"; inputlink.classList.add('dark_grey_white_button');	inputlink.type = "button";		inputlink.style.border = "none";	inputlink.style.width = "100%";	inputlink.name = "textfieldlink" + fieldsCounter;	inputlink.id = "textfieldlink" + fieldsCounter;	inputlink.onclick = copyValuFunctions[fieldsCounter];				inputlink.value = "\u{1F517}"; 	inputlink.title = "Open URL"; inputlink.style.borderRadius = "0.75rem";
																var anchorlnk = document.createElement("a"); 		anchorlnk.style.fontSize = "medium"; anchorlnk.classList.add('dark_grey_white_button'); 								anchorlnk.style.border = "none";	anchorlnk.style.width = "100%";	anchorlnk.name = "textfieldanch" + fieldsCounter;	anchorlnk.id = "textfieldanch" + fieldsCounter;	anchorlnk.href = inputvalu.value;	anchorlnk.target = "_blanc";					 				anchorlnk.title = "Open URL"; anchorlnk.style.borderRadius = "0.75rem"; anchorlnk.appendChild(inputlink);
															}
							if ( data_type === 'password' ) { 	showPassFunctions[fieldsCounter] = getShowPassFunction(inputvalu.id); }
																copyValuFunctions[fieldsCounter] = getCopyValueFunction(inputvalu.id);
							if ( data_type === 'textarea' ) { 	inputvalu.rows = data_value.split(/\\n/).length; }
																var inputshow = document.createElement("input");	inputshow.style.fontSize = "medium"; inputshow.classList.add('dark_grey_white_button'); inputshow.type = "checkbox";	inputshow.style.border = "none";	inputshow.style.width = "100%";	inputshow.name = "textfieldshow" + fieldsCounter;	inputshow.id = "textfieldshow" + fieldsCounter;	inputshow.onclick = showPassFunctions[fieldsCounter];	inputshow.value = ""; 			inputshow.title = "Show / hide " + data_type; inputshow.style.borderRadius = "0.75rem";

																var inputcopy = document.createElement("input");	inputcopy.style.fontSize = "medium"; inputcopy.classList.add('dark_grey_white_button'); inputcopy.type = "button";		inputcopy.style.border = "none";	inputcopy.style.width = "100%";	inputcopy.name = "textfieldcopy" + fieldsCounter;	inputcopy.id = "textfieldcopy" + fieldsCounter;	inputcopy.onclick = copyValuFunctions[fieldsCounter];	inputcopy.value = "\u{1F4C4}"; 	inputcopy.title = "Copy to clipboard (10 sec)"; inputcopy.style.borderRadius = "0.75rem";
							deleNodeFunctions[fieldsCounter] = getDeleteNodeFunction(fieldsCounter,inputfdid.value,form,tablerow);
																var inputdele = document.createElement("input");	inputdele.style.fontSize = "medium"; inputdele.classList.add('dark_grey_red_button'); inputdele.type = "button";		inputdele.style.border = "none";	inputdele.style.width = "100%";	inputdele.name = "textfielddele" + fieldsCounter;	inputdele.id = "textfielddele" + fieldsCounter;	inputdele.onclick = deleNodeFunctions[fieldsCounter];	inputdele.value = "\u{1F5D1}"; 	inputdele.title = "Delete row (including fields)"; inputdele.style.borderRadius = "0.75rem";
							
							//~ Add input elements to table TDs

							upupcell.appendChild(inputupup);
							downcell.appendChild(inputdown);

							funccell.appendChild(inputfunc);
							fdidcell.appendChild(inputfdid);
							namecell.appendChild(inputname);
							typecell.appendChild(inputtype);
							valucell.appendChild(inputvalu);
							if ( data_type === 'password' )	{ showcell.appendChild(inputshow); }
							if ( data_type === 'url' ) 		{ copycell.appendChild(anchorlnk); } else { copycell.appendChild(inputcopy); }
							delecell.appendChild(inputdele);
							
							//~ Add table TDs to table row
							tablerow.appendChild(funccell);
							tablerow.appendChild(fdidcell);
							tablerow.appendChild(namecell);
							tablerow.appendChild(upupcell);
							tablerow.appendChild(downcell);
							tablerow.appendChild(typecell);
							tablerow.appendChild(valucell);
							tablerow.appendChild(showcell);
							tablerow.appendChild(copycell);
							tablerow.appendChild(delecell);

							//~ Add table row to table node
							AddSecretTableBody.appendChild(tablerow);
							
							document.getElementById("textfieldname" + fieldsCounter).focus();
							
							fieldsCounter++;
						}

						function setInnerHTML(id,data)
						{
							document.getElementById(id).innerHTML = data;
						}

						function setProgressBar(id,val,valmax)
						{
							var node = document.getElementById(id);
							node.style.display = 'inline';
							node.value = val;
							node.max = valmax;
						}


/*
						$(document).ready(function()
						{
							//~ $('.hider)'.hide();

							$('.header').click(function()
							{
								//~ $(this).find('span').text(function(_, value)
								//~ {
								//~ return value == '⯈' ? '⯆' : '⯈'; // ⯅ ⯆ ⯇ ⯈ 🠄 🠅 🠆 🠇 🠈 🠉 🠊 🠋 ⚫ ⚪ ⭱⭲⭷⭸
								//~ });
								//~ $('.hider').hide();
							$(this).nextUntil('.header').slideToggle(200, function() {});
							});
						}
*/

/*
						var row;
						function start()	{ row = event.target; }
						function dragover()
						{
							var e = event;
							e.preventDefault();

							let children= Array.from(e.target.parentNode.parentNode.children);

							if(children.indexOf(e.target.parentNode)>children.indexOf(row)) { e.target.parentNode.after(row); }
							else { e.target.parentNode.before(row); }
						}
*/
						
						function onDeleteAll() {
						  console.log("Deleted all history");
						}

						function deleteAllHistory() {
						  let deletingAll = browser.history.deleteAll();
						  deletingAll.then(onDeleteAll);
						}

						document.onkeydown = checkKey;
						function checkKey(e)
						{
							e = e || window.event;

							if (e.keyCode == '37')
							{
								//~ console.log('left ' + e.keyCode)
							}
							if (e.keyCode == '38')
							{
								//~ console.log('up ' + e.keyCode)
							}
							if (e.keyCode == '39')
							{
								//~ console.log('right ' + e.keyCode)
							}
							if (e.keyCode == '40')
							{
								//~ console.log('down ' + e.keyCode)
								//~ alert('weŕ\'re going down....');
							}
						}
						
					</script>

					<style>
						p { padding: 0px 10px 0px 10px;   font-size: medium; }
						hr { margin-top: 5px; margin-bottom: 5px; }
						button { border-radius: 0.75rem; }
						
						label
						{
							font-weight: normal;
							text-align: left;
						}

						td, th { _border: thin solid rgba(200, 200, 200, 0.2); border-collapse: collapse; text-align: left; }
						th { text-align: center; }
						tr { text-align: left; vertical-align: baseline; } 

						.tfield
						{
							padding: 0.25rem 1rem;
							margin: 0.25rem 0;
							display: inline;
							border: 1px solid rgba(50,50,50);
							color: lightgrey;
							background-color: transparent;
							box-sizing: border-box;
							border-radius: 0.75rem;
							font-size: medium;
							font-family: Verdana,sans-serif;
							text-overflow: ellipsis;
						}

						.pfield
						{
							padding: 0.25rem 1rem;
							margin: 0.25rem 0;
							display: inline;
							border: 1px solid rgba(50,50,50);
							color: lightgrey;
							background-color: transparent;
							box-sizing: border-box;
							border-radius: 0.75rem;
							font-size: medium;
							font-family: Lucida Console,Liberation Mono,DejaVu Sans Mono,Courier New, monospace;
							text-overflow: ellipsis;
						  }

						pinput[type=text], pinput[type=password]
						{
							padding: 12px 10px;
							padding: 0.25rem 1rem;
							margin: 0.25rem 0;
							display: inline;
							border: 1px solid rgba(50,50,50);
							color: lightgrey;
							background-color: transparent;
							box-sizing: border-box;
							color: black;
							border-radius: 0.75rem;
							font: medium Verdana,sans-serif;
						}

						.dark_grey_white_button
						{
							color: lightgrey;
							background-color: transparent;
							
							font-size: medium;
							border: thin solid grey;
							box-shadow: none;
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.dark_grey_white_button:hover
						{
							color: white;
							background-color: rgba(40, 40, 40, 1.0);
						}
						.dark_grey_white_button:active
						{
							color: black;
							background-color: rgba(200, 200, 200, 1.0);
							transform: translate(+0.1rem, +0.1rem);
						}

						.darkboldbutton
						{
							color: white;
							background-color: transparent;
							
							font-size: medium;
							font-weight: bold;
							border: thin solid grey;
							box-shadow: none;
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.darkboldbutton:hover
						{
							color: white;
							background-color: rgba(40, 40, 40, 1.0);
						}
						.darkboldbutton:active
						{
							color: black;
							background-color: rgba(200, 200, 200, 1.0);
							transform: translate(+0.1rem, +0.1rem);
						}

						.dark_grey_red_button
						{
							color: lightgrey;
							background-color: transparent;
							font-size: medium;
							border: thin solid grey;
							box-shadow: none;
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.dark_grey_red_button:hover
						{
							color: red;
							background-color: rgba(40, 40, 40, 1.0);
						}
						.dark_grey_red_button:active
						{
							color: black;
							background-color: rgba(200, 0, 0, 1.0);
							transform: translate(+0.1rem, +0.1rem);
						}

						.dark_red_white_button
						{
							color: red;
							background-color: transparent;
							font-size: medium;
							border: thin solid grey;
							box-shadow: none;
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.dark_red_white_button:hover
						{
							color: white;
							background-color: rgba(40, 40, 40, 1.0);
						}
						.dark_red_white_button:active
						{
							color: black;
							background-color: rgba(200, 0, 0, 1.0);
							transform: translate(+0.1rem, +0.1rem);
						}

						.dark_grey_green_button
						{
							color: lightgrey;
							background-color: transparent;
							font-size: medium;
							border: thin solid grey;
							box-shadow: none;
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.dark_grey_green_button:hover
						{
							color: yellowgreen;
							background-color: rgba(40, 40, 40, 1.0);
						}
						.dark_grey_green_button:active
						{
							color: black;
							background-color: rgba(200, 0, 0, 1.0);
							transform: translate(+0.1rem, +0.1rem);
						}

						.dark_green_white_button
						{
							color: yellowgreen;
							background-color: transparent;
							font-size: medium;
							border: thin solid grey;
							box-shadow: none;
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.dark_green_white_button:hover
						{
							color: white;
							background-color: rgba(40, 40, 40, 1.0);
						}
						.dark_green_white_button:active
						{
							color: black;
							background-color: rgba(200, 0, 0, 1.0);
							transform: translate(+0.1rem, +0.1rem);
						}

						.darkdimbutton
						{
							opacity: 0.4;
							background-color: transparent;
							font-size: medium;
							border: thin solid grey;
							box-shadow: none;
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.darkdimbutton:hover
						{
							opacity: 1.0;
							color: red;
							background-color: rgba(40, 40, 40, 1.0);
						}
						.darkdimbutton:active
						{
							color: black;
							background-color: rgba(200, 0, 0, 1.0);
							transform: translate(+0.1rem, +0.1rem);
						}

						.groupnormbutton
						{
							color: grey;
							background-color: transparent;
							
							font-size: medium;
							border: thin solid grey;
							box-shadow: none;
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.groupnormbutton:hover
						{
							color: white;
							background-color: rgba(40, 40, 40, 1.0);
						}
						.groupnormbutton:active
						{
							color: black;
							background-color: rgba(200, 200, 200, 1.0);
							transform: translate(+0.1rem, +0.1rem);
						}

						.smallbutton
						{
							border-width: thin;
							transition: transform .0s ease-out;
						}
						.smallbutton:hover
						{
							color: rgba(255, 255, 255, 1.0);
							background-color: rgba(30, 30, 30, 1.0);
							box-shadow: 1px 1px 1px rgba(0,0,0,0.2);
							transform: translate(-1px, -1px);
						}

						.bigbutton
						{
							width:auto;
							color: rgba(255, 255, 255, 0.7);
							background-color: rgba(30, 30, 30, 1.0);
							font-size: large;
							padding: 5px 10px 5px 10px;
							margin: 5px 0px 5px 0px;
							border: 5px;
							border-color: rgba(255, 255, 255, 1.0);
							cursor: pointer;
							border-radius: 12px;
							opacity: 0.9;
							transition: transform .0s ease-out;
							box-shadow: 3px 3px 2px rgba(0,0,0,0.3);
						}
						.bigbutton:hover
						{
							color: rgba(255, 255, 255, 1.0);
							background-color: rgba(30, 30, 30, 1.0);
							transform: translate(-2px, -1px);
						}

						.knob
						{
							width:auto;
							vertical-align: middle;
							color: rgba(255, 255, 255, 0.7);
							background-color: rgba(30, 30, 30, 1.0);
							font-size: large;
							padding: 5px 10px 5px 10px;
							margin: 5px 0px 5px 0px;
							border: 5px;
							border-color: rgba(255, 255, 255, 1.0);
							cursor: pointer;
							border-radius: 12px;
							opacity: 0.9;
							transition: transform .0s ease-out;
							box-shadow: 3px 3px 2px rgba(0,0,0,0.3);
							white-space: nowrap;
						}
						.knob:hover
						{
							color: rgba(255, 255, 255, 1.0);
							background-color: rgba(30, 30, 30, 1.0);
							box-shadow: 3px 3px 2px rgba(0,0,0,0.5);
							transform: translate(-2px, -1px);
						}


						.cancelbtn { width: auto;padding: 10px 18px;background-color: #f44336; }
						.imgcontainer {text-align: center;margin: 24px 0 12px 0;}
						img.avatar {width: 40%;border-radius: 50%;}
						.container {padding: 16px;}

						span.psw { float: right;padding-top: 16px; }

						/* Change styles for span and cancel button on extra small screens */
						@media screen and (max-width: 300px)
						{
							span.psw {display: block;float: none;}
							.cancelbtn {width: 100%;}
						}

						.border
						{
							border-radius:5px;
							border-width: thin;
							border-style: solid;
							border-color: rgba(255, 255, 255, 1.0);
							padding: 0.0vw 0.05vw 0.0vw 0.05vw;
							color: #0075ff;
						}

						.border:hover
						{
							color: white;
							text-decoration: none;
							background: rgba(0, 0, 0, 0.2);
						}

						/* input[type='checkbox'] { display:none; } */
						.wrap-collabsible                                       { font-size: medium; margin: auto; color: #282828; background: white; padding: 0% 0% 0% 0%; vertical-align: middle; width: 100%; margin: 0.0rem 0; }
						.lbl-toggle                                             { display: block; font-weight: normal; font-size: 2.5rem; _text-transform: uppercase; text-align: left; padding: 0.3rem; color: #FFF;  background: rgb(40,40,40); cursor: pointer; border-radius: 7px; transition: all 0.25s ease-out; }
						.lbl-toggle:hover                                       { color: #FFF; background: rgb(69, 72, 154); }
						.lbl-toggle::before                                     { content: ' '; display: inline-block; border-top: 0px solid transparent; border-bottom: 0px solid transparent; border-left: 0px solid currentColor; vertical-align: middle; margin-right: .7rem; transform: translateY(-2px); transition: transform .2s ease-out; }
						.toggle:checked + .lbl-toggle::before                   { transform: rotate(90deg) translateX(-3px); }
						.collapsible-content                                    { max-height: 0px; overflow: hidden; transition: max-height .25s ease-in-out; }
						.toggle:checked + .lbl-toggle + .collapsible-content    { max-height: 100%; }
						.toggle:checked+.lbl-toggle                             { border-bottom-right-radius: 0; border-bottom-left-radius: 0; }
						.collapsible-content .content-inner                     { overflow: hidden; ccolor: rgba(30, 30, 30, 1); bbackground: rgb(220 220 230); bborder-bottom: thin solid grey; bborder-left: thin solid grey; bborder-right: thin solid grey; padding: .2rem 0.1rem; }
						.collapsible-content p                                  { margin-bottom: 0; }

						.webapps { width:6%; text-align: center; border: 0px solid; float: left; padding: 15px 15px 15px 15px; margin: 5px 5px 5px 5px; _box-shadow: 0px 0px 5px rgba(0,0,0,0.5); }

						/*Spinners*/

						.loader { position: fixed; left: 85%; top: 50%; transform: translate(-50%, -50%); width: 100px; height: 100px; }
						.loader:before , .loader:after { content: ''; border-radius: 50%; position: fixed; left: 85%; top: 50%; inset: 0; box-shadow: 0 0 10px 2px rgba(0, 0, 0, 0.3) inset; }
						.loader:after { box-shadow: 0 2px 0 #FF3D00 inset; animation: rotate 2s linear infinite; }
						@keyframes rotate { 0% { transform: rotate(0) } 100% { transform: rotate(360deg) } }


						.diceloaded
						{
							position: fixed; left: 89%; top: 45%;
							width: 54px;
							height: 54px;
							border-radius: 4px;
							background-color: #fff;
							background-image:
							radial-gradient(circle 5px , #FF3D00 100%, transparent 0),
							radial-gradient(circle 5px , #FF3D00 100%, transparent 0),
							radial-gradient(circle 5px , #FF3D00 100%, transparent 0),
							radial-gradient(circle 5px , #FF3D00 100%, transparent 0),
							radial-gradient(circle 5px , #FF3D00 100%, transparent 0),
							radial-gradient(circle 5px , #FF3D00 100%, transparent 0);
							background-repeat: no-repeat;
						  animation: move 4s linear infinite , rotatedice 2s linear infinite;
						}

						@keyframes rotatedice {
						  0% , 20%{ transform: rotate(0deg)}
						  30% , 40% { transform: rotate(90deg)}
						  50% , 60% { transform: rotate(180deg)}
						  70% , 80% { transform: rotate(270deg)}
						  90%,  100% { transform: rotate(360deg)} }
						@keyframes move {
						  0% ,  9%  { background-position: -12px -15px,  -12px 0px, -12px 15px, 12px -15px,  12px 0px,  12px 15px; }
						  10% , 25% { background-position: 0px -15px,  -12px 0px, -12px 15px, 34px -15px,  12px 0px,  12px 15px; }
						  30% , 45% { background-position: 0px -34px, -12px -10px, -12px 12px, 34px -15px, 12px -10px, 12px 12px; }
						  50% , 65% { background-position: 0px -34px, -12px -34px, -12px 12px, 34px -12px, 0px -10px, 12px 12px; }
						  70% , 85% { background-position: 0px -34px, -12px -34px, 0px 12px, 34px -12px, 0px -10px, 34px 12px; }
						 90% , 100% { background-position: 0px -34px, -12px -34px, 0px 0px, 34px -12px, 0px 0px, 34px 12px; } }


						.eyesloaded
						{
							position: fixed; top: 50%; left: 85%; transform: translate(-50%, -50%); 
							width: 108px;
							display: flex;
							justify-content: space-between;
						}
						.eyesloaded::after , .eyesloaded::before
						{
							content: '';
							display: inline-block;
							width: 48px;
							height: 48px;
							background-color: #FFF;
							background-image:  radial-gradient(circle 14px, #0d161b 100%, transparent 0);
							background-repeat: no-repeat;
							border-radius: 50%;
							animation: eyeMove 10s infinite , blink 10s infinite;
						}
						@keyframes eyeMove
						{
							0%  , 10% {     background-position: 0px 0px}
							13%  , 40% {     background-position: -15px 0px}
							43%  , 70% {     background-position: 15px 0px}
							73%  , 90% {     background-position: 0px 15px}
							93%  , 100% {     background-position: 0px 0px}
						}
						@keyframes blink
						{
							0%  , 10% , 12% , 20%, 22%, 40%, 42% , 60%, 62%,  70%, 72% , 90%, 92%, 98% , 100%
							{ height: 48px}
							11% , 21% ,41% , 61% , 71% , 91% , 99%
							{ height: 18px}
						}

						.activestatus { background-size: contain; background-repeat: no-repeat; width: 5em; }
			/*            Password popup for manage_service*/
						.open-button { background-color: #555; color: white; padding: 16px 20px; border: none; cursor: pointer; opacity: 0.8; width: 280px;}
						.form-popup { display: none; width: 100%; position: relative; z-index: 999;}
						.form-container { width: 100%; padding: 0px 0px 0 0px; background-color: white;}
						.form-container input[type=text], .form-container input[type=password] { width: 100%; background: #f1f1f1; font-size: medium;       border: thin; solid; border-color: black; padding: 5px; margin: 5px 0 5px 0; }
						.form-container input[type=text]:focus, .form-container input[type=password]:focus { background-color: #ddd; outline: none;         border: thin; solid; border-color: black; padding: 5px; margin: 5px 0 5px 0; }
						.form-container .btn    { background-color: red; color: white; cursor: pointer; width: 100%; opacity: 0.8;                          border: thin; solid; border-color: black; padding: 5px; margin: 5px 0 5px 0; }
						.form-container .cancel { background-color: grey; color: white; cursor: pointer; width: 100%;                                       border: thin; solid; border-color: black; padding: 5px; margin: 5px 0 5px 0; }
						.form-container .btn:hover, .open-button:hover {opacity: 1;}

						[data-title]
						{
							position: relative;
						}

						[data-title]:hover:after
						{
							opacity: 1;
							transition: all 0.1s ease 0.5s;
							visibility: visible;
						}
						
						[data-title]:after
						{
							content: attr(data-title);
							position: relative;
							b_ottom: -1.6em;
							l_eft: 50%;
							padding: 4px 4px 4px 8px;
							color: #666;
							white-space: nowrap;
							-moz-border-radius: 5px;
							-webkit-border-radius: 5px;
							border-radius: 5px;
							-moz-box-shadow: 0px 0px 4px #666;
							-webkit-box-shadow: 0px 0px 4px #666;
							box-shadow: 0px 0px 4px #666;
							background-image: -moz-linear-gradient(top, #f0eded, #bfbdbd);
							background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #f0eded), color-stop(1, #bfbdbd));
							background-image: -webkit-linear-gradient(top, #f0eded, #bfbdbd);
							background-image: -moz-linear-gradient(top, #f0eded, #bfbdbd);
							background-image: -ms-linear-gradient(top, #f0eded, #bfbdbd);
							background-image: -o-linear-gradient(top, #f0eded, #bfbdbd);
							opacity: 0;
							z-index: 99999;
							visibility: hidden;
						}
						
						
/* ========================================================================== */


						.servicecard
						{
						  border: 3px solid blue;
						  padding: 20px 40px 40px;
						  max-width: 600px;
						  &__image  { width: 150px; margin: 30px 30px 30px 0; float: left; }
						  &__text   { display: inline; font-size: xx-small; }
						}
						.servicecard p { padding: 5rem; font-size: xx-small; font-family: 'Raleway', Arial, sans-serif; }

/* -------------------------------------------------------------------------- */
						
						/* TOOLTIP Service Manager */

						.service-tooltip                                        { _display: inline; _position: absolute; z-index: 999; }
						.service-tooltip h2                                     { font-size: x-large; }
						.service-tooltip-item                                   { cursor: pointer; display: inline-block; }
						.service-tooltip-item::after                            { content: ''; position: absolute;  padding: 10px; height: 20px; bottom: 100%; pointer-events: none; -webkit-transform: translateX(-50%); transform: translateX(-50%); }
						.service-tooltip:hover .service-tooltip-item::after     { pointer-events: auto; }
						.service-tooltip-content
						{
							display: inline;
							position: absolute;
							left: 8rem; 
							width: 28rem;
							padding: 1rem;
							z-index: 9999;
							text-align: justify;
							text-justify: inter-word;
							font-size: small;
							line-height: 1.4;
							box-shadow: -5px -5px 15px rgba(48,54,61,0.2);
							color: white;
							background: rgba(0,0,0,0.9);
							opacity: 0;
							cursor: default;
							pointer-events: none;
						}

						.service-tooltip-effect-1 .service-tooltip-content { -webkit-transform: translate3d(0,-10px,0); transform: translate3d(0,-10px,0); -webkit-transition: opacity 0.3s, -webkit-transform 0.3s; transition: opacity 0.3s, transform 0.3s; }
						.service-tooltip-effect-2 .service-tooltip-content {
							-webkit-transform-origin: 50% calc(100% + 10px);
							transform-origin: 50% calc(100% + 10px);
							-webkit-transform: perspective(1000px) rotate3d(1,0,0,45deg);
							transform: perspective(1000px) rotate3d(1,0,0,45deg);
							-webkit-transition: opacity 0.2s, -webkit-transform 0.2s;
							transition: opacity 0.2s, transform 0.2s;
							padding: 1rem;
						}

						.service-tooltip-effect-3 .service-tooltip-content { -webkit-transform: translate3d(0,10px,0) rotate3d(1,1,0,25deg); transform: translate3d(0,10px,0) rotate3d(1,1,0,25deg); -webkit-transition: opacity 0.3s, -webkit-transform 0.3s; transition: opacity 0.3s, transform 0.3s; }
						.service-tooltip-effect-4 .service-tooltip-content
						{
							-webkit-transform-origin: 50% 100%;
							transform-origin: 50% 100%;
							-webkit-transform: scale3d(0.7,0.3,1);
							transform: scale3d(0.7,0.3,1);
							-webkit-transition: opacity 0.2s, -webkit-transform 0.2s;
							transition: opacity 0.2s, transform 0.2s;
						}

						.service-tooltip-effect-5 .service-tooltip-content
						{
						/*    width: 180px;*/
							margin-left: -90px;
							-webkit-transform-origin: 50% calc(100% + 6em);
							transform-origin: 50% calc(100% + 6em);
							-webkit-transform: rotate3d(0,0,1,15deg);
							transform: rotate3d(0,0,1,15deg);
							-webkit-transition: opacity 0.2s, -webkit-transform 0.2s;
							transition: opacity 0.2s, transform 0.2s;
							-webkit-transition-timing-function: ease, cubic-bezier(.17,.67,.4,1.39);
							transition-timing-function: ease, cubic-bezier(.17,.67,.4,1.39);
						}

						.service-tooltip:hover .service-tooltip-content { pointer-events: auto; opacity: 1; -webkit-transform: translate3d(0,0,0) rotate3d(0,0,0,0); transform: translate3d(0,0,0) rotate3d(0,0,0,0); }
						.service-tooltip.service-tooltip-effect-2:hover .service-tooltip-content { -webkit-transform: perspective(1000px) rotate3d(1,0,0,0deg); transform: perspective(1000px) rotate3d(1,0,0,0deg); }
						.service-tooltip-content::after
						{
						/* Arrow */
						/*    content: '';*/
							top: 100%;
							left: 50%;
							border: solid transparent;
							height: 0;
							width: 0;
							position: absolute;
							pointer-events: none;
							border-color: transparent;
							border-top-color: #2a3035;
							border-width: 10px;
							margin-left: -10px;
						}

						.service-tooltip-content img                    { position: relative; height: 75px; display: block;   margin: 1rem 1rem 1rem 0rem; }
						.service-tooltip-text                           { font-size: small; line-height: 1.35; display: block; padding: 0rem 0rem 0rem 0rem; color: #fff; }
						.service-tooltip-effect-5 .service-tooltip-text { padding: 1.4em; }
						.service-tooltip-text a                         { font-weight: bold; }

						.container { position: relative; width: 100%; max-width: 400px; }
						.container img { width: 100%; height: auto; }

						.container .btn
						{
						  position: absolute;
						  top: 50%;
						  left: 50%;
						  transform: translate(-50%, -50%);
						  -ms-transform: translate(-50%, -50%);
						  /*background-color: rgba(0,0,0,0.8);*/
						  color: color: rgba(0,0,0,0);
						  font-size: 16px;
						  padding: 12px 24px;
						  border: none;
						  cursor: pointer;
						  border-radius: 1rem;
						  text-align: center;
						}

						.container .btn:hover { color: rgba(0,0,0,1); }

						.lds-ripple {
						  display: inline-block;
						  position: relative;
						  width: 80px;
						  height: 80px;
						}
						.lds-ripple div {
						  position: absolute;
						  border: 4px solid #fff;
						  opacity: 1;
						  border-radius: 50%;
						  animation: lds-ripple 3s cubic-bezier(0, 0.2, 0.8, 1) infinite;
						}
						.lds-ripple div:nth-child(2) {
						  animation-delay: -0.5s;
						}
						@keyframes lds-ripple {
						  0% {
							top: 36px;
							left: 36px;
							width: 0;
							height: 0;
							opacity: 0;
						  }
						  4.9% {
							top: 36px;
							left: 36px;
							width: 0;
							height: 0;
							opacity: 0;
						  }
						  5% {
							top: 36px;
							left: 36px;
							width: 0;
							height: 0;
							opacity: 1;
						  }
						  100% {
							top: 0px;
							left: 0px;
							width: 72px;
							height: 72px;
							opacity: 0;
						  }
						}



						background-color: #050505;
						color: white;
						
						.search_name_container {
						  height: 2rem;
						  background-color: transparent;
						  position: relative;
						  padding-right: 8rem;
						}
						.search_name_box {
						  background-color: transparent;
						  color: lightgrey;
						  outline: none;
						  font-size: large;
						  border: 1px solid rgba(50,50,50);
						  border-radius: 1rem;
						  width: 100%;
						}
						.search_name_button {
						  position: absolute;
						  top: 0rem;
						  right: 1rem;
						  border-radius: 50%;
						  border: 0;
						  width: 2rem;
						  font-size: large;
						  outline: 0;
						  color: grey;
						  background-color: transparent;
						  border: none;
						}
						.search-result {
						  position: absolute;
						  top: 0.5rem;
						  right: 2rem;
						  border-radius: 50%;
						  border: 0;
						  width: 10rem;
						  font-size: small;
						  text-align: right;
						  outline: 0;
						  //~ color: grey;
						  //~ background-color: transparent;
						  border: none;
						}
						
						.search_group_container {
						  height: 2rem;
						  background-color: transparent;
						  position: relative;
						  border-radius	: 2rem;
						  padding-right: 8rem;
						}
						.search_group_box {
						  background-color: transparent;
						  color: black;
						  outline: none;
						  font-size: large;
						  border: 0;
						  width: 100%;
						}
						.search_group_button {
						  position: absolute;
						  top: 0rem;
						  right: 1rem;
						  border-radius: 50%;
						  border: 0;
						  width: 2rem;
						  font-size: large;
						  outline: 0;
						  color: grey;
						  background-color: transparent;
						  border: none;
						}

						.header			{ color: lightgrey; background-color: transparent; }
						.header:hover
						{
							background-color: rgba(40, 40, 40, 0.5);
							c_ursor: pointer;
						}
						.header:hover .brighten,
						.header:hover ~ .brighten { color: white; }
						
						::-webkit-scrollbar { width: 10px; }
						::-webkit-scrollbar-track { -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.4); border-radius: 8px; -webkit-border-radius: 8px; }
						::-webkit-scrollbar-thumb { -webkit-border-radius: 10px; border-radius: 10px; background: rgba(100,100,100,0.8); -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.5); }
						
						.sidebar::-webkit-scrollbar { display: inherit; }
						.sidebar:hover::-webkit-scrollbar { width: 10px; }
						.sidebar:hover::-webkit-scrollbar-track { -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.4); border-radius: 8px; -webkit-border-radius: 8px; }
						.sidebar:hover::-webkit-scrollbar-thumb { -webkit-border-radius: 10px; border-radius: 10px; background: rgba(100,100,100,0.8); -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.5); }
						
						.checklabel 		{ display: block; position: relative; text-align: center; margin-bottom: 1rem; cursor: pointer; font-size: medium; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; }
						.checklabel input 	{ position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0px; }
						.checkmark 			{ position: absolute; top: -0.5rem; right: 0.2rem; height: 2rem; width: 2rem; text-align: center; background-color: #eee; border: 1px solid rgba(70,70,70); background: transparent; }
						.checklabel:hover input ~ .checkmark 			{ background-color: darkgrey; font-size: small; text-align: center; }
						.checklabel input:checked ~ .checkmark 			{ background-color: transparent; text-align: center; }
						.checkmark:after 								{ content: ""; position: absolute; display: none; text-align: center; font-size: x-small; }
						.checklabel input:checked ~ .checkmark:after 	{ display: block; text-align: center; }
						.checklabel .checkmark:after 					{ top: 0rem; width: 1.5rem; height: 1.5rem; text-align: center; border: solid white; border-width: 0 2px 2px 0; font-size: xx-small; -webkit-transform: rotate(45deg); -ms-transform: rotate(45deg); transform: rotate(45deg);
						
					</style>
				</head>
EOF;

				//~ Landing (no form)
				connectDB("landing","[ ◀ OK ▶ ]","index.php"); // Makes sure DB gets created at first landing
				echo "<body style=\"background-color: #050505; \">";
				//~ echo "<div style=\"text-align: center; position:fixed; bottom:0; left:45%; z-index: 1000;\"><p style=\"text-align: center; color: lightgrey; \"><a href=\"http://tiny-server.com/\">Tiny Server<BR>Hosting at Home</a></p></div>";
        } // send_html_header()

//~ ----------------------------------------------------------------------------
												

//~ ============================================================================
//~ 							Web Menu Parser
//~ ============================================================================


		function logoutString($user_name, $form, $button, $target)
		{
			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
			$role = getRoleNameByUserName($pdo, $user_name, $form, "[ ◀ OK ▶ ]", "index.php");
			$admin = userIsAdmin($user_name, $form, $button, $target);

			if ( $admin ) 	{ $logout_string = "\u{1F46E} $user_name <span style=\"_color: red;\">[ exit ]</span></b>"; } // \u{2B95}
			else 			{ $logout_string = "\u{1F465} $user_name <span style=\"_color: red;\">[ exit ]</span></b>"; } // \u{2B95}
			return $logout_string;
		}


//~ ----------------------------------------------------------------------------


		function getMenuItems($user_name, $new_secret, $new_group, $new_user, $show_users, $show_groups, $show_secrets, $change_password, $logout, $form, $button, $target)
		{
			if ( ! empty($user_name) ) { $user_id = 	getUserIdByUserName($user_name, $form, "[ ◀ OK ▶ ]","index.php"); }
			
			$new_secret_key = "new_secret"; $new_group_key = "new_group"; $new_user_key = "new_user"; $show_users_key = "show_users"; $show_groups_key = "show_groups"; $show_secrets_key = "show_secrets";
			$change_password_key = "change_password"; $logout_key = "logout";

			if ( ! empty($user_name) ) { $admin = userIsAdmin($user_name, $form, $button, $target); }
			
			$menu_items = array ();
			
			if ( ! empty($user_name) )
			{
								if ( $new_secret === strtolower($new_secret_key))				{ $menu_items[] = array("new_secret_button", 		"",								"",				"",				"New Secret", 										""); } elseif ( $new_secret === strtoupper($new_secret_key))				{ $menu_items[] = array("new_secret_button", 		"",								"",				"",				"New Secret", 										"selected"); }
								if ( $new_group === strtolower($new_group_key))					{ $menu_items[] = array("new_group_button", 		"",								"",				"",				"New Group", 										""); } elseif ( $new_group === strtoupper($new_group_key))					{ $menu_items[] = array("new_group_button", 		"",								"",				"",				"New Group", 										"selected"); }
				if ( $admin ) { if ( $new_user === strtolower($new_user_key))					{ $menu_items[] = array("new_user_button", 			"",								"",				"",				"New User", 										""); } elseif ( $new_user === strtoupper($new_user_key))					{ $menu_items[] = array("new_user_button", 			"",								"",				"",				"New User", 										"selected"); } }
				if ( $admin ) { if ( $show_users === strtolower($show_users_key))				{ $menu_items[] = array("show_users_button",		"",								"",				"",				"Show Users", 										""); } elseif ( $show_users === strtoupper($show_users_key))				{ $menu_items[] = array("show_users_button",		"",								"",				"",				"Show Users", 										"selected"); } }
								if ( $show_groups === strtolower($show_groups_key))				{ $menu_items[] = array("show_groups_button",		"",								"",				"",				"Show Groups", 										""); } elseif ( $show_groups === strtoupper($show_groups_key))				{ $menu_items[] = array("show_groups_button",		"",								"",				"",				"Show Groups", 										"selected"); }
								if ( $show_secrets === strtolower($show_secrets_key))			{ $menu_items[] = array("show_secrets_button", 		"",								"",				"",				"Show Secrets", 									""); } elseif ( $show_secrets === strtoupper($show_secrets_key))			{ $menu_items[] = array("show_secrets_button", 		"",								"",				"",				"Show Secrets", 									"selected"); }
								if ( $change_password === strtolower($change_password_key))		{ $menu_items[] = array("change_password_button", 	"",								"",				$user_id,		"Change Password", 									""); } elseif ( $change_password === strtoupper($change_password_key))		{ $menu_items[] = array("change_password_button", 	"",								"",				$user_id,		"Change Password", 									"selected"); }
								if ( $logout === strtolower($logout_key))						{ $menu_items[] = array("logout_button", 			"Logout \u{1F464} $user_name",	LOGOUT_ACTION,	"",				logoutString($user_name, $form, $button, $target),	""); } elseif ( $logout === strtoupper($logout_key))						{ $menu_items[] = array("logout_button", 			"Logout \u{1F464} $user_name",	LOGOUT_ACTION,	"",				logoutString($user_name, $form, $button, $target),	"selected"); }
			}
							
			//~ print_r($menu_items);
			return $menu_items;
		}


//~ ============================================================================
//~ 							Web Menu Pane
//~ ============================================================================


		function send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items)
		{
			$form = "menu_form";
			
			$logo_bg = $GLOBALS["logo_bg"];
			$menu_bg = $GLOBALS["menu_bg"];

			foreach($menu_items as $menu_item) // Only mobile menu when logged-in
			{
				if ( $menu_item[0] == "logout_button")
				{
					$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
					$role = getRoleNameByUserName($pdo, $tp_login_uname, $form, "[ ◀ OK ▶ ]", "index.php");
					$logout_string = "Logout $role [ $tp_login_uname ]";
					//~ $logout_string = $menu_item[2];
					$formwidth = ( strlen($logout_string) + 2);
					$fieldwidth = strlen($logout_string);
					//~ $formwidth = 12; $fieldwidth = 10;

					echo "<header style=\"height: 40px; background-color: transparent;\" class=\"nav-down responsive-nav hidden-lg hidden-md\">\n";
					echo "<button type=\"button\" id=\"nav-toggle\" style=\"margin-top: 9px; background-color: transparent; border: 1px solid grey; height: 40px;\" class=\"navbar-toggle\" data-toggle=\"collapse\" data-target=\"#main-nav\">\n";
					echo "	<span class=\"sr-only\">Toggle navigation</span>\n";
					echo "	<span style=\"background-color: grey;\" class=\"icon-bar\"></span>\n";
					echo "	<span style=\"background-color: grey;\" class=\"icon-bar\"></span>\n";
					echo "	<span style=\"background-color: grey;\" class=\"icon-bar\"></span>\n";
					echo "</button>\n";

					echo "<div id=\"main-nav\" style=\"padding: 0px;\" class=\"collapse navbar-collapse\">\n";
					echo "<nav style=\"left: 0px; \">\n";

					echo "	<ul style=\"\" class=\"nav navbar-nav\">\n";
									
					echo "<form style=\"position: fixed; top: 5rem; left: 50%; transform: translateX(-50%); width:100%; height: 4rem; border: 1px solid grey; background-color: #050505; width: ${formwidth}rem; height: auto; \" class=\"inf\" action=\"index.php\" method=\"post\">\n";
					echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>\n";
					echo "	<input type=\"hidden\" id=\"menu_tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>\n";
					echo "	<input type=\"hidden\" id=\"menu_tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>\n";

					echo "	<input type=\"hidden\" id=\"menu_search_names\" name=\"search_names\" value=\"$search_name_filter\"/>\n";
					echo "	<input type=\"hidden\" id=\"menu_search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>\n";

					echo "	<input type=\"hidden\" id=\"menu_primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>\n";
					echo "	<input type=\"hidden\" id=\"menu_primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>\n";
					echo "	<input type=\"hidden\" id=\"menu_second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>\n";
					echo "	<input type=\"hidden\" id=\"menu_second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>\n";


					foreach($menu_items as $menu_item)
					{
						if 		( $menu_item[4] === "selected" ) 	{ echo "<li><button style=\"width: ${fieldwidth}rem; background-color: white; color: black; font-weight: bold; 	box-shadow: none;\" type=\"submit\" name=\"$menu_item[0]\" title=\"$menu_item[1]\" onclick=\"$menu_item[2]\" value=\"$menu_item[3]\">$menu_item[4]</button></li>\n"; }
						else 										{ echo "<li><button style=\"width: ${fieldwidth}rem; background-color: white; color: black; font-weight: normal; box-shadow: none;\" type=\"submit\" name=\"$menu_item[0]\" title=\"$menu_item[1]\" onclick=\"$menu_item[2]\" value=\"$menu_item[3]\">$menu_item[4]</button></li>\n"; }
					}

					echo "</form>\n";
					echo "	</ul>\n";
					echo "</nav>\n";
					echo "</div>\n";
					echo "</header>\n";

				}
			}


			echo "<div style=\"border-right: 1px solid rgba(50,50,50); background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($menu_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" class=\"sidebar-navigation hidde-sm hidden-xs\">\n";
			echo "<div class=\"logo\" style=\"border-bottom: 1px solid rgba(50,50,50); background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($logo_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\">\n";
			echo "<p style=\"position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 100%; display: flex; vertical-align: middle; text-align: center;\"><a style=\"border: none; height: 1rem;\" href=\"index.php\">Tiny <em>Pass</em></a></p>\n";
			//~ echo "<p style=\"position: absolute; top: 75%; left: 50%; transform: translate(-50%,-50%); width: 100%; display: flex; vertical-align: middle; text-align: center;\"><a style=\"border: none; font-size: 1.0rem; height: 1rem;\" href=\"index.php\"><span style=\"color: white\">Password Manager</span></a></p>\n";
			
			echo "<form style=\"position: absolute; top: 75%; left: 50%; transform: translate(-50%,-50%); display: flex; vertical-align: middle; text-align: center; height: 1rem;\" action=\"index.php\" method=\"post\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>\n";

			echo "	<input type=\"hidden\" id=\"menu_tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>\n";

			echo "	<input type=\"hidden\" id=\"menu_search_names\" name=\"search_names\" value=\"$search_name_filter\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>\n";

			echo "	<input type=\"hidden\" id=\"menu_primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>\n";
			echo "	<button title=\"Get update...\" style=\"line-height: 1rem; padding: 0px; border: none; background-color: transparent; text-align: center; text-transform: uppercase; font-size: 1.16rem; color: white;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_secret_button\" value=\"\" >Password Manager</button>";
			echo "</form>";                
			
			
			
			echo "</div>\n";

			echo "<nav>\n";
			
			echo "	<ul>\n";

			echo "<form style=\"position: fixed; t_op: 42%; transform: translateY(-50%); w_idth:98%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" method=\"post\">\n";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>\n";

			echo "	<input type=\"hidden\" id=\"menu_tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>\n";

			echo "	<input type=\"hidden\" id=\"menu_search_names\" name=\"search_names\" value=\"$search_name_filter\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>\n";

			echo "	<input type=\"hidden\" id=\"menu_primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>\n";
			echo "	<input type=\"hidden\" id=\"menu_second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>\n";

			foreach($menu_items as $menu_item)
			{
				if 		( $menu_item[4] === "selected" ) 	{ echo "<li><button style=\"border: none;\" class=\"darkboldbutton\" type=\"submit\" name=\"$menu_item[0]\" title=\"$menu_item[1]\" onclick=\"$menu_item[2]\" value=\"$menu_item[3]\">$menu_item[4]</button></li>\n"; }
				else 										{ echo "<li><button style=\"border: none;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"$menu_item[0]\" title=\"$menu_item[1]\" onclick=\"$menu_item[2]\" value=\"$menu_item[3]\">$menu_item[4]</button></li>\n"; }
			}

			echo "</form>\n";

			echo "	</ul>\n";
			
			echo "</nav>\n";
			echo "<ul class=\"social-icons\">\n";
			echo "<!-- <li><a href=\"#\"><i class=\"fa fa-facebook\"></i></a></li>\n";
			echo "<li><a href=\"#\"><i class=\"fa fa-twitter\"></i></a></li>\n";
			echo "<li><a href=\"#\"><i class=\"fa fa-linkedin\"></i></a></li>\n";
			echo "<li><a href=\"#\"><i class=\"fa fa-rss\"></i></a></li>\n";
			echo "<li><a href=\"#\"><i class=\"fa fa-behance\"></i></a></li> -->\n";
			echo "</ul>\n";
			echo "</div>\n";
		}

//~ ----------------------------------------------------------------------------

		function send_html_post_script()
		{
			$form = "";
			echo "<script src=\"js/vendor/bootstrap.min.js\"></script>";
			echo "<script src=\"js/plugins.js\"></script>";
			echo "<script src=\"js/main.js\"></script>";

			echo "<script type='text/javascript'>";
				echo "// Hide Header on on scroll down";
				echo "var didScroll;";
				echo "var lastScrollTop = 0;";
				echo "var delta = 5;";
				echo "var navbarHeight = $('header').outerHeight();";

				echo "$(window).scroll(function(event){";
					echo "didScroll = true;";
				echo "});";

				echo "setInterval(function() {";
					echo "if (didScroll) {";
						echo "hasScrolled();";
						echo "didScroll = false;";
					echo "}";
				echo "}, 250);";

				echo "function hasScrolled() {";
					echo "var st = $(this).scrollTop();";
				echo "	";
					echo "// Make sure they scroll more than delta";
					echo "if(Math.abs(lastScrollTop - st) <= delta)";
						echo "return;";
				echo "	";
					echo "// If they scrolled down and are past the navbar, add class .nav-up.";
					echo "// This is necessary so you never see what is \"behind\" the navbar.";
					echo "if (st > lastScrollTop && st > navbarHeight){";
						echo "// Scroll Down";
						echo "$('header').removeClass('nav-down').addClass('nav-up');";
					echo "} else {";
						echo "// Scroll Up";
						echo "if(st + $(window).height() < $(document).height()) {";
							echo "$('header').removeClass('nav-up').addClass('nav-down');";
						echo "}";
					echo "}";
				echo "	";
					echo "lastScrollTop = st;";
				echo "}";
			echo "</script>";

		}

//~ ----------------------------------------------------------------------------

		function send_html_footer()
		{
		  print <<< EOF
</body>
</html>
EOF;
		}


//~ ============================================================================


		function send_html_login_page($delay)
		{
			$form = "login_form";
			
			sleep($delay);						// Slows down brute force attacks
			$login_bg = $GLOBALS["login_bg"];

			send_html_header("");

			$menu_items = getMenuItems("", "" , "", "", "", "", "", "", "", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu("", "", "", "", "", "", "", "", $menu_items);

			echo "<div style_=\"background: _radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: #050505;\" class=\"page-content\">";
			echo "<section style=\"background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($login_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "<div class=\"section-heading\">";
			echo "<h1><em></em></h1>";
			echo "</div>";

			echo "<form style=\"position: fixed; top: 42%; left: 50%; transform: translate(-50%,-50%); width:98%; padding: 0px 10px 0px 10px;\" class=\"e\" action=\"index.php\" method=\"post\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"login_form\"/>";
			echo "		<table style=\"width: 100%; \" id=\"LoginElement\" border=0>";

			echo "		<tbody style=\"color: black;\">";
			echo "			<tr><td style=\"text-align: center; text-transform: uppercase; font-size: 2.7rem; color: white;\" colspan=1>Tiny Pass</td></tr>";
			echo "			<tr><td style=\"text-align: center; text-transform: uppercase; font-size: 1.24rem; color: white;\" colspan=1>Password Manager</td></tr>";
			echo "			<tr><td style=\"text-align: center; text-transform: uppercase; font-size: 1.24rem; color: white;\" colspan=1><button title=\"\" style=\"line-height: 1rem; padding: 0px; border: none; background-color: transparent; font-size: 1.24rem; color: white;\" class=\"dark_grey_white_button\" type=\"button\" name=\"\" value=\"\" >v" . TINY_PASS_VERSION_DESC . "</button></td></tr>";
			echo "			<tr><td >&nbsp</td></tr>";
			echo "			<tr><td style=\"text-align: center;\"><img src=\"img/tinypass-web.png\" alt=\"Avatar\" ></td></tr>										";
			echo "			<tr><td >&nbsp</td></tr>";

			if ( authenticateUser("admin", $GLOBALS["user_stnd_pass_word"], $form, "[ ◀ OK ▶ ]", "index.php") )
			{
				echo "			<tr><td style=\"text-align: center;\">";
				echo "				<input style=\"width: 20rem; text-align: center; border: thin solid rgba(200, 200, 200, 0.9);\" class=\"tfield\" type=\"text\" placeholder=\"Username\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"admin\"required>";
				echo "			</td></tr>";

				echo "			<tr><td >&nbsp</td></tr>";
				echo "			<tr><td style=\"text-align: center;\">";
				echo "				<input style=\"width: 20rem; text-align: center; border: thin solid rgba(200, 200, 200, 0.9); font-family: Verdana,sans-serif;\" class=\"pfield\" type=\"password\" placeholder=\"Password\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"" . $GLOBALS["user_stnd_pass_word"] . "\" required>";
				echo "			</td></tr>";
			}
			else if ( authenticateUser("tiny", $GLOBALS["user_stnd_pass_word"], $form, "[ ◀ OK ▶ ]", "index.php") )
			{
				echo "			<tr><td style=\"text-align: center;\">";
				echo "				<input style=\"width: 20rem; text-align: center; border: thin solid rgba(200, 200, 200, 0.9);\" class=\"tfield\" type=\"text\" placeholder=\"Username\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"tiny\"required>";
				echo "			</td></tr>";

				echo "			<tr><td >&nbsp</td></tr>";
				echo "			<tr><td style=\"text-align: center;\">";
				echo "				<input style=\"width: 20rem; text-align: center; border: thin solid rgba(200, 200, 200, 0.9); font-family: Verdana,sans-serif;\" class=\"pfield\" type=\"password\" placeholder=\"Password\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"" . $GLOBALS["user_stnd_pass_word"] . "\" required>";
				echo "			</td></tr>";
			}
			else
			{
				echo "			<tr><td style=\"text-align: center;\">";
				echo "				<input style=\"width: 20rem; text-align: center; border: thin solid rgba(200, 200, 200, 0.9);\" class=\"tfield\" type=\"text\" placeholder=\"Username\" id=\"tp_login_uname\" name=\"tp_login_uname\" required>";
				echo "			</td></tr>";

				echo "			<tr><td >&nbsp</td></tr>";
				echo "			<tr><td style=\"text-align: center;\">";
				echo "				<input style=\"width: 20rem; text-align: center; border: thin solid rgba(200, 200, 200, 0.9); font-family: Verdana,sans-serif;\" class=\"pfield\" type=\"password\" placeholder=\"Password\" id=\"tp_login_pword\" name=\"tp_login_pword\" required>";
				echo "			</td></tr>";
			}

			echo "			<tr><td >&nbsp</td></tr>";
			echo "		</tbody>";

			echo "		<tfoot>";
			//~ echo "			<tr><td >&nbsp</td></tr>										";
			echo "			<tr>";
			echo "			<td style=\"text-align: center; color: black;\">";

			if ( DEMO )
			{
				echo "			<button style=\"box-shadow: none; border: thin solid rgba(200, 200, 200, 0.9);\" class=\"dark_grey_white_button\" type=\"submit\" value=\"submit_button\">Login Demo</button>";
			}
			else
			{
				echo "			<button style=\"box-shadow: none; border: thin solid rgba(200, 200, 200, 0.9);\" class=\"dark_grey_white_button\" type=\"submit\" value=\"submit_button\">Login</button>";
			} 

			echo "			</td>";
			echo "			</tr>";
			echo "		</tfoot>";
			echo "	</table>";
			echo "	";
			echo "</form>";
			echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"tp_login_uname\").focus(); }</script>";

			echo "<div style=\"position: fixed; bottom: 5%; left: 50%; transform: translate(-50%,-50%); width:98%; padding: 0px 10px 0px 10px;\" class=\"e\" >";
			echo "		<table style=\"width: 100%; \" border=0>";
			echo "		<tbody style=\"color: grey;\">";
			echo "			<tr><td style=\"text-align: center; font-size: 2.15rem; \" colspan=1><a style=\"color: dimgrey;\" href=\"http://tiny-server.com/\">Get a Tiny Server</a></td></tr>";
			echo "			<tr><td style=\"text-align: center; font-size: 1.10rem; \" colspan=1><a style=\"color: dimgrey;\" href=\"http://tiny-server.com/\">Free your Internet Imprisonment</a></td></tr>";
			echo "			<tr><td >&nbsp</td></tr>";
			echo "		</tbody>";
			echo "	</table>";
			echo "	";
			echo "</div>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");
			
			exit();
		}


//~ ============================================================================
//~                               SHOW FORMS                                    
//~ ============================================================================


//~ ============================================================================

		function send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir) // $primar_column_order_fld = "left_column" or "right_column", $primar_column_order_dir = "ASC"or "DESC"
		{
			$form = "show_secrets_form";

			$secretCounter = 0;
			$fieldsCounter = 0;
			
			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "new_secret" , "", "", "show_users", "show_groups", "SHOW_SECRETS", "change_password", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			//~ echo "<style>";  
			//~ echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Secrets</p>";

			echo "<!----------------------------------------------------------------------------->";
			echo "<!--						   SEARCH FIELDS                                 -->";
			echo "<!----------------------------------------------------------------------------->";

			echo "		<form style=\"position: absolute; top: 7rem; left: 50%; transform: translateX(-50%); width: 95%; max-width: 40rem; padding-bottom: 7rem; p_adding: 0px 10px 0px 10px;\" class=\"info\" id=\"$form-1\" action=\"index.php\" method=\"post\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "			<table style=\"width: 100%;\" border=0>";
			echo "				<tbody style=\"color: black;\">";

			echo "					<tr id=\"tablerow0\">";
			echo "						<td style=\"color: grey;\" class=\"search_name_container\" colspan=1>";
			echo "							<input class=\"tfield search_name_box\" type=\"text\" name=\"search_names\" id=\"search_names\" placeholder=\"Search..\" title=\"\" value=\"$search_name_filter\">";
			echo "							<p class=\"search-result\" name=\"search_result\" id=\"search_result\">-/-</p>";
			echo "							<button class=\"search_name_button\" type=\"submit\"><span class=\"fa fa-search\"></span></button>";
			echo "						</td>";
			echo "					</tr>";
			
			echo "					<tr id=\"tablerow0\">";
			echo "						<td style=\"color: grey;\" c_lass=\"search_group_container\" colspan=1>";
			echo "							<input style=\"width:100%;\" list=\"groups\" class=\"tfield se_cret_group_search-box\" type=\"text\" name=\"search_groups\" id=\"search_groups\" placeholder=\"Search by Group\" title=\"\" onchange=\"\$(this).closest('form').trigger('submit');\" value=\"$search_group_filter\">";
			echo "							<datalist id=\"groups\">";

											$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
											
											$records = selectGroups($pdo, $tp_login_uname, $form, "[ ◀ OK ▶ ]", "index.php");
											//~ echo "<option value=\"\">";
											foreach ($records as $record)	{ echo "<option value=\"$record->group_name\">";	}
			echo "							</datalist>";							
			//~ echo "							<button class=\"s_ecret_group_search-button\" type=\"button\" onclick=\"alert(\"sad\");\"><span class=\"fa fa-refresh\">po</span></button>";
			echo "						</td>";						
			echo "					</tr>";

			echo "					<tr style=\"line-height: 10px;\" id=\"tablerow0\">";
			echo "						<td style=\"text-align: center; color: grey;\" colspan=1>";
			echo "						<progress style=\"display: none; height: 5px; width: 95%;\" id=\"progressbar\" name=\"progressbar\" value=\"25\" max=\"100\"> 25% </progress>";
			echo "						</td>";
			echo "					</tr>";
			echo "					<!--";
			echo "					<script type='text/javascript'>setProgressBar('progressbar',50,100);</script> */";
			echo "					<script type='text/javascript'>var node = document.getElementById('progressbar'); node.style.display = 'inline'; node.value = 90; node.max = 100;</script> */";
			echo "					-->";

			echo "				</tbody>";
			echo "				";
			echo "			</table>";
			echo "		</form>					";


			echo "<!----------------------------------------------------------------------------->";
			echo "<!--						   HIDDEN FIELDS                                 -->";
			echo "<!----------------------------------------------------------------------------->";

			echo "		<form style=\"position: absolute; width:99%; top: 14rem; padding-bottom: 7rem; t_ransform: translateY(-50%);\" class=\"info\" action=\"index.php\" id=\"$form\" method=\"post\" enctype=\"multipart/form-data\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "			<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";
						
			//~ Get Searched and Non Searched Secrets Records for comparison

			$records_all = 	searchSecretsByName($pdo, $tp_login_uname, '', 						'', 					$primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form, "[ ◀ OK ▶ ]", "index.php");
			$records = 		searchSecretsByName($pdo, $tp_login_uname, $search_name_filter, 	$search_group_filter, 	$primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form, "[ ◀ OK ▶ ]", "index.php");
			if ( $records != $records_all  ) { $search_result_data = count($records) . " / " . count($records_all); } else { $search_result_data = count($records); }
			echo "<script type='text/javascript'>setInnerHTML(\"search_result\",\"" . $search_result_data . "\");</script>";
			//~ print_r($records);

			echo "			<table style=\"width:99%; color: white; text-align: center; font-size: large;\" class=\"tble _table-bordered\" border=0>";
			echo "				<tbody>";

			//~ Determine and set state of Order Buttons

			$active = "white"; $dimmed = "dimgrey";
			$column_1_label = "Id"; $column_2_label = "Secrets"; $column_3_label = "Groups";
			$column_1_style = "color: $dimmed; border: none;"; $column_2_style = "color: $dimmed; border: none;"; $column_3_style = "color: $dimmed; border: none;"; 
			
			if ( $primar_column_order_fld == "secrets.secret_id" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id&uarr;";
					$column_2_label = "Secrets";
					$column_3_label = "Groups";
					$column_1_style = "color: $active; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id&darr;";
					$column_2_label = "Secrets";
					$column_3_label = "Groups";
					$column_1_style = "color: $active; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}
			elseif ( $primar_column_order_fld == "secrets.secret_name" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Secrets&nbsp;&uarr;";
					$column_3_label = "Groups";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Secrets&nbsp;&darr;";
					$column_3_label = "Groups";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}
			elseif ( $primar_column_order_fld == "groups.group_name" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Secrets";
					$column_3_label = "&uarr;&nbsp Groups";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $active; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Secrets";
					$column_3_label = "&darr;&nbsp Groups";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $active; border: none;";
				}
			}
			else
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Secrets&nbsp;&uarr;";
					$column_3_label = "Groups";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Secrets&nbsp;&darr;";
					$column_3_label = "Groups";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}

			//~ Print Order Buttons
			
			echo "					<tr style=\"height: 1rem; vertical-align: text-bottom; background-color: transparent;\">\n";
			echo "						<td colspan=1 style=\"text-align: right; width: clamp(1.0rem, 1.2rem, 6rem);\" 	><button style=\"font-size: medium; $column_1_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"secrets.secret_id\" 	>$column_1_label</button></td>";
			echo "						<td colspan=1 style=\"text-align: left;\" 	><button style=\"font-size: medium; $column_2_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"secrets.secret_name\" 	>$column_2_label</button></td>";
			echo "						<td colspan=2 style=\"text-align: right;\" 	><button style=\"font-size: medium; $column_3_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"groups.group_name\" 	>$column_3_label</button></td>";
			echo "						<td colspan=1 style=\"width: 2.5rem; text-align: center;\"><label class=\"checklabel\"><input class=\"checkmark\" type=\"checkbox\" id=\"\" onClick=\"toggle(this)\" title=\"Select all / none\" value=\"\"/><span class=\"checkmark\"></span></label></td>";
			echo "						<td colspan=1 style=\"width: 1rem; text-align: center;\">&nbsp;</td>";
			echo "					</tr>\n";

			//~ Print Secrets Table Records
			
			foreach ($records as $record)
			{
				//~ 1st Secret_Name & Secret_Group - Header Table Record

				echo "				<tr style=\"height: 1rem; vertical-align: text-bottom; \" class=\"header\">\n";
				echo "					<td colspan=1 style=\"text-align: right; width: clamp(1.0rem, 1.2rem, 6rem);\" 	class=\"\"	>	<button style=\"font-size: small; white-space:nowrap; border: none;\" class=\"groupnormbutton _brighten\" type=\"submit\" name=\"view_secret_button\"   value=\"$record->secret_id\">" . $record->secret_id . "</button></td>";
				echo "					<td colspan=1 style=\"text-align: left;\" 	class=\"\"	>	<button style=\"white-space:nowrap; border: none; color: lightgrey;\" class=\"dark_grey_white_button brighten\" type=\"submit\" name=\"view_secret_button\"   value=\"$record->secret_id\">" . htmlspecialchars($record->secret_name, ENT_QUOTES, 'UTF-8') . "</button></td>";
				echo "					<td colspan=2 style=\"text-align: right;\" 	class=\"group\"><button style=\"font-size: small; white-space:nowrap; border: none;\" class=\"groupnormbutton _brighten\" type=\"submit\" name=\"view_group_button\" value=\"$record->group_id\">$record->group_name</button></td>";
				echo "					<td colspan=1 style=\"width: 2.5rem; text-align: center;\"><label class=\"checklabel\"><input class=\"checkmark\" type=\"checkbox\" id=\"selected\" name=\"selected_$record->secret_id\" value=\"$record->secret_id\"/><span class=\"checkmark\"></span></label></td>";
				echo "					<td colspan=1 style=\"width: 1rem; text-align: center;\">&nbsp;</td>";
				echo "				</tr>\n";
			}

			//~ Print Selection Function Buttons

			echo "				<tr style=\"h_eight: 1rem; vertical-align: text-bottom; \" class=\"header\">\n";
			//~ echo "					<td colspan=1 style=\"text-align: left;\">	</td>";
			echo "					<td colspan=1 style=\"text-align: left;\">	</td>";
			echo "					<td colspan=1 style=\"text-align: right;\">	</td>";
			echo "					<td colspan=3 style=\"width: auto; text-align: right;\">";

			echo "							<label 		style=\"width: 20px; border: none;\" class=\"dark_green_white_button\" 					for=\"import_exported_secrets_in_limited_format_from_csv_file_button\" 		title=\"" . IMPORT_CSV_FORMATS_LIMITED_DESC . "\" <i class=\"\"></i>\u{21F1}</label><input style=\"width: 20px; display: none;\" class=\"\" id=\"import_exported_secrets_in_limited_format_from_csv_file_button\" name=\"import_exported_secrets_in_limited_format_from_csv_file_button\" type=\"file\" onchange=\"var dialog = confirm('Are you sure you want to import secrets from this file ?'); if (dialog) { form.submit(); } else { this.value = ''; } \"/>";
			echo "							<button  	style=\"width: 25px; border: none;\" class=\"dark_green_white_button\" type=\"submit\"  name=\"export_selected_secrets_to_tinypass_format_to_csv_file_button\" 		title=\"" . EXPORT_CSV_FORMATS_TINYPASS_DESC . "\" value=\"\" onclick=\"return confirm(`Are you sure you want to export the selected secrets ?`);\" formnovalidate>\u{21F2}</button>";
			echo "							<label 		style=\"width: 20px; border: none;\" class=\"dark_red_white_button\" 					for=\"import_exported_secrets_in_original_format_from_csv_file_button\" 	title=\"" . IMPORT_CSV_FORMATS_ORIGINAL_DESC . "\" ><i class=\"\"></i>\u{21F1}</label><input style=\"width: 20px; display: none;\" class=\"\" id=\"import_exported_secrets_in_original_format_from_csv_file_button\" name=\"import_exported_secrets_in_original_format_from_csv_file_button\" type=\"file\" onchange=\"var dialog = confirm('Are you sure you want to import secrets from this file ?'); if (dialog) { form.submit(); } else { this.value = ''; } \"/>&nbsp;";
			echo "							<button  	style=\"width: 25px; border: none;\" class=\"dark_red_white_button\" type=\"submit\"  name=\"export_selected_secrets_to_non_tiny_pass_csv_format_button\" 			title=\"" . EXPORT_CSV_FORMATS_NON_TINYPASS_DESC . "\" value=\"\" onclick=\"return confirm(`Are you sure you want to export the selected secrets ?`);\" formnovalidate>\u{21F2}</button>";
			echo "							<button 	style=\"width: 20px; border: none;\" class=\"dark_grey_red_button\"  type=\"submit\"   name=\"delete_selected_secrets_button\" title=\"Delete selected items\" value=\"\" onclick=\"return confirm(`Are you sure you want to delete the selected secrets ?`);\" formnovalidate>\u{1F5D1}</button>";
			echo "					</td>";
			echo "					<td colspan=1 style=\"width: 1rem; text-align: center;\">&nbsp;</td>";
			echo "				</tr>\n";			

			echo "				</tbody>";

			echo "			</table>";

			echo "		</form>";
			echo "		<script type='text/javascript'>window.onload = function()";
			echo "		{";
			echo "			var node = document.getElementById(\"search_names\");";
			echo "			node.selectionStart = node.selectionEnd = node.value.length;";
			echo "			node.focus();";
			echo "		}</script>";
			echo "		<script type='text/javascript'>";
			echo "		{";
			echo "			function toggle(source) { checkboxes = document.querySelectorAll('[id^=selected]'); for(var i=0, n=checkboxes.length;i<n;i++) { checkboxes[i].checked = source.checked; } }";
			echo "		}</script>";
			echo "	</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir) // $primar_column_order_fld = "left_column" or "right_column", $primar_column_order_dir = "ASC"or "DESC"
		{
			$form = "show_groups_form";

			$secretCounter = 0;
			$fieldsCounter = 0;
			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "new_group", "", "show_users", "SHOW_GROUPS", "show_secrets", "change_password", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			//~ echo "<style>";
			//~ echo "</style>";


			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Groups</p>";

			echo "<!----------------------------------------------------------------------------->";
			echo "<!--						   SEARCH FIELDS                                 -->";
			echo "<!----------------------------------------------------------------------------->";

			echo "		<form style=\"position: absolute; top: 7rem; left: 50%; transform: translateX(-50%); width: 95%; max-width: 40rem; padding-bottom: 7rem; p_adding: 0px 10px 0px 10px;\" class=\"info\" id=\"search_groups\" action=\"index.php\" method=\"post\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "			<table style=\"width: 100%;\" border=0>";
			echo "				<tbody style=\"color: black;\">";

			echo "					<tr id=\"tablerow0\">";
			echo "						<td style=\"color: grey;\" class=\"search_name_container\" colspan=1>";
			echo "							<input class=\"tfield search_name_box\" type=\"text\" name=\"search_names\" id=\"search_names\" placeholder=\"Search..\" title=\"\" value=\"$search_name_filter\">";
			echo "							<p class=\"search-result\" name=\"search_result\" id=\"search_result\">-/-</p>";
			echo "							<button class=\"search_name_button\" type=\"submit\"><span class=\"fa fa-search\"></span></button>";
			echo "						</td>";
			echo "					</tr>";
			
			echo "					<tr id=\"tablerow0\">";
			echo "						<td style=\"color: grey;\" c_lass=\"search_group_container\" colspan=1>";
			echo "							<input style=\"width:100%;\" list=\"groups\" class=\"tfield se_cret_group_search-box\" type=\"text\" name=\"search_groups\" id=\"search_groups\" placeholder=\"Search by Group\" title=\"\" onchange=\"\$(this).closest('form').trigger('submit');\" value=\"$search_group_filter\">";
			echo "							<datalist id=\"groups\">";
											
											$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
											
											$groups = selectGroups($pdo, $tp_login_uname, $form, "[ ◀ OK ▶ ]", "index.php");
											//~ echo "<option value=\"\">";
											foreach ($groups as $group)	{ echo "<option value=\"$group->group_name\">";	}
			echo "							</datalist>";							
			//~ echo "							<button class=\"s_ecret_group_search-button\" type=\"button\" onclick=\"alert(\"sad\");\"><span class=\"fa fa-refresh\">po</span></button>";
			echo "						</td>";						
			echo "					</tr>";

			echo "					<tr style=\"line-height: 10px;\" id=\"tablerow0\">";
			echo "						<td style=\"text-align: center; color: grey;\" colspan=1>";
			echo "						<progress style=\"display: none; height: 5px; width: 95%;\" id=\"progressbar\" name=\"progressbar\" value=\"25\" max=\"100\"> 25% </progress>";
			echo "						</td>";
			echo "					</tr>";
			echo "					<!--";
			echo "					<script type='text/javascript'>setProgressBar('progressbar',50,100);</script> */";
			echo "					<script type='text/javascript'>var node = document.getElementById('progressbar'); node.style.display = 'inline'; node.value = 90; node.max = 100;</script> */";
			echo "					-->";

			echo "				</tbody>";
			echo "				";
			echo "			</table>";
			echo "		</form>					";


			echo "<!----------------------------------------------------------------------------->";
			echo "<!--						   HIDDEN FIELDS                                 -->";
			echo "<!----------------------------------------------------------------------------->";

			echo "		<form style=\"position: absolute; width:99%; top: 14rem; padding-bottom: 7rem; t_ransform: translateY(-50%);\" class=\"info\" action=\"index.php\" method=\"post\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "			<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			//~ Get Searched and Non Searched Secrets Records for comparison

			$records_all = 	searchGroupsByName($pdo, $tp_login_uname, '', 					'', 					$primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form,"[ ◀ OK ▶ ]","index.php");
			$records = 		searchGroupsByName($pdo, $tp_login_uname, $search_name_filter, 	$search_group_filter, 	$primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form,"[ ◀ OK ▶ ]","index.php");
			if ( $records != $records_all  ) { $search_result_data = count($records) . " / " . count($records_all); } else { $search_result_data = count($records); }
			echo "<script type='text/javascript'>setInnerHTML(\"search_result\",\"" . $search_result_data . "\");</script>";
			//~ print_r($records);

			echo "			<table style=\"width:99%; color: white; text-align: center; font-size: large;\" class=\"tble _table-bordered\" border=0>";
			echo "				<tbody>";


			//~ Determine and set state of Order Buttons

			$active = "white"; $dimmed = "dimgrey";
			$column_1_label = "Id"; $column_2_label = "Groups"; $column_3_label = "Secrets";
			$column_1_style = "color: $dimmed; border: none;"; $column_2_style = "color: $dimmed; border: none;"; $column_3_style = "color: $dimmed; border: none;"; 

			if ( $primar_column_order_fld == "groups.group_id" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id&nbsp;&uarr;";
					$column_2_label = "Groups";
					$column_3_label = "Secrets";
					$column_1_style = "color: $active; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id&nbsp;&darr;";
					$column_2_label = "Groups";
					$column_3_label = "Secrets";
					$column_1_style = "color: $active; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}
			elseif ( $primar_column_order_fld == "groups.group_name" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Groups&nbsp;&uarr;";
					$column_3_label = "Secrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Groups&nbsp;&darr;";
					$column_3_label = "Secrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}
			elseif ( $primar_column_order_fld == "group_secrets" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Groups";
					$column_3_label = "&uarr;&nbspSecrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $active; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Groups";
					$column_3_label = "&darr;&nbspSecrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $active; border: none;";
				}
			}
			else
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Groups&nbsp;&uarr;";
					$column_3_label = "Secrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Groups&nbsp;&darr;";
					$column_3_label = "Secrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}

			//~ $active = "white"; $dimmed = "dimgrey";
			//~ if ( $primar_column_order_fld == "left_column" )		{ if ( $primar_column_order_dir == "ASC" )		{ $column_2_label = "Groups&nbsp;&uarr;"; $column_3_label = "Secrets"; 	$column_2_style = "color: $active; border: none;"; 	$column_3_style = "color: $dimmed; border: none;"; }
																							//~ else 	{ $column_2_label = "Groups&nbsp;&darr;"; $column_3_label = "Secrets"; 	$column_2_style = "color: $active; border: none;"; 	$column_3_style = "color: $dimmed; border: none;"; } }
			//~ elseif ( $primar_column_order_fld == "right_column" )	{ if ( $primar_column_order_dir == "ASC" )		{ $column_2_label = "Groups"; $column_3_label = "&uarr;&nbspSecrets"; 	$column_2_style = "color: $dimmed; border: none;"; 	$column_3_style = "color: $active; border: none;"; }
																							//~ else 	{ $column_2_label = "Groups"; $column_3_label = "&darr;&nbspSecrets"; 	$column_2_style = "color: $dimmed; border: none;"; 	$column_3_style = "color: $active; border: none;"; } }
			//~ else 											{ if ( $primar_column_order_dir == "ASC" )		{ $column_2_label = "Groups&nbsp;&uarr;"; $column_3_label = "Secrets"; 	$column_2_style = "color: $active; border: none;"; 	$column_3_style = "color: $dimmed; border: none;"; }
																							//~ else 	{ $column_2_label = "Groups&nbsp;&darr;"; $column_3_label = "Secrets"; 	$column_2_style = "color: $active; border: none;"; 	$column_3_style = "color: $dimmed; border: none;"; } }

			//~ Print Order Buttons
			
			echo "					<tr style=\"height: 1rem; vertical-align: text-bottom; background-color: transparent;\">\n";
			echo "						<td colspan=1 style=\"text-align: right; width: clamp(1.0rem, 1.2rem, 6rem);\" 	><button style=\"font-size: medium; $column_1_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"groups.group_id\" 	>$column_1_label</button></td>";
			echo "						<td colspan=1 style=\"text-align: left;\" 	><button style=\"font-size: medium; $column_2_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"groups.group_name\" 	>$column_2_label</button></td>";
			echo "						<td colspan=2 style=\"text-align: right;\" 	><button style=\"font-size: medium; $column_3_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"group_secrets\" 	>$column_3_label</button></td>";
			echo "						<td colspan=1 style=\"width: 2.5rem; text-align: center;\"><label class=\"checklabel\"><input class=\"checkmark\" type=\"checkbox\" id=\"\" onClick=\"toggle(this)\" title=\"Select all / none\" value=\"\"/><span class=\"checkmark\"></span></label></td>";
			echo "						<td colspan=1 style=\"width: 1rem; text-align: center;\">&nbsp;</td>";
			echo "					</tr>\n";

			//~ Print Secrets Table Records
			
			foreach ($records as $record)
			{
				//~ 1st Secret_Name & Secret_Group - Header Table Record

				echo "				<tr style=\"height: 1rem; vertical-align: text-bottom; \" class=\"header\">\n";
				echo "					<td colspan=1 style=\"text-align: right; width: clamp(1.0rem, 1.2rem, 6rem);\" 	class=\"\"	>	<button style=\"font-size: small; white-space:nowrap; border: none;\" class=\"groupnormbutton _brighten\" type=\"submit\" name=\"view_group_button\"   value=\"$record->group_id\">" . $record->group_id . "</button></td>";
				echo "					<td colspan=1 style=\"text-align: left;\" 	class=\"\"	>	<button style=\"white-space:nowrap; border: none; color: lightgrey;\" class=\"dark_grey_white_button brighten\" type=\"submit\" name=\"view_group_button\"   value=\"$record->group_id\">" . htmlspecialchars($record->group_name, ENT_QUOTES, 'UTF-8') . "</button></td>";
				echo "					<td colspan=2 style=\"text-align: right;\" 	class=\"group\"><button style=\"font-size: small; white-space:nowrap; border: none;\" 				 class=\"groupnormbutton _brighten\" type=\"submit\" name=\"show_secrets_button\" value=\"$record->group_name\">$record->group_secrets</button></td>";
				echo "					<td colspan=1 style=\"width: 1rem; text-align: center;\"><label class=\"checklabel\"><input class=\"checkmark\" type=\"checkbox\" id=\"selected\" name=\"selected_$record->group_id\" value=\"$record->group_id\"/><span class=\"checkmark\"></span></label></td>";
			echo "						<td colspan=1 style=\"width: 1rem; text-align: center;\">&nbsp;</td>";
				echo "				</tr>\n";
			}

			echo "				<tr style=\"height: 1rem; vertical-align: text-bottom; \" class=\"header\">\n";
			echo "					<td colspan=1 style=\"text-align: left;\">	</td>";
			echo "					<td colspan=3 style=\"text-align: right;\">	</td>";
			echo "					<td colspan=1 style=\"width: 1rem; text-align: center;\"><button 	style=\"margin: 0rem 0rem 0rem 0rem; border: none;\" class=\"darkdimbutton\"  type=\"submit\" name=\"delete_selected_groups_button\" title=\"Delete selected items\" value=\"\" onclick=\"return confirm(`Are you sure you want to delete the selected groups ?`);\" formnovalidate>\u{1F5D1}</button></td>";
			echo "				</tr>\n";

			echo "				</tbody>";
			echo "			</table>";

			echo "		</form>";
			echo "		<script type='text/javascript'>window.onload = function()";
			echo "		{";
			echo "			var node = document.getElementById(\"search_names\");";
			echo "			node.selectionStart = node.selectionEnd = node.value.length;";
			echo "			node.focus();";
			echo "		}</script>";
			echo "		<script type='text/javascript'>";
			echo "		{";
			echo "			function toggle(source) { checkboxes = document.querySelectorAll('[id^=selected]'); for(var i=0, n=checkboxes.length;i<n;i++) { checkboxes[i].checked = source.checked; } }";
			echo "		}</script>";
			echo "	</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir) // $primar_column_order_fld = "left_column" or "right_column", $primar_column_order_dir = "ASC"or "DESC"
		{
			$form = "show_users_form";

			$secretCounter = 0;
			$fieldsCounter = 0;
			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "new_user", "SHOW_USERS", "show_groups", "show_secrets", "change_password", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>\n";
			//~ echo "</script>\n";

			//~ echo "<style>\n";
			//~ echo "</style>\n";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Users</p>";

			echo "<!----------------------------------------------------------------------------->";
			echo "<!--						   SEARCH FIELDS                                 -->";
			echo "<!----------------------------------------------------------------------------->";

			echo "		<form style=\"position: absolute; top: 7rem; left: 50%; transform: translateX(-50%); width: 95%; max-width: 40rem; padding-bottom: 7rem; p_adding: 0px 10px 0px 10px;\" class=\"info\" id=\"search_users\" action=\"index.php\" method=\"post\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "			<table style=\"width: 100%;\" border=0>";
			echo "				<tbody style=\"color: black;\">";

			echo "					<tr id=\"tablerow0\">";
			echo "						<td style=\"color: grey;\" class=\"search_name_container\" colspan=1>";
			echo "							<input class=\"tfield search_name_box\" type=\"text\" name=\"search_names\" id=\"search_names\" placeholder=\"Search..\" title=\"\" value=\"$search_name_filter\">";
			echo "							<p class=\"search-result\" name=\"search_result\" id=\"search_result\">-/-</p>";
			echo "							<button class=\"search_name_button\" type=\"submit\"><span class=\"fa fa-search\"></span></button>";
			echo "						</td>";
			echo "					</tr>";
			
			echo "					<tr id=\"tablerow0\">";
			echo "						<td style=\"color: grey;\" c_lass=\"search_group_container\" colspan=1>";
			echo "							<input style=\"width:100%;\" list=\"groups\" class=\"tfield se_cret_group_search-box\" type=\"text\" name=\"search_groups\" id=\"search_groups\" placeholder=\"Search by Group\" title=\"\" onchange=\"\$(this).closest('form').trigger('submit');\" value=\"$search_group_filter\">";
			echo "							<datalist id=\"groups\">";
											
											$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
											
											$users = selectUsers($pdo, $form, "[ ◀ OK ▶ ]", "index.php");
											//~ echo "<option value=\"\">";
											foreach ($users as $user)	{ echo "<option value=\"$user->user_name\">";	}
			echo "							</datalist>";							
			//~ echo "							<button class=\"s_ecret_group_search-button\" type=\"button\" onclick=\"alert(\"sad\");\"><span class=\"fa fa-refresh\">po</span></button>";
			echo "						</td>";						
			echo "					</tr>";

			echo "					<tr style=\"line-height: 10px;\" id=\"tablerow0\">";
			echo "						<td style=\"text-align: center; color: grey;\" colspan=1>";
			echo "						<progress style=\"display: none; height: 5px; width: 95%;\" id=\"progressbar\" name=\"progressbar\" value=\"25\" max=\"100\"> 25% </progress>";
			echo "						</td>";
			echo "					</tr>";
			echo "					<!--";
			echo "					<script type='text/javascript'>setProgressBar('progressbar',50,100);</script> */";
			echo "					<script type='text/javascript'>var node = document.getElementById('progressbar'); node.style.display = 'inline'; node.value = 90; node.max = 100;</script> */";
			echo "					-->";

			echo "				</tbody>";
			echo "				";
			echo "			</table>";
			echo "		</form>					";


			echo "<!----------------------------------------------------------------------------->";
			echo "<!--						   HIDDEN FIELDS                                 -->";
			echo "<!----------------------------------------------------------------------------->";

			echo "		<form style=\"position: absolute; width:99%; top: 14rem; padding-bottom: 7rem; t_ransform: translateY(-50%);\" class=\"info\" action=\"index.php\" method=\"post\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "			<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			//~ Get Searched and Non Searched Secrets Records for comparison

			$records_all = 	searchUsersByName($pdo, '', 					'', 					$primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form,"[ ◀ OK ▶ ]","index.php");
			$records = 		searchUsersByName($pdo, $search_name_filter, 	$search_group_filter, 	$primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $form,"[ ◀ OK ▶ ]","index.php");
			if ( $records != $records_all  ) { $search_result_data = count($records) . " / " . count($records_all); } else { $search_result_data = count($records); }
			echo "<script type='text/javascript'>setInnerHTML(\"search_result\",\"" . $search_result_data . "\");</script>";
			//~ print_r($records);

			echo "			<table style=\"width:99%; color: white; text-align: center; font-size: large;\" class=\"tble _table-bordered\" border=0>";
			echo "				<tbody>";


			//~ Determine and set state of Order Buttons

			$active = "white"; $dimmed = "dimgrey";
			$column_1_label = "Id"; $column_2_label = "Users"; $column_3_label = "Secrets";
			$column_1_style = "color: $dimmed; border: none;"; $column_2_style = "color: $dimmed; border: none;"; $column_3_style = "color: $dimmed; border: none;"; 

			if ( $primar_column_order_fld == "users.user_id" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id&nbsp;&uarr;";
					$column_2_label = "Users";
					$column_3_label = "Secrets";
					$column_1_style = "color: $active; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id&nbsp;&darr;";
					$column_2_label = "Users";
					$column_3_label = "Secrets";
					$column_1_style = "color: $active; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}
			elseif ( $primar_column_order_fld == "users.user_name" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Users&nbsp;&uarr;";
					$column_3_label = "Secrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Users&nbsp;&darr;";
					$column_3_label = "Secrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}
			elseif ( $primar_column_order_fld == "user_secrets" )
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Users";
					$column_3_label = "&uarr;&nbspSecrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $active; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Users";
					$column_3_label = "&darr;&nbspSecrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $dimmed; border: none;";
					$column_3_style = "color: $active; border: none;";
				}
			}
			else
			{
				if ( $primar_column_order_dir == "ASC" )
				{
					$column_1_label = "Id";
					$column_2_label = "Users&nbsp;&uarr;";
					$column_3_label = "Secrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
				else
				{
					$column_1_label = "Id";
					$column_2_label = "Users&nbsp;&darr;";
					$column_3_label = "Secrets";
					$column_1_style = "color: $dimmed; border: none;";
					$column_2_style = "color: $active; border: none;";
					$column_3_style = "color: $dimmed; border: none;";
				}
			}

			//~ $active = "white"; $dimmed = "dimgrey";
			//~ if ( $primar_column_order_fld == "left_column" )		{ if ( $primar_column_order_dir == "ASC" )		{ $column_2_label = "Users&nbsp;&uarr;"; $column_3_label = "Secrets"; 	$column_2_style = "color: $active; border: none;"; 	$column_3_style = "color: $dimmed; border: none;"; }
																							//~ else 	{ $column_2_label = "Users&nbsp;&darr;"; $column_3_label = "Secrets"; 	$column_2_style = "color: $active; border: none;"; 	$column_3_style = "color: $dimmed; border: none;"; } }
			//~ elseif ( $primar_column_order_fld == "right_column" )	{ if ( $primar_column_order_dir == "ASC" )		{ $column_2_label = "Users"; $column_3_label = "&uarr;&nbspSecrets"; 	$column_2_style = "color: $dimmed; border: none;"; 	$column_3_style = "color: $active; border: none;"; }
																							//~ else 	{ $column_2_label = "Users"; $column_3_label = "&darr;&nbspSecrets"; 	$column_2_style = "color: $dimmed; border: none;"; 	$column_3_style = "color: $active; border: none;"; } }
			//~ else 											{ if ( $primar_column_order_dir == "ASC" )		{ $column_2_label = "Users&nbsp;&uarr;"; $column_3_label = "Secrets"; 	$column_2_style = "color: $active; border: none;"; 	$column_3_style = "color: $dimmed; border: none;"; }
																							//~ else 	{ $column_2_label = "Users&nbsp;&darr;"; $column_3_label = "Secrets"; 	$column_2_style = "color: $active; border: none;"; 	$column_3_style = "color: $dimmed; border: none;"; } }

			//~ Print Order Buttons
			
			echo "					<tr style=\"height: 1rem; vertical-align: text-bottom; background-color: transparent;\">\n";
			echo "						<td colspan=1 style=\"text-align: right; width: clamp(1.0rem, 1.2rem, 6rem);\" 	><button style=\"font-size: medium; $column_1_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"users.user_id\" 	>$column_1_label</button></td>";
			echo "						<td colspan=1 style=\"text-align: left;\" 	><button style=\"font-size: medium; $column_2_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"users.user_name\" 	>$column_2_label</button></td>";
			echo "						<td colspan=2 style=\"text-align: right;\" 	><button style=\"font-size: medium; $column_3_style;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"primar_column_order_field_inp_button\" value=\"user_secrets\" 	>$column_3_label</button></td>";
			echo "						<td colspan=1 style=\"width: 1rem; text-align: center;\"><label class=\"checklabel\"><input class=\"checkmark\" type=\"checkbox\" id=\"\" onClick=\"toggle(this)\" title=\"Select all / none\" value=\"\"/><span class=\"checkmark\"></span></label></td>";
			echo "						<td colspan=1 style=\"width: 1rem; text-align: center;\">&nbsp;</td>";
			echo "					</tr>\n";

			//~ Print Secrets Table Records
			
			foreach ($records as $record)
			{
				//~ 1st User_Name & User_Secrets - Header Table Record

				echo "				<tr style=\"height: 1rem; vertical-align: text-bottom; \" class=\"header\">\n";
				echo "					<td colspan=1 style=\"text-align: right; width: clamp(1.0rem, 1.2rem, 6rem);\" 	class=\"\"	>	<button style=\"font-size: small; white-space:nowrap; border: none;\" class=\"groupnormbutton _brighten\" type=\"submit\" name=\"view_user_button\"   value=\"$record->user_id\">" . $record->user_id . "</button></td>";
				echo "					<td colspan=1 style=\"text-align: left;\" 	class=\"\"	>	<button style=\"white-space:nowrap; border: none;\" class=\"dark_grey_white_button _brighten\" type=\"submit\" name=\"view_user_button\"   value=\"$record->user_id\">" . htmlspecialchars($record->user_name, ENT_QUOTES, 'UTF-8') . "</button></td>";
				echo "					<td colspan=2 style=\"text-align: right;\" 	class=\"group\"><button style=\"font-size: small; white-space:nowrap; border: none;\" 				 class=\"groupnormbutton _brighten\" type=\"submit\" name=\"show_secrets_button\" value=\"$record->user_name\">$record->user_secrets</button></td>";
				echo "					<td colspan=1 style=\"width: 2.5rem; text-align: center;\"><label class=\"checklabel\"><input class=\"checkmark\" type=\"checkbox\" id=\"selected\" name=\"selected_$record->user_id\" value=\"$record->user_id\"/><span class=\"checkmark\"></span></label></td>";
				echo "					<td colspan=1 style=\"width: 1rem; text-align: center;\">&nbsp;</td>";
				echo "				</tr>\n";
			}

			echo "				<tr style=\"height: 1rem; vertical-align: text-bottom; \" class=\"header\">\n";
			echo "					<td colspan=1 style=\"text-align: left;\">	</td>";
			echo "					<td colspan=3 style=\"text-align: right;\">	</td>";
			echo "					<td colspan=1 style=\"width: 1rem; text-align: center;\"><button 	style=\"margin: 0rem 0rem 0rem 0rem; border: none;\" class=\"darkdimbutton\"  type=\"submit\" name=\"delete_selected_users_button\" title=\"Delete selected items\" value=\"\" onclick=\"return confirm(`Are you sure you want to delete the selected users ?`);\" formnovalidate>\u{1F5D1}</button></td>";
			echo "				</tr>\n";

			echo "				</tbody>";
			echo "			</table>";

			echo "		</form>";
			echo "		<script type='text/javascript'>window.onload = function()";
			echo "		{";
			echo "			var node = document.getElementById(\"search_names\");";
			echo "			node.selectionStart = node.selectionEnd = node.value.length;";
			echo "			node.focus();";
			echo "		}</script>";
			echo "		<script type='text/javascript'>";
			echo "		{";
			echo "			function toggle(source) { checkboxes = document.querySelectorAll('[id^=selected]'); for(var i=0, n=checkboxes.length;i<n;i++) { checkboxes[i].checked = source.checked; } }";
			echo "		}</script>";
			echo "	</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}



//~ ============================================================================
//~                               CREATE FORMS                                    
//~ ============================================================================



		function send_html_create_secret_page($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "create_secret_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "", "SHOW_SECRETS", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			echo "<script type='text/javascript'>";
			echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Secrets</p>";

			echo "		<table style=\"position: fixed; left: 0rem; top: 10rem; width: 99%;\" id=\"NewSecretHeaderTable\" border=0>";
			echo "				<tr>";
			echo "					<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>New Secret</td>";
			echo "				</tr>";
			echo "		</table>";

			echo "		<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "			<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "			<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"AddSecretTable\" border=0>";

			echo "				<tbody id=\"AddSecretTableBody\" style=\"color: black; \">";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "						<td style=\"color: grey;\" colspan=2><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"secret_name\" id=\"secret_name\" placeholder=\"Name of Secret\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=3></td>";
			echo "					</tr>					";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Group</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "						<td style=\"color: grey; \" colspan=2>";
						
			echo "							<input style=\"width:100%;\" list=\"groups\" class=\"tfield\" type=\"text\" name=\"group_name\" id=\"group_name\" placeholder=\"Name of Group\" title=\"Name of Group\" value=\"\">";
			echo "							<datalist id=\"groups\">";
												$pdo = connectDB("create_secrets_form","[ ◀ OK ▶ ]","index.php");
												$groups = selectGroups($pdo, $tp_login_uname,"create_secrets","[ ◀ OK ▶ ]","index.php");
												//~ echo "<option value=\"\">";
												foreach ($groups as $group)	{ echo "<option value=\"" . htmlspecialchars($group->group_name, ENT_QUOTES, 'UTF-8') . "\">";	}
			echo "							</datalist>";						

			echo "						</td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=3></td>";
			echo "					</tr>";			
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" ><td colspan=10>&nbsp</td></tr>";
			echo "				</tbody>";
			
			echo "				<tfoot>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "						<td style=\"width:0%\">&nbsp;</td>";
			echo "						<td style=\"width:0%\">&nbsp;</td>";
			echo "						<td style=\"text-align: center; font-size: small;\" colspan=8>";
			echo "							<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Internet Address Field\" 							onclick=\"addField('INSERT', -1, 			'update_secret_form', 'input',		'url', 					'', 'https://'	)\">	URL</button>";
			echo "							<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Email / Logon Field\" 							onclick=\"addField('INSERT', -1, 			'update_secret_form', 'input', 		'email', 				'', ''			)\">	Email</button>";
			echo "							<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Password Field (Password / Pincode / Secret)\" 	onclick=\"addField('INSERT', -1, 			'update_secret_form', 'input', 		'password', 			'', ''			)\">	Pass</button>";
			echo "							<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Generic Text Field\" 								onclick=\"addField('INSERT', -1, 			'update_secret_form', 'input', 		'text', 				'', ''			)\">	Text</button>";
			echo "							<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Multiline Text Area Field\" 						onclick=\"addField('INSERT', -1, 			'update_secret_form', 'textarea',	'textarea', 			'', ''			)\">	Note</button>";
			echo "						</td>";
			echo "					</tr>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "						<td style=\"text-align: center; 	color: black;\" colspan=10>";
			echo "							<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_secret_button\" value=\"\" >Save</button>";
			echo "							<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_secret_button\" value=\"\" formnovalidate>Cancel</button>";
			echo "						</td>";
			echo "					</tr>";

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			echo "					<tr><td colspan=10>&nbsp</td></tr>";
			echo "					<tr><td colspan=10>&nbsp</td></tr>";
			echo "					<tr><td colspan=10>&nbsp</td></tr>";
			echo "				</tfoot>";
			echo "			</table>";
			echo "	</form>";
			echo "	<script type='text/javascript'>window.onload = function() { document.getElementById(\"secret_name\").focus(); }</script>";

			echo "	</section>";
			echo "	</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_create_group_page($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "create_group_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "SHOW_GROUPS", "", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Groups</p>";

			echo "		<table style=\"position: fixed; left: 0rem; top: 10rem; width: 99%;\" id=\"NewGroupHeaderTable\" border=0>";
			echo "				<tr>";
			echo "					<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>New Group</td>";
			echo "				</tr>";
			echo "		</table>";

			echo "		<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "			<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "			<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"AddGroupTable\" border=0>";

			echo "				<tbody id=\"AddGroupTableBody\" style=\"color: black; \">";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "						<td style=\"color: grey;\" colspan=2><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"group_name\" id=\"group_name\" placeholder=\"Name of Group\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=3></td>";
			echo "					</tr>					";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" ><td colspan=10>&nbsp</td></tr>";
			echo "				</tbody>";
			
			echo "				<tfoot>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "						<td style=\"text-align: center; 	color: black;\" colspan=10>";
			echo "							<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_group_button\" value=\"\" >Save</button>";
			echo "							<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_group_button\" value=\"\" formnovalidate>Cancel</button>";
			echo "						</td>";
			echo "					</tr>";

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			echo "					<tr><td colspan=10>&nbsp</td></tr>";
			echo "					<tr><td colspan=10>&nbsp</td></tr>";
			echo "					<tr><td colspan=10>&nbsp</td></tr>";
			echo "				</tfoot>";
			echo "			</table>";
			echo "		</form>";
			echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"group_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

//~ ----------------------------------------------------------------------------

		function send_html_create_user_page($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "create_user_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "SHOW_USERS", "", "", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Users</p>";

			echo "		<table style=\"position: fixed; left: 0rem; top: 10rem; width: 99%;\" id=\"NewUserHeaderTable\" border=0>";
			echo "				<tr>";
			echo "					<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>New User</td>";
			echo "				</tr>";
			echo "		</table>";

			echo "		<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "			<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\"/>";
			echo "			<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\"/>";

			echo "			<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "			<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "			<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "			<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "			<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"AddUserTable\" border=0>";

			echo "				<tbody id=\"AddUserTableBody\" style=\"color: black; \">";			
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=6>&nbsp</td></tr>";

									//~ Role Field
									
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Role</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 90%;\" colspan=1>";
			echo "							<select style=\"width:100%;\" list=\"roles\" class=\"tfield\" type=\"text\" name=\"role_name\" id=\"role_name\" required>";
												$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php"); $roles = selectRoles($pdo, '',"create_user","[ ◀ OK ▶ ]","index.php");
												foreach ($roles as $role)	{ echo "<option selected=\"" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "</option>";	}
			echo "							</select>";						

			echo "						</td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=2></td>";
			echo "					</tr>";			

									//~ User Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 90%;\" colspan=1><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"user_name\" id=\"user_name\" placeholder=\"Name of User\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=2></td>";
			echo "					</tr>					";

									//~ Password Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Password</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 90%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"pass_word\" id=\"pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "						<td style=\"color: grey;\" width: 3rem; colspan=1><input style=\"border: none; width: 3rem;\" type=\"checkbox\" tabindex=\"-1\" tabindex=\"-1\" id=\"\" title=\"Show / unmask\" onclick=\"showPass('pass_word')\" value=\"\" </td>";
			echo "						<td style=\"color: white; width: auto; border-right: 1px solid rgba(50,50,50);\" colspan=1></td>";
			echo "					</tr>					";
			echo "				</tbody>";

								//~ Action Buttons

			echo "				<tfoot>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=6>&nbsp</td></tr>";
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "						<td style=\"text-align: center; 	color: black;\" colspan=6>";
			echo "							<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_user_button\" value=\"\" >Save</button>";
			echo "							<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_user_button\" value=\"\" formnovalidate>Cancel</button>";
			echo "						</td>";
			echo "					</tr>";

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=6>&nbsp</td></tr>";										
			echo "					<tr><td colspan=6>&nbsp</td></tr>";
			echo "					<tr><td colspan=6>&nbsp</td></tr>";
			echo "					<tr><td colspan=6>&nbsp</td></tr>";
			echo "				</tfoot>";
			echo "			</table>";
			echo "		</form>";
			echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"user_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}



//~ ============================================================================
//~                                VIEW FORMS                                    
//~ ============================================================================



		function send_html_view_secret_page($secret_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "view_secret_form";

			$fieldsCounter = 0;
			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "", "SHOW_SECRETS", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			echo "<style>";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Secrets</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"ViewSecretHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>View Secret</td>";
			echo "					</tr>";
			echo "			</table>";
		
			//~ General Form Hidden Fields for maintaining state

			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
			
			$records = selectSecretById($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php");
			$record = $records[0];

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";

			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "	<input type=\"hidden\" id=\"secret_id\" name=\"secret_id\" value=\"$record->secret_id\">";

			//~ Print Name & Group  Fields

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"ViewSecretTable\" border=0>";

			echo "		<tbody id=\"ViewSecretTableBody\" style=\"color: black; border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50);\">";
			echo "			<tr style=\"height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=8>&nbsp</td></tr>";			
			echo "			<tr style=\"height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 1rem;\" ></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" >Name</td>";
			echo "				<td style=\"color: grey; \" colspan=3><input style=\"width:100%;\" class=\"tfield\" type=\"text\" n_ame=\"secret_name\" id=\"secret_name\" placeholder=\"Name of Secret\" title=\"Name of Secret\" value=\"" . htmlspecialchars($record->secret_name, ENT_QUOTES, 'UTF-8') . "\" readonly required></td>";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: white; width: 1rem;\" ></td>";
			echo "			</tr>";

			echo "			<tr style=\"height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 1rem;\" ></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" >Group</td>";
			echo "				<td style=\"color: grey;\" colspan=3><input style=\"width:100%;\" list=\"groups\" class=\"tfield\" type=\"text\" n_ame=\"group_name\" id=\"group_name\" placeholder=\"Name of Group\" title=\"Name of Group\" value=\"" . htmlspecialchars($record->group_name, ENT_QUOTES, 'UTF-8') . "\" readonly></td>";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: white; width: 1rem;\" ></td>";
			echo "			</tr>";
			echo "			<tr style=\"height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=8>&nbsp</td></tr>";

			//~ Secret Fields Table Records

			$fields = selectFieldsBySecretId($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php");
			//~ print_r($fields);

			echo "<tr style=\"height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\" ><td colspan=8 style=\"\"></td></tr>\n";
			foreach ($fields as $field)
			{
				echo "<tr style=\"height: 1rem; vertical-align: text-bottom; \" class=\"header\">\n";
				
				///~ Field 0 (Indent)
				echo "<td style=\"color: white; width: 1rem;\" ></td>";

				///~ Field 1 (name / label field)
				echo "<td style=\"color: lightgrey; width: 10rem;\"><label for=\"textfieldvalu" . $field->field_id . "\">$field->field_name</label></td>";

				///~ Field 2 (value field)
				echo "	<td style=\"vertical-align: text-bottom; width: auto;\" colspan=3>\n";
					if 		( $field->field_type === 'password' )	{ echo "  <input style=\"width: 100%; border: none; color: lightgrey; font-family:Lucida Console,Liberation Mono,DejaVu Sans Mono,Courier New, monospace; background-color: transparent;\" class=\"tfield\" type=\"" . $field->field_type . "\" id=\"textfieldvalu" . $field->field_id . "\" n_ame=\"textfieldvalu" . $field->field_id . "\" title=\"Copy to clipboard\" onclick=\"copyValue('textfieldvalu" . $field->field_id . "')\" value=\"". decrypt_password($tp_login_pword,$field->field_value) . "\" readonly>"; }
					elseif 	( $field->field_type === 'textarea' )	{ echo "  <textarea style=\"width: 100%; border: none; color: lightgrey; background-color: transparent; overflow-y: auto; resize: vertical; border: 1px solid rgba(50,50,50);\" class=\"tfield\" type=\"" . $field->field_type . "\" id=\"textfieldvalu" . $field->field_id . "\" n_ame=\"textfieldvalu" . $field->field_id . "\" readonly rows=\"". (substr_count($field->field_value, "\n" ) + 1) . "\">". $field->field_value . "</textarea></div>"; }
					elseif 	( $field->field_type === 'url' )		{ echo "  <input style=\"width: 100%; border: none; color: lightgrey; background-color: transparent;\" class=\"tfield\" type=\"" . $field->field_type . "\" id=\"textfieldvalu" . $field->field_id . "\" n_ame=\"textfieldvalu" . $field->field_id . "\" title=\"Open URL\" onclick=\"openURL('" . $field->field_value . "')\" value=\"". $field->field_value . "\" readonly>"; }
					else 											{ echo "  <input style=\"width: 100%; border: none; color: lightgrey; background-color: transparent;\" class=\"tfield\" type=\"" . $field->field_type . "\" id=\"textfieldvalu" . $field->field_id . "\" n_ame=\"textfieldvalu" . $field->field_id . "\" title=\"Copy to clipboard\" onclick=\"copyValue('textfieldvalu" . $field->field_id . "')\" value=\"". $field->field_value . "\" readonly>"; }
				echo "  </td>";																


				///~ Field 3 (openLink & showPass checkbox column)
				echo "	<td style=\"text-align: center; vertical-align: text-bottom; width: 3rem; \" c_lass=\"dark_grey_white_button\">";
					if ( $field->field_type === 'password' ) 	{ echo "<input style=\"border: none; transparent; width: 3rem;\" class=\"dark_grey_white_button\" type=\"checkbox\" tabindex=\"-1\" id=\"showfieldvalu" . $field->field_id . "\" n_ame=\"showfieldvalu" . $field->field_id . "\" title=\"Show / unmask\" onclick=\"showPass('textfieldvalu" . $field->field_id . "')\" value=\"\">"; }
					//~ elseif ( $field->field_type === 'url' )		{ echo "<input style=\"text-align: center; border: none; c_olor: lightgrey; b_ackground-color: transparent; width: 3rem;\" class=\"dark_grey_white_button\" type=\"button\" id=\"showfieldvalu" . $field->field_id . "\" n_ame=\"showfieldvalu" . $field->field_id . "\" title=\"Copy to clipboard\" onclick=\"copyValue('textfieldvalu" . $field->field_id . "')\" value=\"&#128196;\">"; }
				echo "	</td>";


				///~ Field 4 (copy value function column)
				echo "	<td style=\"text-align: center; vertical-align: text-bottom; width: 3rem;\">";
					if ( $field->field_type === 'url' )		{ echo "<a href=\"$field->field_value\" target=\"_blanc\"><input style=\"border: none; text-align: center; transparent; width: 3rem;\" class=\"dark_grey_white_button\" type=\"button\" id=\"openfieldvalu" . $field->field_id . "\" n_ame=\"openfieldvalu" . $field->field_id . "\" title=\"Open URL\" onclick=\"openURL('" . $field->field_value . "')\" value=\"&#128279;\"></a>"; }
					else									{ echo "<input style=\"text-align: center; border: none; text-align: center; transparent; width: 3rem;\" class=\"dark_grey_white_button\" type=\"button\" id=\"showfieldvalu" . $field->field_id . "\" n_ame=\"showfieldvalu" . $field->field_id . "\" title=\"Copy to clipboard\" onclick=\"copyValue('textfieldvalu" . $field->field_id . "')\" value=\"&#128196;\">"; }
				
				
					
				echo "	</td>";

				///~ Field 5 (Indent)
				echo "<td style=\"color: white; width: 1rem;\" ></td>";
				echo "</tr>\n";
									
				$fieldsCounter++;
			}

			echo "		</tbody>";

			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=8>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"color: white; width: 1rem;\" ></td>";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=6>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\"  type=\"submit\" name=\"cancel_secret_button\" value=\"$secret_id\" formnovalidate>Back</button>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"edit_secret_button\" value=\"$secret_id\" formnovalidate>Edit</button>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"copy_secret_button\" value=\"$secret_id\" formnovalidate>Copy</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"delete_secret_button\" value=\"$secret_id\" onclick=\"return confirm(`Are you sure you want to delete: [ " . str_replace(array("'", '"'), array("\'", '\"'), $record->secret_name) . " ] ?`);\" formnovalidate>Delete</button>";
			echo "				</td>";
			echo "				<td style=\"color: white; width: 1rem;\" ></td>";
			echo "			</tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=8>&nbsp</td></tr>";										
			echo "			<tr><td colspan=8>&nbsp</td></tr>";
			echo "			<tr><td colspan=8>&nbsp</td></tr>";
			echo "			<tr><td colspan=8>&nbsp</td></tr>";
			echo "		</tfoot>";
			echo "	</table>";
			echo "</form>";
			//~ echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"secret_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_view_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "view_group_form";

			$fieldsCounter = 0;
			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "SHOW_GROUPS", "", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<style>";
			//~ echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Groups</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"ViewGroupHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>View Group</td>";
			echo "					</tr>";
			echo "			</table>";
		
			//~ General Form Hidden Fields for maintaining state

			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
			
			$records = selectGroupById($pdo, $group_id, $form, "[ ◀ OK ▶ ]", "index.php");
			$record = $records[0];

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";

			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "	<input type=\"hidden\" id=\"group_id\" name=\"group_id\" value=\"$record->group_id\">";

			//~ Print Group Field

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"ViewGroupTable\" border=0>";

			echo "		<tbody id=\"ViewGroupTableBody\" style=\"color: black;\">";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=9>&nbsp</td></tr>";			
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 1rem;\" ></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" >Group</td>";
			echo "				<td style=\"color: grey;\" colspan=3><input style=\"width:100%;\" class=\"tfield\" type=\"text\" n_ame=\"group_name\" id=\"group_name\" placeholder=\"Name of Group\" title=\"Name of Group\" value=\"" . htmlspecialchars($record->group_name, ENT_QUOTES, 'UTF-8') . "\" readonly></td>";
			echo "				<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=2></td>";
			echo "			</tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=9>&nbsp</td></tr>";
			echo "		</tbody>";

			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=9>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\"  type=\"submit\" name=\"cancel_group_button\" value=\"$group_id\" formnovalidate>Back</button>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"edit_group_button\" value=\"$group_id\" formnovalidate>Edit</button>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"copy_group_button\" value=\"$group_id\" formnovalidate>Copy</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"delete_group_button\" value=\"$group_id\" onclick=\"return confirm(`Are you sure you want to delete: [ " . str_replace(array("'", '"'), array("\'", '\"'), $record->group_name) . " ] ?`);\" formnovalidate>Delete</button>";
			echo "				</td>";
			echo "			</tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=9>&nbsp</td></tr>";										
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "		</tfoot>";
			echo "	</table>";
			echo "</form>";
			//~ echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"secret_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "view_user_form";

			$fieldsCounter = 0;
			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "SHOW_USERS", "", "", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<style>";
			//~ echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Users</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"ViewUserHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>View User</td>";
			echo "					</tr>";
			echo "			</table>";
		
			//~ General Form Hidden Fields for maintaining state

			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
			
			$records = selectUserById($pdo, $user_id, $form, "[ ◀ OK ▶ ]", "index.php");
			$record = $records[0];

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";

			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "	<input type=\"hidden\" id=\"user_id\" name=\"user_id\" value=\"$record->user_id\">";

			//~ Print User Field

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"ViewUserTable\" border=0>";

			echo "				<tbody id=\"AddUserTableBody\" style=\"color: black; \">";			
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=6>&nbsp</td></tr>";

									//~ Role Field
									
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Role</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 90%;\" colspan=1>";
			echo "							<select style=\"width:100%;\" list=\"roles\" class=\"tfield\" type=\"text\" name=\"role_name\" id=\"role_name\" value=\"$record->role_id</select>\" disabled required>";
												//~ $pdo = connectDB("create_user","[ ◀ OK ▶ ]","index.php"); $roles = selectRoles($pdo, '',"create_user","[ ◀ OK ▶ ]","index.php");
												//~ foreach ($roles as $role)	{ echo "<option selected=\"" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "</option>";	}
												$role_name = getRoleNameByUserName($pdo, $record->user_name, $form, "[ ◀ OK ▶ ]", "index.php");
												echo "<option selected=\"" . htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8') . "</option>";
			echo "							</select>";						

			echo "						</td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=2></td>";
			echo "					</tr>";			

									// User Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 90%;\" colspan=1><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"user_name\" id=\"user_name\" placeholder=\"Name of User\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"$record->user_name\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" disabled readonly required></td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=2></td>";
			echo "					</tr>					";

									//~ // Password Field

			//~ echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			//~ echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			//~ echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Password</td>";
			//~ echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			//~ echo "						<td style=\"color: grey; width: 90%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"pass_word\" id=\"pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"[ *** SET *** ]\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" disabled readonly required></td>";
			//~ echo "						<td style=\"color: grey;\" width: 3rem; colspan=1><input style=\"border: none; width: 3rem;\" type=\"checkbox\" tabindex=\"-1\" id=\"\" title=\"Show / unmask\" onclick=\"showPass('pass_word')\" value=\"\" </td>";
			//~ echo "						<td style=\"color: white; width: auto; border-right: 1px solid rgba(50,50,50);\" colspan=1></td>";
			//~ echo "					</tr>					";
			//~ echo "				</tbody>";

								//~ Action Buttons

			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=9>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\"  type=\"submit\" name=\"cancel_user_button\" value=\"$user_id\" formnovalidate>Back</button>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"edit_user_button\" value=\"$user_id\" formnovalidate>Edit</button>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"copy_user_button\" value=\"$user_id\" formnovalidate>Copy</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"delete_user_button\" value=\"$user_id\" onclick=\"return confirm(`Are you sure you want to delete: [ " . str_replace(array("'", '"'), array("\'", '\"'), $record->user_name) . " ] ?`);\" formnovalidate>Delete</button>";
			echo "				</td>";
			echo "			</tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; b_ackground-color: rgba(0, 0, 0, 0.5);\"><td colspan=9>&nbsp</td></tr>";										
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "		</tfoot>";
			echo "	</table>";
			echo "</form>";
			//~ echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"secret_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}



//~ ============================================================================
//~                               EDIT FORMS                                    
//~ ============================================================================



		function send_html_edit_secret_page($secret_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "update_secret_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "", "SHOW_SECRETS", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Secrets</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"EditSecretHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>Edit Secret</td>";
			echo "					</tr>";
			echo "			</table>";
		
			//~ General Form Hidden Fields for maintaining state
			
			$pdo = connectDB("update_secret_form","[ ◀ OK ▶ ]","index.php");
			$records = selectSecretById($pdo, $secret_id, "update_secret_form","[ ◀ OK ▶ ]","index.php");
			$record = $records[0];

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"update_secret_form\" name=\"update_secret_form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"update_secret_form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";

			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			//~ Print Name &Group Search Fields

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"AddSecretTable\" border=0>";

			echo "		<tbody id=\"AddSecretTableBody\" style=\"color: black;\">";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: grey; \" colspan=2><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"secret_name\" id=\"secret_name\" placeholder=\"Name of Secret\" title=\"Name of Secret\" value=\"" . htmlspecialchars($record->secret_name, ENT_QUOTES, 'UTF-8') . "\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "				<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=3></td>";
			echo "			</tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Group</td>";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";

			echo "				<td style=\"color: grey;\" colspan=2><input style=\"width:100%;\" list=\"groups\" class=\"tfield\" type=\"text\" name=\"group_name\" id=\"group_name\" placeholder=\"Name of Group\" title=\"Name of Group\" value=\"" . htmlspecialchars($record->group_name, ENT_QUOTES, 'UTF-8') . "\">";
			echo "				<datalist id=\"groups\">";
								$groups = selectGroups($pdo, $tp_login_uname,"update_secret_form","[ ◀ OK ▶ ]","index.php");
								//~ echo "<option value=\"\">";
								foreach ($groups as $group)	{ echo "<option value=\"" . htmlspecialchars($group->group_name, ENT_QUOTES, 'UTF-8') . "\">";	}
			echo "				</datalist>";
			echo "				</td>";
			
			echo "				<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=3></td>";
			echo "			</tr>";

			//~ Secret Fields Table Records

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			
				$fields = selectFieldsBySecretId($pdo, $secret_id, "show_secrets_form","[ ◀ OK ▶ ]","index.php");
				
				echo "<tr style=\"display: none; border-top: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" class=\"hider\"><td colspan=5 style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50);\"></td></tr>\n";
				foreach ($fields as $field)
				{
					if 		( $field->field_type === 'password' )	{ echo "<script type='text/javascript'>																			addField('UPDATE',$field->field_id, 'update_secret_form', 'input', 		'$field->field_type', 	`$field->field_name`, `" . decrypt_password($tp_login_pword,$field->field_value) . "`);</script>\n"; } // notice JS backticks `$field->field_value`
					elseif 	( $field->field_type != "textarea" )	{ echo "<script type='text/javascript'>																			addField('UPDATE',$field->field_id, 'update_secret_form', 'input', 		'$field->field_type', 	`$field->field_name`, `$field->field_value`);</script>\n"; } // notice JS backticks `$field->field_value`
					else 											{ echo "<script type='text/javascript'>																			addField('UPDATE',$field->field_id, 'update_secret_form', 'textarea',	'textarea', 			`$field->field_name`, `$field->field_value`);</script>\n"; } // notice JS backticks `$field->field_value`
				}
			
			echo "		</tbody>";

			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"width:0%\">&nbsp;</td>";
			echo "				<td style=\"width:0%\">&nbsp;</td>";
			echo "				<td style=\"text-align: center; font-size: small;\" colspan=8>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Internet Address Field\" 							onclick=\"addField('INSERT', -1, 			'update_secret_form', 'input',		'url', 					'', 'https://'	)\">	URL</button>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Email / Logon Field\" 							onclick=\"addField('INSERT', -1, 			'update_secret_form', 'input', 		'email', 				'', ''			)\">	Email</button>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Password Field (Password / Pincode / Secret)\" 	onclick=\"addField('INSERT', -1, 			'update_secret_form', 'input', 		'password', 			'', ''			)\">	Pass</button>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Generic Text Field\" 								onclick=\"addField('INSERT', -1, 			'update_secret_form', 'input', 		'text', 				'', ''			)\">	Text</button>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Multiline Text Area Field\" 						onclick=\"addField('INSERT', -1, 			'update_secret_form', 'textarea',	'textarea', 			'', ''			)\">	Note</button>";
			echo "				</td>";
			echo "			</tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=10>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_secret_button\" value=\"$secret_id\" >Save</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_secret_button\" value=\"$secret_id\" formnovalidate>Cancel</button>";
			echo "				</td>";
			echo "			</tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "		</tfoot>";

			echo "	</table>";

			echo "</form>";
			//~ echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"secret_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_edit_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "update_group_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "SHOW_GROUPS", "", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Groups</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"EditGroupHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>Edit Group</td>";
			echo "					</tr>";
			echo "			</table>";
		
			//~ General Form Hidden Fields for maintaining state
			
			$pdo = connectDB("update_group_form","[ ◀ OK ▶ ]","index.php");
			$records = selectGroupById($pdo, $group_id, "update_group_form","[ ◀ OK ▶ ]","index.php");
			$record = $records[0];

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"update_group_form\" name=\"update_group_form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"update_group_form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";

			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			//~ Print Name &Group Search Fields

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"EditGroupTable\" border=0>";

			echo "		<tbody id=\"EditGroupTableBody\" style=\"color: black;\">";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 0.25rem;\" colspan=2></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "				<td style=\"color: white; width: 0rem;\" colspan=2></td>";
			echo "				<td style=\"color: grey; \" colspan=2><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"group_name\" id=\"group_name\" placeholder=\"Name of Group\" title=\"Name of Group\" value=\"" . htmlspecialchars($record->group_name, ENT_QUOTES, 'UTF-8') . "\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "				<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=3></td>";
			echo "			</tr>";
			echo "		</tbody>";

			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=10>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_group_button\" value=\"$group_id\" >Save</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_group_button\" value=\"$group_id\" formnovalidate>Cancel</button>";
			echo "				</td>";
			echo "			</tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "		</tfoot>";

			echo "	</table>";

			echo "</form>";
			echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"group_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}


//~ ============================================================================



//~ ----------------------------------------------------------------------------

		//~ Similar to view/edit_user_form
		
		function send_html_change_password_page($user_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "change_password_form";

			$fieldsCounter = 0;
			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "", "", "CHANGE_PASSWORD", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			echo "<style>";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Users</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"ViewUserHeaderTable\" border=0>";
			echo "					<tr><td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>Change Password</td></tr>";
			echo "			</table>";
		
			//~ General Form Hidden Fields for maintaining state

			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
			
			$records = selectUserById($pdo, $user_id, $form, "[ ◀ OK ▶ ]", "index.php");
			$record = $records[0];

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";

			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "	<input type=\"hidden\" id=\"user_id\" name=\"user_id\" value=\"$record->user_id\">";

			//~ Print User Field

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"ViewUserTable\" border=0>";

			echo "				<tbody id=\"AddUserTableBody\" style=\"color: black; \">";			
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=6>&nbsp</td></tr>";

									//~ Role Field
									
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Role</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 90%;\" colspan=1>";
			echo "							<select style=\"width:100%;\" list=\"roles\" class=\"tfield\" type=\"text\" name=\"role_name\" id=\"role_name\" value=\"$record->role_id</select>\" readonly required>";
												//~ $pdo = connectDB("create_user","[ ◀ OK ▶ ]","index.php"); $roles = selectRoles($pdo, '',"create_user","[ ◀ OK ▶ ]","index.php");
												//~ foreach ($roles as $role)	{ echo "<option selected=\"" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "</option>";	}
												$role_name = getRoleNameByUserName($pdo, $record->user_name, $form, "[ ◀ OK ▶ ]", "index.php");
												echo "<option selected=\"" . htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8') . "</option>";
			echo "							</select>";						

			echo "						</td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=2></td>";
			echo "					</tr>";			

									//~ User Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 90%;\" colspan=1><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"user_name\" id=\"user_name\" placeholder=\"Name of User\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"$record->user_name\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" readonly required></td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=2></td>";
			echo "					</tr>					";

									//~ Old Password Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(12rem, 13rem, 20rem);\" colspan=1>Old&nbsp;Password</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";

			if ( authenticateUser($record->user_name, $GLOBALS["user_stnd_pass_word"], $form, "[ ◀ OK ▶ ]", "index.php") )
			{
				echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"old_pass_word\" id=\"old_pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"" . $GLOBALS["user_stnd_pass_word"] . "\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" readonly required></td>";
			}
			else
			{
				echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"old_pass_word\" id=\"old_pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"$tp_login_pword\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" readonly required></td>";
			}

			echo "						<td style=\"color: grey;\" width: 3rem; colspan=1><input style=\"border: none; width: 3rem;\" type=\"checkbox\" tabindex=\"-1\" id=\"\" title=\"Show / unmask\" onclick=\"showPass('old_pass_word')\" value=\"\" </td>";
			echo "						<td style=\"color: white; width: auto; border-right: 1px solid rgba(50,50,50);\" colspan=1></td>";
			echo "					</tr>					";
									//~ New Password Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(12rem, 13rem, 20rem);\" colspan=1>New&nbsp;Password</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"new_pass_word\" id=\"new_pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "						<td style=\"color: grey;\" width: 3rem; colspan=1><input style=\"border: none; width: 3rem;\" type=\"checkbox\" tabindex=\"-1\" id=\"\" title=\"Show / unmask\" onclick=\"showPass('new_pass_word')\" value=\"\" </td>";
			echo "						<td style=\"color: white; width: auto; border-right: 1px solid rgba(50,50,50);\" colspan=1></td>";
			echo "					</tr>					";
			echo "				</tbody>";

								//~ Action Buttons

			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=9>&nbsp</td></tr>";										
			
			if (( file_exists($GLOBALS["tiny_setup_wrapper"]) ) && ( $record->user_name == "admin" || $record->user_name == "tiny" ))
			{
				if ( DEMO )
				{
					echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=9 style=\"text-align: center; color: red;\">You can't change passwords in Demo Mode !!</td></tr>";										
					echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=9>&nbsp</td></tr>";										
				}
				else
				{
					echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=9 style=\"text-align: center; color: grey;\">It is advised to use <a href=\"../../desktop/?search=setup\" target=\"_blank\">Tiny Desktop</a> -> <a href=\"http://" . $GLOBALS["customer_domain"] . "/setup/\" target=\"_blank\">Tiny Server Setup</a> -> <span style=\"color: white;\">Set Password</span> to change your password server wide for all Internet Services (including Tiny Pass)</td></tr>";										
					echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=9>&nbsp</td></tr>";										
				}
			}

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; color: black;\" colspan=9>";

			if (DEMO)
			{
				echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_password_button\" value=\"$user_id\" disabled>Save</button>";
			}
			else
			{
				echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_password_button\" value=\"$user_id\" >Save</button>";
			}
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_password_button\" value=\"$user_id\" formnovalidate>Cancel</button>";
			echo "				</td>";
			echo "			</tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=9>&nbsp</td></tr>";										
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "			<tr><td colspan=9>&nbsp</td></tr>";
			echo "		</tfoot>";
			echo "	</table>";
			echo "</form>";
			//~ echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"secret_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}






		function send_html_edit_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "update_user_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "SHOW_USERS", "", "", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Users</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"EditGroupHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>Edit User</td>";
			echo "					</tr>";
			echo "			</table>";
		
			//~ General Form Hidden Fields for maintaining state
			
			$pdo = connectDB($form,"[ ◀ OK ▶ ]","index.php");
			$records = selectUserById($pdo, $user_id, $form, "[ ◀ OK ▶ ]", "index.php");
			$record = $records[0];
			$role_name = getRoleNameByUserName($pdo, $record->user_name, $form, "[ ◀ OK ▶ ]", "index.php");

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";

			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			//~ Print Name &Group Search Fields

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"EditUserTable\" border=0>";

			echo "				<tbody id=\"AddUserTableBody\" style=\"color: black; \">";			
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=6>&nbsp</td></tr>";

									//~ Role Field
									
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Role</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 85%;\" colspan=1>";
			echo "							<select style=\"width:100%;\" list=\"roles\" class=\"tfield\" type=\"text\" name=\"role_name\" id=\"role_name\" value=\"$role_name\" required>";
												$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php"); $roles = selectRoles($pdo, '',$form, "[ ◀ OK ▶ ]", "index.php");
												foreach ($roles as $role)
												{
													if ( $role_name == $role->role_name ) 	{ echo "<option selected=\"" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "</option>"; }
													else 									{ echo "<option value=\"" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "</option>"; }
												}
			echo "							</select>";						

			echo "						</td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=2></td>";
			echo "					</tr>";			

									//~ User Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(12rem, 13rem, 20rem);\" colspan=1>Name</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"user_name\" id=\"user_name\" placeholder=\"Name of User\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"$record->user_name\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=2></td>";
			echo "					</tr>					";

									//~ // Old Password Field

			//~ echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			//~ echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			//~ echo "						<td style=\"color: white; width: clamp(12rem, 13rem, 20rem);\" colspan=1>Old&nbsp;Password</td>";
			//~ echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";

			//~ if ( authenticateUser($record->user_name, $GLOBALS["user_stnd_pass_word"], $form, "[ ◀ OK ▶ ]", "index.php") )
			//~ {
				//~ echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"old_pass_word\" id=\"old_pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"" . $GLOBALS["user_stnd_pass_word"] . "\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			//~ }
			//~ else
			//~ {
				//~ echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"old_pass_word\" id=\"old_pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" readonly required></td>";
			//~ }

			//~ echo "						<td style=\"color: grey;\" width: 3rem; colspan=1><input style=\"border: none; width: 3rem;\" type=\"checkbox\" tabindex=\"-1\" id=\"\" title=\"Show / unmask\" onclick=\"showPass('old_pass_word')\" value=\"\" </td>";
			//~ echo "						<td style=\"color: white; width: auto; border-right: 1px solid rgba(50,50,50);\" colspan=1></td>";
			//~ echo "					</tr>					";

									//~ // New Password Field

			//~ echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			//~ echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			//~ echo "						<td style=\"color: white; width: clamp(12rem, 13rem, 20rem);\" colspan=1>New&nbsp;Password</td>";
			//~ echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			//~ echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"new_pass_word\" id=\"new_pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			//~ echo "						<td style=\"color: grey;\" width: 3rem; colspan=1><input style=\"border: none; width: 3rem;\" type=\"checkbox\" tabindex=\"-1\" id=\"\" title=\"Show / unmask\" onclick=\"showPass('new_pass_word')\" value=\"\" </td>";
			//~ echo "						<td style=\"color: white; width: auto; border-right: 1px solid rgba(50,50,50);\" colspan=1></td>";
			//~ echo "					</tr>					";
			//~ echo "				</tbody>";

								//~ Action Buttons


			echo "		<tfoot>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			
			//~ if (( file_exists($GLOBALS["tiny_setup_wrapper"]) ) && ( $record->user_name == "admin" || $record->user_name == "tiny" ))
			//~ {
				//~ echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10 style=\"text-align: center; color: grey;\">It is advised to use <a href=\"../../desktop/?search=setup\" target=\"_blank\">Tiny Desktop</a> -> <a href=\"http://" . $GLOBALS["customer_domain"] . "/setup/\" target=\"_blank\">Tiny Server Setup</a> -> <span style=\"color: white;\">Set Password</span> to change your password server wide for all Internet Services (including Tiny Pass)</td></tr>";										
				//~ echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			//~ }

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=10>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_user_button\" value=\"$user_id\" >Save</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_user_button\" value=\"$user_id\" formnovalidate>Cancel</button>";
			echo "				</td>";
			echo "			</tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "		</tfoot>";

			echo "	</table>";

			echo "</form>";
			//~ echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"user_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_copy_secret_page($secret_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "copy_secret_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "", "SHOW_SECRETS", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Secrets</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"EditSecretHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>Copy Secret</td>";
			echo "					</tr>";
			echo "			</table>";

			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
			$records = selectSecretById($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php");
			$record = $records[0];

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";
			
			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"AddSecretTable\" border=0>";

			echo "		<tbody id=\"AddSecretTableBody\" style=\"color: black;\">";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: grey; \" colspan=2><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"secret_name\" id=\"secret_name\" placeholder=\"Name of Secret\" title=\"Name of Secret\" value=\"" . htmlspecialchars($record->secret_name, ENT_QUOTES, 'UTF-8') . "\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "				<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=3></td>";
			echo "			</tr>					";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Group</td>";
			echo "				<td style=\"color: white; width: 1rem;\" colspan=2></td>";
			echo "				<td style=\"color: grey;\" colspan=2><input style=\"width:100%;\" list=\"groups\" class=\"tfield\" type=\"text\" name=\"group_name\" id=\"group_name\" placeholder=\"Name of Group\" title=\"Name of Group\" value=\"" . htmlspecialchars($record->group_name, ENT_QUOTES, 'UTF-8') . "\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" >";
			echo "				<datalist id=\"groups\">";

								$groups = selectGroups($pdo, $tp_login_uname, $form, "[ ◀ OK ▶ ]", "index.php");
								//~ echo "<option value=\"\">";
								foreach ($groups as $group)	{ echo "<option value=\"$group->group_name\">";	}

			echo "				</datalist>";

			echo "				</td>";
			echo "				<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=3></td>";
			echo "			</tr>";

			//~ Secret Fields Table Records

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			
				$fields = selectFieldsBySecretId($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php");
				
				echo "<tr style=\"display: none; border-top: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" class=\"hider\"><td colspan=5 style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50);\"></td></tr>\n";
				foreach ($fields as $field)
				{
					if 		( $field->field_type === 'password' )	{ echo "<script type='text/javascript'>																			addField('INSERT',-1,				'$form', 'input', 	'$field->field_type', 	`$field->field_name`, `" . decrypt_password($tp_login_pword,$field->field_value) . "`);</script>\n"; } // notice JS backticks `$field->field_value`
					elseif 	( $field->field_type != "textarea" )	{ echo "<script type='text/javascript'>																			addField('INSERT',-1,				'$form', 'input', 	'$field->field_type', 	`$field->field_name`, `$field->field_value`);</script>\n"; } // notice JS backticks `$field->field_value`
					else 											{ echo "<script type='text/javascript'>																			addField('INSERT',-1,				'$form', 'textarea',	'textarea', 			`$field->field_name`, `$field->field_value`);</script>\n"; } // notice JS backticks `$field->field_value`
				}
			
			echo "		</tbody>";

			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"width:0%\">&nbsp;</td>";
			echo "				<td style=\"width:0%\">&nbsp;</td>";
			echo "				<td style=\"text-align: center; font-size: small;\" colspan=8>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Internet Address Field\" 							onclick=\"addField('INSERT', -1, 			'$form', 'input',		'url', 					'', 'https://'	)\">	URL</button>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Email / Logon Field\" 							onclick=\"addField('INSERT', -1, 			'$form', 'input', 		'email', 				'', ''			)\">	Email</button>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Password Field (Password / Pincode / Secret)\" 	onclick=\"addField('INSERT', -1, 			'$form', 'input', 		'password', 			'', ''			)\">	Pass</button>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Generic Text Field\" 								onclick=\"addField('INSERT', -1, 			'$form', 'input', 		'text', 				'', ''			)\">	Text</button>";
			echo "					<button style=\"margin: 0rem 0.25rem 0rem 0.25rem;\" class=\"dark_grey_white_button\" type=\"button\" title=\"Multiline Text Area Field\" 						onclick=\"addField('INSERT', -1, 			'$form', 'textarea',	'textarea', 			'', ''			)\">	Note</button>";
			echo "				</td>";
			echo "			</tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=10>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_secret_button\" value=\"$secret_id\" >Save</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_secret_button\" value=\"$secret_id\" formnovalidate>Cancel</button>";
			echo "				</td>";
			echo "			</tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "		</tfoot>";

			echo "	</table>";

			echo "</form>";
			//~ echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"secret_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_copy_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "copy_group_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "", "SHOW_GROUPS", "", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Groups</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"CopyGroupHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>Copy Group</td>";
			echo "					</tr>";
			echo "			</table>";

			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
			$records = selectGroupById($pdo, $group_id, $form, "[ ◀ OK ▶ ]", "index.php");
			$record = $records[0];

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";
			
			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"CopyGroupTable\" border=0>";

			echo "		<tbody id=\"CopyGroupTableBody\" style=\"color: black;\">";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "				<td style=\"color: white; width: 0.25rem;\" colspan=2></td>";
			echo "				<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Name</td>";
			echo "				<td style=\"color: white; width: 0rem;\" colspan=2></td>";
			echo "				<td style=\"color: grey; \" colspan=2><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"group_name\" id=\"group_name\" placeholder=\"Name of Group\" title=\"Name of Group\" value=\"" . htmlspecialchars($record->group_name, ENT_QUOTES, 'UTF-8') . "\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "				<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=3></td>";
			echo "			</tr>					";			
			echo "		</tbody>";

			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=10>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_group_button\" value=\"$group_id\" >Save</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_group_button\" value=\"$group_id\" formnovalidate>Cancel</button>";
			echo "				</td>";
			echo "			</tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "		</tfoot>";

			echo "	</table>";

			echo "</form>";
			echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"group_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

//~ ----------------------------------------------------------------------------

		function send_html_copy_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir)
		{
			$form = "copy_user_form";

			$default_bg = $GLOBALS["default_bg"];

			send_html_header("");

			$menu_items = getMenuItems($tp_login_uname, "" , "", "", "SHOW_USERS", "", "", "", "logout", $form, "[ ◀ OK ▶ ]", "index.php");
			send_html_menu($tp_login_uname, $tp_login_pword, $search_name_filter, $search_group_filter, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir, $menu_items);

			//~ echo "<script type='text/javascript'>";
			//~ echo "</script>";

			echo "<style>	";
			echo "	table";
			echo "	{";
			echo "		border-collapse: collapse;";
			echo "		-webkit-user-select: none; /* Safari */";
			echo "		-ms-user-select: none; /* IE 10+ and Edge */";
			echo "		user-select: none; /* Standard syntax */";
			echo "		margin:0 auto;";
			echo "	}";
			echo "</style>";

			echo "<div style=\"background: radial-gradient(rgba(12, 12 ,12, 0.9), lightsteelblue); background: black;\" class=\"page-content\">";
			echo "	<section style=\"overflow-y: auto; scroll-behavior: smooth; scrollbar-gutter: auto; background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url($default_bg); background-size: 100% 100%; object-fit: fill; background-position: center;\" id=\"createlicense\" class=\"content-section\">";
			echo "	<p style=\"position: fixed; top: 50%; left: 51%; transform: translate(-0%,-52%); vertical-align: middle; text-align: center; font-size: 6.5vh; color: rgba(0,0,0,0.5);\">Users</p>";

			echo "			<table style=\"position: fixed; left: 1rem; top: 10rem; width: 99%;\" id=\"CopyUserHeaderTable\" border=0>";
			echo "					<tr>";
			echo "						<td style=\"text-align: center; font-size: x-large; color: white;\" colspan=1>Copy User</td>";
			echo "					</tr>";
			echo "			</table>";
		
			//~ General Form Hidden Fields for maintaining state
			
			$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
			$records = selectUserById($pdo, $user_id, $form, "[ ◀ OK ▶ ]", "index.php");
			$record = $records[0];
			$role_name = getRoleNameByUserName($pdo, $record->user_name, $form, "[ ◀ OK ▶ ]", "index.php");

			echo "<form style=\"width:99%; padding: 0px 10px 0px 10px;\" class=\"info\" action=\"index.php\" id=\"$form\" name=\"$form\" method=\"post\" autocomplete=\"off\">";
			echo "	<input type=\"hidden\" id=\"tinypass_formname\" name=\"tinypass_formname\" value=\"$form\">";
			
			echo "	<input type=\"hidden\" id=\"tp_login_uname\" name=\"tp_login_uname\" value=\"$tp_login_uname\">";
			echo "	<input type=\"hidden\" id=\"tp_login_pword\" name=\"tp_login_pword\" value=\"$tp_login_pword\">";

			echo "	<input type=\"hidden\" id=\"search_names\" name=\"search_names\" value=\"$search_name_filter\"/>";
			echo "	<input type=\"hidden\" id=\"search_groups\" name=\"search_groups\" value=\"$search_group_filter\"/>";

			echo "	<input type=\"hidden\" id=\"primar_column_order_fld\" name=\"primar_column_order_fld\" value=\"$primar_column_order_fld\">";
			echo "	<input type=\"hidden\" id=\"primar_column_order_dir\" name=\"primar_column_order_dir\" value=\"$primar_column_order_dir\">";
			echo "	<input type=\"hidden\" id=\"second_column_order_fld\" name=\"second_column_order_fld\" value=\"$second_column_order_fld\"/>";
			echo "	<input type=\"hidden\" id=\"second_column_order_dir\" name=\"second_column_order_dir\" value=\"$second_column_order_dir\"/>";

			//~ Print Name &Group Search Fields

			echo "	<table style=\"position: fixed; left: 0rem; top: 14rem; p_adding-bottom: 7rem; width: 99%;\" id=\"CopyUserTable\" border=0>";

			echo "				<tbody id=\"CopyUserTableBody\" style=\"color: black; \">";			
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-top: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=6>&nbsp</td></tr>";

									//~ Role Field
									
			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(7rem, 8rem, 20rem);\" colspan=1>Role</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 85%;\" colspan=1>";
			echo "							<select style=\"width:100%;\" list=\"roles\" class=\"tfield\" type=\"text\" name=\"role_name\" id=\"role_name\" value=\"$role_name\" required>";
												$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php"); $roles = selectRoles($pdo, '', $form, "[ ◀ OK ▶ ]", "index.php");
												foreach ($roles as $role)
												{
													if ( $role_name == $role->role_name ) 	{ echo "<option selected=\"" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "</option>"; }
													else 									{ echo "<option value=\"" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') . "</option>"; }
												}
			echo "							</select>";						

			echo "						</td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50); \" colspan=2></td>";
			echo "					</tr>";			

									//~ User Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(12rem, 13rem, 20rem);\" colspan=1>Name</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"tfield\" type=\"text\" name=\"user_name\" id=\"user_name\" placeholder=\"Name of User\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"$record->user_name\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "						<td style=\"color: white; width: 1rem; border-right: 1px solid rgba(50,50,50);\" colspan=2></td>";
			echo "					</tr>					";

									//~ Password Field

			echo "					<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\" id=\"tablerow0\">";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: white; width: clamp(12rem, 13rem, 20rem);\" colspan=1>New&nbsp;Password</td>";
			echo "						<td style=\"color: white; width: 1rem;\" colspan=1></td>";
			echo "						<td style=\"color: grey; width: 88%;\" colspan=1><input style=\"width:100%;\" class=\"pfield\" type=\"password\" name=\"pass_word\" id=\"pass_word\" placeholder=\"\" title=\"a-Z0-9 !@#$%^&*()_+-={}[];:<>,./?\" value=\"\" pattern=\"[a-zA-Z0-9 \[\!\@\#\$\%\^\&\*\(\)\+\-\=\{\}\[\]\;\:\<\>\,\.\/\?\']+\" required></td>";
			echo "						<td style=\"color: grey;\" width: 3rem; colspan=1><input style=\"border: none; width: 3rem;\" type=\"checkbox\" tabindex=\"-1\" id=\"\" title=\"Show / unmask\" onclick=\"showPass('pass_word')\" value=\"\" </td>";
			echo "						<td style=\"color: white; width: auto; border-right: 1px solid rgba(50,50,50);\" colspan=1></td>";
			echo "					</tr>					";
			echo "				</tbody>";

								//~ Action Buttons


			echo "		<tfoot>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";
			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\">";
			echo "				<td style=\"text-align: center; 	color: black;\" colspan=10>";
			echo "					<button  	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_white_button\" type=\"submit\" name=\"save_user_button\" value=\"$user_id\" >Save</button>";
			echo "					<button 	style=\"margin: 0rem 0.5rem 0rem 0.5rem;\" class=\"dark_grey_red_button\"  type=\"submit\" name=\"cancel_user_button\" value=\"$user_id\" formnovalidate>Cancel</button>";
			echo "				</td>";
			echo "			</tr>";

			echo "			<tr style=\"border-left: 1px solid rgba(50,50,50); border-bottom: 1px solid rgba(50,50,50); border-right: 1px solid rgba(50,50,50); height: 1rem; background-color: rgba(0, 0, 0, 0.5);\"><td colspan=10>&nbsp</td></tr>";										
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "			<tr><td colspan=10>&nbsp</td></tr>";
			echo "		</tfoot>";

			echo "	</table>";

			echo "</form>";
			//~ echo "<script type='text/javascript'>window.onload = function() { document.getElementById(\"user_name\").focus(); }</script>";

			echo "</section>";
			echo "</div>";

			send_html_post_script();
			send_html_footer("");

			exit();
		}

		//~ Used by external callers such as Tiny Setup
		function change_password($user_name, $old_pass_word, $new_pass_word, $webcall)
		{
			$form = "change_password_form";

			//~ Verify Old Password
			if ( ! authenticateUser($user_name, $old_pass_word, $form, "[ ◀ OK ▶ ]","index.php") )
			{
				if ($webcall)
				{
					dboError(array("-","-","Old Password Incorrect"), "header", $form, "authenticateUser(\"$user_name\")", "[ ◀ OK ▶ ]","index.php");
				}
				else
				{
					//~ echo "Tiny Pass Authentication failed for user: \"$user_name\"! Old Password \"$old_pass_word\" -> \"$new_pass_word\" Incorrect!\n";
					echo "Authentication failed for user: \"$user_name\" ! Change password for [Tiny Pass] manually !\n";
				}
			}
			else
			{
				$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
				
				
				$user_id = 	getUserIdByUserName($user_name, $form, "[ ◀ OK ▶ ]", "index.php");
				$role_id = getRoleIdByUserName($pdo, $user_name, $form, "[ ◀ OK ▶ ]", "index.php");

				//~ Update User
				updateUserCredentials($pdo, $role_id, $user_id, $user_name, $new_pass_word, $form, "[ ◀ OK ▶ ]", "index.php");

				//~ Reencrypt Password								
				$fields = selectSecretPasswordFieldsByUserId($pdo, $user_id, $form, "[ ◀ OK ▶ ]", "index.php");
				foreach ($fields as $field)
				{
					$field_decr = decrypt_password($old_pass_word, $field->field_value);
					
					if  ( hash($GLOBALS["user_encr_hash_type"], $field_decr) == $field->field_hash )
					{
						$field_encr = encrypt_password($new_pass_word, $field_decr);
						updateField($pdo, $field->field_id, $field->field_ordr, $field->field_name, $field_encr, $field->field_hash, $form, "[ ◀ OK ▶ ]","index.php");
					}
					else
					{
						if ($webcall)
						{
							dboError(array("-","-","Decryption failed for fields.field_id: " . $field->field_id), "header", $form, "updateUserCredentials(\"$user_name\")", "[ ◀ OK ▶ ]","index.php");
						}
						else
						{
							echo "Tiny Pass Decryption failed for user: \"$user_name\" ! fields.field_id: " . $field->field_id . " \n";
						}
					}
				}
			}
		}


//~ ============================================================================
							//~ Begin HTTP Connection
//~ ============================================================================

		//~ foreach ($_SERVER as $key => $value) { echo $key . " = " . $value. "<br>\n"; } exit();
		//~ echo "\$_SERVER\n"; print_r($_SERVER); echo "\n"; 
		//~ echo "\$_POST\n"; print_r($_POST); echo "\n"; 
		//~ echo "\$_FILES\n"; print_r($_FILES); echo "\n";

		if (! empty($_POST) || ! empty($_FILES))
		{
			if ( isset($_POST['tinypass_formname']) )
			{

				$form = $_POST['tinypass_formname'];
				
				if ( isset($_POST['tp_login_uname']) )			{ $tp_login_uname = htmlspecialchars($_POST['tp_login_uname'], ENT_QUOTES, 'UTF-8'); } 			else { $tp_login_uname = ""; }
				if ( isset($_POST['tp_login_pword']) )			{ $tp_login_pword = htmlspecialchars($_POST['tp_login_pword'], ENT_QUOTES, 'UTF-8'); } 			else { $tp_login_pword = ""; }
				
				if ( isset($_POST['search_names']) )			{ $search_names = htmlspecialchars($_POST['search_names'], ENT_QUOTES, 'UTF-8'); } 				else { $search_names = ""; }
				if ( isset($_POST['search_groups']) )			{ $search_groups = htmlspecialchars($_POST['search_groups'], ENT_QUOTES, 'UTF-8'); } 			else { $search_groups = ""; }

				//~ Maintain $primar_column_order_fld & $primar_column_order_dir					
				if ( isset($_POST['menu_primar_column_order_fld']) )	{ $primar_column_order_fld = htmlspecialchars($_POST['menu_primar_column_order_fld'], ENT_QUOTES, 'UTF-8'); } else { $primar_column_order_fld = ""; }
				if ( isset($_POST['menu_primar_column_order_dir']) )	{ $primar_column_order_dir = htmlspecialchars($_POST['menu_primar_column_order_dir'], ENT_QUOTES, 'UTF-8'); } else { $primar_column_order_dir = ""; }
				if ( isset($_POST['menu_second_column_order_fld']) )	{ $second_column_order_fld = htmlspecialchars($_POST['menu_second_column_order_fld'], ENT_QUOTES, 'UTF-8'); } else { $second_column_order_fld = ""; }
				if ( isset($_POST['menu_second_column_order_dir']) )	{ $second_column_order_dir = htmlspecialchars($_POST['menu_second_column_order_dir'], ENT_QUOTES, 'UTF-8'); } else { $second_column_order_dir = ""; }

				if ( isset($_POST['primar_column_order_fld']) )		{ $primar_column_order_fld = htmlspecialchars($_POST['primar_column_order_fld'], ENT_QUOTES, 'UTF-8'); } 		else { $primar_column_order_fld = ""; }
				if ( isset($_POST['primar_column_order_dir']) )		{ $primar_column_order_dir = htmlspecialchars($_POST['primar_column_order_dir'], ENT_QUOTES, 'UTF-8'); } 		else { $primar_column_order_dir = ""; }
				if ( isset($_POST['second_column_order_fld']) )		{ $second_column_order_fld = htmlspecialchars($_POST['second_column_order_fld'], ENT_QUOTES, 'UTF-8'); } 		else { $second_column_order_fld = ""; }
				if ( isset($_POST['second_column_order_dir']) )		{ $second_column_order_dir = htmlspecialchars($_POST['second_column_order_dir'], ENT_QUOTES, 'UTF-8'); } 		else { $second_column_order_dir = ""; }

				//~ Change $primar_column_order_fld & $primar_column_order_dir on input					
				if ( isset($_POST['primar_column_order_field_inp_button']) ) { $primar_column_order_field_inp = htmlspecialchars($_POST['primar_column_order_field_inp_button'], ENT_QUOTES, 'UTF-8'); } else { $primar_column_order_field_inp = ""; }
				
				if ($form == "login_form" )
				{                    
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]", "index.php") ) // User authed
					{
						if ( ( authenticateUser($tp_login_uname, $GLOBALS["user_stnd_pass_word"], $form, "[ ◀ OK ▶ ]", "index.php") ) && ( ! DEMO ) ) // User has default pass and needs pass change
						{
							$user_id = 	getUserIdByUserName($tp_login_uname, $form, "[ ◀ OK ▶ ]","index.php"); send_html_change_password_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, '', '', 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC');
						}
					}
					else // user not authed
					{
						send_html_login_page(3);
					}
				}
				elseif ($form == "menu_form" )
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]", "index.php") )
					{
						if 		(isset($_POST['new_secret_button'])) 		{ send_html_create_secret_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
						elseif 	(isset($_POST['new_group_button'])) 		{ send_html_create_group_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
						elseif 	(isset($_POST['new_user_button'])) 			{ send_html_create_user_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
						elseif 	(isset($_POST['show_secrets_button'])) 		{ send_html_show_secrets_page($tp_login_uname, $tp_login_pword, '', '', 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC'); }
						elseif 	(isset($_POST['show_groups_button'])) 		{ send_html_show_groups_page($tp_login_uname, $tp_login_pword, '', '', 'groups.group_name', 'ASC', 'group_secrets', 'ASC'); }
						elseif 	(isset($_POST['show_users_button'])) 		{ send_html_show_users_page($tp_login_uname, $tp_login_pword, '', '', 'users.user_name', 'ASC', 'user_secrets', 'ASC'); }
						elseif 	(isset($_POST['change_password_button']))	{ $user_id = htmlspecialchars($_POST['change_password_button'], ENT_QUOTES, 'UTF-8'); send_html_change_password_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
						elseif 	(isset($_POST['logout_button']))			{ send_html_login_page(0); }
					}
					else { send_html_login_page(3); }
				}
				elseif ($form == "show_secrets_form" )
				{
					if 	( isset($_POST['primar_column_order_field_inp_button']) )
					{
						//~ If same fields clicked
						if ( $primar_column_order_field_inp == "secrets.secret_id" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = $primar_column_order_field_inp;
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						elseif 	( $primar_column_order_field_inp == "secrets.secret_name" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = "groups.group_name";
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						elseif 	( $primar_column_order_field_inp == "groups.group_name" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = "secrets.secret_name";
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
					}

					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						$user_id = 	getUserIdByUserName($tp_login_uname, $form, "[ ◀ OK ▶ ]","index.php");
						
						if (isset($_POST['view_secret_button']))
						{
							$secret_id = htmlspecialchars($_POST['view_secret_button'], ENT_QUOTES, 'UTF-8');
							send_html_view_secret_page($secret_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['view_group_button']))
						{
							$group_id = htmlspecialchars($_POST['view_group_button'], ENT_QUOTES, 'UTF-8');
							send_html_view_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['delete_selected_secrets_button']))
						{
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");
							foreach ($_POST as $key => $value)
							{								
								if ( preg_match('/selected/', $key, $matches))
								{
									//~ echo "textfieldvalue: " . $key . " value: " . $value. "<br>\n";																		
									$secret_id="$value";
									deleteSecretById($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php");
								}
							}
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['export_selected_secrets_to_tinypass_format_to_csv_file_button']))
						{
							//~ $secrets = [];
							$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
//~ ----------------------------------------------------------------------------
//~ 						Build the Ouput Array
//~ ----------------------------------------------------------------------------

							//~ Add Fields Header to output array
							$csv_records[] = array_values(EXPORT_CSV_FORMATS["TinyPass"]["field_names1"]); // 1st header record
							
							$line_counter = 0;
							foreach ($_POST as $key => $value)
							{								
								if ( preg_match('/^selected/', $key, $matches))
								{
									//~ echo "textfieldvalue: " . $key . " value: " . $value. "<br>\n";
									$secret_id="$value";
									$secrets = selectSecretById($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php"); $secret = $secrets[0];									

									//~ Add Secret record to output array
									$csv_records[] = array($secret->secret_name, $secret->group_name, "", "", "", ""); // secret record

									$line_counter++;

									//~ echo "secret id: ". $secret->secret_id . "<BR>\n";
									$fields = selectFieldsBySecretId($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php");
									//~ print_r($fields); echo "<BR>\n";

									//~ Add Secret Fields records to output array
									foreach ($fields as $field)
									{
										//~ If it's a password field decrypt it
										if ( $field->field_type === 'password' )	{ $csv_records[] = array("", "", "", $field->field_name, $field->field_type, decrypt_password($tp_login_pword,$field->field_value)); }
										else 										{ $csv_records[] = array("", "", "", $field->field_name, $field->field_type, $field->field_value); }
									}
								}
							}
							
							//~ print_r($csv_records); echo "<BR>\n";

//~ ----------------------------------------------------------------------------
//~ 						Write the Ouput Array
//~ ----------------------------------------------------------------------------

							if ( $line_counter > 0 )
							{								
								//~ ob_start();

								///~ ob_get_contents — Return the contents of the output buffer
								///~ ob_flush — Flush (send) the return value of the active output handler
								///~ ob_end_flush — Flush (send) the return value of the active output handler and turn the active output buffer off
								///~ ob_end_clean — Clean (erase) the contents of the active output buffer and turn it off

								header('Content-Type: text/csv; charset=utf-8');
								header("Content-Disposition: attachment; filename=". $GLOBALS['tiny_pass_export_file'] . "_server_". TINY_PASS_HOST . "_user_${tp_login_uname}" . "_search_names_${search_names}_". "search_groups_${search_groups}.csv");
								//~ header('Pragma: no-cache'); // May not be longer support by browsers
								//~ header('Expires: 0');

								///~ Writing records to output

								if ( ($output_file_handle = fopen( 'php://output', 'w' )) !== FALSE ) { foreach( $csv_records as $csv_record )	{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } } fclose( $output_file_handle );
								
								//~ ob_end_clean();								
								
								exit(); //~ Don't parse any non file export related HTML code inside this codeblock as it's addded to the filehandle corrupting export data so force exit
							}
							else
							{
								send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
							}
						}
						elseif (isset($_POST['export_selected_secrets_to_non_tiny_pass_csv_format_button']))
						{
							//~ $secrets = [];
							$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
//~ ----------------------------------------------------------------------------
//~ 						Build the Ouput Array
//~ ----------------------------------------------------------------------------

							//~ Add Field Headers to the output arrays

							$csv_records_edge_2[] = 		array_values(EXPORT_CSV_FORMATS["Edge_2"]["field_names1"]);			// 4
							$csv_records_1password_2[] = 	array_values(EXPORT_CSV_FORMATS["1Password_2"]["field_names1"]);	// 4
							$csv_records_chrome_2[] = 		array_values(EXPORT_CSV_FORMATS["Chrome_2"]["field_names1"]);		// 5
							$csv_records_keepass_2[] = 		array_values(EXPORT_CSV_FORMATS["KeePass_2"]["field_names1"]);		// 5
							$csv_records_dashlane_2[] = 	array_values(EXPORT_CSV_FORMATS["Dashlane_2"]["field_names1"]);		// 7
							$csv_records_firefox_2[] = 		array_values(EXPORT_CSV_FORMATS["Firefox_2"]["field_names1"]);		// 9
							$csv_records_1password2_2[] = 	array_values(EXPORT_CSV_FORMATS["1Password2_2"]["field_names1"]);	// 9
							$csv_records_bitwarden_2[] = 	array_values(EXPORT_CSV_FORMATS["Bitwarden_2"]["field_names1"]);	// 11
							$csv_records_teampass_2[] = 	array_values(EXPORT_CSV_FORMATS["TeamPass_2"]["field_names1"]);		// 12
							$csv_records_nordpass_2[] = 	array_values(EXPORT_CSV_FORMATS["NordPass_2"]["field_names1"]);		// 19

							$line_counter = 0;
							$format_found = false;
							$select_secrets_found = false;
							
							foreach ($_POST as $key => $value)
							{
								if ( preg_match('/^selected/', $key, $matches))
								{
									$select_secrets_found = true;
									
									//~ echo "textfieldvalue: " . $key . " value: " . $value. "<br>\n";
									$secret_id="$value";
									$secrets = selectSecretById($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php"); $secret = $secrets[0];									

									//~ Add Secret record to output array

									$line_counter++;

									//~ echo "secret id: ". $secret->secret_id . "<BR>\n";
									$fields = selectFieldsBySecretId($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php");
									//~ print_r($fields); echo "<BR>\n";

									$field_names = array_column($fields, 'field_name');
									$field_types = array_column($fields, 'field_type');
									$field_values = array_column($fields, 'field_value');
									
									$counted_num_of_fields = count($field_names);

									//~ print_r(array_values($field_names)); echo "<BR>\n"; exit;

									if ( $counted_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["1Password_2"] || $std_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["Edge_2"] ) 						// 4
									{
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["Edge_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_edge_2[] = $values_record;
										}
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["1Password_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_1password_2[] = $values_record;
										}
									}
									if ( $counted_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["Chrome_2"] || $std_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["KeePass_2"] )						// 5
									{
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["Chrome_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_chrome_2[] = $values_record;
										}
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["KeePass_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_keepass_2[] = $values_record;
										}
									}
									if ( $counted_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["Dashlane_2"] )																								// 12
									{
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["Dashlane_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_dashlane_2[] = $values_record;
										}
									}
									if ( $counted_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["Firefox_2"] || $counted_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["1Password2_2"] )				// 9
									{
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["Firefox_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_firefox_2[] = $values_record;
										}
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["1Password2_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_1password2_2[] = $values_record;
										}
									}
									if ( $counted_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["Bitwarden_2"] )																								// 12
									{
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["Bitwarden_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_bitwarden_2[] = $values_record;
										}
									}
									if ( $counted_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["TeamPass_2"] )																								// 12
									{
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["TeamPass_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_teampass_2[] = $values_record;
										}
									}
									if ( $counted_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS["NordPass_2"] )																								//19
									{
										if ( count(array_diff(array_map('strtolower', $field_names), array_map('strtolower', array_values(EXPORT_CSV_FORMATS["NordPass_2"]["field_names1"])) )) == 0 )
										{
											$format_found = true; $values_record = array();
											for ($row = 0; $row < count(array_values($field_names)); $row++)
											{
												if ( array_values($field_types)[$row] === 'password' )	{ array_push($values_record,decrypt_password($tp_login_pword,array_values($field_values)[$row])); }
												else 													{ array_push($values_record,								 array_values($field_values)[$row]); }
											} 	$csv_records_nordpass_2[] = $values_record;
										}
									}
								}
							}

//~ ----------------------------------------------------------------------------
//~ 						Write the Ouput Arrays
//~ ----------------------------------------------------------------------------

							if ( $select_secrets_found )
							{
								$section = "--------------------------------------------------------------------------------" . PHP_EOL;
								if ( ! $format_found )
								{
									echo "No NON Tiny Pass fields (for supported formats) amongs the selected records found !\n<br>";												
									send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
								}
								else
								{
									if ( ($output_file_handle = fopen( 'php://output', 'w' )) !== FALSE )
									{
										header('Content-Type: text/csv; charset=utf-8');
										header("Content-Disposition: attachment; filename=". $GLOBALS['non_tiny_pass_export_file'] . "_server_". TINY_PASS_HOST . "_user_${tp_login_uname}" . "_search_names_${search_names}_". "search_groups_${search_groups}.csv");

										fwrite($output_file_handle, "Please remove all non field lines (such as these comments) and empty lines !" . PHP_EOL);
										fwrite($output_file_handle, "Copy each below [ Format Export Section ] (only the lines in between \"--------\")" . PHP_EOL . "into a seperate file before it can be imported by the related Password Manager." . PHP_EOL . PHP_EOL);
										if ( count($csv_records_edge_2) > 1 ) 		{ fwrite($output_file_handle, "[ Edge Export Section ]" . 		PHP_EOL . $section); foreach( $csv_records_edge_2 as $csv_record )			{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_1password_2) > 1 ) 	{ fwrite($output_file_handle, "[ 1Password Export Section ]" . 	PHP_EOL . $section); foreach( $csv_records_1password_2 as $csv_record )		{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_chrome_2) > 1 ) 	{ fwrite($output_file_handle, "[ Chrome Export Section ]" . 	PHP_EOL . $section); foreach( $csv_records_chrome_2 as $csv_record )		{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_keepass_2) > 1 ) 	{ fwrite($output_file_handle, "[ KeePass Export Section ]" . 	PHP_EOL . $section); foreach( $csv_records_keepass_2 as $csv_record )		{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_dashlane_2) > 1 ) 	{ fwrite($output_file_handle, "[ Dashlane Export Section ]" . 	PHP_EOL . $section); foreach( $csv_records_dashlane_2 as $csv_record )		{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_firefox_2) > 1 ) 	{ fwrite($output_file_handle, "[ Firefox Export Section ]" . 	PHP_EOL . $section); foreach( $csv_records_firefox_2 as $csv_record )		{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_1password2_2) > 1 ) { fwrite($output_file_handle, "[ 1Password2 Export Section ]" . PHP_EOL . $section); foreach( $csv_records_1password2_2 as $csv_record )	{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_bitwarden_2) > 1 ) 	{ fwrite($output_file_handle, "[ Bitwarden Export Section ]" . 	PHP_EOL . $section); foreach( $csv_records_bitwarden_2 as $csv_record )		{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_teampass_2) > 1 ) 	{ fwrite($output_file_handle, "[ TeamPass Export Section ]" . 	PHP_EOL . $section); foreach( $csv_records_teampass_2 as $csv_record )		{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
										if ( count($csv_records_nordpass_2) > 1 )	{ fwrite($output_file_handle, "[ NordPass Export Section ]" . 	PHP_EOL . $section); foreach( $csv_records_nordpass_2 as $csv_record )		{ 	fputcsv( $output_file_handle,$csv_record,",","\"" ); } fwrite($output_file_handle, $section . PHP_EOL); }
						
										fclose( $output_file_handle );
										exit(); //~ Don't parse any non file export related HTML code inside this codeblock as it's addded to the filehandle corrupting export data so force exit
									} 
								}
							}
							else
							{
									send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
							}

							///~ print_r($csv_records); echo "<BR>\n";

						}
						elseif ( isset($_FILES['import_exported_secrets_in_limited_format_from_csv_file_button']) || isset($_FILES['import_exported_secrets_in_original_format_from_csv_file_button']) )
						{
							$filesArray1 = $_FILES['import_exported_secrets_in_limited_format_from_csv_file_button'];
							$filesArray2 = $_FILES['import_exported_secrets_in_original_format_from_csv_file_button'];
							
							if ( $filesArray1['error'] == UPLOAD_ERR_OK ) { $filesArray = $filesArray1; $limited_format_requested = true; } 	else { $limited_format_requested = false; }
							if ( $filesArray2['error'] == UPLOAD_ERR_OK ) { $filesArray = $filesArray2; $original_format_requested = true; }	else { $original_format_requested = false; }
														
							if ( $filesArray['error'] !== UPLOAD_ERR_OK )
							{
								$uploadErrors = array(
														0 => 'There is no error, the file uploaded with success',
														1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
														2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
														3 => 'The uploaded file was only partially uploaded',
														4 => 'No file was uploaded',
														6 => 'Missing a temporary folder',
														7 => 'Failed to write file to disk.',
														8 => 'A PHP extension stopped the file upload.',
													);
								echo "Import Upload Error: " . $uploadErrors[$filesArray['error']] . "<br>\n";
							}
							else
							{
								$file_name = $filesArray["name"];
								$file_type = $filesArray["type"];
								$file_size = $filesArray["size"];
								$tmp_name =  $filesArray["tmp_name"];

								//~ $file_name = $_FILES["import_secrets_in_original_format_from_csv_file_button"]["name"];
								//~ $file_type = $_FILES["import_secrets_in_original_format_from_csv_file_button"]["type"];
								//~ $file_size = $_FILES["import_secrets_in_original_format_from_csv_file_button"]["size"];
								//~ $tmp_name =  $_FILES["import_secrets_in_original_format_from_csv_file_button"]["tmp_name"];

								///~ echo "Upload: " . $file_name . "<br>";
								///~ echo "Type: " . $file_type . "<br>";
								///~ echo "Size: " . ($file_size) . " Bytes<br>";
								///~ echo "Stored in: " . $tmp_name . "<br>";

								//~ It must be a CSV

								if ( $file_type !== EXPORT_FILE_TYPE )
								{
									echo "Imported file: \"$file_name\" not of type \"". EXPORT_FILE_TYPE . "\"<br>";
									send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
								}
								else
								{
									
									//~ There must be data in it
									
									if ( $file_size == 0 )
									{
										echo "Imported file: \"$file_name\" empty<br>";
										send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
									}
									else
									{										
										$records = [];

										//~ After upload open the tmp file
										if (($input_file_handle = fopen($tmp_name, "r")) !== FALSE)
										{											
//~ ----------------------------------------------------------------------------
//~ 						Header Quantity Validation
//~ ----------------------------------------------------------------------------

											//~ Row & Field Validation

											$line_counter = 0; $std_num_of_fields = 0; $lowest_counted_num_of_fields = 0; $highest_counted_num_of_fields = 0;

											while (($record = fgetcsv($input_file_handle,0,",",'"')) !== FALSE) // Old Way
											{
												$line_counter++;
												$counted_num_of_fields = count($record);
												
												//~ 1st row sets the standard number of rows that may not be defiated from
												
												if ( $line_counter == 1 )
												{
													$lowest_counted_num_of_fields = $counted_num_of_fields; $highest_counted_num_of_fields = $counted_num_of_fields;
													
													//~ Check if the Number of fields match the the known CSV Formats
													
													if (! in_array($counted_num_of_fields, array_values(EXPORT_CSV_FORMATS_FIELD_LENGTHS)))
													{
														echo "Error in file: \"$file_name\" at row: $line_counter: Unsupported number of header fields: [<span style=\"color: red;\">$counted_num_of_fields</span>]/[<span style=\"color: yellowgreen;\">". EXPORT_CSV_FORMATS_FIELD_DESC . "</span>]\n<br>";
														echo "Invalid  1st Input Record Header: [". ($line_counter + 1) . "]: "; foreach ($record as $field) { echo trim($field) . ", "; } echo "\n<br>";
														fclose($input_file_handle);
														
														send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
													}
													else { $std_num_of_fields = $counted_num_of_fields; }
												}
												else // validate forbidden field number variations
												{
													//~ Check if the number of fields in the remaining rows are equal to the 1st row
													
													if ( $counted_num_of_fields < $lowest_counted_num_of_fields ) { $lowest_counted_num_of_fields = $counted_num_of_fields; }
													if ( $counted_num_of_fields > $highest_counted_num_of_fields ) { $highest_counted_num_of_fields = $counted_num_of_fields; }
													
													if ( $lowest_counted_num_of_fields != $highest_counted_num_of_fields || $counted_num_of_fields != $std_num_of_fields )
													{
														echo "Error in file: \"$file_name\" at line: $line_counter: ";
														//~ for ($c=0; $c < $counted_num_of_fields; $c++) { echo "[" . ($c + 1) . "]=\"$record[$c]\" "; }
														echo "wrong num of fields: [<span style=\"color: red;\">$counted_num_of_fields</span>]/[<span style=\"color: yellowgreen;\">$std_num_of_fields</span>] Corruption at line: $line_counter ?\n<br>";

														fclose($input_file_handle);														
														send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
													}
												}
												//~ echo "line_counter $line_counter | std_num_of_fields $std_num_of_fields | lowest_counted_num_of_fields $lowest_counted_num_of_fields | highest_counted_num_of_fields $highest_counted_num_of_fields\n<br>";
											}
											
//~ ----------------------------------------------------------------------------
//~ 						Header Quality Validation
//~ ----------------------------------------------------------------------------

											rewind($input_file_handle);

											//~ Format num of standard fields (supported format) Validation



											if (($record = fgetcsv($input_file_handle,0,",",'"')) !== FALSE) //~ Delete unicode BOM
											{
												$record[0] = ltrim($record[0]); // The PHP file handle inserts 4 extra spaces at the very start of the file (affecting header field 1 causing header match failures) which needs to be trimmed / removed at import
												foreach (array_keys(EXPORT_CSV_FORMATS) as $key)
												{
													if ( ! preg_match('/^.+_2$/', $key, $matches))
													{
														//~ echo "$key\n<br>";
														if ( $std_num_of_fields == EXPORT_CSV_FORMATS_FIELD_LENGTHS[$key] )
														{
															$line_counter = 0;
															$format_found = false;

															if ( count(array_diff(array_map('strtolower', $record), array_map('strtolower', array_values(EXPORT_CSV_FORMATS[$key]["field_names1"])) )) == 0 )	{ $format_found = true; if ( $limited_format_requested ) { $format = $key; } else { if ( ${key} == "TinyPass" )  { $format = $key; } else { $format = $key . "_2"; } } break;	}
														}
													}
												}
											}
											else
											{
																	echo "Error: no lines found in file: \"$file_name\"\n<br>";
																	fclose($input_file_handle);
																	send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
											}

											if (! format_found)
											{
																	echo "Error in file: \"$file_name\"\n<br>";
																	fclose($input_file_handle);
																	send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
											}


//~ ----------------------------------------------------------------------------
//~ 							Records Processing
//~ ----------------------------------------------------------------------------

													
											if ( $format_found && $format != "TinyPass" ) 		// Non Header Rows
											{
												$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
												
												///~ echo "Processing General Record: [". ($line_counter + 1) . "]: "; foreach ($record as $field) { echo trim($field) . ", "; } echo "\n<br>";
												
												while (($record = fgetcsv($input_file_handle,0,",",'"')) !== FALSE) //~ Delete unicode BOM
												{
													$field_counter = 0;

													///~ echo "Inserting  Secret  Record: [". ($line_counter + 1) . "]: $record[EXPORT_CSV_FORMATS[$format][\"group_name\"]] | $format |  |  |  |  \n<br>";
													if ( EXPORT_CSV_FORMATS[$format]["secret_group"] > -1 ) { $group_id = insertGroup($pdo, $user_id, $record[EXPORT_CSV_FORMATS[$format]["secret_group"]], $form, "[ ◀ OK ▶ ]", "index.php"); }	 //~ Insert group (gets ignored when it exists)
													else 													{ $group_id = insertGroup($pdo, $user_id, $format, 												$form, "[ ◀ OK ▶ ]", "index.php"); }					 //~ Insert group (gets ignored when it exists)
													
													$lastInsertSecretId = insertSecret($pdo, $user_id, $group_id, $record[EXPORT_CSV_FORMATS[$format]["secret_name"]], $form, "[ ◀ OK ▶ ]", "index.php");
													///~ echo "<br>\ninsertSecret(" . $user_id . ", " . $group_id . ", " . record[EXPORT_CSV_FORMATS[$format][\"secret_name\"]] . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";
													$fieldOrderCounter = 0;

													foreach($record as $field)
													{														
														//~ "secret_name" 	=> 0,
														//~ "secret_group" 	=> -1,
														//~ "field_names1" 	=> [ "Name",		"URL",			"Username",		"Password" 	],
														//~ "field_types" 	=> [ "name",		"url",			"text",			"password" 	], 						// URL, Email, Pass, Text, Textarea
														//~ "field_orders" 	=> [ -1,			0,				1,				2 			],						// -1, nr (-1 = ignore field)

														//~ Non Ignored Field

														if ( EXPORT_CSV_FORMATS[$format]["field_orders"][$field_counter] > -1 )
														{
															///~ other -> field_name + field_type + field_value 
															$field_order_fld = EXPORT_CSV_FORMATS[$format]["field_orders"][$field_counter]; 	$field_name_fld = EXPORT_CSV_FORMATS[$format]["field_names2"][$field_counter]; 	$field_type_fld = EXPORT_CSV_FORMATS[$format]["field_types"][$field_counter]; 			$field_value_fld = $field;
															///~ echo "Inserting  Field   Record: [". ($line_counter + 1) . "]:  |  | $field_order_fld | $field_name_fld | $field_type_fld | $field_value_fld\n<br>";
															if ( $field_type_fld == "password" ) 	{ insertField($pdo, $user_id, $lastInsertSecretId, $field_order_fld, $field_name_fld, $field_type_fld, encrypt_password($tp_login_pword,$field_value_fld), hash($GLOBALS["user_encr_hash_type"], $field_value_fld), 	$form, "[ ◀ OK ▶ ]","index.php"); }
															else									{ insertField($pdo, $user_id, $lastInsertSecretId, $field_order_fld, $field_name_fld, $field_type_fld, $field_value_fld, "", $form, "[ ◀ OK ▶ ]","index.php"); }
														}
														
														$field_counter++;
														$fieldOrderCounter++;
													}
													$line_counter++;
												}
												
												fclose($input_file_handle);
												send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
											}
											elseif ( $format_found && $format == "TinyPass" ) 		// Non Header Rows
											{
												//~ "secret_name" 	=> 0,
												//~ "secret_group" 	=> -1,
												//~ "field_names1" 	=> [ "Name",		"URL",			"Username",		"Password" 	],
												//~ "field_types" 	=> [ "name",		"url",			"text",			"password" 	], 						// URL, Email, Pass, Text, Textarea
												//~ "field_orders" 	=> [ -1,			0,				1,				2 			],						// -1, nr (-1 = ignore field)
												//~ EXPORT_CSV_FORMATS[$format]["field_names1"][$field_counter]

												$line_counter = 0;
												$tinypass_format = false;
												
												while (($record = fgetcsv($input_file_handle, 0, ",", '"')) !== FALSE)
												{
													
													$pdo = connectDB($form, "[ ◀ OK ▶ ]", "index.php");
													
													///~ echo "Processing General Record: [". ($line_counter + 1) . "]: "; foreach ($record as $field) { echo trim($field) . ", "; } echo "\n<br>";
													
													//~ "SecretName","GroupName","FieldOrder","FieldName","FieldType","FieldValue"											

													if ( ! empty($record[0]))
													{
														//~ echo "Inserting  Secret  Record: [". ($line_counter + 1) . "]: $record[0] | $record[1] | $record[2] | $record[3] | $record[4] | $record[5]\n<br>";
														///~ Insert group (gets ignored when it exists)
														$group_id = insertGroup($pdo, $user_id, $record[1], $form, "[ ◀ OK ▶ ]", "index.php");

														$lastInsertSecretId = insertSecret($pdo, $user_id, $group_id, $record[0], $form, "[ ◀ OK ▶ ]", "index.php");
														//~ echo "<br>\ninsertSecret(" . $user_id . ", " . $group_id . ", " . $record[0] . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";

														$fieldOrderCounter = 0;
													}
													elseif ( ! empty($record[3]) )
													{
														if ( ! empty($record[2]) ) { $fieldOrderCounter = $record[2]; } else { $record[2] = $fieldOrderCounter; }
														
														//~ echo "Inserting  Field   Record: [". ($line_counter + 1) . "]: $record[0] | $record[1] | $record[2] | $record[3] | $record[4] | $record[5]\n<br>";
														if 		( $record[4] === "password" ) 	{ insertField($pdo, $user_id, $lastInsertSecretId, $record[2], $record[3], $record[4], encrypt_password($tp_login_pword,$record[5]), 	hash($GLOBALS["user_encr_hash_type"], $record[5]), 	$form, "[ ◀ OK ▶ ]","index.php"); }
														else 									{ insertField($pdo, $user_id, $lastInsertSecretId, $record[2], $record[3], $record[4], $record[5],										"", 												$form, "[ ◀ OK ▶ ]","index.php"); }
														$fieldOrderCounter++;
													}
													
													$line_counter++;
												} // while lines in tmp file
												
												fclose($input_file_handle);
												send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
											}
											else // unsupported num of fields
											{
												echo "Error in file: \"$file_name\" at row: $line_counter: ";
												echo "Unsupported Header Format: [". ($line_counter + 1) . "]: "; foreach ($record 										as $field) { echo $field . ", "; } echo "\n<br>";												
												fclose($input_file_handle);
												
												send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
											}
										}
										else
										{
											echo "Import Open Error: could not open uploaded file \"$tmp_name\" <br>";
											send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
										}
									} // uploaded file empty
								} // not a csv file    
							} // upload not okay
						} // unknown button pressed
						else
						{
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "show_groups_form" )
				{
					if 	( isset($_POST['primar_column_order_field_inp_button']) )
					{
						//~ If same fields clicked
						if ( $primar_column_order_field_inp == "groups.group_id" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = $primar_column_order_field_inp;
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						elseif 	( $primar_column_order_field_inp == "groups.group_name" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = "group_secrets";
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						elseif 	( $primar_column_order_field_inp == "group_secrets" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = "groups.group_name";
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
					}

					//~ if 	( isset($_POST['primar_column_order_field_inp_button']) )
					//~ {
						//~ if 		( $primar_column_order_field_inp == $primar_column_order_fld )	{ if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; } else { $primar_column_order_dir = "ASC"; } }
						//~ elseif 	( $primar_column_order_field_inp != $primar_column_order_fld ) 										{ $primar_column_order_fld = $primar_column_order_field_inp; $primar_column_order_dir = "ASC"; }
						//~ send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
					//~ }

					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if (isset($_POST['view_group_button']))
						{
							$group_id = htmlspecialchars($_POST['view_group_button'], ENT_QUOTES, 'UTF-8');
							send_html_view_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['show_secrets_button']))
						{
							$search_groups = htmlspecialchars($_POST['show_secrets_button'], ENT_QUOTES, 'UTF-8');
								send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC');
							//~ send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['delete_selected_groups_button']))
						{
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");
							foreach ($_POST as $key => $value)
							{								
								if ( preg_match('/selected/', $key, $matches))
								{
									//~ echo "textfieldvalue: " . $key . " value: " . $value. "<br>\n";																		
									$group_id="$value";
									deleteGroupById($pdo, $group_id, $form, "[ ◀ OK ▶ ]", "index.php");
								}
							}
							send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "show_users_form" )
				{
					if 	( isset($_POST['primar_column_order_field_inp_button']) )
					{
						//~ If same fields clicked
						if ( $primar_column_order_field_inp == "users.user_id" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = $primar_column_order_field_inp;
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						elseif 	( $primar_column_order_field_inp == "users.user_name" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = "user_secrets";
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						elseif 	( $primar_column_order_field_inp == "user_secrets" )
						{
							$primar_column_order_fld = $primar_column_order_field_inp;
							$second_column_order_fld = "users.user_name";
							if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; $second_column_order_dir = "ASC"; } else { $primar_column_order_dir = "ASC"; $second_column_order_dir = "ASC"; }
						}
						send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
					}

					//~ if 	( isset($_POST['primar_column_order_field_inp_button']) )
					//~ {
						//~ if 		( $primar_column_order_field_inp == $primar_column_order_fld )	{ if ( $primar_column_order_dir == "ASC" ) { $primar_column_order_dir = "DESC"; } else { $primar_column_order_dir = "ASC"; } }
						//~ elseif 	( $primar_column_order_field_inp != $primar_column_order_fld ) 										{ $primar_column_order_fld = $primar_column_order_field_inp; $primar_column_order_dir = "ASC"; }
						//~ send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
					//~ }

					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if (isset($_POST['view_user_button']))
						{
							$user_id = htmlspecialchars($_POST['view_user_button'], ENT_QUOTES, 'UTF-8');
							send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['show_secrets_button']))
						{
							$tp_login_uname = htmlspecialchars($_POST['show_secrets_button'], ENT_QUOTES, 'UTF-8');
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC');
						}
						elseif (isset($_POST['delete_selected_users_button']))
						{
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");
							foreach ($_POST as $key => $value)
							{								
								if ( preg_match('/selected/', $key, $matches))
								{
									//~ echo "textfieldvalue: " . $key . " value: " . $value. "<br>\n";																		
									$user_id="$value";
									deleteUserByUserId($pdo, $user_id, $form, "[ ◀ OK ▶ ]", "index.php");
								}
							}
							send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "view_secret_form" )
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if (isset($_POST['edit_secret_button']))
						{
							$secret_id = htmlspecialchars($_POST['edit_secret_button'], ENT_QUOTES, 'UTF-8');
							send_html_edit_secret_page($secret_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['copy_secret_button']))
						{
							$secret_id = htmlspecialchars($_POST['copy_secret_button'], ENT_QUOTES, 'UTF-8');
							send_html_copy_secret_page($secret_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['delete_secret_button']))
						{
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");
							
							$secret_id = htmlspecialchars($_POST['delete_secret_button'], ENT_QUOTES, 'UTF-8');
							deleteSecretById($pdo, $secret_id, $form, "[ ◀ OK ▶ ]", "index.php");
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['cancel_secret_button']))
						{
							$secret_id = htmlspecialchars($_POST['cancel_secret_button'], ENT_QUOTES, 'UTF-8');
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "view_group_form" )
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if (isset($_POST['edit_group_button']))
						{
							$group_id = htmlspecialchars($_POST['edit_group_button'], ENT_QUOTES, 'UTF-8');
							send_html_edit_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['copy_group_button']))
						{
							$group_id = htmlspecialchars($_POST['copy_group_button'], ENT_QUOTES, 'UTF-8');
							send_html_copy_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['delete_group_button']))
						{
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");
							
							$group_id = htmlspecialchars($_POST['delete_group_button'], ENT_QUOTES, 'UTF-8');
							deleteGroupById($pdo, $group_id, $form, "[ ◀ OK ▶ ]", "index.php");
							send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['cancel_group_button']))
						{
							$group_id = htmlspecialchars($_POST['cancel_group_button'], ENT_QUOTES, 'UTF-8');
							send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "view_user_form" )
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if (isset($_POST['edit_user_button']))
						{
							$user_id = htmlspecialchars($_POST['edit_user_button'], ENT_QUOTES, 'UTF-8');
							send_html_edit_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['copy_user_button']))
						{
							$user_id = htmlspecialchars($_POST['copy_user_button'], ENT_QUOTES, 'UTF-8');
							send_html_copy_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['delete_user_button']))
						{
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");
							
							$user_id = htmlspecialchars($_POST['delete_user_button'], ENT_QUOTES, 'UTF-8');
							deleteUserByUserId($pdo, $user_id, $form, "[ ◀ OK ▶ ]", "index.php");
							
							send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['cancel_user_button']))
						{
							$user_id = htmlspecialchars($_POST['cancel_user_button'], ENT_QUOTES, 'UTF-8');
							send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "create_secret_form" || $form == "copy_secret_form")
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if ( isset($_POST['save_secret_button']) && isset($_POST['secret_name']) && isset($_POST['group_name']) )
						{		                    
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");

							$secret_name = $_POST['secret_name'];
							$group_name = $_POST['group_name'];
							
							$user_id = 	getUserIdByUserName($tp_login_uname, $form, "[ ◀ OK ▶ ]", "index.php");

							//~ Insert group (gets ignored when it exists)
							$group_id = insertGroup($pdo, $user_id, $group_name, $form, "[ ◀ OK ▶ ]", "index.php");

							$lastInsertSecretId = insertSecret($pdo, $user_id, $group_id, $secret_name, $form, "[ ◀ OK ▶ ]", "index.php");
							//~ echo "<br>\ninsertSecret(" . $user_id . ", " . $group_id . ", " . $secret_name . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";

							$fieldOrderCounter = 0;
							
							foreach ($_POST as $key => $value)
							{
								//~ echo "key: " . $key . " value: " . $value . "<br>\n";
								if ( preg_match('/textfieldname/', $key, $matches))
								{
									$field_name="$value";
									//~ echo "textfieldname: " . $key . "<br>\n";
								}
								
								if ( preg_match('/textfieldtype/', $key, $matches))
								{
									$field_type="$value";
									//~ echo "textfieldtype: " . $key . "<br>\n";
								}
								
								if ( preg_match('/textfieldvalu/', $key, $matches))
								{
									$field_value="$value";
									$field_hash="$value";
									//~ echo "textfieldvalue: " . $key . "<br>\n";									
									
									if 		( $field_type === "password" ) 	{ insertField($pdo, $user_id, $lastInsertSecretId, $fieldOrderCounter, $field_name, 		$field_type, 		encrypt_password($tp_login_pword,$field_value), hash($GLOBALS["user_encr_hash_type"], $field_value), 	$form, "[ ◀ OK ▶ ]","index.php"); }
									else 									{ insertField($pdo, $user_id, $lastInsertSecretId, $fieldOrderCounter, $field_name, 		$field_type, 		$field_value,									"", 													$form, "[ ◀ OK ▶ ]","index.php"); }

									$fieldOrderCounter++;
								}
							}

							send_html_view_secret_page($lastInsertSecretId, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['cancel_secret_button']))
						{
							$secret_id = htmlspecialchars($_POST['cancel_secret_button'], ENT_QUOTES, 'UTF-8');
							//~ echo "Cancel secret id: ". $secret_id . "<br>\n";

							if 		($form == "create_secret_form")	{ send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
							elseif	($form == "copy_secret_form")	{ send_html_view_secret_page($secret_id, $tp_login_uname,$tp_login_pword,$search_names,$search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
							else 									{ send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
						}
						else
						{
							//~ echo "Pressed Other Button<br>\n";
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "create_group_form" || $form == "copy_group_form")
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if ( isset($_POST['save_group_button']) && isset($_POST['group_name']) )
						{		                    
							$group_name = $_POST['group_name'];
							
							$user_id = 	getUserIdByUserName($tp_login_uname, $form, "[ ◀ OK ▶ ]","index.php");

							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");

							//~ Insert group (gets ignored when it exists)
							$group_id = insertGroup($pdo, $user_id, $group_name, "create_group","[ ◀ OK ▶ ]","index.php");

							send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
							//~ send_html_view_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['cancel_group_button']))
						{
							$group_id = htmlspecialchars($_POST['cancel_group_button'], ENT_QUOTES, 'UTF-8');
							//~ echo "Cancel group id: ". $group_id . "<br>\n";

							if 		($form == "create_group_form")	{ send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
							elseif	($form == "copy_group_form")	{ send_html_view_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
							else 									{ send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
						}
						else
						{
							//~ echo "Pressed Other Button<br>\n";
							send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "create_user_form" || $form == "copy_user_form")
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if ( isset($_POST['save_user_button']) && isset($_POST['role_name']) && isset($_POST['user_name']) && isset($_POST['pass_word']) )
						{
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");

							$role_name = $_POST['role_name'];
							$role_id = 	getRoleIdByRoleName($pdo, $role_name, $form, "[ ◀ OK ▶ ]","index.php");
							
							$user_name = $_POST['user_name'];
							$pass_word = $_POST['pass_word'];
								
						//~ insertUser($pdo, $role_id, 	$user_name, $pass_word, 			$form, $button, $target);
							$user_id = insertUser($pdo, $role_id, 	$user_name, $pass_word,	$form, "[ ◀ OK ▶ ]", "index.php");
							
							create_test_secrets($user_name, $pass_word, 11);
							
							send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
							//~ send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['cancel_user_button']))
						{
							$user_id = htmlspecialchars($_POST['cancel_user_button'], ENT_QUOTES, 'UTF-8');

							if 		($form == "create_user_form")	{ send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
							elseif	($form == "copy_user_form")		{ send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
							else 									{ send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
						}
						else
						{
							//~ echo "Pressed Other Button<br>\n";
							send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "update_secret_form" )
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{
						if ( isset($_POST['save_secret_button']) && isset($_POST['secret_name']) && isset($_POST['group_name']) )
						{							
							$secret_id = $_POST['save_secret_button'];
							$secret_name = $_POST['secret_name'];
							$group_name = $_POST['group_name'];
												
							$user_id = 	getUserIdByUserName($tp_login_uname, $form, "[ ◀ OK ▶ ]","index.php");

							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");

							//~ Insert group (gets ignored when it exists)
							$group_id = insertGroup($pdo, $user_id, $group_name, "create_secret","[ ◀ OK ▶ ]","index.php");
							
							updateSecret($pdo, $secret_id, $group_id, $secret_name, $form, "[ ◀ OK ▶ ]", "index.php");
							//~ echo "<br>\nupdateSecret(" . $secret_id . ", " . $group_id . ", " . $secret_name . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";

							$fieldOrderCounter = 0;

							foreach ($_POST as $key => $value)
							{
								//~ echo "key: " . $key . " value: " . $value . "<br>\n";

								if ( preg_match('/textfieldfunc/', $key, $matches))
								{
									$field_func="$value";
									//~ echo "textfieldfunc: " . $key . "<br>\n";
								}
								
								if ( preg_match('/textfieldfdid/', $key, $matches))
								{
									$field_fdid = "$value";
									//~ echo "textfieldfdid: " . $key . "<br>\n";
									if	( $field_func == "DELETE" )
									{
										deleteField($pdo, $field_fdid, $form, "[ ◀ OK ▶ ]","index.php");
										//~ echo "deleteField(" . $field_fdid . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";
									}
								}
								
								if ( preg_match('/textfieldname/', $key, $matches))
								{
									$field_name = "$value";
									//~ echo "textfieldname: " . $key . "<br>\n";
								}
								
								if ( preg_match('/textfieldtype/', $key, $matches))
								{
									$field_type = "$value";
									//~ echo "textfieldtype: " . $key . "<br>\n";
								}
								
								if ( preg_match('/textfieldvalu/', $key, $matches))
								{
									$field_value = "$value";
									$field_hash = "$value";
									//~ echo "textfieldvalue: " . $key . "<br>\n";

									if		( $field_func == "UPDATE" )
									{
										if 		( $field_type === "password" ) 	{ updateField($pdo, $field_fdid, $fieldOrderCounter, $field_name, 		encrypt_password($tp_login_pword,$field_value), hash($GLOBALS["user_encr_hash_type"], $field_value), 	$form, "[ ◀ OK ▶ ]","index.php"); }
										else 									{ updateField($pdo, $field_fdid, $fieldOrderCounter, $field_name, 		$field_value,									"", 													$form, "[ ◀ OK ▶ ]","index.php"); }

																				  //~ updateField($pdo, $field_fdid, $fieldOrderCounter, $field_name, 		$field_value, 									"", 													$form, "[ ◀ OK ▶ ]","index.php");
										//~ echo "updateField(" . $field_fdid . ", " . $field_ordr . ", " . $field_name . ", " . $field_value .", " . $field_hash . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";
										$fieldOrderCounter++;
									}
									elseif	( $field_func == "DELETE" )
									{
										deleteField($pdo, $field_fdid, $form, "[ ◀ OK ▶ ]","index.php");
										//~ echo "deleteField(" . $field_fdid . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";
									}
									elseif 		( $field_func == "INSERT" )
									{
										if 		( $field_type === "password" ) 	{ insertField($pdo, $user_id, $secret_id, $fieldOrderCounter, $field_name, 		$field_type, 		encrypt_password($tp_login_pword,$field_value), hash($GLOBALS["user_encr_hash_type"], $field_value), 	$form, "[ ◀ OK ▶ ]","index.php"); }
										else 									{ insertField($pdo, $user_id, $secret_id, $fieldOrderCounter, $field_name, 		$field_type, 		$field_value,									"", 													$form, "[ ◀ OK ▶ ]","index.php"); }


																				  //~ insertField($pdo, $user_id, $secret_id, $fieldOrderCounter, $field_name, 		$field_type, 		$field_value, 									"", 													$form, "[ ◀ OK ▶ ]","index.php");
										//~ echo "insertField(" . $user_id . ", " . $secret_id . ", " . $field_ordr . ", " . $field_name . ", " . $field_type . ", " . $field_value .", " . $field_hash . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";
										$fieldOrderCounter++;
									}
									else
									{
										$message = "Error form field_func: " . $field_func . " parameter invalid! (can only be: UPDATE, DELETE or INSERT)";
										$instruction = "<a class=\"border\" href=\"https://www.${supplier_domain}/#contact\" target=\"_blank\"><b><p style=\"text-align:center; color: red;\">Please report this incident to Tiny Server Support!</p></b></a>";
										message($message, $form, $instruction, $button, $target);
									}
								}
							}
							
							send_html_view_secret_page($secret_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
							//~ send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['cancel_secret_button']))
						{
							$secret_id = htmlspecialchars($_POST['cancel_secret_button'], ENT_QUOTES, 'UTF-8');
							send_html_view_secret_page($secret_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							//~ echo "Pressed Other Button<br>\n";
							send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						//~ echo "Not Authenticated<br>\n";
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "update_group_form" )
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{						
						if ( isset($_POST['save_group_button']) && isset($_POST['group_name']) )
						{							
							$group_id = $_POST['save_group_button'];
							$group_name = $_POST['group_name'];
												
							$user_id = 	getUserIdByUserName($tp_login_uname, $form, "[ ◀ OK ▶ ]","index.php");
							
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");

							updateGroup($pdo, $group_id, $group_name, $form, "[ ◀ OK ▶ ]", "index.php");
							//~ echo "<br>\nupdateSecret(" . $group_id . ", " . $group_name . ", " . $form . ", \"[ ◀ OK ▶ ]\",\"index.php\");<br>\n";
							
							send_html_view_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
							//~ send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						elseif (isset($_POST['cancel_group_button']))
						{
							$group_id = htmlspecialchars($_POST['cancel_group_button'], ENT_QUOTES, 'UTF-8');
							send_html_view_group_page($group_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							//~ echo "Pressed Other Button<br>\n";
							send_html_show_groups_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						//~ echo "Not Authenticated<br>\n";
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "update_user_form" )
				{
					if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
					{						
						//~ if ( isset($_POST['save_user_button']) && isset($_POST['role_name']) && isset($_POST['user_name']) && isset($_POST['old_pass_word']) && isset($_POST['new_pass_word']) )
						if ( isset($_POST['save_user_button']) && isset($_POST['role_name']) && isset($_POST['user_name']) )
						{
							$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");
							$user_id = $_POST['save_user_button'];
							$role_name = $_POST['role_name'];
							$role_id = 	getRoleIdByRoleName($pdo, $role_name, $form, "[ ◀ OK ▶ ]","index.php");
							$user_name = $_POST['user_name'];
							//~ $old_pass_word = $_POST['old_pass_word'];
							//~ $new_pass_word = $_POST['new_pass_word'];

							///~ Verify Old Password
							if ( ! authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
							{
								dboError(array("-","-","Old Password Incorrect"), "header", $form, "authenticateUser(\"$user_name\")", "[ ◀ OK ▶ ]","index.php");
								send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
							}
							else
							{
								if ( $user_name === "admin" || $user_name === "tiny" )
								{
									send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
								}
								else
								{
									// Update User
									
									//~ echo "updateUser(\$pdo, $role_id, $user_id, $user_name, $form, \"[ ◀ OK ▶ ]\", \"index.php\");";
									
									updateUser($pdo, $role_id, $user_id, $user_name, $form, "[ ◀ OK ▶ ]", "index.php");

									//~ // Reencrypt Password
									
									//~ $fields = selectSecretPasswordFieldsByUserId($pdo, $user_id, $form, "[ ◀ OK ▶ ]", "index.php");
									//~ foreach ($fields as $field)
									//~ {
										//~ $field_decr = decrypt_password($old_pass_word, $field->field_value);
										
										//~ if  ( hash($GLOBALS["user_encr_hash_type"], $field_decr) == $field->field_hash )
										//~ {
											//~ $field_encr = encrypt_password($new_pass_word, $field_decr);
											//~ updateField($pdo, $field->field_id, $field->field_ordr, $field->field_name, $field_encr, $field->field_hash, $form, "[ ◀ OK ▶ ]","index.php");
										//~ }
										//~ else { dboError(array("-","-","Decryption failed for fields.field_id: " . $field->field_id), "header", $form, "updateUser(\"$user_name\")", "[ ◀ OK ▶ ]","index.php"); }
									//~ }
									//~ if ( $user_name == $tp_login_uname ) 	{ send_html_view_user_page($user_id, $tp_login_uname, $new_pass_word, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
									//~ else 									{ send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
									
									// View User

									if ( $user_name == $tp_login_uname ) 	{ send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
									else 									{ send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir); }
								}	// valid new username
							}		// not authenticated
							
						}
						elseif (isset($_POST['cancel_user_button']))
						{
							$user_id = htmlspecialchars($_POST['cancel_user_button'], ENT_QUOTES, 'UTF-8');
							send_html_view_user_page($user_id, $tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
						else
						{
							//~ echo "Pressed Other Button<br>\n";
							send_html_show_users_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
						}
					}
					else
					{
						//~ echo "Not Authenticated<br>\n";
						send_html_login_page(3);
					}
					exit();    
				}
				elseif ($form == "change_password_form" )
				{
					if ( DEMO )
					{
						if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
						{						
							if (isset($_POST['cancel_password_button']))
							{
								//~ $user_id = htmlspecialchars($_POST['cancel_password_button'], ENT_QUOTES, 'UTF-8');
								send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC');
							}
							else
							{
								//~ echo "Pressed Other Button<br>\n";
								send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC');
							}
						}
						else
						{
							//~ echo "Not Authenticated<br>\n";
							send_html_login_page(3);
						}
						exit();    
					}
					else
					{
						if ( authenticateUser($tp_login_uname, $tp_login_pword, $form, "[ ◀ OK ▶ ]","index.php") )
						{						
							if ( isset($_POST['save_password_button']) && isset($_POST['role_name']) && isset($_POST['user_name']) && isset($_POST['old_pass_word']) && isset($_POST['new_pass_word']) )
							{
								$pdo = connectDB($form, "[ ◀ OK ▶ ]","index.php");
								$user_id = $_POST['save_password_button'];
								$role_name = $_POST['role_name'];
								$role_id = getRoleIdByRoleName($pdo, $role_name, $form, "[ ◀ OK ▶ ]","index.php");
								$user_name = $_POST['user_name'];
								$old_pass_word = $_POST['old_pass_word'];
								$new_pass_word = $_POST['new_pass_word'];
								
								change_password($user_name, $old_pass_word, $new_pass_word, true);
								
								if ( authenticateUser("tiny", $GLOBALS["user_stnd_pass_word"], $form, "[ ◀ OK ▶ ]", "index.php") ) // User has default pass and needs pass change
								{
									$user_id = 	getUserIdByUserName("tiny", $form, "[ ◀ OK ▶ ]","index.php");	send_html_change_password_page($user_id, "tiny", $GLOBALS["user_stnd_pass_word"], $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
									send_html_login_page(0);
								}
								else
								{
									if ( $old_pass_word == $GLOBALS["user_stnd_pass_word"] )
									{
										send_html_login_page(0);
									}
									else
									{
										send_html_show_secrets_page($tp_login_uname, $new_pass_word, $search_names, $search_groups, 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC');
									}
								}							
							}
							elseif (isset($_POST['cancel_password_button']))
							{
								//~ $user_id = htmlspecialchars($_POST['cancel_password_button'], ENT_QUOTES, 'UTF-8');
								send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC');
							}
							else
							{
								//~ echo "Pressed Other Button<br>\n";
								send_html_show_secrets_page($tp_login_uname, $tp_login_pword, $search_names, $search_groups, 'secrets.secret_name', 'ASC', 'groups.group_name', 'ASC');
							}
						}
						else
						{
							//~ echo "Not Authenticated<br>\n";
							send_html_login_page(3);
						}
						exit();    
					}
				}
				else
				{
					send_html_header("");
					while (@ ob_end_flush());
					echo "<div style=\"position: fixed; left: 84%; top: 50%;\"class=\"diceloaded\"></div>";
					echo '<pre>';

					echo "<p style=\"position: fixed; top: 10%; left: 85%; transform: translateX(-50%); text-shadow: 0px 0px 5px rgba(30,30,5,0.8); font-size: x-large; white-space: nowrap;\">T E R M I N A L</p>";

					echo "<div style=\"width: 100vw; position: stycky; top: 25%; margin-left: 50%; transform: translateX(-50%); z-index: 0; overflow-y: auto;\" ><p style=\"text-align:center;\">[ Invalid Form ]</p><p><br /></p><p style=\"text-align:center;\">*** Invalid Form Submitted ***</p>";
					echo "<a class=\"border\" href=\"https://www.${supplier_domain}/#contact\" target=\"_blank\"><b><p style=\"text-align:center; color: red;\">Please report this incident to Tiny Server Support!</p></b></a><p><br /></p>";

					echo '</pre>';
					$button = "[ ◀ OK ▶ ]"; $target = "index.php#createlicense"; echo "<div style=\"position: fixed; top: 85%; left: 85%; transform: translateX(-50%); z-index: 100;\"><table style=\"width:100%; text-align: center; border: 0px solid;\"><tr><td><form class=\"info\" action=\"$target\" method=\"post\" autocomplete=\"off\"><button class=\"bigbutton\" style=\"font-size: large; white-space: nowrap;\" type=\"submit\">$button</button></form></td></tr></table></div>";
					exit();
				}
			}
		}
		elseif (PHP_SAPI === 'cli' || empty($_SERVER['REMOTE_ADDR'])) // from command line
		{
			//~ echo "SERVER\n";
			//~ foreach ($_SERVER as $key => $value) { echo $key . " = " . $value. "<br>\n"; }
			//~ echo "\n";
			//~ exit();
						
			$options = 			getopt(null, array("call_func:","user_name:","old_pass_word:","new_pass_word:"));
			
			if ( count($options) > 0 )
			{
				if ( isset($options['call_func']) )				
				{
					$call_func = rtrim("$options[call_func]");

					if ( $call_func === "change_password" )
					{
						if ( isset($options['user_name']) )			{ $user_name = rtrim("$options[user_name]"); } 			else { usage("Warning: Shell -> PHP Function: \"$call_func\" --user_name=\"user\" missing !"); }
						if ( isset($options['old_pass_word']) )		{ $old_pass_word = rtrim("$options[old_pass_word]"); } 	else { usage("Warning: Shell -> PHP Function: \"$call_func\" --old_pass_word=\"old_password\" missing !"); }
						if ( isset($options['new_pass_word']) )		{ $new_pass_word = rtrim("$options[new_pass_word]"); } 	else { usage("Warning: Shell -> PHP Function: \"$call_func\" --new_pass_word=\"new_password\" missing !"); }

						if ( $old_pass_word === $new_pass_word ) 	{ usage("Warning: Shell -> PHP Function: \"$call_func\" --old_pass_word=\"$old_pass_word\" --new_pass_word=\"$new_pass_word\" are equal ! Aborting !"); }
						
						//~ Test Shell -> PHP Function
						//~ php "/home/tiny/Services/by-name/apache2/apps/tinypass/www/index.php" --call_func="change_password" --user_name="admin" --old_pass_word="old_pass_word" --new_pass_word="new_pass_word";

						//~ echo "\ncall_func: $call_func\nuser_name: $user_name\nold_sys_pass: $old_pass_word\nnew_sys_pass: $new_pass_word\n\n";
						change_password($user_name, $old_pass_word, $new_pass_word, false);
					}
					else
					{
						usage("Warning: Shell -> PHP Function: \"--call_func=\"$call_func\" unknown !");
					}
				}
				else
				{
					usage("Warning: Shell -> PHP Option: \"--call_func=\"php_function\" not set !");
				}
			}
			else
			{
				usage("Warning: Shell -> No PHP Options !");
			}
		}
		else // Defaults to web interface landing
		{
			$form = "change_password_form"; $search_names = $search_groups = $primar_column_order_fld = $primar_column_order_dir = $second_column_order_fld = $second_column_order_dir = "";
			if ( ( authenticateUser("admin", $GLOBALS["user_stnd_pass_word"], $form, "[ ◀ OK ▶ ]", "index.php") ) && ( ! DEMO )) // User has default pass and needs pass change
			{
				$user_id = 	getUserIdByUserName("admin", $form, "[ ◀ OK ▶ ]","index.php"); send_html_change_password_page($user_id, "admin", $GLOBALS["user_stnd_pass_word"], $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
			}
			else if ( ( authenticateUser("tiny", $GLOBALS["user_stnd_pass_word"], $form, "[ ◀ OK ▶ ]", "index.php") ) && (! DEMO )) // User has default pass and needs pass change
			{
				$user_id = 	getUserIdByUserName("tiny", $form, "[ ◀ OK ▶ ]","index.php"); send_html_change_password_page($user_id, "tiny", $GLOBALS["user_stnd_pass_word"], $search_names, $search_groups, $primar_column_order_fld, $primar_column_order_dir, $second_column_order_fld, $second_column_order_dir);
			}
			else
			{
				send_html_login_page(0);
			}
		}

		function usage($message)
		{
			//~ echo "\$_SERVER\n"; print_r($_SERVER); echo "\n"; 
			//~ echo "\$_POST\n"; print_r($_POST); echo "\n"; 
			//~ echo "\$_FILES\n"; print_r($_FILES); echo "\n";

			echo "\n";
			echo basename($_SERVER['SCRIPT_FILENAME']) . " <--call_func=\"function\" [--param1=\"value1\"] [--param2=\"value2\"] ..\n";
			echo "\n";
			echo "Options\n";
			echo "\n";
			echo "	--call_func=\"change_password\" --user_name=\"user\" --old_pass_word=\"current_password\" --new_pass_word=\"new_password\"\n";
			echo "\n";
			echo "Example\n";
			echo "\n";
			echo "php \"". $_SERVER['SCRIPT_FILENAME']. "\" --call_func=\"change_password\" --user_name=\"user\" --old_pass_word=\"oldpass\" --new_pass_word=\"newpass\";\n";
			echo "\n";
			if ( ! empty($message) )
			{
				echo "$message\n\n";
			}
			exit(1);
		}
    ?>
