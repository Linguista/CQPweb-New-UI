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

/* This file has the setup for the new-user-account form. */




/**
 * Used on the user signup page, this function retrieves and inserts into the DOM
 * a new captcha from the server. Useful for when the captcha is randomly too hard. 
 */
function refresh_captcha()
{
	var req = new XMLHttpRequest();
	
	req.open("GET", "../usr/useracct-act.php?userAction=ajaxNewCaptchaImage&cacheblock=" + Math.floor(Math.random()*99999999), true);
	
	req.onreadystatechange = 
		function()
		{
			if (req.readyState != 4) 
				return;
			if (req.status != 200 && req.status != 304)
				return;
			if (req.responseText == null)
				alert("Could not load new CAPTCHA image!");
			else
			{
				which = req.responseText;
				
				/* stick the captcha ref into the form */
				document.getElementById('captchaRef').value = which;
				
				/* change the image */
				document.getElementById('captchaImg').src = "../usr/useracct-act.php?userAction=captchaImage&which=" + which + "&cacheblock=" + Math.floor(Math.random()*99999999);
			}
		}
	
	req.send();
}






/* 
TODO add javascript strength meter for passwords. . (see user-acct-forms) 

strength link:
https://github.com/dropbox/zxcvbn/blob/master/README.md
(which in turn points at https://requirejs.org/docs/start.html to explain the bundling)
*/

/**
 * Event handler which checks passwords before allowing form submission.
 */
function check_password_on_submit(e)
{
	var msg = check_password_security(
					$("#newUsername").val(),
					$("#newEmail").val(), 
					$("#newPassword").val(), 
					$("#newPasswordCheck").val() 
					);
	
	if (0 == msg)
		return true;
	
	greyout_and_acknowledge(msg);
	
	return false;
}

/**
 * 
 * @param username   Proposed username.
 * @param email      User's email address.
 * @param password   Password to check.
 * @return           0 if the paswword is OK; otherwise, a string description of why it's no good.
 */
function check_password_security(username, email, password, check_password)
{
	/* cqpweb-new-ui: this function used an undefined variable "pwd" instead of "password", so it
	 * stopped with an error and the form was sent whatever the password */
	if (undefined === password || '' === password || null === password)
		return "Your password cannot be an empty string!";

	var password_minimum_length = parseInt(get_application_data("pwdMinLength"));

	/* following array-breaking trick is needed to count codepoints instead of UTF-16 units */
	var len = [...password].length;
	if (len  < password_minimum_length)
		return "Your password is too short! It needs to be " + (password_minimum_length - len) + " characters longer.";
	
	if (password == username)
		return "The password cannot be the same as the username!";
	
	if (password == email)
		return "The password cannot be the same as your email address!";
	
	if (password != check_password)
		return "The password you typed the second time did not match what you typed the first time!";
	
	var blacklist = JSON.parse(get_application_data("pwdBlacklist"));
	
	if (blacklist.includes(password))
		return "The password you specified is very common. Please choose something harder to guess."
		
	return 0;
}





$( function() {
	
	/* setup for account creation page: add the event handler */
	$("#newUserForm").submit(check_password_on_submit);
	
} );
