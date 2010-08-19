<?php

// Settings page in the admin panel
function zopim_account_config() {
   global $usernameToCodeURL, $languagesURL, $current_user;

?>

<div class="wrap">

<?php

   if ($_GET["action"]=="deactivate") {
      update_option('zopimSalt', "");
      update_option('zopimCode', "zopim");
   }

   $message = "";
   if ($_POST["action"]=="login") {
      if ($_POST["zopimUseSSL"] == "") {
         $_POST["zopimUseSSL"] = "nossl";
      }
      update_option('zopimUseSSL', $_POST["zopimUseSSL"]);

      if ($_POST["zopimPassword"] != "password") {

         $logindata = array("email" => $_POST["zopimUsername"], "password" => $_POST["zopimPassword"]);
         $loginresult = json_to_array(do_post_request(ZOPIM_LOGIN_URL, $logindata));

         if (isset($loginresult->error)) {
            $error["login"] = "<b>Could not log in to Zopim. Please check your login details. If problem persists, try connecting without SSL enabled.</b>";
            $gotologin = 1;
            update_option('zopimSalt', "wronglogin");
         } else if (isset($loginresult->salt)) {
            update_option('zopimUsername', $_POST["zopimUsername"]);
            update_option('zopimSalt', $loginresult->salt);
            $account = getAccountDetails(get_option('zopimSalt'));

            if (isset($account)) {
               update_option('zopimCode', $account->account_key);

               if (get_option('zopimGreetings') == "") {
                  $jsongreetings = to_json($account->settings->greetings);
                  update_option('zopimGreetings', $jsongreetings);
               }
            }
         } else {
            update_option('zopimSalt', "");
            $error["login"] = "<b>Could not log in to Zopim. We were unable to contact Zopim servers. Please check with your server administrator to ensure that <a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl</a> is installed and permissions are set correctly.</b>";
         }
      }
   } else if ($_POST["action"]=="signup") {

      if ($_POST["zopimUseSSL"] == "") {
         $_POST["zopimUseSSL"] = "nossl";
      }
      update_option('zopimUseSSL', $_POST["zopimUseSSL"]);

      $createdata = array(
         "email" => $_POST["zopimnewemail"], 
         "first_name" => $_POST["zopimfirstname"], 
         "last_name" => $_POST["zopimlastname"], 
         "display_name" => $_POST["zopimfirstname"]." ".$_POST["zopimlastname"],
         "eref" => $_POST["zopimeref"],
         "source" => "wordpress",
         "recaptcha_challenge_field" => $_POST["recaptcha_challenge_field"],
         "recaptcha_response_field" => $_POST["recaptcha_response_field"]
      );

      $signupresult = json_to_array(do_post_request(ZOPIM_SIGNUP_URL, $createdata));
      if (isset($signupresult->error)) {
         $message = "<div style='color:#c33;'>Error during activation: <b>".$signupresult->error."</b>. Please try again.</div>";
      } else if (isset($signupresult->account_key)) {
         $message = "<b>Thank you for signing up. Please check your mail for your password to complete the process. </b>";
         $gotologin = 1;
      } else {
         $message = "<b>Could not activate account. The wordpress installation was unable to contact Zopim servers. Please check with your server administrator to ensure that <a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl</a> is installed and permissions are set correctly.</b>";
      }
   }

   if (get_option('zopimCode') != "" && get_option('zopimCode') != "zopim") {

      $accountDetails = getAccountDetails(get_option('zopimSalt'));

      if (!isset($accountDetails) || isset($accountDetails->error)) {
         $gotologin = 1;
         $error["auth"] = '
    <div class="metabox-holder">
	<div class="postbox">
		<h3 class="hndle"><span>Account no longer linked!</span></h3>
		<div style="padding:10px;line-height:17px;">
      We could not verify your Zopim account. Please check your password and try again.
		</div>
	</div>	
    </div>'
;
      } else {
         $authenticated = "ok";
      }
   }

   if ($authenticated == "ok") {
      if ($accountDetails->package_id=="trial") {
         $accountDetails->package_id = "Free Lite Package + 14 Days Full-features";
      } else {
         $accountDetails->package_id .= " Package";
      }
?>
<div id="icon-options-general" class="icon32"><br/></div><h2>Set up your Zopim Account</h2>
<br/>
<div style="background:#FFFEEB;padding:25px;border:1px solid #eee;">
<span style="float:right;"><a href="admin.php?page=zopim_account_config&action=deactivate">Deactivate</a></span>
Currently Activated Account &rarr; <b><?php echo get_option('zopimUsername'); ?></b> <div style="display:inline-block;background:#444;color:#fff;font-size:10px;text-transform:uppercase;padding:3px 8px;-moz-border-radius:5px;-webkit-border-radius:5px;"><?php echo ucwords($accountDetails->package_id); ?></div> 
<br><p><br>You can <a href="admin.php?page=zopim_customize_widget">customize</a> the chat widget, <a href="admin.php?page=zopim_instant_messaging">relay messages</a> to your favourite IM client, or <a href="admin.php?page=zopim_dashboard">launch the dashboard</a> for advanced features.
</div>

<?php } else { ?>
<div id="icon-options-general" class="icon32"><br/></div><h2>Set up your Zopim Account</h2>
<?php if ($error && $error["auth"]) {
   echo $error["auth"];
   } else if ($message == "") { ?>
Congratulations on successfully installing the Zopim WordPress plugin! Activate an account to start using Zopim Live Chat.<br>
<br>
<?php } else { echo $message; } ?>

<script type="text/javascript">

function showSignup(whichform) {
      if (whichform == '1') {
         document.getElementById('existingform').style.display = "none";
         document.getElementById('signupform').style.display = "block";
         document.getElementById('formtoshow_signup').checked = 'true';
      } else {
         document.getElementById('signupform').style.display = "none";
         document.getElementById('existingform').style.display = "block";
         document.getElementById('formtoshow_existing').checked = 'true';
      }
}


</script>
<div class="metabox-holder">
	<div class="postbox">
		<h3 class="hndle"><span>Select a Setup</span></h3>
		<div style="padding:10px;">
<div onclick="javascript: showSignup(1)"><input type="radio" name="formtoshow" id="formtoshow_signup" value="yes" onchange="javascript: showSignup(1)"/> Give me a new account &mdash; <i>absolutely free!</i></div>
<br/>
<div onclick="javascript: showSignup(0)"><input type="radio" name="formtoshow" id="formtoshow_existing" value="no" onchange="javascript: showSignup(0)"/> I already have a Zopim account</div>
		</div>
	</div>	
</div>


<div id="existingform" style="display: none">
	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span>Link up to your Zopim account</span></h3>
			<div style="padding:10px;">
<?php if (isset($error) && isset($error["login"])) { echo $error["login"]; } ?>
<form method="post" action="admin.php?page=zopim_account_config">
   <input type="hidden" name="action" value="login">
   <table class="form-table">

        <tr valign="top">
        <th scope="row">Zopim Username (E-mail)</th>
        <td><input type="text" name="zopimUsername" value="<?php echo get_option('zopimUsername'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Zopim Password</th>
        <td><input type="password" name="zopimPassword" value="<?php if (get_option('zopimSalt') != "") { echo "password"; }; ?>" /></td>
        </tr>

        <tr valign="center">
        <th scope="row">Use SSL</th>
        <td><input type="checkbox" name="zopimUseSSL" value="zopimUseSSL" <?php if (get_option('zopimUseSSL') == "zopimUseSSL") { echo "checked='checked'"; } ?> /> uncheck this if you are unable to login</td>
        </tr>
    </table>
		<br/>
		The Zopim chat bar will displayed on your blog once your account is linked up.
		<br/><br/>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Link Up') ?>" />
    </p>

</form>

			</div>
		</div>	
	</div>
</div>

<div id="signupform" style="display: none">
	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span>Activate your free Zopim Account</span></h3>
			<div style="padding:10px;">
<form method="post" action="admin.php?page=zopim_account_config" onSubmit="return checkSignUp();">
    <input type="hidden" name="action" value="signup">
    <div id="signuperror"></div>
    <table class="form-table">

        <tr valign="top">
        <th scope="row">First Name</th>
        <td><input id="zopimfirstname" type="text" name="zopimfirstname" value="<?php if (isset($_POST["zopimfirstname"])) { echo $_POST["zopimfirstname"]; } else { echo $current_user->data->first_name; } ?>"></td>
        </tr>

        <tr valign="top">
        <th scope="row">Last Name</th>
        <td><input id="zopimlastname" type="text" name="zopimlastname" value="<?php if (isset($_POST["zopimlastname"])) { echo $_POST["zopimnlastname"]; } else { echo $current_user->data->last_name; } ?>"></td>
        </tr>

        <tr valign="top">
        <th scope="row">E-mail</th>
        <td><input id="zopimnewemail" type="text" name="zopimnewemail" value="<?php if (isset($_POST["zopimnewemail"])) { echo $_POST["zopimnewemail"]; } else { echo $current_user->data->user_email; }  ?>"></td>
        </tr>

        <tr valign="top">
        <th scope="row">Use SSL</th>
        <td><input type="checkbox" name="zopimUseSSL" value="zopimUseSSL" checked="checked" } ?> uncheck this if you are unable to login</td>
        </tr>

        <tr valign="top">
        <th scope="row">Verification</th>
        <td>
          <script type="text/javascript" src="https://api-secure.recaptcha.net/challenge?k=6Lfr8AQAAAAAAC7MpRXM2hgLfyss_KKjvcJ_JFIk">
         </script>
         <noscript>
            <iframe src="https://api-secure.recaptcha.net/noscript?k=6Lfr8AQAAAAAAC7MpRXM2hgLfyss_KKjvcJ_JFIk"
                height="300" width="500" frameborder="0"></iframe><br>
            <textarea name="recaptcha_challenge_field" rows="3" cols="40">
            </textarea>
            <input type="hidden" name="recaptcha_response_field"
                value="manual_challenge">
         </noscript>

        </td>
        </tr>
<!--
        <tr valign="top">
        <th scope="row">Referral E-mail or ID</th>
        <td><input id="zopimeref" type="text" name="zopimeref" value="<?php if (isset($_POST)) { echo $_POST["zopimeref"]; } ?>"></td>
        </tr>
-->
    </table>
		<br/>
		The Zopim chat bar will displayed on your blog once your account is activated.<br/><br/>
		<input id="zopimagree" type="checkbox" name="zopimagree" value=""> I agree to Zopim's <a href="http://www.zopim.com/termsnconditions" target="_blank">Terms of Service</a> & <a href="http://www.zopim.com/privacypolicy" target="_blank">Privacy Policy</a>.
		<br/><br/>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Activate Now') ?>" />
    </p>
</form>
			</div>
		</div>	
	</div>

</div>
</div>
        
<script type="text/javascript">
<?php 
if ($authenticated != "ok" && !isset($gotologin) && get_option("zopimCode")=="zopim") {
   echo "showSignup(1); ";
} else {
   echo "showSignup(0); ";
}

?>

function checkSignUp() {

   var message = 'Oops! ';
   if (document.getElementById('zopimfirstname').value == '') {

      message = message + 'First name is required. ';
   }
   if (document.getElementById('zopimlastname').value == '') {

      message = message + 'Last name is required. ';
   }
   if (document.getElementById('zopimagree').checked == '') {

      message = message + 'You must agree to our Terms of Service to continue. ';
   }

   if (message != 'Oops! ') {

      document.getElementById('signuperror').innerHTML = message;
      return false;
   }

  return true; 
}
</script>

<?php } } ?>
