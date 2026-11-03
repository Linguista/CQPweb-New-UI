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
 * The exiterror module prints out an error page and performs necessary
 * error-actions before shutting down CQPweb.
 * 
 * Functions outside the module should always call one of the functions
 * that builds up a message template, e.g. exiterror(), which is the usual choice.
 * 
 * These functions in turn call the ones that do formatting etc.
 * 
 * This file also contains the debug functions squawk() and flaunt():
 * emit a message, and display a value, respectively. 
 */
 

// TODO all funcs need to be sensible about text / JSON output. ie,. they should use $Config for that. 



/**
 * Tests whether the system is in a mode to emit debug messages (squawks and flaunts).
 * 
 * @return bool  If true: squawks should be emitted. 
 */
function squawkable() : bool
{
	global $Config;
	global $User;
	
	/* if something is squawking before Config is set up, then it should be printed - no matter what */
	if (empty($Config))
		return true;
	
	if (!$Config->debug_on)
		return false;
	
	if ( ! ($Config->debug_for_all || (isset($User) && $User->is_admin())) )
		return false;
	
	return true;
}

/** Backend function for squawk() and flaunt() */ 
function emit_squawk(string $message, bool $is_flaunt = false) : void
{
	global $Config;

	/* if global Config not set up, supply some guessed values for emission */
	if (!is_object($Config))
	{
		$reset_config = true;
		$Config = (object)['debug_to_screen'=>true,'debug_to_log'=>true,'debug_html'=>true,'client_is_disconnected'=>false];
	}
	else
		$reset_config = false;

	$verbs = $is_flaunt ? 'flaunts' : 'squawks';

	if ($Config->debug_to_screen && !$Config->client_is_disconnected)
	{
		if ($Config->debug_html)
			pre_echo(escape_html($message));
		else
			echo $message, "\n";
	}

	if ($Config->debug_to_log || $Config->client_is_disconnected)
	{
		$pid = getmypid();
		error_log("CQPweb (#$pid) $verbs: " . $message, 4);
		/* 4 is the constant for "send this message to the standard webserver log"; 
		 * but note that in CLI, it still goes to the console. */
	}

	//TODO: allow for JS ?? see exiterror. (shoudl we append to the emssage in the Api if it's Api mode? ??)

	if ($reset_config)
		$Config = null;
}

/**
 * Prints a debug message. 
 * 
 * Messages are not printed if the config variable $debug_on is not set to true.
 * 
 * If the browser is disconnected, then messages go to the error log.
 * 
 * Messages may be text only or HTML-wrapped (with a 'pre' tag). 
 * This depends on the config variable $debug_html.
 * 
 * @param string $message   The message to squawk out.
 */
function squawk(string $message) : void
{
	if (squawkable())
		emit_squawk($message, false);
}


/**
 * Advanced var_dump.
 * 
 * @param mixed   $expression
 * @param string  $name
 */
function flaunt(/* mixed */$expression, ?string $name = null) : void
{
	$m = null;

	if (!squawkable())
		return;

	$val = stripcslashes(var_export($expression, true));

	/* turn object var_export to something remotely akin to object var_dump .... */
	if (preg_match('/^(\w+)::__set_state\(array\((.*)\)\)\s*$/s', $val, $m))
	{
		$n = 0;
		$mems = preg_replace("/,(\r?\n)/", '$1', $m[2], -1, $n);
		$val = "object({$m[1]}) ($n) {" . $mems . "}";
	}

	$message = 'Variable: $'. ($name ?? 'FLAUNT') . " == $val";

	emit_squawk($message, true);
}

// TODO: special email_flaunt() / email_squawk funcs for special use


/** Emits a backtrace. */
function excavate() : void
{
	global $Config;
	
	if (!$Config->backtrace_on)
		return;
	
	$backtrace_string = render_backtrace(null, $Config->backtrace_compact); 
	
	if ($Config->backtrace_to_screen && !$Config->client_is_disconnected)
	{
		if (!$Config->debug_html)
			echo "\n\nPHP callstack backtrace\n=======================\n$backtrace_string";
		else
		{
			?>
			
			<tr>
				<th class="layout-strong">PHP callstack backtrace</th>
			</tr>
			<tr>
				<td class="layout-contrast">
					<?php pre_echo($backtrace_string); ?>
				</td>
			</tr>
			
			<?php
		}
	}
	
	if ($Config->debug_to_log || $Config->client_is_disconnected)
		error_log("CQPweb (#" . getmypid() . ") backtrace: $backtrace_string", 4);
		/* see notes in emit_squawk() */
}

/**
 * Get a PHP backtrace array ready for output, in normal or compact view. 
 * 
 * @param  array  $bt         Backtrace array from PHP's core function debug_backtrace().
 *                            If null (default), debug_backtrace() is called here. 
 * @param  bool   $compact    If true, prints compactly (one line per call). 
 *                            If false, uses standard print_r() return format.  
 * @return string             
 */
function render_backtrace(?array $bt = null, bool $compact = true) : string
{
	if (null === $bt)
	{
		$bt = debug_backtrace();
		
		unset($bt[0]); /* don't print this call */
		
		if ('excavate' == ($bt[1]['function'] ?? ''))
		{
			/* don't print the call to excavate() */
			$bt = array_values($bt);
			unset($bt[0]);
		}
		if ('exiterror_endpage' == ($bt[1]['function'] ?? ''))
		{
			/* don't print the call to exiterror_endpage() */
			$bt = array_values($bt);
			unset($bt[0]);
		}
	}
	
	if ($compact)
	{
		$r = '';
		
		foreach($bt as $i => $call)
		{
			$file = basename($call['file'] ?? '????');
			
			if(isset($call['class']))
				$func =  "{$call['class']}::{$call['function']}";
			else
				$func = "{$call['function']}";
			
			/* this hideous line pads things with strings. */
			$r .= sprintf('#%-2d %25s @ %-5d: %s(', $i, $file, $call['line'] ?? 0, $func);
			
			foreach($call['args'] as $a)
				$r .= render_argument_in_compact_backtrace($a) . ', ';
			
			$r = rtrim($r, ', ');
			
			$r .= ")\n";
		}
		return $r;
	}
	else
		return print_r($bt, true) . "\n";
}



function emit_sql_query(string $sql) : void
{
	global $Config;
	
	if (!(squawkable() && $Config->debug_sql))
		return;
	
	$sql = trim(str_replace("\t", '    ', $sql));
	
	emit_squawk("SQL <<< $sql\n", false);
}


function emit_sql_note(string $note) : void
{
	global $Config;
	
	if (!(squawkable() && $Config->debug_sql))
		return;
	
	$note = trim(str_replace("\t", '    ', $note));
	
	emit_squawk("SQL     # $note\n", false);
}


function emit_sql_success(float $secs = -1.0) : void
{
	global $Config;
	
	if (!(squawkable() && $Config->debug_sql))
		return;
	
	$timing = (0 <= $secs ? (' (took ' . number_format($secs) . ' seconds)') : '');  
	
	emit_squawk("SQL >>> OK!$timing\n");
}




/* 
 * ==============================================================
 * END of squawking and flaunting; now the exiterror() functions.
 * ==============================================================
 */




/**
 * Function internal to the exiterror module.
 * 
 * Writes the start of an error page, if and only if nothing has been sent back
 * via HTTP yet.
 * 
 * If the HTTP response headers have been sent, it does nothing.
 * 
 * Used by other exiterror functions (can be called unconditionally).
 *
 * @param string $page_title            Text for the HTML "title" element.
 * @param string $page_heading_message  Text to appear as the header for the table
 *                                      that this function starts.
 */
function exiterror_beginpage(string $page_title = 'CQPweb has encountered an error!', string $page_heading_message = 'CQPweb encountered an error and could not continue.') : void
{
	global $Config;
	
	/*
	 * if the exiterror module is called BEFORE $Config exists, then we need to set up a dummy object
	 * with the members used in this module.
	 *
	 * NOTE that exiterror_beginpage always runs first - and this bit of the function executes
	 * even if the page has already started.
	 */
	if (!is_object($Config))
		$Config = (object)['debug_on' => false, 'debug_html' => true, 'backtrace_on' => true, 'backtrace_compact' => true, 'backtrace_for_all' => false];
	
	/* don't send headers, or write <head>, if we've already started writing. */
	if (headers_sent())
		return;

	if (!$Config->debug_html)
	{
		header("Content-Type: text/plain; charset=utf-8");
		echo "$page_heading_message\n";
		for ($i= 0, $n = strlen($page_heading_message); $i < $n ; $i++)
			echo '=';
		echo "\n\n";
	}
	else
	{
		echo print_html_header($page_title, $Config->colour_scheme ?? '~red'), "\n";
		?>
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong"><?php echo escape_html($page_heading_message); ?></th>
			</tr>
		<?php
	}
}



/**
 * Function internal to the exiterror module.
 * 
 * Prints error message lines in either plaintext or HTML.
 * 
 * (The actual HTML that causes the formatting of the error page is here.)
 */
function exiterror_printlines(array $lines) : void
{
	global $Config;
	
	$lines = array_map('trim', $lines);
	
	$before = ($Config->debug_html ? '<p class="errormessage">' : '');
	$after  = ($Config->debug_html ? "</p>\n"                   : "\n" );

	if ($Config->debug_html)
		$lines = array_map('escape_html', $lines);

	echo $Config->debug_html ? '<tr><td class="layout-contrast">' : '', "\n";
	
	foreach($lines as $l)
		if (! empty($l))
			echo $before , $l , $after;
	
	echo $Config->debug_html ? "</td></tr>\n" : "\n";
}



/**
 * Function internal to exiterror module.
 * 
 * Prints a debug backtrace if user is superuser.
 * 
 * Prints a footer iff we're in HTML context; then kills CQPweb.
 * 
 * If $backlink is true, a link to the home page for the corpus is included.
 */
function exiterror_endpage(bool $backlink = true) : void
{
	global $Config;
	global $User;
	
	/* print the PHP back trace */
	if ( $Config->backtrace_on && ( (isset($User) && $User->is_admin()) || $Config->backtrace_for_all ) )
		excavate();
	
	/* print the backlink, if requested. */	
	if ( $Config->debug_html )
	{
		if ($backlink)
		{
			?>
			<tr>
				<td class="layout-fg">
					<p class="errormessage">
						<a class="control-inline" href="index.php">Back to main query page</a>
					</p>
				</td>
			</tr>
			<?php
		}
		?>
		
		</table>
		
		<?php
		echo print_html_footer('hello');
	}
}





//TODO add a "go back" button to the standard exiterror page

/**
 * Primary function to be called by other modules.
 * 
 * Prints the specified error messages (with location of error if we're told that)
 * and then exits.
 * 
 * The error message is allowed to be an array of paragraphs.
 */
function exiterror(/* array|string */$errormessage, ?string $script = null, ?int $line = null) : void
{
	global $Config;
	
	$msg = is_array($errormessage) ? $errormessage : [ $errormessage ] ;
	
	if ($script)
		$msg[] = "... in file $script"
			. ( empty($line) ? '' : ", line $line")
			. '.'
			;

	/* report error via API return object */
	if (is_object($Config) && $Config->Api)
	{
		if (is_int($errormessage))
			$Config->Api->raise_known_error($errormessage);
		else
			$Config->Api->raise_described_error($errormessage);
	}
		
	/* report error to error log when client is disconnected */
	else if(is_object($Config) && $Config->client_is_disconnected)
		foreach ($msg as $k => $m)
			error_log("CQPweb aborts: (" . (1+$k) . ") $m", 4);
	
	/* if script switched the Config to JSON mode, use json to transmit error message */
	else if (is_object($Config) && $Config->client_expects_json)
	{
		/* we use the same obj format as in ApiController: status=error, errno=nonzero, errors=[messages] */
		$o = (object)['status' => 'error' , 'errno' => 1023 , 'errors' => $msg];
		/* 1023 = API_ERR_OTHER. we aren't in API mode, so we don't have that constant defined. */
		
		if (!headers_sent())
			header("Content-Type: application/json");
		echo json_encode($o);
		error_log('Error response sent as JSON: ' . print_r($o, true), 4);
	}

	/* all other cases: print error to page */
	else
	{
		exiterror_beginpage();
		exiterror_printlines($msg);
		exiterror_endpage();
// need to make sure the above 3 funcs behave themselves in commandline context. 
// TODO Ideally, work out a way for the style of exit message (cmdline text, browser, log, json) to happen automatically.
	}
	
	cqpweb_shutdown_environment();
	exit();
}



/**
 * Variation on general error function specifically for failed login.
 * 
 * Unlike other exiterrors, it does not admit of script / line errors
 * (because this "error" is not a bug: it's a user error but not a
 * software error).
 * 
 * @param mixed $errormessage
 */
function exiterror_login(/* array|string */ $errormessage) : void
{
	global $Config;
	if (is_array($errormessage))
		$msg = $errormessage;
	else
		$msg = array($errormessage);
	
	if (is_object($Config) && $Config->Api)
		$Config->Api->raise_error(API_ERR_LOGIN_FAIL);
	else 
	{
		exiterror_beginpage("Unsuccessful login!", "Your login was not successful.");
		exiterror_printlines($msg);
		exiterror_endpage();
	}
	cqpweb_shutdown_environment();
	exit();
	
	// TODO this should be a proper separate page, not just an error. function do_login_error_page()
	// which could embed a "try again" form.
	// Or, just redirect to he usr/ page with the login form, with the correct message?
}



function exiterror_toomanydbprocesses(string $process_type) : void
{
	global $Config;

	/* cqpweb-new-ui: a waiting page that retries by itself, instead of an error */
	require_once(__DIR__ . '/cqpweb-new-ui/site-lib.php');
	cp_queue_page($process_type);

	exiterror(array(
			"Too many database processes!",
			"There are already {$Config->sql_process_limit[$process_type]} " . "{$Config->sql_process_name[$process_type]}	databases being compiled.",
			"Please go Back and try again in a few moments."
		));
}



function exiterror_sqlquery(int $errornumber, string $errormessage, ?string $origquery = null, ?string $script = null, ?int $line = null) : void
{
	/* in case we're mid-transaction and haven't rolled-back yet */
	rollback_sql_transaction();
	/* just in case .... should be done by exiterror, since it calls the shutdown function. But, hey. */
	do_sql_query("unlock tables", false, false);
	
	$msg = array("An SQL query did not run successfully!");
	
	if ($origquery)
		$msg[] = "Original query: \n\t$origquery\n\n";
	
	$msg[] = "Error # $errornumber: $errormessage ";
	
	exiterror($msg, $script, $line);
}



/** 
 * Prints out CQP error messages in exiterror format.
 * 
 *  Only to be used as a CQP object error handler callback.
 */
function exiterror_cqp(array $error_array = []) : void
{
	exiterror(array_merge(["CQP reports an error! The CQP program sent back these error messages:"], $error_array));
}




