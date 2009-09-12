<?php
/*
Plugin Name: Fast and Secure Contact Form
Plugin URI: http://www.642weather.com/weather/scripts-wordpress-si-contact.php
Description: Fast and Secure Contact Form for WordPress. The contact form lets your visitors send you a quick E-mail message. Blocks all common spammer tactics. Spam is no longer a problem. Includes a CAPTCHA and Akismet support. Does not require JavaScript. <a href="plugins.php?page=si-contact-form/si-contact-form.php">Settings</a> | <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6105441">Donate</a>
Version: 1.6.2
Author: Mike Challis
Author URI: http://www.642weather.com/weather/scripts.php
*/

/*  Copyright (C) 2008 Mike Challis  (http://www.642weather.com/weather/contact_us.php)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//error_reporting(E_ALL ^ E_NOTICE); // Report all errors except E_NOTICE warnings
//error_reporting(E_ALL); // Report all errors and warnings (very strict, use for testing only)
//ini_set('display_errors', 1); // turn error reporting on

if (!class_exists('siContactForm')) {

 class siContactForm {
     var $si_contact_error;

function si_contact_add_tabs() {
    add_submenu_page('plugins.php', __('SI Contact Form Options', 'si-contact-form'), __('SI Contact Form Options', 'si-contact-form'), 'manage_options', __FILE__,array(&$this,'si_contact_options_page'));
}

function si_contact_unset_options() {
  delete_option('si_contact_form');
}

function si_contact_update_lang() {
  global $si_contact_opt, $option_defaults;

   // a few language options need to be re-translated now.
   // had to do this becuse the options were actually needed to be set before the language translator was initialized

  // update translation for these options (for when switched from English to another lang)
  if ($si_contact_opt['welcome'] == '<p>Comments or questions are welcome.</p>' ) {
     $si_contact_opt['welcome'] = __('<p>Comments or questions are welcome.</p>', 'si-contact-form');
     $option_defaults['welcome'] = $si_contact_opt['welcome'];
  }

  if ($si_contact_opt['email_to'] == 'Webmaster,'.get_option('admin_email')) {
       $si_contact_opt['email_to'] = __('Webmaster', 'si-contact-form').','.get_option('admin_email');
       $option_defaults['email_to'] = $si_contact_opt['email_to'];
  }

  if ($si_contact_opt['email_subject'] == get_option('blogname') . ' ' .'Contact:') {
      $si_contact_opt['email_subject'] =  get_option('blogname') . ' ' .__('Contact:', 'si-contact-form');
      $option_defaults['email_subject'] = $si_contact_opt['email_subject'];
  }

} // end function si_contact_update_lang

function si_contact_options_page() {
  global $si_contact_nonce, $captcha_url_cf, $si_contact_opt, $option_defaults;

  // a couple language options need to be translated now.
  $this->si_contact_update_lang();

  if (isset($_POST['submit'])) {
    if ( function_exists('current_user_can') && !current_user_can('manage_options') )
                        die(__('You do not have permissions for managing this option', 'si-contact-form'));

   // post changes to the options array
   $optionarray_update = array(
         'donated' =>          (isset( $_POST['si_contact_donated'] ) ) ? 'true' : 'false',
         'welcome' =>             trim($_POST['si_contact_welcome']),  // can be empty
         'email_to' =>          ( trim($_POST['si_contact_email_to']) != '' ) ? trim($_POST['si_contact_email_to']) : $option_defaults['email_to'], // use default if empty
         'email_from' =>          trim($_POST['si_contact_email_from']),
         'email_bcc' =>           trim($_POST['si_contact_email_bcc']),
         'email_subject' =>     ( trim($_POST['si_contact_email_subject']) != '' ) ? trim($_POST['si_contact_email_subject']) : $option_defaults['email_subject'],
         'double_email' =>     (isset( $_POST['si_contact_double_email'] ) ) ? 'true' : 'false', // true or false
         'domain_protect' =>   (isset( $_POST['si_contact_domain_protect'] ) ) ? 'true' : 'false',
         'email_check_dns' =>  (isset( $_POST['si_contact_email_check_dns'] ) ) ? 'true' : 'false',
         'captcha_enable' =>   (isset( $_POST['si_contact_captcha_enable'] ) ) ? 'true' : 'false',
         'captcha_perm' =>     (isset( $_POST['si_contact_captcha_perm'] ) ) ? 'true' : 'false',
         'captcha_perm_level' =>       $_POST['si_contact_captcha_perm_level'],
         'redirect_enable' =>  (isset( $_POST['si_contact_redirect_enable'] ) ) ? 'true' : 'false',
         'redirect_url' =>        trim($_POST['si_contact_redirect_url']),
         'border_enable' =>    (isset( $_POST['si_contact_border_enable'] ) ) ? 'true' : 'false',
         'border_width' => absint(trim($_POST['si_contact_border_width'])),
         'title_style' =>         trim($_POST['si_contact_title_style']),
         'field_style' =>         trim($_POST['si_contact_field_style']),
         'error_style' =>         trim($_POST['si_contact_error_style']),
         'field_size' =>   absint(trim($_POST['si_contact_field_size'])),
         'text_cols' =>    absint(trim($_POST['si_contact_text_cols'])),
         'text_rows' =>    absint(trim($_POST['si_contact_text_rows'])),
         'aria_required' =>    (isset( $_POST['si_contact_aria_required'] ) ) ? 'true' : 'false',
         'auto_fill_enable' => (isset( $_POST['si_contact_auto_fill_enable'] ) ) ? 'true' : 'false',
         'title_border' =>        trim($_POST['si_contact_title_border']),
         'title_dept' =>          trim($_POST['si_contact_title_dept']),
         'title_name' =>          trim($_POST['si_contact_title_name']),
         'title_email' =>         trim($_POST['si_contact_title_email']),
         'title_email2' =>        trim($_POST['si_contact_title_email2']),
         'title_email2_help' =>   trim($_POST['si_contact_title_email2_help']),
         'title_subj' =>          trim($_POST['si_contact_title_subj']),
         'title_mess' =>          trim($_POST['si_contact_title_mess']),
         'title_capt' =>          trim($_POST['si_contact_title_capt']),
         'title_submit' =>        trim($_POST['si_contact_title_submit']),
         'text_message_sent' =>   trim($_POST['si_contact_text_message_sent']),
  );


    // deal with quotes
    foreach($optionarray_update as $key => $val) {
           $optionarray_update[$key] = str_replace('&quot;','"',trim($val));
    }

    // save updated options to the database
    update_option('si_contact_form', $optionarray_update);

    // get the options from the database
    $si_contact_opt = get_option('si_contact_form');

    // strip slashes on get options array
    foreach($si_contact_opt as $key => $val) {
           $si_contact_opt[$key] = $this->ctf_stripslashes($val);
    }

    if (function_exists('wp_cache_flush')) {
	     wp_cache_flush();
	}

  } // end if (isset($_POST['submit']))

  // update translation for this setting (when switched from English to something else)
  if ($si_contact_opt['welcome'] == '<p>Comments or questions are welcome.</p>') {
       $si_contact_opt['welcome'] = __('<p>Comments or questions are welcome.</p>', 'si-contact-form');
  }

?>
<?php if ( !empty($_POST ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php echo esc_html( __('Options saved.', 'si-contact-form')); ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
<h2><?php echo esc_html( __('Fast and Secure Contact Form Options', 'si-contact-form')); ?></h2>

<script type="text/javascript">
    function toggleVisibility(id) {
       var e = document.getElementById(id);
       if(e.style.display == 'block')
          e.style.display = 'none';
       else
          e.style.display = 'block';
    }
</script>

<p>
<a href="http://wordpress.org/extend/plugins/si-contact-form/changelog/" target="_blank"><?php echo esc_html( __('Changelog', 'si-contact-form')); ?></a> |
<a href="http://wordpress.org/extend/plugins/si-contact-form/faq/" target="_blank"><?php echo esc_html( __('FAQ', 'si-contact-form')); ?></a> |
<a href="http://wordpress.org/extend/plugins/si-contact-form/" target="_blank"><?php echo esc_html( __('Rate This', 'si-contact-form')); ?></a> |
<a href="http://wordpress.org/tags/si-contact-form?forum_id=10" target="_blank"><?php echo esc_html( __('Support', 'si-contact-form')); ?></a> |
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6105441" target="_blank"><?php echo esc_html( __('Donate', 'si-contact-form')); ?></a> |
<a href="http://www.642weather.com/weather/scripts.php" target="_blank"><?php echo esc_html( __('Free PHP Scripts', 'si-contact-form')); ?></a> |
<a href="http://www.642weather.com/weather/contact_us.php" target="_blank"><?php echo esc_html( __('Contact', 'si-contact-form')); ?> Mike Challis</a>
</p>

<?php
if ($si_contact_opt['donated'] != 'true') {
?>
<h3><?php echo esc_html( __('Donate', 'si-contact-form')); ?></h3>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post">

<table style="background-color:#FFE991; border:none; margin: -5px 0;" width="500">
        <tr>
        <td>
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="6105441" />
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" style="border:none;" name="submit" alt="Paypal Donate" />
<img alt="" style="border:none;" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
</td>
<td><?php echo esc_html( __('If you find this plugin useful to you, please consider making a small donation to help contribute to further development. Thanks for your kind support!', 'si-contact-form')); ?> - Mike Challis</td>
</tr></table>
</form>
<br />

<?php
}
?>

<form name="formoptions" action="#" method="post">
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="form_type" value="upload_options" />
    <?php si_contact_nonce_field($si_contact_nonce) ?>

    <input name="si_contact_donated" id="si_contact_donated" type="checkbox" <?php if( $si_contact_opt['donated'] == 'true' ) echo 'checked="checked"'; ?> />
    <label name="si_contact_donated" for="si_contact_donated"><?php echo esc_html( __('I have donated to help contribute for the development of this Contact Form.', 'si-contact-form')); ?></label>
    <br />

<h3><?php echo esc_html( __('Usage', 'si-contact-form')); ?></h3>
	<p>
    <?php echo __('You must add the shortcode <b>[si_contact_form]</b> in a Page. That page will become your Contact Form', 'si-contact-form'); ?>. <a href="<?php echo WP_PLUGIN_URL; ?>/si-contact-form/screenshot-4.jpg" target="_new"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
    </p>

<h3><?php echo esc_html( __('Options', 'si-contact-form')); ?></h3>

        <fieldset class="options">

    <table cellspacing="2" cellpadding="5" class="form-table">


    <tr>
         <th scope="row" style="width: 75px;"><?php echo esc_html( __('Form:', 'si-contact-form')); ?></th>
      <td>
        <label name="si_contact_welcome" for="si_contact_welcome"><?php echo esc_html( __('Welcome introduction', 'si-contact-form')); ?>:</label><br />
        <textarea rows="2" cols="40" name="si_contact_welcome" id="si_contact_welcome"><?php echo $this->ctf_output_string($si_contact_opt['welcome']); ?></textarea>
        <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_welcome_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
        <div style="text-align:left; display:none" id="si_contact_welcome_tip">
        <?php _e('This gets printed when the contact form is first presented. It is not printed when there is an input error and not printed after the form is completed.', 'si-contact-form') ?>
        </div>
      </td>
    </tr>
    <tr>
         <th scope="row" style="width: 75px;"><?php echo esc_html( __('E-mail:', 'si-contact-form')); ?></th>
      <td>
<?php
// checks for properly configured E-mail To: addresses in options.
$ctf_contacts = array ();
$ctf_contacts_test = trim($si_contact_opt['email_to']);
$ctf_contacts_error = 0;
if(!preg_match("/,/", $ctf_contacts_test) ) {
    if($this->ctf_validate_email($ctf_contacts_test)) {
        // user1@example.com
       $ctf_contacts[] = array('CONTACT' => __('Webmaster', 'si-contact-form'),  'EMAIL' => $ctf_contacts_test );
    }
} else {
  $ctf_ct_arr = explode("\n",$ctf_contacts_test);
  if (is_array($ctf_ct_arr) ) {
    foreach($ctf_ct_arr as $line) {
        // echo '|'.$line.'|' ;
       list($key, $value) = explode(",",$line);
       $key   = trim($key);
       $value = trim($value);
       if ($key != '' && $value != '') {
          if(!preg_match("/;/", $value)) {
               // just one email here
               // Webmaster,user1@example.com
               if ($this->ctf_validate_email($value)) {
                  $ctf_contacts[] = array('CONTACT' => $key,  'EMAIL' => $value);
               } else {
                  $ctf_contacts_error = 1;
               }
          } else {
               // multiple emails here (additional ones will be Cc:)
               // Webmaster,user1@example.com;user2@example.com
               $multi_cc_arr = explode(";",$value);
               $multi_cc_string = '';
               foreach($multi_cc_arr as $multi_cc) {
                  if ($this->ctf_validate_email($multi_cc)) {
                     $multi_cc_string .= "$multi_cc,";
                  } else {
                     $ctf_contacts_error = 1;
                  }
               }
               if ($multi_cc_string != '') {  // multi cc emails
                  $ctf_contacts[] = array('CONTACT' => $key,  'EMAIL' => rtrim($multi_cc_string, ','));
               }
         }
      }
   } // end foreach
  } // end if (is_array($ctf_ct_arr) ) {
} // end else

//print_r($ctf_contacts);

?>
        <label name="si_contact_email_to" for="si_contact_email_to"><?php echo esc_html( __('E-mail To', 'si-contact-form')); ?>:</label>
<?php
if (empty($ctf_contacts) || $ctf_contacts_error ) {
   echo '<span style="color:red;">'.esc_html( __('ERROR: Misconfigured E-mail address in options.', 'si-contact-form')).'</span>'."\n";
}
?>
        <br />
        <textarea rows="3" cols="70" name="si_contact_email_to" id="si_contact_email_to"><?php echo $this->ctf_output_string($si_contact_opt['email_to']);  ?></textarea>
        <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_email_to_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
        <div style="text-align:left; display:none" id="si_contact_email_to_tip">
        <?php echo esc_html( __('E-mail address the messages are sent to (your email). Add as many contacts as you need, the drop down list on the contact form will be made automatically. Each contact has a name and an email address separated by a comma. Separate each contact by pressing enter. If you need to add more than one contact, follow this example:', 'si-contact-form')); ?><br />
        Webmaster,user1@example.com<br />
        Sales,user2@example.com<br /><br />

        <?php echo esc_html( __('Also, you can have multiple E-mails per contact, this is called a CC(Carbon Copy). Separate each CC with a semicolon. If you need to add more than one contact, each with a CC, follow this example:', 'si-contact-form')); ?><br />
        Webmaster,user1@example.com<br />
        Sales,user3@example.com;user4@example.com;user5@example.com
        </div>
        <br />

        <label name="si_contact_email_from" for="si_contact_email_from"><?php echo esc_html( __('E-mail From (optional)', 'si-contact-form')); ?>:</label>
<?php
if ( $si_contact_opt['email_from'] != '' && !$this->ctf_validate_email($si_contact_opt['email_from'])  ) {
   echo '<span style="color:red;">'.esc_html( __('ERROR: Misconfigured E-mail address in options.', 'si-contact-form')).'</span><br />'."\n";
}
?>
        <input name="si_contact_email_from" id="si_contact_email_from" type="text" value="<?php echo $si_contact_opt['email_from'];  ?>" size="50" />
        <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_email_from_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
        <div style="text-align:left; display:none" id="si_contact_email_from_tip">
        <?php echo esc_html( __('E-mail address the messages are sent from. Normally you should leave this blank. Some web hosts do not allow PHP to send E-mail unless the "From:" E-mail address is on the same web domain. If your contact form does not send any E-mail, then set this to an E-mail address on the SAME domain as your web site as a possible fix.', 'si-contact-form')); ?>
        </div>
        <br />

        <label name="si_contact_email_bcc" for="si_contact_email_bcc"><?php echo esc_html( __('E-mail Bcc (optional)', 'si-contact-form')); ?>:</label>
<?php
if ( $si_contact_opt['email_bcc'] != '' && !$this->ctf_validate_email($si_contact_opt['email_bcc'])  ) {
   echo '<span style="color:red;">'.esc_html( __('ERROR: Misconfigured E-mail address in options.', 'si-contact-form')).'</span><br />'."\n";
}
?>
        <input name="si_contact_email_bcc" id="si_contact_email_bcc" type="text" value="<?php echo $si_contact_opt['email_bcc'];  ?>" size="50" />
        <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_email_bcc_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
        <div style="text-align:left; display:none" id="si_contact_email_bcc_tip">
        <?php echo esc_html( __('This Bcc address is global, which means that if you have multi "E-mail To" contacts, any contact selected will send to this also.', 'si-contact-form')); ?>
        <?php echo esc_html( __('E-mail address(s) to receive Bcc (Blind Carbon Copy) messages. You can send to multiple or single, both methods are acceptable:', 'si-contact-form')); ?>
        <br />
        user1@example.com<br />
        user1@example.com, user2@example.com
        </div>
        <br />

        <label name="si_contact_email_subject" for="si_contact_email_subject"><?php _e('E-mail Subject Prefix', 'si-contact-form') ?>:</label><input name="si_contact_email_subject" id="si_contact_email_subject" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['email_subject']);  ?>" size="55" />
        <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_email_subject_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
        <div style="text-align:left; display:none" id="si_contact_email_subject_tip">
        <?php echo esc_html( __('This will become a prefix of the subject for the E-mail you receive.', 'si-contact-form')); ?>
        </div>
        <br />

        <input name="si_contact_double_email" id="si_contact_double_email" type="checkbox" <?php if( $si_contact_opt['double_email'] == 'true' ) echo 'checked="checked"'; ?> />
        <label name="si_contact_double_email" for="si_contact_double_email"><?php echo esc_html( __('Enable double E-mail entry required on contact form.', 'si-contact-form')); ?></label>
        <br />
        <input name="si_contact_domain_protect" id="si_contact_domain_protect" type="checkbox" <?php if( $si_contact_opt['domain_protect'] == 'true' ) echo 'checked="checked"'; ?> />
        <label name="si_contact_domain_protect" for="si_contact_domain_protect"><?php echo esc_html( __('Enable Form Post security by requiring domain name match for', 'si-contact-form')); ?>
        <?php
        $uri = parse_url(get_option('siteurl'));
        $blogdomain = str_replace('www.','',$uri['host']);
        echo " $blogdomain ";
        ?><?php echo esc_html( __('(recommended).', 'si-contact-form')); ?>
        </label>
        <br />
        <input name="si_contact_email_check_dns" id="si_contact_email_check_dns" type="checkbox" <?php if( $si_contact_opt['email_check_dns'] == 'true' ) echo 'checked="checked"'; ?> />
        <label name="si_contact_email_check_dns" for="si_contact_email_check_dns"><?php echo esc_html( __('Enable checking DNS records for the domain name when checking for a valid E-mail address.', 'si-contact-form')); ?></label>

      </td>
    </tr>

    <tr>
       <th scope="row" style="width: 75px;"><?php echo esc_html( __('CAPTCHA:', 'si-contact-form')); ?></th>
      <td>
        <input name="si_contact_captcha_enable" id="si_contact_captcha_enable" type="checkbox" <?php if ( $si_contact_opt['captcha_enable'] == 'true' ) echo ' checked="checked" '; ?> />
        <label for="si_contact_captcha_enable"><?php echo esc_html( __('Enable CAPTCHA (recommended).', 'si-contact-form')); ?></label><br />

        <input name="si_contact_captcha_perm" id="si_contact_captcha_perm" type="checkbox" <?php if( $si_contact_opt['captcha_perm'] == 'true' ) echo 'checked="checked"'; ?> />
        <label name="si_contact_captcha_perm" for="si_contact_captcha_perm"><?php echo esc_html( __('Hide CAPTCHA for', 'si-contact-form')); ?>
        <strong><?php echo esc_html( __('registered', 'si-contact-form')); ?></strong> <?php echo esc_html( __('users who can', 'si-contact-form')); ?>:</label>
        <?php $this->si_contact_captcha_perm_dropdown('si_contact_captcha_perm_level', $si_contact_opt['captcha_perm_level']);  ?><br />

        <a href="<?php echo "$captcha_url_cf/test/index.php"; ?>"><?php echo esc_html( __('Test if your PHP installation will support the CAPTCHA', 'si-contact-form')); ?></a>
      </td>
    </tr>

    <tr>
         <th scope="row" style="width: 75px;"><?php echo esc_html( __('Redirect:', 'si-contact-form')); ?></th>
      <td>
        <input name="si_contact_redirect_enable" id="si_contact_redirect_enable" type="checkbox" <?php if( $si_contact_opt['redirect_enable'] == 'true' ) echo 'checked="checked"'; ?> />
        <label name="si_contact_redirect_enable" for="si_contact_redirect_enable"><?php echo esc_html( __('Enable redirect after the message sends', 'si-contact-form')); ?>.</label><br  />

        <label name="si_contact_redirect_url" for="si_contact_redirect_url"><?php echo esc_html( __('Redirect URL', 'si-contact-form')); ?>:</label><input name="si_contact_redirect_url" id="si_contact_redirect_url" type="text" value="<?php echo $si_contact_opt['redirect_url'];  ?>" size="50" />
        <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_redirect_url_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
        <div style="text-align:left; display:none" id="si_contact_redirect_url_tip">
        <?php echo esc_html( __('After a user sends a message, the web browser will display "message sent" for 5 seconds, then redirect to this URL.', 'si-contact-form')); ?>
        </div>
        <br />
      </td>
    </tr>


        </table>

        <h3><a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Advanced Options', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_advanced');"><?php echo esc_html( __('Click for Advanced Options', 'si-contact-form')); ?></a></h3>
        <div style="text-align:left; display:none" id="si_contact_advanced">

         <table cellspacing="2" cellpadding="5" class="form-table">

        <tr>
         <th scope="row" style="width: 75px;"><?php echo esc_html( __('Form:', 'si-contact-form')); ?></th>
        <td>

        <input name="si_contact_auto_fill_enable" id="si_contact_auto_fill_enable" type="checkbox" <?php if( $si_contact_opt['auto_fill_enable'] == 'true' ) echo 'checked="checked"'; ?> />
       <label name="si_contact_auto_fill_enable" for="si_contact_auto_fill_enable"><?php echo esc_html( __('Enable auto form fill', 'si-contact-form')); ?>.</label>
       <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_auto_fill_enable_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
       <div style="text-align:left; display:none" id="si_contact_auto_fill_enable_tip">
       <?php echo esc_html( __('Auto form fill email address and name (username) on the contact form for logged in users who are not administrators.', 'si-contact-form')); ?>

       </td>
      </tr>

        <tr>
         <th scope="row" style="width: 75px;"><?php echo esc_html( __('Style:', 'si-contact-form')); ?></th>
        <td>

        <input name="si_contact_border_enable" id="si_contact_border_enable" type="checkbox" <?php if ( $si_contact_opt['border_enable'] == 'true' ) echo ' checked="checked" '; ?> />
        <label for="si_contact_border_enable"><?php echo esc_html( __('Enable border on contact form', 'si-contact-form')) ?>.</label>
        <label for="si_contact_border_width"><?php echo esc_html( __('Border Width', 'si-contact-form')); ?>:</label><input name="si_contact_border_width" id="si_contact_border_width" type="text" value="<?php echo absint($si_contact_opt['border_width']);  ?>" size="3" />
        <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_border_width_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
        <div style="text-align:left; display:none" id="si_contact_border_width_tip">
        <?php echo esc_html( __('Use to adjust the width of the contact form border (if border is enabled).', 'si-contact-form')); ?>
        </div>
        <br />


        <label for="si_contact_title_style"><?php echo esc_html( __('CSS style for form input titles on the contact form', 'si-contact-form')); ?>:</label><input name="si_contact_title_style" id="si_contact_title_style" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_style']);  ?>" size="50" /><br />
        <label for="si_contact_field_style"><?php echo esc_html( __('CSS style for form input fields on the contact form', 'si-contact-form')); ?>:</label><input name="si_contact_field_style" id="si_contact_field_style" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['field_style']);  ?>" size="50" /><br />
        <label for="si_contact_error_style"><?php echo esc_html( __('CSS style for form input errors on the contact form', 'si-contact-form')); ?>:</label><input name="si_contact_error_style" id="si_contact_error_style" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['error_style']);  ?>" size="50" /><br />

       <label for="si_contact_field_size"><?php echo esc_html( __('Input Text Field Size', 'si-contact-form')); ?>:</label><input name="si_contact_field_size" id="si_contact_field_size" type="text" value="<?php echo absint($si_contact_opt['field_size']);  ?>" size="3" />
       <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_field_size_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
       <div style="text-align:left; display:none" id="si_contact_field_size_tip">
       <?php echo esc_html( __('Use to adjust the size of the contact form text input fields.', 'si-contact-form')); ?>
       </div>
       <br />

       <label for="si_contact_text_cols"><?php echo esc_html( __('Input Textarea Field Cols', 'si-contact-form')); ?>:</label><input name="si_contact_text_cols" id="si_contact_text_cols" type="text" value="<?php echo absint($si_contact_opt['text_cols']);  ?>" size="3" />
       <label for="si_contact_text_rows"><?php echo esc_html( __('Rows', 'si-contact-form')); ?>:</label><input name="si_contact_text_rows" id="si_contact_text_rows" type="text" value="<?php echo absint($si_contact_opt['text_rows']);  ?>" size="3" />
       <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_text_rows_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
       <div style="text-align:left; display:none" id="si_contact_text_rows_tip">
       <?php echo esc_html( __('Use to adjust the size of the contact form message textarea.', 'si-contact-form')); ?>
       </div>
       <br />

       <input name="si_contact_aria_required" id="si_contact_aria_required" type="checkbox" <?php if( $si_contact_opt['aria_required'] == 'true' ) echo 'checked="checked"'; ?> />
       <label name="si_contact_aria_required" for="si_contact_aria_required"><?php echo esc_html( __('Enable aria-required tags for screen readers', 'si-contact-form')); ?>.</label>
       <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_aria_required_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
       <div style="text-align:left; display:none" id="si_contact_aria_required_tip">
       <?php echo esc_html( __('aria-required is a form input WAI ARIA tag. Screen readers use it to determine which fields are required. Enabling this is good for accessability, but will cause the HTML to fail the W3C Validation (there is no attribute "aria-required"). WAI ARIA attributes are soon to be accepted by the HTML validator, so you can safely ignore the validation error it will cause.', 'si-contact-form')); ?>

      </td>
    </tr>
    <tr>
         <th scope="row" style="width: 75px;"><?php echo esc_html( __('Fields:', 'si-contact-form')); ?></th>
        <td>
        <a style="cursor:pointer;" title="<?php echo esc_html( __('Click for Help!', 'si-contact-form')); ?>" onclick="toggleVisibility('si_contact_text_fields_tip');"><?php echo esc_html( __('help', 'si-contact-form')); ?></a>
       <div style="text-align:left; display:none" id="si_contact_text_fields_tip">
       <?php echo esc_html( __('Some people wanted to change the text labels for the contact form. These fields can be filled in to override the standard included field titles.', 'si-contact-form')); ?>
       </div>
       <br />
         <label for="si_contact_title_border"><?php echo esc_html( __('Contact Form', 'si-contact-form')); ?>:</label><input name="si_contact_title_border" id="si_contact_title_border" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_border']);  ?>" size="50" /><br />
         <label for="si_contact_title_dept"><?php echo esc_html( __('Department to Contact', 'si-contact-form')); ?>:</label><input name="si_contact_title_dept" id="si_contact_title_dept" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_dept']);  ?>" size="50" /><br />
         <label for="si_contact_title_name"><?php echo esc_html( __('Name', 'si-contact-form')); ?>:</label><input name="si_contact_title_name" id="si_contact_title_name" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_name']);  ?>" size="50" /><br />
         <label for="si_contact_title_email"><?php echo esc_html( __('E-Mail Address', 'si-contact-form')); ?>:</label><input name="si_contact_title_email" id="si_contact_title_email" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_email']);  ?>" size="50" /><br />
         <label for="si_contact_title_email2"><?php echo esc_html( __('E-Mail Address again', 'si-contact-form')); ?>:</label><input name="si_contact_title_email2" id="si_contact_title_email2" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_email2']);  ?>" size="50" /><br />
         <label for="si_contact_title_email2"><?php echo esc_html( __('Please enter your E-mail Address a second time.', 'si-contact-form')); ?></label><input name="si_contact_title_email2_help" id="si_contact_title_email2_help" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_email2_help']);  ?>" size="50" /><br />
         <label for="si_contact_title_subj"><?php echo esc_html( __('Subject', 'si-contact-form')); ?>:</label><input name="si_contact_title_subj" id="si_contact_title_subj" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_subj']);  ?>" size="50" /><br />
         <label for="si_contact_title_mess"><?php echo esc_html( __('Message', 'si-contact-form')); ?>:</label><input name="si_contact_title_mess" id="si_contact_title_mess" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_mess']);  ?>" size="50" /><br />
         <label for="si_contact_title_capt"><?php echo esc_html( __('CAPTCHA Code', 'si-contact-form')); ?>:</label><input name="si_contact_title_capt" id="si_contact_title_capt" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_capt']);  ?>" size="50" /><br />
         <label for="si_contact_title_submit"><?php echo esc_html( __('Submit', 'si-contact-form')); ?></label><input name="si_contact_title_submit" id="si_contact_title_submit" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['title_submit']);  ?>" size="50" /><br />
         <label for="si_contact_text_message_sent"><?php echo esc_html( __('Your message has been sent, thank you.', 'si-contact-form')); ?></label><input name="si_contact_text_message_sent" id="si_contact_text_message_sent" type="text" value="<?php echo $this->ctf_output_string($si_contact_opt['text_message_sent']);  ?>" size="50" /><br />

        </td>
    </tr>
    </table>
  </div>

        </fieldset>


        <p class="submit">
                <input type="submit" name="submit" value="<?php echo esc_attr( __('Update Options', 'si-contact-form')); ?> &raquo;" />
        </p>
</form>
</div>
<?php
}// end function options_page

function si_contact_captcha_perm_dropdown($select_name, $checked_value='') {
        // choices: Display text => permission_level
        $choices = array (
                 esc_attr( __('All registered users', 'si-contact-form')) => 'read',
                 esc_attr( __('Edit posts', 'si-contact-form')) => 'edit_posts',
                 esc_attr( __('Publish Posts', 'si-contact-form')) => 'publish_posts',
                 esc_attr( __('Moderate Comments', 'si-contact-form')) => 'moderate_comments',
                 esc_attr( __('Administer site', 'si-contact-form')) => 'level_10'
                 );
        // print the <select> and loop through <options>
        echo '<select name="' . $select_name . '" id="' . $select_name . '">' . "\n";
        foreach ($choices as $text => $capability) :
                if ($capability == $checked_value) $checked = ' selected="selected" ';
                echo "\t". '<option value="' . $capability . '"' . $checked . ">$text</option> \n";
                $checked = '';
        endforeach;
        echo "\t</select>\n";
} // end function si_contact_captcha_perm_dropdown


// this function prints the contact form
// and does all the decision making to send the email or not
function si_contact_form_short_code() {
   global $captcha_path_cf, $si_contact_opt;

  // a couple language options need to be translated now.
  $this->si_contact_update_lang();

// Email address(s) to receive Bcc (Blind Carbon Copy) messages
$ctf_email_address_bcc = $si_contact_opt['email_bcc']; // optional

// E-mail Contacts
// the drop down list array will be made automatically by this code
// checks for properly configured E-mail To: addresses in options.
$ctf_contacts = array ();
$ctf_contacts_test = trim($si_contact_opt['email_to']);
if(!preg_match("/,/", $ctf_contacts_test) ) {
    if($this->ctf_validate_email($ctf_contacts_test)) {
        // user1@example.com
       $ctf_contacts[] = array('CONTACT' => __('Webmaster', 'si-contact-form'),  'EMAIL' => $ctf_contacts_test );
    }
} else {
  $ctf_ct_arr = explode("\n",$ctf_contacts_test);
  if (is_array($ctf_ct_arr) ) {
    foreach($ctf_ct_arr as $line) {
       // echo '|'.$line.'|' ;
       list($key, $value) = explode(",",$line);
       $key   = trim($key);
       $value = trim($value);
       if ($key != '' && $value != '') {
          if(!preg_match("/;/", $value)) {
               // just one email here
               // Webmaster,user1@example.com
               if ($this->ctf_validate_email($value)) {
                  $ctf_contacts[] = array('CONTACT' => $this->ctf_output_string($key),  'EMAIL' => $value);
               }
          } else {
               // multiple emails here (additional ones will be Cc:)
               // Webmaster,user1@example.com;user2@example.com
               $multi_cc_arr = explode(";",$value);
               $multi_cc_string = '';
               foreach($multi_cc_arr as $multi_cc) {
                   if ($this->ctf_validate_email($multi_cc)) {
                     $multi_cc_string .= "$multi_cc,";
                   }
               }
               if ($multi_cc_string != '') { // multi cc emails
                  $ctf_contacts[] = array('CONTACT' => $this->ctf_output_string($key),  'EMAIL' => rtrim($multi_cc_string, ','));
               }
         }
      }

   } // end foreach
  } // end if (is_array($ctf_ct_arr) ) {
} // end else

//print_r($ctf_contacts);

// Normally this setting will be left blank in options.
$ctf_email_on_this_domain =  $si_contact_opt['email_from']; // optional

// Site Name / Title
$ctf_sitename = get_option('blogname');

// Site Domain without the http://www like this: $domain = '642weather.com';
// Can be a single domain:      $ctf_domain = '642weather.com';
// Can be an array of domains:  $ctf_domain = array('642weather.com','someothersite.com');
        // get blog domain
        $uri = parse_url(get_option('siteurl'));
        $blogdomain = str_replace('www.','',$uri['host']);

$this->ctf_domain = $blogdomain;

// Make sure the form was posted from your host name only.
// This is a security feature to prevent spammers from posting from files hosted on other domain names
// "Input Forbidden" message will result if host does not match
$this->ctf_domain_protect = $si_contact_opt['domain_protect'];

// Double E-mail entry is optional
// enabling this requires user to enter their email two times on the contact form.
$ctf_enable_double_email = $si_contact_opt['double_email'];

// You can ban known IP addresses
// SET  $ctf_enable_ip_bans = 1;  ON,  $ctf_enable_ip_bans = 0; for OFF.
$ctf_enable_ip_bans = 0;

// Add IP addresses to ban here:  (be sure to SET  $ctf_enable_ip_bans = 1; to use this feature
$ctf_banned_ips = array(
'22.22.22.22', // example (add, change, or remove as needed)
'33.33.33.33', // example (add, change, or remove as needed)
);

// Wordwrap E-Mail message text so lines are no longer than 70 characters.
// SET  $ctf_wrap_message = 1;  ON,  $ctf_wrap_message = 0; for OFF.
$ctf_wrap_message = 1;

// Redirect to Home Page after message is sent
$ctf_redirect_enable = $si_contact_opt['redirect_enable'];
// Used for the delay timer once the message has been sent
$ctf_redirect_timeout = 5; // time in seconds to wait before loading another Web page
// Web page to send the user to after the time has expired
$ctf_redirect_url = $si_contact_opt['redirect_url'];

// The $ctf_welcome_intro is what gets printed when the contact form is first presented.
// It is not printed when there is an input error and not printed after the form is completed
$ctf_welcome_intro = '

'.$si_contact_opt['welcome'].'

';

// The $thank_you is what gets printed after the form is sent.
$ctf_thank_you = '
<p>
';
if ($si_contact_opt['text_message_sent'] != '') {
        $ctf_thank_you .= esc_html( $si_contact_opt['text_message_sent']);
} else {
        $ctf_thank_you .= esc_html(__('Your message has been sent, thank you.', 'si-contact-form'));
}
$ctf_thank_you .= '
</p>
';

if ($ctf_redirect_enable == 'true') {
  $wp_plugin_url = WP_PLUGIN_URL;

 $ctf_thank_you .= <<<EOT

<script type="text/javascript" language="javascript">
<!--
var count=$ctf_redirect_timeout;
var time;
function timedCount() {
  document.title='Redirecting in ' + count + ' seconds';
  count=count-1;
  time=setTimeout("timedCount()",1000);
  if (count==-1) {
    clearTimeout(time);
    document.title='Redirecting ...';
    self.location='$ctf_redirect_url';
  }
}
window.onload=timedCount;
//-->
</script>
EOT;

$ctf_thank_you .= '
<img src="'.$wp_plugin_url.'/si-contact-form/ctf-loading.gif" alt="'.esc_attr(__('Redirecting', 'si-contact-form')).'" />&nbsp;&nbsp;
'.esc_html( __('Redirecting', 'si-contact-form')).' ... ';


// do not remove the above EOT line

}

// add numbered keys starting with 1 to the $contacts array
$cont = array();
$ct = 1;
foreach ($ctf_contacts as $v)  {
    $cont["$ct"] = $v;
    $ct++;
}
$contacts = $cont;
unset($cont);

// initialize vars
$string = '';
$this->si_contact_error = 0;
$si_contact_error_print = '';
$message_sent = 0;
$mail_to    = '';
$to_contact = '';
$name       = '';
$email      = '';
$email2     = '';
$subject    = '';
$message       = '';
$captcha_code  = '';
// add another field here like above

$si_contact_error_captcha = '';
$si_contact_error_contact = '';
$si_contact_error_name    = '';
$si_contact_error_email   = '';
$si_contact_error_email2  = '';
$si_contact_error_double_email = '';
$si_contact_error_subject = '';
$si_contact_error_message = '';
// add another field here like above

// see if WP user
global $current_user, $user_ID;
get_currentuserinfo();

// process form now
if (isset($_POST['si_contact_action']) && ($_POST['si_contact_action'] == 'send')) {

    // check all input variables
    $cid = $this->ctf_clean_input($_POST['si_contact_CID']);
    if(empty($cid)) {
       $this->si_contact_error = 1;
       $si_contact_error_contact = __('Selecting a contact is required.', 'si-contact-form');
    }
    else if (!isset($contacts[$cid]['CONTACT'])) {
        $this->si_contact_error = 1;
        $si_contact_error_contact = __('Requested Contact not found.', 'si-contact-form');
    }
    if (empty($ctf_contacts)) {
       $this->si_contact_error = 1;
    }
    $mail_to    = $this->ctf_clean_input($contacts[$cid]['EMAIL']);
    $to_contact = $this->ctf_clean_input($contacts[$cid]['CONTACT']);


    $name    = $this->ctf_name_case($this->ctf_clean_input($_POST['si_contact_name']));
    $email   = strtolower($this->ctf_clean_input($_POST['si_contact_email']));
    if ($ctf_enable_double_email == 'true') {
       $email2   = strtolower($this->ctf_clean_input($_POST['si_contact_email2']));
    }
    $subject      = $this->ctf_name_case($this->ctf_clean_input($_POST['si_contact_subject']));
    $message      = $this->ctf_clean_input($_POST['si_contact_message']);
    if ( $this->isCaptchaEnabled() ) {
     $captcha_code = $this->ctf_clean_input($_POST['si_contact_captcha_code']);
    }
    // add another field here like above

    // check posted input for email injection attempts
    // fights common spammer tactics
    // look for newline injections
    $this->ctf_forbidifnewlines($name);
    $this->ctf_forbidifnewlines($email);
    if ($ctf_enable_double_email == 'true') {
       $this->ctf_forbidifnewlines($email2);
    }
    $this->ctf_forbidifnewlines($subject);

    // look for lots of other injections
    $forbidden = 0;
    $forbidden = $this->ctf_spamcheckpost();
    if ($forbidden) {
       wp_die(__('Contact Form has Invalid Input', 'si-contact-form'));
    }

   // check for banned ip
   if( $ctf_enable_ip_bans && in_array($_SERVER['REMOTE_ADDR'], $ctf_banned_ips) ) {
      wp_die(__('Your IP is Banned', 'si-contact-form'));
   }

   // CAPS Decapitator
   if (!preg_match("/[a-z]/", $message)) {
      $message = $this->ctf_name_case($message);
   }

   if(empty($name)) {
       $this->si_contact_error = 1;
       $si_contact_error_name = __('Your name is required.', 'si-contact-form');
   }
   if (!$this->ctf_validate_email($email)) {
       $this->si_contact_error = 1;
       $si_contact_error_email = __('A proper e-mail address is required.', 'si-contact-form');
   }
   if ($ctf_enable_double_email == 'true' && !$this->ctf_validate_email($email2)) {
       $this->si_contact_error = 1;
       $si_contact_error_email2 = __('A proper e-mail address is required.', 'si-contact-form');
   }
   if ($ctf_enable_double_email == 'true' && ($email != $email2) ) {
       $this->si_contact_error = 1;
       $si_contact_error_double_email = __('The two e-mail addresses did not match, please enter again.', 'si-contact-form');
   }
   if(empty($subject)) {
       $this->si_contact_error = 1;
       $si_contact_error_subject = __('Subject text is required.', 'si-contact-form');
   }
   if(empty($message)) {
       $this->si_contact_error = 1;
       $si_contact_error_message = __('Message text is required.', 'si-contact-form');
   }

   // Check with Akismet, but only if Akismet is installed, activated, and has a KEY. (Recommended for spam control).
   if( function_exists('akismet_http_post') && get_option('wordpress_api_key') ){
			global $akismet_api_host, $akismet_api_port;
			$c['user_ip']    		= preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
			$c['user_agent'] 		= $_SERVER['HTTP_USER_AGENT'];
			$c['referrer']   		= $_SERVER['HTTP_REFERER'];
			$c['blog']       		= get_option('home');
			$c['permalink']       	= get_permalink();
			$c['comment_type']      = 'sicontactform';
			$c['comment_author']    = $name;
			$c['comment_content']   = $message;
            //$c['comment_content']  = "viagra-test-123";  // uncomment this to test spam detection

			$ignore = array( 'HTTP_COOKIE' );

			foreach ( $_SERVER as $key => $value )
				if ( !in_array( $key, $ignore ) )
					$c["$key"] = $value;

			$query_string = '';
			foreach ( $c as $key => $data )
				$query_string .= $key . '=' . urlencode( stripslashes($data) ) . '&';
			$response = akismet_http_post($query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port);
			if ( 'true' == $response[1] ) {
                $this->si_contact_error = 1; // Akismet says it is spam.
                $si_contact_error_message = __('Contact Form has Invalid Input', 'si-contact-form');
			}
    } // end if(function_exists('akismet_http_post')){

   // add another field here like 4 lines above (only if you want it to be required)

  // begin captcha check if enabled
  // captcha is optional but recommended to prevent spam bots from spamming your contact form
  if ( $this->isCaptchaEnabled() ) {
    if (!isset($_SESSION['securimage_code_value']) || empty($_SESSION['securimage_code_value'])) {
          $this->si_contact_error = 1;
          $si_contact_error_captcha = __('Could not read CAPTCHA cookie. Make sure you have cookies enabled and not blocking in your web browser settings. Or another plugin is conflicting. See plugin FAQ.', 'si-contact-form');
    }else{
       if (empty($captcha_code) || $captcha_code == '') {
         $this->si_contact_error = 1;
         $si_contact_error_captcha = __('Please complete the CAPTCHA.', 'si-contact-form');
       } else {
         include_once "$captcha_path_cf/securimage.php";
         $img = new Securimage();
         $valid = $img->check("$captcha_code");
         // Check, that the right CAPTCHA password has been entered, display an error message otherwise.
         if($valid == true) {
             // ok can continue
         } else {
              $this->si_contact_error = 1;
              $si_contact_error_captcha = __('That CAPTCHA was incorrect.', 'si-contact-form');
         }
    }
   }
  } // end if enable captcha
  // end captcha check

  if (!$this->si_contact_error) {
     // ok to send the email, so prepare the email message

     // lines separated by \n on Unix and \r\n on Windows
     if (!defined('PHP_EOL')) define ('PHP_EOL', strtoupper(substr(PHP_OS,0,3) == 'WIN') ? "\r\n" : "\n");

     $subj = $si_contact_opt['email_subject'] ." $subject";

     $msg = __('To', 'si-contact-form').": $to_contact

".__('From', 'si-contact-form').":
$name
$email

".__('Message', 'si-contact-form').":
$message

";
// add another field here (in the $msg code above)

      // add some info about sender to the email message
      $userdomain = '';
      $userdomain = gethostbyaddr($_SERVER['REMOTE_ADDR']);
      $user_info_string = '';
      if ($user_ID != '' && !current_user_can('level_10') ) {
        //user logged in
        $user_info_string .= __('From a WordPress user', 'si-contact-form').': '.$current_user->user_login . PHP_EOL;
      }
      $user_info_string .= __('Sent from (ip address)', 'si-contact-form').': '.$_SERVER['REMOTE_ADDR']." ($userdomain)" . PHP_EOL;
      $user_info_string .= __('Date/Time', 'si-contact-form').': '.date_i18n(get_option('date_format').' '.get_option('time_format'), time() ) . PHP_EOL;
      $user_info_string .= __('Coming from (referer)', 'si-contact-form').': '.get_permalink() . PHP_EOL;
      $user_info_string .= __('Using (user agent)', 'si-contact-form').': '.$this->ctf_clean_input($_SERVER['HTTP_USER_AGENT']) . PHP_EOL . PHP_EOL;
      $msg .= $user_info_string;

      // wordwrap email message
      if ($ctf_wrap_message) {
             $msg = wordwrap($msg, 70);
      }

      // prepare the email header
      if ($ctf_email_on_this_domain != '') {
          $header =  "From: $ctf_email_on_this_domain" . PHP_EOL;
      } else {
          $header = "From: $name <$email>" . PHP_EOL;
      }

      if ($ctf_email_address_bcc !='') $header .= "Bcc: " . $ctf_email_address_bcc . PHP_EOL;
      $header .= "Reply-To: $email" . PHP_EOL;
      $header .= "Return-Path: $email" . PHP_EOL;
      $header .= 'Content-type: text/plain; charset='. get_option('blog_charset') . PHP_EOL;

      ini_set('sendmail_from', $email); // needed for some windows servers

      wp_mail($mail_to,$subj,$msg,$header);

      $message_sent = 1;

   } // end if ! error
} // end if posted si_contact_action = send

if($message_sent) {
      // thank you mesage is printed here
      $string .= $ctf_thank_you;
}else{
      if (!$this->si_contact_error) {
        // welcome intro is printed here unless message is sent
        $string .= $ctf_welcome_intro;
      }

 $this->ctf_title_style = 'style="'.$si_contact_opt['title_style'].'"';
 $this->ctf_field_style = 'style="'.$si_contact_opt['field_style'].'"';
 $this->ctf_error_style = 'style="'.$si_contact_opt['error_style'].'"';
 $ctf_field_size = absint($si_contact_opt['field_size']);


 if ($si_contact_opt['aria_required'] == 'true') {
         $this->ctf_aria_required = ' aria-required="true" ';
 } else {
         $this->ctf_aria_required = '';
 }

$string .= '
<!-- SI Contact Form plugin begin -->
<form action="#" id="si_contact_form" method="post">
';


if ($si_contact_opt['border_enable'] == 'true') {
  $string .= '
  <div style="width:'.absint($si_contact_opt['border_width']).'px;">
    <fieldset>
        <legend>';
     if ($si_contact_opt['title_border'] != '') {
            $string .= esc_html( $si_contact_opt['title_border'] );
     } else {
            $string .= esc_html( __('Contact Form', 'si-contact-form'));
     }
     $string .= '</legend>';
}

// print any input errors
if ($this->si_contact_error) {
    $string .= '<div '.$this->ctf_error_style.'>'.__('INPUT ERROR: Please make corrections below and try again.', 'si-contact-form').'</div>'."\n";
}
if (empty($ctf_contacts)) {
   $string .= '<div '.$this->ctf_error_style.'>'.__('ERROR: Misconfigured E-mail address in options.', 'si-contact-form').'</div>'."\n";
}

if (count($contacts) > 1) {

     $string .= '        <div '.$this->ctf_title_style.'>
                <label for="si_contact_CID">';
     if ($si_contact_opt['title_dept'] != '') {
            $string .= esc_html( $si_contact_opt['title_dept']);
     } else {
            $string .= esc_html( __('Department to Contact', 'si-contact-form')).':';
     }
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_contact).'
        <div '.$this->ctf_field_style.'>
                <select id="si_contact_CID" name="si_contact_CID" '.$this->ctf_aria_required.'>
';

    $string .= '                        <option value="">'.esc_attr(__('Select', 'si-contact-form')).'</option>'."\n";

     if ( !isset($cid) ) {
          $cid = $_GET['si_contact_CID'];
     }

     $selected = '';

      foreach ($contacts as $k => $v)  {
          if (!empty($cid) && $cid == $k) {
                    $selected = 'selected="selected"';
          }
          $string .= '                        <option value="' . esc_attr($k) . '" ' . $selected . '>' . esc_attr($v[CONTACT]) . '</option>' . "\n";
          $selected = '';
      }

      $string .= '            </select>
      </div>' . "\n";
}
else {

     $string .= '<input type="hidden" name="si_contact_CID" value="1" />'."\n";

}

// find logged in user's WP email address (auto form fill feature):
// http://codex.wordpress.org/Function_Reference/get_currentuserinfo
if ($email == '') {
  if (
  $user_ID != '' &&
  $current_user->user_login != 'admin' &&
  !current_user_can('level_10') &&
  $si_contact_opt['auto_fill_enable'] == 'true'
  ) {
     //user logged in (and not admin rights) (and auto_fill_enable set in options)
     $email = $current_user->user_email;
     $email2 = $current_user->user_email;
     if ($name == '') {
        $name = $current_user->user_login;
     }
  }
}

$string .= '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_name">';
     if ($si_contact_opt['title_name'] != '') {
            $string .= esc_html( $si_contact_opt['title_name'] );
     } else {
            $string .= esc_html( __('Name', 'si-contact-form')).':';
     }
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_name).'
        <div '.$this->ctf_field_style.'>
                <input type="text" id="si_contact_name" name="si_contact_name" value="' . $this->ctf_output_string($name) .'" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>';

if ($ctf_enable_double_email == 'true') {
 $string .= '
        <div '.$this->ctf_title_style.'>
        <label for="si_contact_email">';
     if ($si_contact_opt['title_email'] != '') {
            $string .= esc_html( $si_contact_opt['title_email'] );
     } else {
            $string .= esc_html( __('E-Mail Address', 'si-contact-form')).':';
     }
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_email).'
         '.$this->ctf_echo_if_error($si_contact_error_double_email).'
        <div '.$this->ctf_field_style.'>
                <input type="text" id="si_contact_email" name="si_contact_email" value="' . $this->ctf_output_string($email) . '" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>
        <div '.$this->ctf_title_style.'>
        <label for="si_contact_email2">';
     if ($si_contact_opt['title_email2'] != '') {
            $string .= esc_html( $si_contact_opt['title_email2'] );
     } else {
            $string .= esc_html( __('E-Mail Address again', 'si-contact-form')).':';
     }
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_email2).'
        <div '.$this->ctf_field_style.'>
                <input type="text" id="si_contact_email2" name="si_contact_email2" value="' . $this->ctf_output_string($email2) . '" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
                <br /><span class="small">';
     if ($si_contact_opt['title_email2_help'] != '') {
            $string .= esc_html( $si_contact_opt['title_email2_help'] );
     } else {
            $string .= esc_html( __('Please enter your E-mail Address a second time.', 'si-contact-form'));
     }
     $string .= '</span>
        </div>
        ';

 } else {
$string .= '
        <div '.$this->ctf_title_style.'>
        <label for="si_contact_email">';
     if ($si_contact_opt['title_email'] != '') {
            $string .= esc_html( $si_contact_opt['title_email'] );
     } else {
            $string .= esc_html( __('E-Mail Address', 'si-contact-form')).':';
     }
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_email).'
        <div '.$this->ctf_field_style.'>
                <input type="text" id="si_contact_email" name="si_contact_email" value="' . $this->ctf_output_string($email) . '" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>';

}

$string .=   '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_subject">';
     if ($si_contact_opt['title_subj'] != '') {
            $string .= esc_html( $si_contact_opt['title_subj'] );
     } else {
            $string .= esc_html( __('Subject', 'si-contact-form')).':';
     }
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_subject).'
        <div '.$this->ctf_field_style.'>
                <input type="text" id="si_contact_subject" name="si_contact_subject" value="' . $this->ctf_output_string($subject) . '" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>

        <!-- if you add another field here (similar to the title and field div code above
        be sure to change the label, id, name, string name, and si_contact_error_[string name]) -->

        <div '.$this->ctf_title_style.'>
                <label for="si_contact_message">';
     if ($si_contact_opt['title_mess'] != '') {
            $string .= esc_html( $si_contact_opt['title_mess'] );
     } else {
            $string .= esc_html( __('Message', 'si-contact-form')).':';
     }
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_message).'
        <div '.$this->ctf_field_style.'>
                <textarea id="si_contact_message" name="si_contact_message" '.$this->ctf_aria_required.' cols="'.absint($si_contact_opt['text_cols']).'" rows="'.absint($si_contact_opt['text_rows']).'">' . $this->ctf_output_string($message) . '</textarea>
        </div>
';

// captcha is optional but recommended to prevent spam bots from spamming your contact form
if ( $this->isCaptchaEnabled() ) {
    $string .= $this->addCaptchaToContactForm($si_contact_error_captcha);
}

$string .= '
<br />
<br />
<div '.$this->ctf_field_style.'>
  <input type="hidden" name="si_contact_action" value="send" />
  <input type="submit" value="';
     if ($si_contact_opt['title_submit'] != '') {
            $string .= esc_attr( $si_contact_opt['title_submit'] );
     } else {
            $string .= esc_attr( __('Submit', 'si-contact-form'));
     }
     $string .= '" />
</div>
';
if ($si_contact_opt['border_enable'] == 'true') {
  $string .= '
    </fieldset>
    </div>
  ';
}
$string .= '
</form>

<!-- SI Contact Form plugin end -->
';

}
 return $string;
} // end function si_contact_form_short_code

// Fixes the email encoding to uf8
function fixEncoding($in_str) {
  if(mb_detect_encoding($in_str) == "UTF-8" && mb_check_encoding($in_str,"UTF-8"))
    return $in_str;
  else
    return utf8_encode($in_str);
} // fixEncoding

// checks if captcha is enabled based on the current captcha permission settings set in the plugin options
function isCaptchaEnabled() {
   global $user_ID, $si_contact_opt;

   if ($si_contact_opt['captcha_enable'] !== 'true') {
        return false; // captcha setting is disabled for si contact
   }
   // skip the captcha if user is loggged in and the settings allow
   if (isset($user_ID) && intval($user_ID) > 0 && $si_contact_opt['captcha_perm'] == 'true') {
       // skip the CAPTCHA display if the minimum capability is met
       if ( current_user_can( $si_contact_opt['captcha_perm_level'] ) ) {
               // skip capthca
               return false;
        }
   }
   return true;
} // end function isCaptchaEnabled

function captchaCheckRequires() {
  global $captcha_path_cf;

  $ok = 'ok';
  // Test for some required things, print error message if not OK.
  if ( !extension_loaded('gd') || !function_exists('gd_info') ) {
      $this->captchaRequiresError .= '<p '.$this->ctf_error_style.'>'.__('ERROR: si-contact-form.php plugin says GD image support not detected in PHP!', 'si-contact-form').'</p>';
      $this->captchaRequiresError .= '<p>'.__('Contact your web host and ask them why GD image support is not enabled for PHP.', 'si-contact-form').'</p>';
      $ok = 'no';
  }
  if ( !function_exists('imagepng') ) {
      $this->captchaRequiresError .= '<p '.$this->ctf_error_style.'>'.__('ERROR: si-contact-form.php plugin says imagepng function not detected in PHP!', 'si-contact-form').'</p>';
      $this->captchaRequiresError .= '<p>'.__('Contact your web host and ask them why imagepng function is not enabled for PHP.', 'si-contact-form').'</p>';
      $ok = 'no';
  }
  if ( !file_exists("$captcha_path_cf/securimage.php") ) {
       $this->captchaRequiresError .= '<p '.$this->ctf_error_style.'>'.__('ERROR: si-contact-form.php plugin says captcha_library not found.', 'si-contact-form').'</p>';
       $ok = 'no';
  }
  if ($ok == 'no')  return false;
  return true;
}

// this function adds the captcha to the contact form
function addCaptchaToContactForm($si_contact_error_captcha) {
   global $user_ID, $captcha_url_cf, $si_contact_opt;

  $string = '';

// Test for some required things, print error message right here if not OK.
if ($this->captchaCheckRequires()) {

// the captch html
$string = '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_captcha_code">';
     if ($si_contact_opt['title_capt'] != '') {
            $string .= esc_html( $si_contact_opt['title_capt'] );
     } else {
            $string .= esc_html( __('CAPTCHA Code', 'si-contact-form')).':';
     }
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_captcha).'
        <div '.$this->ctf_field_style.'>
                <input type="text" name="si_contact_captcha_code" id="si_contact_captcha_code" '.$this->ctf_aria_required.' style="width:65px;" />
        </div>

<div style="text-align:left; float:left; width:205px; padding-top:5px;">
         <img id="siimage" style="border-style:none; margin:0; padding-right:5px; float:left;"
         src="'.$captcha_url_cf.'/securimage_show.php?sid='.md5(uniqid(time())).'"
         alt="'.__('CAPTCHA Image', 'si-contact-form').'" title="'.esc_attr(__('CAPTCHA Image', 'si-contact-form')).'" />
           <a href="'.$captcha_url_cf.'/securimage_play.php" title="'.esc_attr(__('Audible Version of CAPTCHA', 'si-contact-form')).'">
         <img src="'.$captcha_url_cf.'/images/audio_icon.gif" alt="'.esc_attr(__('Audio Version', 'si-contact-form')).'"
          style="border-style:none; margin:0; vertical-align:top;" onclick="this.blur()" /></a><br />
           <a href="#" title="'.esc_attr(__('Refresh Image', 'si-contact-form')).'" style="border-style: none"
         onclick="document.getElementById(\'siimage\').src = \''.$captcha_url_cf.'/securimage_show.php?sid=\' + Math.random(); return false">
         <img src="'.$captcha_url_cf.'/images/refresh.gif" alt="'.esc_attr(__('Reload Image', 'si-contact-form')).'"
         style="border-style:none; margin:0; vertical-align:bottom;" onclick="this.blur()" /></a>
</div>
<br /><br />
';
} else {
      $string .= $this->captchaRequiresError;
}
  return $string;
} // end function addCaptchaToContactForm

// shows contact form errors
function ctf_echo_if_error($this_error){
  if ($this->si_contact_error) {
    if (!empty($this_error)) {
         return '
         <div '.$this->ctf_error_style.'>'.esc_html(__('ERROR', 'si-contact-form')).': ' . esc_html($this_error) . '</div>'."\n";
    }
  }
} // end function ctf_echo_if_error

// functions for protecting and validating form input vars
function ctf_clean_input($string) {
    if (is_string($string)) {
      return trim($this->ctf_sanitize_string(strip_tags($this->ctf_stripslashes($string))));
    } elseif (is_array($string)) {
      reset($string);
      while (list($key, $value) = each($string)) {
        $string[$key] = $this->ctf_clean_input($value);
      }
      return $string;
    } else {
      return $string;
    }
} // end function ctf_clean_input

// functions for protecting and validating form vars
function ctf_sanitize_string($string) {
    $string = preg_replace("/ +/", ' ', trim($string));
    return preg_replace("/[<>]/", '_', $string);
} // end function ctf_sanitize_string

// functions for protecting and validating form vars
function ctf_stripslashes($string) {
        if (get_magic_quotes_gpc()) {
                return stripslashes($string);
        } else {
                return $string;
        }
} // end function ctf_stripslashes

// functions for protecting and validating form input vars
function ctf_output_string($string) {
    return str_replace('"', '&quot;', $string);
} // end function ctf_output_string

// A function knowing about name case (i.e. caps on McDonald etc)
// $name = name_case($name);
function ctf_name_case($name) {
   if ($name == '') return '';
   $break = 0;
   $newname = strtoupper($name[0]);
   for ($i=1; $i < strlen($name); $i++) {
       $subed = substr($name, $i, 1);
       if (((ord($subed) > 64) && (ord($subed) < 123)) ||
           ((ord($subed) > 48) && (ord($subed) < 58))) {
           $word_check = substr($name, $i - 2, 2);
           if (!strcasecmp($word_check, 'Mc') || !strcasecmp($word_check, "O'")) {
               $newname .= strtoupper($subed);
           }else if ($break){
               $newname .= strtoupper($subed);
           }else{
               $newname .= strtolower($subed);
           }
             $break = 0;
       }else{
             // not a letter - a boundary
             $newname .= $subed;
             $break = 1;
       }
   }
   return $newname;
} // end function ctf_name_case


// checks proper email syntax (not perfect, none of these are, but this is the best I can find)
function ctf_validate_email($email) {
   global $si_contact_opt;

   //check for all the non-printable codes in the standard ASCII set,
   //including null bytes and newlines, and return false immediately if any are found.
   if (preg_match("/[\\000-\\037]/",$email)) {
      return false;
   }
   // regular expression used to perform the email syntax check
   // http://fightingforalostcause.net/misc/2006/compare-email-regex.php
   //$pattern = "/^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|asia|cat|jobs|tel|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i";
   //$pattern = "/^([_a-zA-Z0-9-]+)(\.[_a-zA-Z0-9-]+)*@([a-zA-Z0-9-]+)(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,4})$/i";
   $pattern = "/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD";
   if(!preg_match($pattern, $email)){
      return false;
   }
   // Make sure the domain exists with a DNS check (if enabled in options)
   // MX records are not mandatory for email delivery, this is why this function also checks A and CNAME records.
   // if the checkdnsrr function does not exist (skip this extra check, the syntax check will have to do)
   // checkdnsrr available in Linux: PHP 4.3.0 and higher & Windows: PHP 5.3.0 and higher
   if ($si_contact_opt['email_check_dns'] == 'true') {
      if( function_exists('checkdnsrr') ) {
         list($user,$domain) = explode('@',$email);
         if(!checkdnsrr($domain.'.', 'MX') &&
            !checkdnsrr($domain.'.', 'A') &&
            !checkdnsrr($domain.'.', 'CNAME')) {
            // domain not found in DNS
            return false;
         }
      }
   }
   return true;
} // end function ctf_validate_email

// helps spam protect email input
// finds new lines injection attempts
function ctf_forbidifnewlines($input) {
   if (
       stristr($input, "\r")  !== false ||
       stristr($input, "\n")  !== false ||
       stristr($input, "%0a") !== false ||
       stristr($input, "%0d") !== false) {
         //wp_die(__('Contact Form has Invalid Input', 'si-contact-form'));
         $this->si_contact_error = 1;

   }
} // end function ctf_forbidifnewlines

// helps spam protect email input
// blocks contact form posted from other domains
function ctf_spamcheckpost() {

 if(!isset($_SERVER['HTTP_USER_AGENT'])){
     return 1;
  }

 // Make sure the form was indeed POST'ed:
 //  (requires your html form to use: si_contact_action="post")
 if(!$_SERVER['REQUEST_METHOD'] == "POST"){
    return 2;
 }

  // Make sure the form was posted from an approved host name.
 if ($this->ctf_domain_protect == 'true') {
   // Host names from where the form is authorized to be posted from:
   if (is_array($this->ctf_domain)) {
      $this->ctf_domain = array_map(strtolower, $this->ctf_domain);
      $authHosts = $this->ctf_domain;
   } else {
      $this->ctf_domain =  strtolower($this->ctf_domain);
      $authHosts = array("$this->ctf_domain");
   }

   // Where have we been posted from?
   if( isset($_SERVER['HTTP_REFERER']) and trim($_SERVER['HTTP_REFERER']) != '' ) {
      $fromArray = parse_url(strtolower($_SERVER['HTTP_REFERER']));
      // Test to see if the $fromArray used www to get here.
      $wwwUsed = strpos($fromArray['host'], "www.");
      if(!in_array(($wwwUsed === false ? $fromArray['host'] : substr(stristr($fromArray['host'], '.'), 1)), $authHosts)){
         return 3;
      }
   }
 } // end if domain protect

 // check posted input for email injection attempts
 // Check for these common exploits
 // if you edit any of these do not break the syntax of the regex
 $input_expl = "/(content-type|mime-version|content-transfer-encoding|to:|bcc:|cc:|document.cookie|document.write|onmouse|onkey|onclick|onload)/i";
 // Loop through each POST'ed value and test if it contains one of the exploits fromn $input_expl:
 foreach($_POST as $k => $v){
   $v = strtolower($v);
   if( preg_match($input_expl, $v) ){
     return 4;
   }
 }

 return 0;
} // end function ctf_spamcheckpost

function si_contact_plugin_action_links( $links, $file ) {
    //Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

	if ( $file == $this_plugin ){
        $settings_link = '<a href="plugins.php?page=si-contact-form/si-contact-form.php">' . esc_html( __( 'Settings', 'si-contact-form' ) ) . '</a>';
	    array_unshift( $links, $settings_link ); // before other links
	}
	return $links;
}


function si_contact_init() {
   global $si_contact_opt, $option_defaults;


   if (function_exists('load_plugin_textdomain')) {
      load_plugin_textdomain('si-contact-form', WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__)).'/languages', dirname(plugin_basename(__FILE__)).'/languages' );
   }

    $option_defaults = array(
         'donated' => 'false',
         'welcome' => __('<p>Comments or questions are welcome.</p>', 'si-contact-form'),
         'email_to' => __('Webmaster', 'si-contact-form').','.get_option('admin_email'),
         'email_from' => '',
         'email_bcc' => '',
         'email_subject' => get_option('blogname') . ' ' .__('Contact:', 'si-contact-form'),
         'double_email' => 'false',
         'domain_protect' => 'true',
         'email_check_dns' => 'true',
         'captcha_enable' => 'true',
         'captcha_perm' => 'false',
         'captcha_perm_level' => 'read',
         'redirect_enable' => 'true',
         'redirect_url' => 'index.php',
         'border_enable' => 'false',
         'border_width' => '375',
         'title_style' => 'text-align:left;',
         'field_style' => 'text-align:left;',
         'error_style' => 'color:red; text-align:left;',
         'field_size' => '40',
         'text_cols' => '40',
         'text_rows' => '15',
         'aria_required' => 'false',
         'auto_fill_enable' => 'true',
         'title_border' => '',
         'title_dept' => '',
         'title_name' => '',
         'title_email' => '',
         'title_email2' => '',
         'title_email2_help' => '',
         'title_subj' => '',
         'title_mess' => '',
         'title_capt' => '',
         'title_submit' => '',
         'text_message_sent' => '',
  );

  // upgrade path from old version
  if (!get_option('si_contact_form') && get_option('si_contact_email_to')) {
    // just now updating, migrate settings
    $option_defaults = $this->si_contact_migrate($option_defaults);

  }

  // install the option defaults
  add_option('si_contact_form', $option_defaults, '', 'yes');

  // get the options from the database
  $si_contact_opt = get_option('si_contact_form');

  // array merge incase this version has added new options
  $si_contact_opt = array_merge($option_defaults, $si_contact_opt);

  // strip slashes on get options array
  foreach($si_contact_opt as $key => $val) {
           $si_contact_opt[$key] = $this->ctf_stripslashes($val);
  }

  // a PHP session cookie is set so that the captcha can be remembered and function
  // this has to be set before any header output
  //echo "starting session ctf";
  session_cache_limiter ('private, must-revalidate');
  // start cookie session, but do not start session if captcha is disabled in options
  if( !isset( $_SESSION ) && $si_contact_opt['captcha_enable'] == 'true' ) { // play nice with other plugins
    session_start();
    //echo "session started ctf";
  }

}

function si_contact_migrate($option_defaults) {
  // read the options from the prior version
   $new_options = array ();
   foreach($option_defaults as $key => $val) {
      $new_options[$key] = $this->ctf_stripslashes( get_option( "si_contact_$key" ));
      // now delete the options from the prior version
      delete_option("si_contact_$key");
   }
   // delete settings no longer used
   delete_option('si_contact_email_language');
   delete_option('si_contact_email_charset');
   delete_option('si_contact_email_encoding');
   // by returning this the old settings will carry over to the new version
   return $new_options;
}

} // end of class
} // end of if class

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

// Pre-2.8 compatibility
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return wp_specialchars( $text );
	}
}

// Pre-2.8 compatibility
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return attribute_escape( $text );
	}
}

if (class_exists("siContactForm")) {
 $si_contact_form = new siContactForm();
}

if (isset($si_contact_form)) {

  $captcha_url_cf  = WP_PLUGIN_URL . '/si-contact-form/captcha-secureimage';
  $captcha_path_cf = WP_PLUGIN_DIR . '/si-contact-form/captcha-secureimage';

  // wp_nonce_field is used to make the admin option settings more secure
  if ( !function_exists("wp_nonce_field") ) {
        function si_contact_nonce_field($action = -1) { return; }
        $si_contact_nonce = -1;
  } else {
        function si_contact_nonce_field($action = -1) { return wp_nonce_field($action); }
        $si_contact_nonce = 'si-contact-update-key';
  }

 // si_contact initialize options
  add_action('init', array(&$si_contact_form, 'si_contact_init'));

  // si contact form admin options
  add_action('admin_menu', array(&$si_contact_form,'si_contact_add_tabs'),1);

  // adds "Settings" link to the plugin action page
  add_filter( 'plugin_action_links', array(&$si_contact_form,'si_contact_plugin_action_links'),10,2);

  // use shortcode to print the contact form or process contact form logic
  add_shortcode('si_contact_form', array(&$si_contact_form,'si_contact_form_short_code'),1);

  // options deleted when this plugin is deleted
  register_deactivation_hook(__FILE__, array(&$si_contact_form, 'si_contact_unset_options'), 1);
}

?>