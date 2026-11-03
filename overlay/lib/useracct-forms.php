<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 *
 * See http://cwb.sourceforge.net/cqpweb.php
 *
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CQPweb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @file
 * 
 * Each of these functions prints a table for the right-hand side interface.
 * 
 * This file contains the forms deployed by userhome and not queryhome. 
 * 
 */


function do_usr_ui_accessdenied() : void
{
	global $User;
	
	$access_block_go = false;
	
	$main_message = 'You do not have access to this corpus.' ;
	$reason_why_message = 'It is not clear why you cannot access this data; there may be a setup glitch.';
	
	if (isset($_GET['corpusDenied']))
	{
		if ($c_info = get_corpus_info($_GET['corpusDenied']))
		{
			if (empty($c_info->title))
				$c_info->title = $c_info->name;
			
			$main_message = "You do not have access to the corpus <strong>" .  escape_html($c_info->title) . '</strong>.' ;
			
			if (isset($_GET['why']))
			{
				switch((int)$_GET['why'])
				{
				case CQPwebEnvCorpus::NOACCESS_SYSTEMCORPUS_NO_PRIV:
					$reason_why_message = "Your user account doesn't have the necessary permissions to use this corpus.";
					if (!empty($c_info->access_statement))
					{
						$reason_why_message .= "The corpus access information below may explain why.";
						$access_block_go = true;
					}
					break;
				case CQPwebEnvCorpus::NOACCESS_SYSTEMCORPUS_LOGGED_OUT:
					/* note, this doesn't activate yet, but is dealt with below instead. It anticipates "open" servers. */
					$reason_why_message = "You are not logged in to CQPweb. You need to be logged in to access this corpus. ";
					break;
				case CQPwebEnvCorpus::NOACCESS_USERCORPUS_SWITCHED_OFF:
					$reason_why_message = "The user-corpus system is switched off; all user-owned corpora are offline.";
					break;
				case CQPwebEnvCorpus::NOACCESS_USERCORPUS_NOT_OWNED:
					$reason_why_message = "This corpus belongs to another user.";
					break;
				case CQPwebEnvCorpus::NOACCESS_USERCORPUS_NO_GRANT:
					$reason_why_message = "The user who owns this corpus has not shared it with you.";
					break;
				case CQPwebEnvCorpus::NOACCESS_USERCORPUS_DISKBLOCKED:
					$main_message = "You have used up your available disk space for installing corpora. "; 
					$reason_why_message = "You cannot access <strong>any</strong> of your installed corpora until you have <a href=\"../usr/index.php?ui=showCorpora\">deleted some of the excess data</a>.";
					break;
				}
			}
		}
		else
		{
			$main_message = "The corpus wasn't found on the system. ";
			$reason_why_message = "<a class=\"control-inline\" href=\"../\">[Go back to the main page and start again.]</a>";
		}
	}
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Access denied!</th>
		</tr>
		<tr>
			<td class="layout-fg">
				
				<?php
				if ($User->logged_in)
				{
					?>
					
					<p><?= $main_message       ?></p>
					<p><?= $reason_why_message ?></p>
					
					<?php
				}
				else
				{
					?>
					
					<p>You cannot access that corpus because you are not logged in.</p>
					<p><a class="control-inline" href="../usr/index.php?ui=login">[Please log in to CQPweb]</a> and then try again!</p>
					
					<?php
				}
				?>
				
			</td>
		</tr>
	</table>
	
	<?php
	if ($access_block_go)
		do_bitui_access_statement($c_info->access_statement);
}


function do_usr_ui_welcome()
{
	global $User;
	
	if (empty($User->realname) || $User->realname == 'unknown person')
		$personalise = '';
	else
		$personalise = ', ' . escape_html($User->realname);
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">You are logged on to CQPweb</th>
		</tr>
		<?php
		if (!empty($_GET['extraMsg']))
			echo '<tr><td class="layout-fg">&nbsp;<br>', escape_html($_GET['extraMsg']), "<br>&nbsp;</td></tr>";
		echo "\n";
		?>
		<tr>
			<td class="layout-fg">
				<p>
					Welcome back to the CQPweb server<?= $personalise ?>. You are logged in to the system.
				</p>
				<p>
					This is your user page; select an option from the menu on the left.
				</p>
				<p>
					To choose a corpus and start running queries, 
					<a class="control-inline" href="../">[go back to the main homepage]</a>.
				</p>
			</td>
		</tr>
	</table>
	
	<?php

}


/*
 * A long-termish TODO: enable 2FA
 * Here are some websites about it. 
 * https://dzone.com/articles/enabling-two-factor-authentication-for-your-web-ap
 * https://techblog.bozho.net/enabling-two-factor-authentication-web-application/
 * https://www.zubairalexander.com/blog/comparison-of-google-and-microsoft-authenticator-apps/
 * https://uk.pcmag.com/security/133038/the-best-authenticator-apps
 * https://en.wikipedia.org/wiki/HMAC-based_one-time_password
 * https://en.wikipedia.org/wiki/Time-based_one-time_password
 * https://en.wikipedia.org/wiki/Google_Authenticator
 * 
 * USe TOTP
 * generate seed
 * generate QR
 * present 2FA box on login iff acct has it enabled
 * 
 * also allow a count recovery codes???????
 */

/** note, this function will print out an ExtraMsg, and print out a location-to-go-to-after, given the right values in _GET */ 
function do_usr_ui_login()
{
	global $Config;
	
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Log in to CQPweb</th>
		</tr>
		
		<?php
		if (!empty($_GET['extraMsg']))
			echo '<tr><td class="layout-fg"><p>', escape_html($_GET['extraMsg']), "</p></td></tr>\n";
		?>
		
		<tr>
			<td class="layout-fg algC">
				<?php 
				echo print_login_form( $_GET['locTo'] ?? '' ), "\n"; 
				?>
				<p>To log in to CQPweb, you must have cookies turned on in your browser.</p> 
				
				<?php
				if ($Config->allow_account_self_registration)
				{
					?>
					
					<p>
						Not registered yet? 
						<a class="cp-home-cta" href="index.php?ui=create">Create an account</a>
					</p>
					
					<?php
				}
				?>
				
				<p>
					Forgotten your password?
					<a class="control-inline" href="index.php?ui=lostPassword">Request a reset!</a>
				</p>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_usr_ui_logout()
{
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="2">Log out of CQPweb?</th>
		</tr>
		<tr>
			<td class="layout-fg algR comfy" width="50%">Are you sure you want to log out of the system?</td>
			<td class="layout-fg algL comfy" width="50%">
				<form action="useracct-act.php" method="post">
					<input type="hidden" name="userAction" value="userLogout">
					<button type="submit">Log out</button>
				</form>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_usr_ui_create() : void
{
	global $Config;
	
	/*
	 * If we are returning from a failed CAPTCHA, we should put several of the values into the slots.
	 */
	if (isset($_GET['captchaFail']))
	{
		$prepop = new stdClass();
		foreach (array('newUsername', 'newEmail', 'realName', 'affiliation', 'country') as $x)
			$prepop->$x = isset($_GET[$x]) ? escape_html($_GET[$x]) : '';
	}
	else
		$prepop = false;
	
	if (!$Config->allow_account_self_registration)
	{
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong">Account self-registration not available</th>
			</tr>
			<tr>
				<td class="layout-bg" colspan="2">
					<p>Sorry but self-registration has been disabled on this CQPweb server.</p> 
					<?php
					if (!empty($Config->account_create_contact))
						echo "<p>To request an account, contact {$Config->account_create_contact}.</p>";
					echo "\n";
					?>
				</td>
			</tr>
		</table>
		
		<?php
		return;
	}
	
	/* initialise the iso 3166-1 array... */
	require('../lib/user-iso31661.php');
	initialise_iso_31661();
	?>
	
	<form id="newUserForm" action="useracct-act.php" method="post">
		<input type="hidden" name="userAction" value="newUser">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Create an account</th>
			</tr>
			<?php require_once('../lib/cqpweb-new-ui/site-lib.php'); echo cp_signup_notice_row(); /* cqpweb-new-ui */ ?>
			<tr>
				<td class="layout-bg" colspan="2">
					<p>
						<strong>First</strong>, select a username and password. Your username can be up to <?= NONHANDLE_MAX_USERNAME ?> 
						letters long, and must not contain any spaces, quote marks or angle-brackets. 
						You <strong>can</strong> use your email address as your username if you want to (but you don't have to).
					</p>
					<p>
						<?= escape_html(cp_password_rule_text()) /* cqpweb-new-ui */ ?> 
						The length limit is <?= NONHANDLE_MAX_STRING ?> characters.
					</p>
				</td>
			</tr>
			
			<?php
			if ($prepop)
			{
				?>
				
				<tr>
					<td class="layout-contrast" colspan="2">
						<p>You failed the human-being test; please try again.</p>
						<p>You will need to re-enter your chosen password!</p>
					</td>
				</tr>
				
				<?php
			}
			//TODO - add a new on-submit check for invalid username (check_c_word no longer applied to usernames). 
			?>
			
			<tr>
				<td class="layout-fg" width="40%">
					<label for="newUsername">Enter your chosen username:</label>
				</td>
				<td class="layout-fg">
					<p style="display:inline-block">
						<input 
							type="text" 
							id="newUsername" 
							name="newUsername" 
							placeholder="Choose a username (no spaces or quote marks)" 
							size="50" 
							maxlength="<?= NONHANDLE_MAX_USERNAME ?>" 
							<?= $prepop ? " value=\"{$prepop->newUsername}\"" : '' ?>
							required
						>
					</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="newPassword">Enter your password or passphrase:</label>
				</td>
				<td class="layout-fg">
					<p style="display:inline-block">
						<input 
							type="password" 
							id="newPassword" 
							name="newPassword" 
							size="50" 
							maxlength="<?= NONHANDLE_MAX_STRING ?>" 
							placeholder="Please enter your chosen password here!"
							required
						>
					</p>
					<p id="capsLock:newPassword" style="display:none"><strong>WARNING</strong>: Caps Lock seems to be on!</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="newPasswordCheck">Retype the password or passphrase:</label>
				</td>
				<td class="layout-fg">
					<p style="display:inline-block">
						<input 
							type="password" 
							id="newPasswordCheck" 
							name="newPasswordCheck" 
							size="50" 
							maxlength="<?= NONHANDLE_MAX_STRING ?>" 
							placeholder="Please re-type your password!"
							required
						>
					</p>
					<p id="capsLock:newPasswordCheck" style="display:none"><strong>WARNING</strong>: Caps Lock seems to be on!</p>
				</td>
			</tr>
			<tr>
				<td class="layout-bg" colspan="2">
					<p>
						<strong>Now</strong>, enter your email address. We will send a verification message to this email address, 
						so it is critical that you <strong>double-check for typing errors</strong>.
					</p>
					<p>
						<em>Your account will not be activated until you click on the link that we send in that email message!</em>
					</p>
					<p>
						<strong>If you have an institutional email address</strong> (linked to a company or university, for instance), 
						<strong>you should use it to sign up</strong>.
					</p>
					<p>
						This is because your access to some corpora may depend on what
						institution you are affiliated to &ndash; and we use your email address to detect your affiliation.
						If you specify a Gmail, Yahoo or other freely-obtainable email address, we won't be able to detect
						your affiliation, and you may not have access to all the corpora that you should have access to.
					</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="newEmail">Enter your email address:</label>
					<br>
					<em>Note that this cannot be changed later!</em>
				</td>
				<td class="layout-fg">
					<input 
						type="email" 
						id="newEmail" 
						name="newEmail" 
						placeholder="your.address@somewhere.net" 
						size="50" 
						maxlength="<?= NONHANDLE_MAX_STRING ?>" 
						<?= $prepop ? " value=\"{$prepop->newEmail}\"" : '' ?>
						required
					>
				</td>
			</tr>
			
			<?php
			if ($Config->account_create_captcha)
			{
				$captcha_code = create_new_captcha();
				$params = "userAction=captchaImage&which=$captcha_code&cacheblock=" . uniqid();
				?>
				
				<tr>
					<td class="layout-fg">
						<label for="captchaResponse">Type in the 6 characters from the picture to prove you are a human being:</label>
						<br>
						<em>N.B.: all letters are lowercase.</em>
					</td>
					<td class="layout-fg">
						<img id="captchaImg" alt="Image for the human being check" src="useracct-act.php?<?= $params ?>">
						<br>
						<a onClick="refresh_captcha();" class="control-inline">[Too hard? Click for another]</a>
						<br>
						<input type="text" id="captchaResponse" name="captchaResponse" placeholder="Enter the 6 characters" size="30" maxlength="10" required>
						<input id="captchaRef" type="hidden" name="captchaRef" value="<?= $captcha_code ?>">
					</td>
				</tr>
				
				<?php
			}
			?>
			
			<tr>
				<td class="layout-bg comfy" colspan="2">
					The following three questions are optional. You can leave these parts of the form empty if you wish.
					However, it is highly useful to us to know a bit more about who is using our CQPweb installation,
					so we will be very grateful if you supply this information.
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="realName">Please enter your real name:</label>
				</td>
				<td class="layout-fg">
					<input 
						type="text" 
						id="realName"
						name="realName"
						size="50" 
						maxlength="<?= NONHANDLE_MAX_STRING ?>" 
						<?= $prepop ? " value=\"{$prepop->realName}\"" : '' ?> 
					>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="affiliation">Please enter your affiliation:</label>
					<br>
					<em>(a company, university or other body that you are associated with)</em>
				</td>
				<td class="layout-fg">
					<input 
						type="text" 
						id="affiliation" 
						name="affiliation" 
						size="50" 
						maxlength="<?= NONHANDLE_MAX_STRING ?>" 
						<?= $prepop ? " value=\"{$prepop->affiliation}\"" : '' ?>
					>
				</td>
			</tr>
			<tr>
				<td class="layout-fg"><!-- cqpweb-new-ui: the label was printed twice -->
					<label for="country">Please enter your location (select a country or territory):</label>
				</td>
				<td class="layout-fg">
					<select id="country" name="country">
						<option selected value="00">Prefer not to specify</option>
						<?php
						$use_select = ( (! $prepop) || '00' == $prepop->country ? ' selected' : '');
						echo '<option value="00"', $use_select, '>Prefer not to specify</option>', "\n";

						unset($Config->iso31661['00']);
						foreach($Config->iso31661 as $code => $country)
						{
							$use_select = ($prepop && $code == $prepop->country ? ' selected' : '');
							echo "\t\t\t\t\t\t<option value=\"$code\"$use_select>$country</option>\n";
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-bg algC comfy" colspan="2">
					When you are happy with the settings you have entered, use the button below to register.
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC comfy" colspan="2">
					<button type="submit">Register account</button>
				</td>
			</tr>

			<?php
			if (!empty($Config->account_privacy_policy_url))
			{
				?>
				<tr>
					<td class="layout-bg comfy" colspan="2">
						The data you submit when creating an account is stored subject to 
						<a href="<?= $Config->account_privacy_policy_url ?>" class="control-inline external">this server's data protection and privacy policy</a>.
					</td>
				</tr>
				<?php
			}
			?>

		</table>
	</form>

	<?php
}


function do_usr_ui_verify()
{
	$screentype = $_GET['verifyScreenType'] ?? 'newform';
	
	echo "\n";
	
	switch($screentype)
	{
	case 'newform':
	case 'badlink':
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong">Enter activation key</th>
			</tr>
			<tr>
				<td class="layout-fg">
					<?php
					if ($screentype == 'badlink')
						echo "\n\t\t\t\t\t<p>CQPweb could not read a verification key from the link you clicked.</p>\n"
							, "\t\t\t\t\t<p>Enter your 32-letter key code manually instead?</p>\n"
							;
					else
						echo "\n\t\t\t\t\t<p>You should have received an email with a 32-letter code.</p>\n"
							, "\t\t\t\t\t<p>Enter this code into the form below to activate the account.</p>\n"
							;
					?>
					<form action="useracct-act.php" method="get">
						<input type="hidden" name="userAction" value="verifyUser">
						<table class="basicbox" style="margin:auto">
							<tr>
								<td class="basicbox">
									<label for="v">Enter code here:</label>
								</td>
								<td class="basicbox">
									<input type="text" id="v" name="v" size="32" maxlength="32" required>
								</td>
							</tr>
							<tr>
								<td class="basicbox algC" colspan="2">
									<button type="submit">Verify account</button> 
								</td>
							</tr>
						</table>
					</form>
					<p>
						If you have not received an email with an activation code,
						<a class="control-inline" href="index.php?ui=resend">click here</a>
						to ask for one to be sent to your account&rsquo;s designated email address.
					</p>
				</td>
			</tr>
		</table>
		
		<?php
		break;
		
	case 'already':
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong">Account already verified</th>
			</tr>
			<tr>
				<td class="layout-fg algC">
					<p>This account has already been verified at some earlier point. Double verification isn't necessary.</p>
					<p>You are now able to log on with the username and password you created.</p>
				</td>
			</tr>
		</table>
		
		<?php 
		echo print_login_form(), "\n";
		break;
		
		
	case 'success':
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong">New account verification has succeeded!</th>
			</tr>
			<tr>
				<td class="layout-fg algC">
					<p>Your new user account has been successfully activated.</p>
					<p>Welcome to this CQPweb server!</p>
				</td>
			</tr>
		</table>
		
		<?php 
		echo print_login_form(), "\n";
		break;
		
	case 'failure':
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong">Account verification failed!</th>
			</tr>
			<tr>
				<td class="layout-fg">
					<p>Your account could not be verified. The activation key you supplied could not be found in our database.</p>
					<p>We recommend you request <a href="index.php?ui=resend">a new activation email</a>.</p>
					<p>
						If a new email does not solve the problem, we suggest 
						<a class="control-inline" href="index.php?ui=create">restarting the account-creation process from scratch</a>.
					</p>
				</td>
			</tr>
		</table>
		
		<?php
		break;
		
	case 'newEmailSent':
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong">A new verification email has been sent!</th>
			</tr>
			<tr>
				<td class="layout-fg">
					<p>Please check your email account: a message with a new activation link should arrive soon.</p>
					<p>Activation links from earlier emails will <em>no longer work</em>.</p>
				</td>
			</tr>
		</table>
		
		<?php
		break;
		
		/* no default */
	}
	
	echo "\n";
}


function do_usr_ui_resend()
{
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Re-send account activation email</th>
		</tr>
		<tr>
			<td class="layout-fg">
				<p>
					If you have created an account on CQPweb but have not received an email to activate it,
					you can use this control to request another activation email.
				</p>
				<p>
					All accounts must be verified by the owner of the associated email address via clicking
					on the activation link in the email message.
				</p>
				<form action="useracct-act.php" method="get">
					<input type="hidden" name="userAction" value="resendVerifyEmail">
					<table class="basicbox" style="margin:auto">
						<tr>
							<td class="basicbox">
								<label for="email">Enter your email address:</label>
							</td>
							<td class="basicbox">
								<input type="email" id="email" name="email" placeholder="your.address@somewhere.net" size="40" required>
							</td>
						</tr>
						<tr>
							<td class="basicbox algC" colspan="2">
								<button type="submit">Request a new activation email</button>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_usr_ui_lostusername()
{
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Retrieve lost or forgotten username</th>
		</tr>
		<tr>
			<td class="layout-fg">
				<form action="useracct-act.php" method="GET">
					<input type="hidden" name="userAction" value="remindUsername">
					<p>If you have lost or forgotten your username, you can request an email reminder.</p>
					<p>
						<label for="emailToRemind">Enter the email address you used to sign up in the text box below 
						and press &rdquo;Request username reminder email&ldquo;.</label>
					</p>
					<p>A message will be sent to your email with a reminder of your username.</p>
					<p class="algC">
						<input type="email" id="emailToRemind" name="emailToRemind" placeholder="your.address@somewhere.net" size="30" maxlength="<?= NONHANDLE_MAX_STRING ?>" required>
					</p>
					<p class="algC">
						<button type="submit">Request username reminder email</button>
					</p>
					<p class="spacer"></p>
				</form>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_usr_ui_lostpassword()
{
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="2">Reset lost password</th>
		</tr>
		
		<?php
		if (isset($_GET['showSentMessage']) && $_GET['showSentMessage'])
		{
			?>
			
			<tr>
				<td class="layout-fg algC comfy" colspan="2">
					<strong>An email has been sent to the address associated with your account. Please check your inbox!</strong>
				</td>
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td class="layout-bg" colspan="2">
				<p>
					If you have forgotten your password, or if your password has expired, 
					you can request a password-reset.
				</p>
				<p>
					<em>CQPweb does not store your password and so we cannot send you a reminder of your password</em>
					(because doing so would risk the security of your account).
					You must instead reset the password to something new.
				</p>
				<p>
					Use the <strong>first</strong> form below to request a password-reset verification code.
					This will be sent to the email address associated with your username.
				</p>
				<p>
					Then, return to this page, and use the <strong>second</strong> form below to change your password, 
					using the verification code that you have received.
				</p>
			</td>
		</tr>
	</table>
	
	<form action="useracct-act.php" method="post">
		<input type="hidden" name="userAction" value="requestPasswordReset">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Request password reset via email</th>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="userForResetRequest">Enter your username:</label>
				</td>
				<td class="layout-fg">
					<input type="text" id="userForResetRequest" name="userForResetRequest" size="40" maxlength="<?= NONHANDLE_MAX_USERNAME ?>" required>
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC comfy" colspan="2">
					<button type="submit">Request a password reset code via email</button>
				</td>
			</tr>
		</table>
	</form>
	
	<form action="useracct-act.php" method="post">
		<input type="hidden" name="userAction" value="resetUserPassword">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Reset your password</th>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="userForPasswordReset">Enter your username:</label>
				</td>
				<td class="layout-fg">
					<input type="text" id="userForPasswordReset" name="userForPasswordReset" size="40" maxlength="<?= NONHANDLE_MAX_USERNAME ?>" required>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="newPassword" >Enter your <strong>new</strong> password or passphrase:</label>
				</td>
				<td class="layout-fg">
					<p style="display:inline-block">
						<input type="password" id="newPassword" name="newPassword" size="40" maxlength="<?= NONHANDLE_MAX_STRING ?>" required>
					</p>
					<p id="capsLock:newPassword" style="display:none"><strong>WARNING</strong>: Caps Lock seems to be on!</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="newPasswordCheck">Retype the <strong>new</strong> password or passphrase:</label>
				</td>
				<td class="layout-fg">
					<p style="display:inline-block">
						<input type="password" id="newPasswordCheck" name="newPasswordCheck" size="40" maxlength="<?= NONHANDLE_MAX_STRING ?>" required>
					</p>
					<p id="capsLock:newPasswordCheck" style="display:none"><strong>WARNING</strong>: Caps Lock seems to be on!</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<label for="v">Enter the 32-letter verification code sent to you by email:</label>
					<br>
					<em>(spaces optional)</em>
				</td>
				<td class="layout-fg">
					<input type="text" id="v" name="v" size="40" maxlength="40" required>
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC comfy" colspan="2">
					<button type="submit">Reset password</button>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}


function do_usr_ui_usersettings() : void
{
	global $User;
	
	list ($optionsfrom, $optionsto) = print_fromto_form_options(10, $User->coll_from, $User->coll_to);
	?>
	
	<form action="useracct-act.php" method="get">
		<input type="hidden" name="userAction" value="revisedUserSettings">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">User interface settings</th>
			</tr>
			<tr>
				<td class="layout-bg algC" colspan="2">
					<p>Use this form to personalize your options for the user interface.</p>
					<p>Important note: these settings apply to all the corpora that you access on CQPweb.</p>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Display options</th>
			</tr>
			<tr>
				<td class="layout-fg">Default view for concordances</td>
				<td class="layout-fg">
					<select name="newSetting_conc_kwicview">
						<option value="1"<?= ($User->conc_kwicview ? ' selected' : '') ?>>KWIC view</option>
						<option value="0"<?= ($User->conc_kwicview ? '' : ' selected') ?>>Sentence view</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Default display order for concordances</td>
				<td class="layout-fg">
					<select name="newSetting_conc_corpus_order">
						<option value="1"<?= ($User->conc_corpus_order ? ' selected' : '') ?>>Corpus order</option>
						<option value="0"<?= ($User->conc_corpus_order ? '' : ' selected') ?>>Random order</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<p>Work in CQP syntax wherever possible</p>
					<ul>
						<li>select CQP syntax as query mode by default</li>
						<li>turn on CQP syntax display in query history and in title bar and query history)</li>
					</ul>
				</td>
				<td class="layout-fg">
					<select name="newSetting_cqp_syntax">
						<option value="1"<?= ($User->cqp_syntax ? ' selected' : '') ?>>Yes : work with CQP syntax</option>
						<option value="0"<?= ($User->cqp_syntax ? '' : ' selected') ?>>No  : work with the Simple Query language</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Context display</td>
				<td class="layout-fg">
					<select name="newSetting_context_with_tags">
						<option value="0"<?= ($User->context_with_tags ? '' : ' selected') ?>>Without tags</option>
						<option value="1"<?= ($User->context_with_tags ? ' selected' : '') ?>>With tags</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					Show tooltips
					<br>
					<em>
						(When moving the mouse over some links (e.g. in a concordance), additional
						information will be displayed in tooltip boxes.)
					</em>
				</td>
				<td class="layout-fg">
					<select name="newSetting_use_tooltips">
						<option value="1"<?= ($User->use_tooltips ? ' selected' : '') ?>>Yes</option>
						<option value="0"<?= ($User->use_tooltips ? '' : ' selected') ?>>No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Default setting for thinning queries</td>
				<td class="layout-fg">
					<select name="newSetting_thin_default_reproducible">
						<option value="0"<?= ($User->thin_default_reproducible ? '' : ' selected') ?>>Random: selection is not reproducible</option>
						<option value="1"<?= ($User->thin_default_reproducible ? ' selected' : '') ?>>Random: selection is reproducible</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Default setting for categorise-query autosave</td>
				<td class="layout-fg">
					<select name="newSetting_catquery_autosave">
						<option value="1"<?= ($User->catquery_autosave ? ' selected' : '') ?>>Autosave on by default</option>
						<option value="0"<?= ($User->catquery_autosave ? '' : ' selected') ?>>Autosave off by default</option>
					</select>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Collocation options</th>
			</tr>
			<tr>
				<td class="layout-fg">Default statistic to use for collocations</td>
				<td class="layout-fg">
					<select name="newSetting_coll_statistic">
						<?= print_colloc_statistic_form_options($User->coll_statistic) ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Default minimum for f(node, collocate) [<em>frequency of node/collocate co-occurrence</em>]</td>
				<td class="layout-fg">
					<select name="newSetting_coll_freqtogether">
						<?= print_freqtogether_form_options($User->coll_freqtogether) ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Default minimum for f(collocate) [<em>overall frequency of collocate</em>]</td>
				<td class="layout-fg">
					<select name="newSetting_coll_freqalone">
						<?= print_freqalone_form_options($User->coll_freqalone) ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Default span for calculating collocations</td>
				<td class="layout-fg">
					From
					<select name="newSetting_coll_from">
						<?= $optionsfrom ?>
					</select>
					to
					<select name="newSetting_coll_to">
						<?= $optionsto ?>
					</select>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Keywords options</th>
			</tr>
			<tr>
				<td class="layout-fg">Default statistic to use for keywords</td>
				<td class="layout-fg">
					<select name="newSetting_key_statistic">
						<?= print_key_statistic_form_options($User->key_statistic) ?>
					</select>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Download options</th>
			</tr>
			<tr>
				<td class="layout-fg">File format to use in text-only downloads</td>
				<td class="layout-fg">
					<select name="newSetting_linefeed">
						<option value="au"<?= ($User->linefeed == 'au' ? ' selected' : '') ?>>Automatically detect my computer</option>
						<option value="da"<?= ($User->linefeed == 'da' ? ' selected' : '') ?>>Windows</option>
						<option value="a"<?=  ($User->linefeed == 'a'  ? ' selected' : '') ?>>Unix / Linux / Mac OS X</option>
						<option value="d"<?=  ($User->linefeed == 'd'  ? ' selected' : '') ?>>Mac OS 9 and below</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">File layout in frequency-list downloads</td>
				<td class="layout-fg">
					<select name="newSetting_freqlist_altstyle">
						<option value="0"<?= ($User->freqlist_altstyle ? '' : ' selected') ?>>CQPweb default layout</option>
						<option value="1"<?= ($User->freqlist_altstyle ? ' selected' : '') ?>>AntConc-compatible layout</option>
					</select>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Accessibility options</th>
			</tr>
			<tr>
				<td class="layout-fg">
					Override corpus color scheme with monochrome
					<br>
					<em>(useful if the color schemes cause you vision difficulties)</em>
				</td>
				<td class="layout-fg">
					<select name="newSetting_css_monochrome">
						<option value="1"<?= ($User->css_monochrome ? ' selected' : '') ?>>Yes</option>
						<option value="0"<?= ($User->css_monochrome ? '' : ' selected') ?>>No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-bg algC comfy" colspan="2">
					<button type="submit">Save changes</button>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}


function do_usr_ui_usermacros()
{
	global $User;
	
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="3">User's CQP macros</th>
		</tr>
		
		<?php
		$macrolist = get_all_user_macros($User->id);
		
		if(empty($macrolist))
		{
			?>
			
			<tr>
				<td class="layout-bg algC comfy" colspan="3">You have not created any user macros.</td>
			</tr>
			
			<?php
		}
		else
		{
			?>
			
			<tr>
				<th class="layout-strong">Macro</th>
				<th class="layout-strong">Macro expansion</th>
				<th class="layout-strong">Actions</th>
			</tr>
			
			<?php
			foreach ($macrolist as $o)
				echo "\n\t\t<tr>"
					, "<td class=\"layout-fg\">{$o->macro_name}({$o->macro_num_args})</td>"
					, '<td class="layout-bg"><code>', escape_html($o->macro_body), '</code></td>'
					, '<td class="layout-fg algC">'
						, '<form action="useracct-act.php" method="get">'
							, '<input type="hidden" name="userAction" value="deleteUserMacro">'
							, '<input type="hidden" name="macroId" value="', $o->id, '">'
							, '<button type="submit">Delete macro</button>'
						, '</form>'
					, '</td>'
					, "</tr>"
					;
			echo "\n";
		}
		?>
	
	</table>
	
	<form action="useracct-act.php" method="get">
		<input type="hidden" name="userAction" value="newUserMacro">
		<input type="hidden" name="macroUsername" value="<?= escape_html($User->username); ?>">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Create a new CQP macro</th>
			</tr>
			<tr>
				<td class="layout-fg">Enter a name for the macro:</td>
				<td class="layout-fg">
					<input type="text" maxlength="20" name="macroNewName" onKeyUp="check_c_word(this)">
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Enter the body of the macro:</td>
				<td class="layout-fg">
					<textarea rows="25" cols="80" name="macroNewBody"></textarea>
				</td>
			</tr>
			<tr>
				<td class="layout-bg">Click here to save your macro<br>(it will be available in all CQP queries)</td>
				<td class="layout-bg">
					<button type="submit">Create macro</button>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}


function do_usr_ui_corpusaccess() : void
{
	global $User;
	
	$header_text_mapper = array(
		PRIVILEGE_TYPE_CORPUS_FULL       => "You have <em>full</em> access to:",
		PRIVILEGE_TYPE_CORPUS_NORMAL     => "You have <em>normal</em> access to:",
		PRIVILEGE_TYPE_CORPUS_RESTRICTED => "You have <em>restricted</em> access to:"
		);
	
	/* now, compile an array of corpora to create table cells for */
	$accessible_corpora = array(
		PRIVILEGE_TYPE_CORPUS_FULL       => [],
		PRIVILEGE_TYPE_CORPUS_NORMAL     => [],
		PRIVILEGE_TYPE_CORPUS_RESTRICTED => []
		);
	foreach ($User->privileges as $p)
	{
		switch($p->type)
		{
		case PRIVILEGE_TYPE_CORPUS_FULL:
		case PRIVILEGE_TYPE_CORPUS_NORMAL:
		case PRIVILEGE_TYPE_CORPUS_RESTRICTED:
			foreach ($p->scope_object as $c)
				if ( !in_array($c, $accessible_corpora[$p->type]) )
					$accessible_corpora[$p->type][] = $c;
			break;
		default:
			break;
		}
	}
	/* remove from normal if in full */
	foreach($accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL] as $k=>$c)
		if (in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_FULL]))
			unset($accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL][$k]);
	/* remove from restricted if in full or normal */
	foreach($accessible_corpora[PRIVILEGE_TYPE_CORPUS_RESTRICTED] as $k=>$c)
		if (in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_FULL]) || in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL]))
			unset($accessible_corpora[PRIVILEGE_TYPE_CORPUS_RESTRICTED][$k]);
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="3">Corpus access permissions</th>
		</tr>
		<tr>
			<td class="layout-bg algC comfy" colspan="3">You have permission to access the following corpora:</td>
		</tr>
		
		<?php
		if ($User->is_admin())
		{
			?>
			
			<tr>
				<td class="layout-fg algC" colspan="3">
					<p><strong>You are a superuser. You have full access to everything.</strong></p>
					<p>The corpora listed below are those you would have access to <em>even without</em> superuser powers.</p>
				</td>
			</tr>
			
			<?php
		}
		
		foreach(array_keys($header_text_mapper) as $t)
// 		foreach(array(PRIVILEGE_TYPE_CORPUS_FULL, PRIVILEGE_TYPE_CORPUS_NORMAL, PRIVILEGE_TYPE_CORPUS_RESTRICTED) as $t)
		{
			if ( empty($accessible_corpora[$t] ))
				continue;
			?>
			
			<tr>
				<th class="layout-strong" colspan="3"><?= $header_text_mapper[$t] ?></th>
			</tr>
			
			<?php
			
			/* the following hunk o' code is a variant on what is found in mainhome */
			
			$i = 0;
			$celltype = 'layout-fg';
			
			foreach($accessible_corpora[$t] as $c)
			{
				if ($i == 0)
					echo "\t\t\t<tr>";
				
				/* get corpus title */
				$c_info = get_corpus_info($c);
				$corpus_title_html = (empty($c_info->title) ? $c : escape_html($c_info->title));
				
				echo <<<END_OF_TD
				
								<td class="$celltype algC comfy" width="33.3%">
									<a href="../{$c}/">$corpus_title_html</a>
								</td>
				
				END_OF_TD;
				
				$celltype = ($celltype=='layout-bg'?'layout-fg':'layout-bg');
				
				if ($i == 2)
				{
					echo "\t\t\t</tr>\n";
					$i = 0;
				}
				else
					$i++;
			}
			
			if ($i == 1)
			{
				echo "\t\t\t<td class=\"$celltype algC\" width=\"33.3%\">&nbsp;</td>\n";
				$i++;
				$celltype = ($celltype=='layout-bg'?'layout-fg':'layout-bg');
			}
			if ($i == 2)
				echo "\t\t\t<td class=\"$celltype algC\" width=\"33.3%\">&nbsp;</td>\n\t\t</tr>\n";
		}
		?>
		
		<tr>
			<td class="layout-bg comfy" colspan="3">
				If you think that you should have permissions for more corpora than are listed above,
				you should contact the system administrator, explaining which corpora you wish to use,
				and on what grounds you believe you have permission to use them.
			</td>
		</tr>
	</table>
	
	<?php
}


function do_usr_ui_userdetails()
{
	global $User;
	global $Config;
	
	/* initialise the iso 3166-1 array... */
	require('../lib/user-iso31661.php');
	initialise_iso_31661();
	
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="3">Account details</th>
		</tr>
		<tr>
			<td class="layout-fg">Username:</td>
			<td class="layout-fg" colspan="2"><?= $User->username ?></td>
		</tr>
		<?php
		echo "\n";
		if ($Config->allow_account_email_change)
		{
			?>
			<tr>
				<td class="layout-fg">
					Email address:
					<form id="updateAccountEmailForm" action="useracct-act.php" method="post"></form>
					<input form="updateAccountEmailForm" type="hidden" name="userAction" value="updateUserAccountDetails">
					<input form="updateAccountEmailForm" type="hidden" name="fieldToUpdate" value="email">
				</td>
				<td class="layout-fg">
					<input form="updateAccountEmailForm" type="email" name="updateValue" placeholder="your.address@somewhere.net" width="64" value="<?= escape_html($User->email) ?>">
				</td>
				<td class="layout-fg algC">
					<button form="updateAccountEmailForm" type="submit">Update</button>
				</td>
			</tr>
			<?php
			if (!$Config->allow_account_email_change_group_persist)
			{
				?>
				
				<tr>
					<td class="layout-bg comfy" colspan="3"><strong>Warning:</strong> changing your account's email address may result in you losing access to some corpora.</td>
				</tr>
				
				<?php
			}
		}
		else
		{
			?>
			<tr>
				<td class="layout-fg">Email address:</td>
				<td class="layout-fg" colspan="2"><?= escape_html($User->email) ?></td>
			</tr>
			<tr>
				<td class="layout-bg comfy" colspan="3">You cannot change the email address that this account is associated with.</td>
			</tr>
			<?php
		}
		echo "\n";
		?>
		<tr>
			<td class="layout-fg">
				Your name:
				<form id="updateAccountRealnameForm" action="useracct-act.php" method="post"></form>
				<input form="updateAccountRealnameForm" type="hidden" name="userAction" value="updateUserAccountDetails">
				<input form="updateAccountRealnameForm" type="hidden" name="fieldToUpdate" value="realname">
			</td>
			<td class="layout-fg">
				<input form="updateAccountRealnameForm" type="text" name="updateValue" value="<?= escape_html($User->realname) ?>">
			</td>
			<td class="layout-fg algC">
				<button form="updateAccountRealnameForm" type="submit">Update</button>
			</td>
		</tr>
		<tr>
			<td class="layout-fg">
				Your affiliation (institution or company):
				<form id="updateAccountAffiliationForm" action="useracct-act.php" method="post"></form>
				<input form="updateAccountAffiliationForm" type="hidden" name="userAction" value="updateUserAccountDetails">
				<input form="updateAccountAffiliationForm" type="hidden" name="fieldToUpdate" value="affiliation">
			</td>
			<td class="layout-fg">
				<input form="updateAccountAffiliationForm" type="text" name="updateValue" value="<?= escape_html($User->affiliation) ?>">
			</td>
			<td class="layout-fg algC">
				<button form="updateAccountAffiliationForm" type="submit">Update</button>
			</td>
		</tr>
		<tr>
			<td class="layout-fg">
				Your location:
				<form id="updateAccountCountryForm" action="useracct-act.php" method="post"></form>
				<input form="updateAccountCountryForm" type="hidden" name="userAction" value="updateUserAccountDetails">
				<input form="updateAccountCountryForm" type="hidden" name="fieldToUpdate" value="country">
			</td>
			<td class="layout-fg">
				<table class="basicbox fullwidth">
					<tr>
						<td class="basicbox"><?= escape_html($Config->iso31661[$User->country]) ?></td>
						<td class="basicbox">
							<select form="updateAccountCountryForm" name="updateValue">
								<option disabled selected>Select new location ...</option>
								<?php
								foreach ($Config->iso31661 as $k => $country)
									echo "\t\t\t\t\t\t<option value=\"$k\">", escape_html($country), "</option>\n";
								?>
							</select>
						</td>
					</tr>
				</table>
			</td>
			<td class="layout-fg algC">
				<button form="updateAccountCountryForm" type="submit">Update</button>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_usr_ui_changepassword() : void
{
	global $User;
	
	?>
	
	<form action="useracct-act.php" method="post">
		<input type="hidden" name="userAction" value="resetUserPassword">
		<input type="hidden" name="userForPasswordReset" value="<?= escape_html($User->username) ?>">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Change your password</th>
			</tr>
			<tr>
				<td class="layout-fg">Enter your <strong>current</strong> password or passphrase:</td>
				<td class="layout-fg">
					<p style="display:inline-block">
						<input id="oldPassword" type="password" name="oldPassword" size="30" maxlength="<?= NONHANDLE_MAX_STRING ?>">
					</p>
					<p id="capsLock:oldPassword" style="display:none"><strong>WARNING</strong>: Caps Lock seems to be on!</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Enter your <strong>new</strong> password or passphrase:</td>
				<td class="layout-fg">
					<p style="display:inline-block">
						<input id="newPassword" type="password" name="newPassword" size="30" maxlength="<?= NONHANDLE_MAX_STRING;?>">
					</p>
					<p id="capsLock:newPassword" style="display:none"><strong>WARNING</strong>: Caps Lock seems to be on!</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Retype the <strong>new</strong> password or passphrase:</td>
				<td class="layout-fg">
					<p style="display:inline-block">
						<input id="newPasswordCheck" type="password" name="newPasswordCheck" size="30" maxlength="<?= NONHANDLE_MAX_STRING ?>">
					</p>
					<p id="capsLock:newPasswordCheck" style="display:none"><strong>WARNING</strong>: Caps Lock seems to be on!</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC comfy" colspan="2">
					<button type="submit">Change your password</button>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}


function do_usr_ui_filepreview() : void
{
	if (empty(($_GET['filename'])))
		exiterror("No file to preview was specified.");
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Previewing uploaded file</th>
		</tr>
		<tr>
			<td class="layout-fg">
				<?php do_uploaded_file_view($_GET['filename'], false); ?>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_usr_ui_useruploads() : void
{
	global $User;
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Your uploaded files</th>
		</tr>
	</table>
	
	<?php
	if (1024 < user_upload_space_remaining($User->username))
		do_ui_upload_form_as_table("upload-act.php", "Files that you upload can be used to install your own corpora.", ['uplAction' => 'userFileUpload']);
	else
	{
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<td class="layout-contrast algC comfy"><strong>File upload disabled!</strong></td>
			</tr>
			<tr>
				<td class="layout-bg">
					<p>You cannot currently upload any files, because your upload area is full.</p>
					<p>To re-enable file upload, either request an increase to your file storage limit, or delete some of your files to free up space.</p>
				</td>
			</tr>
		</table>
		
		<?php
	}
	
	do_ui_display_upload_area($User->username, false);
}


function do_usr_ui_showcolleaguates() : void
{
	global $Config;
	global $User;
	
	if (!$Config->colleaguate_system_enabled)
		exiterror("The Colleaguate system is not enabled on this CQPweb server. ");
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="6">Your Colleaguates</th>
		</tr>
		<tr>
			<th class="layout-strong">Username</th>
			<th class="layout-strong">Realname</th>
			<th class="layout-strong">Affiliation</th>
			<th class="layout-strong">Email address</th>
			<th class="layout-strong">Shares with you</th>
			<th class="layout-strong">Actions</th>
		</tr>
		
		<?php
		$peeps = get_all_active_colleaguate_info($User->id);
		if (empty($peeps))
			echo "\n\t\t<tr><td class=\"layout-bg comfy\" colspan=\"6\">You do not have any colleaguations.</td></tr>\n";
		
		foreach($peeps as $mucker)
			echo "\n\t\t<tr>"
				, '<td class="layout-fg">', $mucker->username, '</td>'
				, '<td class="layout-fg">', escape_html($mucker->realname), '</td>'
				, '<td class="layout-fg">', escape_html($mucker->affiliation), '</td>'
				, '<td class="layout-fg">', escape_html($mucker->email), '</td>'
				, '<td class="layout-fg">', 'TODO: N corpora', '</td>'
				, '<td class="layout-fg hollow">'
					, '<a class="control-box" data-are-you-sure-q="Really remove '
 					, $mucker->username
 					, ' as colleaguate?" href="useracct-act.php?userAction=breakColleaguation&who='
 					, $mucker->username
 					, '">[Remove colleaguate]</a>'
				, '</td>'
				, '</tr>'
				;
		//TODO, more is needed here in terms of formatting etx.
		
		?>
	
	</table>
	
	<?php
}

function do_usr_ui_newcolleaguate()
{
	global $Config;
	if (!$Config->colleaguate_system_enabled)
		exiterror("The Colleaguate system is not enabled on this CQPweb server. ");
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Add New Colleaguate</th>
		</tr>
		<tr>
			<td class="layout-fg">
				<p>A &ldquo;Colleaguate&rdquo; is another user of this CQPweb who you wish to share data with.</p>
				<p>Use this tool to add new colleaguates.</p>
				<p>You can only add people as colleaguates if you know their email address.</p>
				<p>The link between you and the new person will only become active if they accept your request.</p>
			</td>
		</tr>
		<tr>
			<td class="layout-bg">
				<?= print_colleaguate_privacy_notice(); ?>
			</td>
		</tr>
		<tr>
			<th class="layout-strong">Send a colleaguate request to another user</th>
		</tr>
		<tr>
			<td class="layout-fg algC">
				<form action="useracct-act.php" method="get">
					<input type="hidden" name="userAction" value="requestColleaguation">
					<p>
						<label for="cgtEmail">Enter your collaborator's email address:</label>
						<input type="email" size="60" name="cgtEmail" placeholder="Please type carefully!">
					</p>
					<p>
						<button type="submit">Send colleaguate request</button>
					</p>
				</form>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_usr_ui_colleaguaterequests() : void
{
	global $User;
	global $Config;
	if (!$Config->colleaguate_system_enabled)
		exiterror("The Colleaguate system is not enabled on this CQPweb server. ");
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="6">Colleaguation requests received</th>
		</tr>
		<tr>
			<th class="layout-strong">From user</th>
			<th class="layout-strong">Their name</th>
			<th class="layout-strong">Their affiliation</th>
			<th class="layout-strong">Their email address</th>
			<th class="layout-strong" colspan="2">Actions</th>
		</tr>
		
		<?php
		$requesters = get_all_colleaguation_requesters_info($User->id);
		if (empty($requesters))
			echo "\n\t\t<tr><td class=\"layout-bg\" colspan=\"6\"><p>You do not have any colleaguate requests right now.</p></td></tr>\n";
		
		foreach($requesters as $dude)
			echo "\n\t\t<tr>"
				, "<td class=\"layout-fg\">{$dude->username}</td>"
				, "<td class=\"layout-fg\">", escape_sql($dude->realname), "</td>"
				, "<td class=\"layout-fg\">", (empty($dude->affiliation) ? '<em>(unknown)</em>' : escape_sql($dude->affiliation)), "</td>"
				, "<td class=\"layout-fg\">", escape_sql($dude->email), "</td>"
				, "<td class=\"layout-fg hollow\">"
					, "<a class=\"control-box\" href=\"useracct-act.php?userAction=confirmColleaguation&confirmCgtId={$dude->id}\">[Accept]</a>"
				, "</td>"
				, "<td class=\"layout-fg hollow\">"
					, "<a class=\"control-box\" href=\"useracct-act.php?userAction=breakColleaguation&who={$dude->id}&from=requests\">[Decline]</a>"
				, "</td>"
				, "</tr>\n"
				;
		?>
		
		<tr>
			<td class="layout-bg" colspan="6">
				<?= print_colleaguate_privacy_notice(); ?>
			</td>
		</tr>
	</table>
	
	<?php
}


function print_colleaguate_privacy_notice() : string
{
	return <<<END_OF_HTML
	
					<p>
						<strong>Privacy and data protection:</strong>
					</p>
					<p>
						When you add someone as a colleaguate, you make it possible for them to see your
						<em>username</em> as well as the <em>real name</em>, <em>affiliation</em> and 
						<em>email address</em> that you have added to your <a href="index.php?ui=userDetails">account profile</a>.
					</p>
					<p>
						Corpus data you have uploaded is only accessible to other people if you actively share it with them.
					</p>
	
	END_OF_HTML;
}

