<?php

// the form is being displayed now
 $this->ctf_form_style = $this->si_contact_convert_css($si_contact_opt['form_style']);
 $this->ctf_border_style = $this->si_contact_convert_css($si_contact_opt['border_style']);
 $this->ctf_select_style = $this->si_contact_convert_css($si_contact_opt['select_style']);
 $this->ctf_title_style = $this->si_contact_convert_css($si_contact_opt['title_style']);
 $this->ctf_field_style = $this->si_contact_convert_css($si_contact_opt['field_style']);
 $this->ctf_field_div_style = $this->si_contact_convert_css($si_contact_opt['field_div_style']);
 $this->ctf_error_style = $this->si_contact_convert_css($si_contact_opt['error_style']);
 $this->ctf_required_style = $this->si_contact_convert_css($si_contact_opt['required_style']);

 $ctf_field_size = absint($si_contact_opt['field_size']);

 $this->ctf_aria_required = ($si_contact_opt['aria_required'] == 'true') ? ' aria-required="true" ' : '';

if ($this->si_contact_error)
  $this->ctf_form_style = str_replace('display: none;','',$this->ctf_form_style);

$string .= '
<!-- Fast and Secure Contact Form plugin begin -->
<a name="FSContact'.$form_id_num.'" id="FSContact'.$form_id_num.'"></a>
<div '.$this->ctf_form_style.'>
';


if ($si_contact_opt['border_enable'] == 'true') {
  $string .= '
    <form '.$have_attach.'action="'.get_permalink().'#FSContact'.$form_id_num.'" id="si_contact_form'.$form_id_num.'" method="post">
    <fieldset '.$this->ctf_border_style.'>
        <legend>';
     $string .= ($si_contact_opt['title_border'] != '') ? $si_contact_opt['title_border'] : __('Contact Form', 'si-contact-form');
     $string .= '</legend>';
} else {

 $string .= '
<form '.$have_attach.'action="'.get_permalink().'#FSContact'.$form_id_num.'" id="si_contact_form'.$form_id_num.'" method="post">
';
}

// check attachment directory
$attach_dir_error = 0;
if ($have_attach){
	$attach_dir = WP_PLUGIN_DIR . '/si-contact-form/attachments/';
    $this->si_contact_init_attach_dir($attach_dir);
    if ($si_contact_opt['php_mailer_enable'] == 'php'){
       $this->si_contact_error = 1;
	   $attach_dir_error = __( 'This contact form has file attachment fields. Attachments are only supported when the Send E-Mail function is set to WordPress. You can find this setting on the contact form settings page.', 'si-contact-form' );
    }
	if ( !is_dir($attach_dir) ) {
        $this->si_contact_error = 1;
		$attach_dir_error = sprintf( __( 'This contact form has file attachment fields, but the temporary folder for the files (%s) does not exist or is not writable. Create the folder or change its permission manually.', 'si-contact-form' ), $attach_dir );
	} else {
       // delete files over 5 minutes old in the attachment directory
       $this->si_contact_clean_attach_dir($attach_dir);
	}
}

// print any input errors
if ($this->si_contact_error) {
    $string .= '<div '.$this->ctf_error_style.'>';
    $string .= ($si_contact_opt['error_correct'] != '') ? $si_contact_opt['error_correct'] : __('Please make corrections below and try again.', 'si-contact-form');
    $string .= '</div>'."\n";
    if($have_attach && $attach_dir_error) {
      $string .= '<div '.$this->ctf_error_style.'>';
      $string .= $attach_dir_error;
      $string .= '</div>'."\n";
    }
}
if (empty($ctf_contacts)) {
   $string .= '<div '.$this->ctf_error_style.'>'.__('ERROR: Misconfigured E-mail address in options.', 'si-contact-form').'</div>'."\n";
}

if ($si_contact_opt['req_field_label_enable'] == 'true' && $si_contact_opt['req_field_indicator_enable'] == 'true' ) {
   $string .=  '<div '.$this->ctf_required_style.'>';
   $string .= ($si_contact_opt['tooltip_required'] != '') ? '<span class="required">'.$si_contact_opt['req_field_indicator'].'</span>' .$si_contact_opt['tooltip_required'] : '<span class="required">'.$si_contact_opt['req_field_indicator'].'</span>' . __('(denotes required field)', 'si-contact-form');
   $string .= '</div>
';
}

if (count($contacts) > 1) {

     $string .= '        <div '.$this->ctf_title_style.'>
                <label for="si_contact_CID'.$form_id_num.'">';
     $string .= ($si_contact_opt['title_dept'] != '') ? $si_contact_opt['title_dept'] : __('Department to Contact', 'si-contact-form').':';
     $string .= $req_field_ind.'</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_contact).'
        <div '.$this->ctf_field_div_style.'>
                <select '.$this->ctf_select_style.' id="si_contact_CID'.$form_id_num.'" name="si_contact_CID" '.$this->ctf_aria_required.'>
';

    $string .= '                        <option value="">';
    $string .= ($si_contact_opt['title_select'] != '') ? esc_attr($si_contact_opt['title_select']) : esc_attr( __('Select', 'si-contact-form'));
    $string .= '</option>'."\n";

     if ( !isset($cid) && isset($_GET['si_contact_CID']) ) {
          $cid = (int)$_GET['si_contact_CID'];
     }

     $selected = '';

      foreach ($contacts as $k => $v)  {
          if (!empty($cid) && $cid == $k) {
                    $selected = ' selected="selected"';
          }
          $string .= '                        <option value="' . esc_attr($k) . '"' . $selected . '>' . esc_attr($v['CONTACT']) . '</option>' . "\n";
          $selected = '';
      }

      $string .= '            </select>
      </div>' . "\n";
}
else {

     $string .= '<div><input type="hidden" name="si_contact_CID" value="1" /></div>'."\n";

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

if($si_contact_opt['name_type'] != 'not_available' ) {

     $f_name_string = '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_f_name'.$form_id_num.'">';
     $f_name_string .= __('First Name', 'si-contact-form').':';
     if($si_contact_opt['name_type'] == 'required' )
           $f_name_string .= $req_field_ind;
     $f_name_string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_f_name).'
        <div '.$this->ctf_field_div_style.'>
                <input '.$this->ctf_field_style.' type="text" id="si_contact_f_name'.$form_id_num.'" name="si_contact_f_name" value="' . $this->ctf_output_string($f_name) .'" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>';

     $l_name_string = '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_l_name'.$form_id_num.'">';
     $l_name_string .= __('Last Name', 'si-contact-form').':';
     if($si_contact_opt['name_type'] == 'required' )
           $l_name_string .= $req_field_ind;
     $l_name_string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_l_name).'
        <div '.$this->ctf_field_div_style.'>
                <input '.$this->ctf_field_style.' type="text" id="si_contact_l_name'.$form_id_num.'" name="si_contact_l_name" value="' . $this->ctf_output_string($l_name) .'" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>';


    switch ($si_contact_opt['name_format']) {
       case 'name':

$string .= '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_name'.$form_id_num.'">';
     $string .= ($si_contact_opt['title_name'] != '') ? $si_contact_opt['title_name'] : __('Name', 'si-contact-form').':';
     if($si_contact_opt['name_type'] == 'required' )
           $string .= $req_field_ind;
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_name).'
        <div '.$this->ctf_field_div_style.'>
                <input '.$this->ctf_field_style.' type="text" id="si_contact_name'.$form_id_num.'" name="si_contact_name" value="' . $this->ctf_output_string($name) .'" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>';

      break;
      case 'first_last':

     $string .= $f_name_string;
     $string .= $l_name_string;

      break;
      case 'first_middle_i_last':

     $string .= $f_name_string;

$string .= '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_mi_name'.$form_id_num.'">';
     $string .= __('Middle Initial', 'si-contact-form').':';
     //if($si_contact_opt['name_type'] == 'required' )
     //      $string .= $req_field_ind;
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_mi_name).'
        <div '.$this->ctf_field_div_style.'>
                <input '.$this->ctf_field_style.' type="text" id="si_contact_mi_name'.$form_id_num.'" name="si_contact_mi_name" value="' . $this->ctf_output_string($mi_name) .'" '.$this->ctf_aria_required.' size="2" />
        </div>';

     $string .= $l_name_string;

      break;
      case 'first_middle_last':

     $string .= $f_name_string;

$string .= '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_m_name'.$form_id_num.'">';
     $string .= __('Middle Name', 'si-contact-form').':';
     //if($si_contact_opt['name_type'] == 'required' )
     //      $string .= $req_field_ind;
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_m_name).'
        <div '.$this->ctf_field_div_style.'>
                <input '.$this->ctf_field_style.' type="text" id="si_contact_m_name'.$form_id_num.'" name="si_contact_m_name" value="' . $this->ctf_output_string($m_name) .'" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>';

     $string .= $l_name_string;

      break;
    }
}
if($si_contact_opt['email_type'] != 'not_available' ) {
 if ($ctf_enable_double_email == 'true') {
   $string .= '
        <div '.$this->ctf_title_style.'>
        <label for="si_contact_email'.$form_id_num.'">';
     $string .= ($si_contact_opt['title_email'] != '') ? $si_contact_opt['title_email'] : __('E-Mail Address', 'si-contact-form').':';
     if($si_contact_opt['email_type'] == 'required' )
           $string .= $req_field_ind;
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_email).'
         '.$this->ctf_echo_if_error($si_contact_error_double_email).'
        <div '.$this->ctf_field_div_style.'>
                <input '.$this->ctf_field_style.' type="text" id="si_contact_email'.$form_id_num.'" name="si_contact_email" value="' . $this->ctf_output_string($email) . '" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>
        <div '.$this->ctf_title_style.'>
        <label for="si_contact_email2_'.$form_id_num.'">';
     $string .= ($si_contact_opt['title_email2'] != '') ? $si_contact_opt['title_email2'] : __('E-Mail Address again', 'si-contact-form').':';
     $string .= $req_field_ind.'</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_email2).'
        <div '.$this->ctf_field_div_style.'>
                <input '.$this->ctf_field_style.' type="text" id="si_contact_email2_'.$form_id_num.'" name="si_contact_email2" value="' . $this->ctf_output_string($email2) . '" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
                <br /><span class="small">';
     $string .= ($si_contact_opt['title_email2_help'] != '') ? $si_contact_opt['title_email2_help'] : __('Please enter your E-mail Address a second time.', 'si-contact-form');
     $string .= '</span>
        </div>
        ';

  } else {
    $string .= '
        <div '.$this->ctf_title_style.'>
        <label for="si_contact_email'.$form_id_num.'">';
     $string .= ($si_contact_opt['title_email'] != '') ? $si_contact_opt['title_email'] : __('E-Mail Address', 'si-contact-form').':';
     if($si_contact_opt['email_type'] == 'required' )
           $string .= $req_field_ind;
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_email).'
        <div '.$this->ctf_field_div_style.'>
                <input '.$this->ctf_field_style.' type="text" id="si_contact_email'.$form_id_num.'" name="si_contact_email" value="' . $this->ctf_output_string($email) . '" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />
        </div>';

  }
}

if ($si_contact_opt['ex_fields_after_msg'] != 'true') {
     // are there any optional extra fields/
     for ($i = 1; $i <= $si_contact_gb['max_fields']; $i++) {
        if ($si_contact_opt['ex_field'.$i.'_label'] != '') {
           // include the code to display extra fields
           include(WP_PLUGIN_DIR . '/si-contact-form/si-contact-form-ex-fields.php');
           break;
        }
      }
}

if($si_contact_opt['subject_type'] != 'not_available' ) {
   if (count($subjects) > 0) {

       $string .=   '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_subject_ID'.$form_id_num.'">';
     $string .= ($si_contact_opt['title_subj'] != '') ? $si_contact_opt['title_subj'] : __('Subject', 'si-contact-form').':';
     if($si_contact_opt['subject_type'] == 'required' )
           $string .= $req_field_ind;
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_subject).'
        <div '.$this->ctf_field_div_style.'>

                <select '.$this->ctf_select_style.' id="si_contact_subject_ID'.$form_id_num.'" name="si_contact_subject_ID" '.$this->ctf_aria_required.'>
';

    $string .= '                        <option value="">';
    $string .= ($si_contact_opt['title_select'] != '') ? esc_attr($si_contact_opt['title_select']) : esc_attr( __('Select', 'si-contact-form'));
    $string .= '</option>'."\n";

     if ( !isset($sid) && isset($_GET['si_contact_SID']) ) {
          $sid = (int)$_GET['si_contact_SID'];
     }

     $selected = '';

      foreach ($subjects as $k => $v)  {
          if (!empty($sid) && $sid == $k) {
                    $selected = ' selected="selected"';
          }
          $string .= '                        <option value="' . esc_attr($k) . '"' . $selected . '>' . esc_attr($v) . '</option>' . "\n";
          $selected = '';
      }

      $string .= '            </select>';

       } else {
            $string .=   '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_subject'.$form_id_num.'">';
     $string .= ($si_contact_opt['title_subj'] != '') ? $si_contact_opt['title_subj'] : __('Subject', 'si-contact-form').':';
     if($si_contact_opt['subject_type'] == 'required' )
           $string .= $req_field_ind;
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_subject).'
        <div '.$this->ctf_field_div_style.'>
          <input '.$this->ctf_field_style.' type="text" id="si_contact_subject'.$form_id_num.'" name="si_contact_subject" value="' . $this->ctf_output_string($subject) . '" '.$this->ctf_aria_required.' size="'.$ctf_field_size.'" />';
       }

        $string .= '
        </div>';
}

if($si_contact_opt['message_type'] != 'not_available' ) {
$string .=   '
        <div '.$this->ctf_title_style.'>
                <label for="si_contact_message'.$form_id_num.'">';
     $string .= ($si_contact_opt['title_mess'] != '') ? $si_contact_opt['title_mess'] : __('Message', 'si-contact-form').':';
     if($si_contact_opt['message_type'] == 'required' )
           $string .= $req_field_ind;
     $string .= '</label>
        </div> '.$this->ctf_echo_if_error($si_contact_error_message).'
        <div '.$this->ctf_field_div_style.'>
                <textarea '.$this->ctf_field_style.' id="si_contact_message'.$form_id_num.'" name="si_contact_message" '.$this->ctf_aria_required.' cols="'.absint($si_contact_opt['text_cols']).'" rows="'.absint($si_contact_opt['text_rows']).'">' . $this->ctf_output_string($message) . '</textarea>
        </div>
';
}

if ($si_contact_opt['ex_fields_after_msg'] == 'true') {
     // are there any optional extra fields/
     for ($i = 1; $i <= $si_contact_gb['max_fields']; $i++) {
        if ($si_contact_opt['ex_field'.$i.'_label'] != '') {
           // include the code to display extra fields
           include(WP_PLUGIN_DIR . '/si-contact-form/si-contact-form-ex-fields.php');
           break;
        }
      }
}

 $this->ctf_submit_style = $this->si_contact_convert_css($si_contact_opt['button_style']);
// captcha is optional but recommended to prevent spam bots from spamming your contact form
$string .= ( $this->isCaptchaEnabled() ) ? $this->addCaptchaToContactForm($si_contact_error_captcha,$form_id_num)."\n"  : '';
$string .= '
<div '.$this->ctf_title_style.'>
<div style="clear: both;">
  <input type="hidden" name="si_contact_action" value="send" />
  <input type="hidden" name="si_contact_form_id" value="'.$form_id_num.'" />
  <input type="submit" '.$this->ctf_submit_style.' value="';
     $string .= ($si_contact_opt['title_submit'] != '') ? esc_attr( $si_contact_opt['title_submit'] ) : esc_attr( __('Submit', 'si-contact-form'));
     $string .= '" />
</div>    
</div>
';
if ($si_contact_opt['border_enable'] == 'true') {
  $string .= '
    </fieldset>
  ';
}
$string .= '
</form>
</div>
';
if ($si_contact_opt['enable_credit_link'] == 'true') {
$string .= '
<p style="clear:both; padding-top: 5px;"><small>'.__('Powered by', 'si-contact-form'). ' <a href="http://wordpress.org/extend/plugins/si-contact-form/">'.__('Fast and Secure Contact Form', 'si-contact-form'). '</a></small></p>
';
}
$string .= '<!-- Fast and Secure Contact Form plugin end -->
';

?>