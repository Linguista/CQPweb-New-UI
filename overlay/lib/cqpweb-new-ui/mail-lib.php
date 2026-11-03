<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: sending e-mail through an SMTP server.
 * VERSION 1.0.2
 *
 * CQPweb sends every message (account verification, password reset, username reminder,
 * notices) through send_cqpweb_email() in lib/general-lib.php, which calls PHP's mail().
 * On Linux, mail() cannot talk to a mail server itself: it hands the message to a local
 * "sendmail" program, and on a fresh server either there is none or it tries to deliver
 * straight from the server's own address, which most providers block or reject as spam.
 *
 * This file lets CQPweb log in to a real outgoing mail server (an SMTP relay such as
 * SMTP2GO, Brevo, Mailgun, Amazon SES, or your institution's server) and send through it,
 * with no other software on the server. The settings are made on the admin page
 * "E-mail" (lib/cqpweb-new-ui/mail-ui.php) and kept in CP_DATA_DIR/mail.json, which only
 * the web server can read. When SMTP is not switched on, CQPweb uses mail() as before.
 */

require_once(__DIR__ . '/paths.php');

const CP_MAIL_FILE = CP_DATA_DIR . '/mail.json';

const CP_MAIL_DEFAULTS = [
	'enabled'    => false,
	'host'       => '',
	'port'       => 587,
	'security'   => 'starttls',   /* starttls | tls | none */
	'auth'       => true,
	'username'   => '',
	'password'   => '',
	'fromAddress'=> '',
	'fromName'   => '',
	'replyTo'    => '',
	'timeout'    => 20,
];

function cp_mail_settings(bool $reload = false) : array
{
	static $cache = null;
	if (null !== $cache && !$reload)
		return $cache;
	$d = is_file(CP_MAIL_FILE) ? json_decode((string)@file_get_contents(CP_MAIL_FILE), true) : null;
	if (!is_array($d))
		$d = [];
	$out = CP_MAIL_DEFAULTS;
	foreach ($out as $k => $v)
		if (array_key_exists($k, $d))
			$out[$k] = $d[$k];
	return $cache = $out;
}

/**
 * Saves the settings from the admin page. The password is replaced only when a new one is
 * typed ($in['password'] not empty) or when $in['clearPassword'] is set. Returns '' or an error.
 */
function cp_mail_save(array $in) : string
{
	$old = cp_mail_settings(true);
	$s = $old;
	$s['enabled']  = !empty($in['enabled']);
	$host = trim((string)($in['host'] ?? ''));
	if ('' !== $host && !preg_match('/^[A-Za-z0-9.-]{1,253}$|^\[[0-9A-Fa-f:.]+\]$/', $host))
		return 'The server name may contain only letters, digits, dots and hyphens (or an IP address).';
	$s['host'] = $host;
	$port = (int)($in['port'] ?? 0);
	if ($port < 1 || $port > 65535)
		return 'The port must be a number between 1 and 65535.';
	$s['port'] = $port;
	$s['security'] = in_array($in['security'] ?? '', ['starttls', 'tls', 'none'], true) ? $in['security'] : 'starttls';
	$s['auth'] = !empty($in['auth']);
	$s['username'] = trim((string)($in['username'] ?? ''));
	if (!empty($in['clearPassword']))
		$s['password'] = '';
	else if ('' !== (string)($in['password'] ?? ''))
		$s['password'] = (string)$in['password'];
	foreach (['fromAddress', 'replyTo'] as $k)
	{
		$v = trim((string)($in[$k] ?? ''));
		if ('' !== $v && false === filter_var($v, FILTER_VALIDATE_EMAIL))
			return ('fromAddress' == $k ? 'The sender address' : 'The reply-to address') . ' is not a valid e-mail address.';
		$s[$k] = $v;
	}
	$s['fromName'] = trim(preg_replace('/[\r\n"]+/', ' ', (string)($in['fromName'] ?? '')));
	$s['timeout'] = max(5, min(120, (int)($in['timeout'] ?? 20)));
	if ($s['enabled'])
	{
		if ('' === $s['host'])
			return 'To switch SMTP sending on, give the server name.';
		if ($s['auth'] && ('' === $s['username'] || '' === $s['password']))
			return 'To switch SMTP sending on with log-in, give the user name and the password.';
	}

	if (!is_dir(CP_DATA_DIR) && !@mkdir(CP_DATA_DIR, 02775, true))
		return 'Cannot create ' . CP_DATA_DIR;
	$tmp = CP_MAIL_FILE . '.tmp' . getmypid();
	$old_umask = umask(0077);
	$ok = false !== @file_put_contents($tmp, json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
	umask($old_umask);
	if (!$ok)
		return 'Cannot write ' . CP_MAIL_FILE;
	@chmod($tmp, 0600);
	if (!@rename($tmp, CP_MAIL_FILE))
	{
		@unlink($tmp);
		return 'Cannot write ' . CP_MAIL_FILE;
	}
	cp_mail_settings(true);
	return '';
}

function cp_mail_smtp_on() : bool
{
	$s = cp_mail_settings();
	return $s['enabled'] && '' !== $s['host'];
}


/*
 * ================
 * THE SMTP CLIENT
 * ================
 */

final class CpSmtp
{
	private $sock = null;
	public array $log = [];
	private array $s;
	public string $error = '';

	public function __construct(array $settings)
	{
		$this->s = $settings;
	}

	private function note(string $dir, string $line) : void
	{
		$this->log[] = $dir . ' ' . rtrim($line, "\r\n");
	}

	private function fail(string $msg) : bool
	{
		$this->error = $msg;
		$this->note('!!', $msg);
		if ($this->sock)
		{
			@fclose($this->sock);
			$this->sock = null;
		}
		return false;
	}

	/** Reads one reply (all its lines). Returns [code, text]. */
	private function read() : array
	{
		$text = '';
		$code = 0;
		while (true)
		{
			$line = @fgets($this->sock, 4096);
			if (false === $line)
			{
				$meta = @stream_get_meta_data($this->sock);
				return [0, !empty($meta['timed_out']) ? 'The server did not answer in time.' : 'The server closed the connection.'];
			}
			$this->note('S:', $line);
			$text .= $line;
			if (strlen($line) < 4 || ' ' === $line[3])
			{
				$code = (int)substr($line, 0, 3);
				break;
			}
		}
		return [$code, trim($text)];
	}

	private function send(string $line, ?string $shown = null) : void
	{
		$this->note('C:', $shown ?? $line);
		@fwrite($this->sock, $line . "\r\n");
	}

	/** Sends a command and checks the reply code. */
	private function cmd(string $line, array $ok, ?string $shown = null) : ?array
	{
		$this->send($line, $shown);
		$r = $this->read();
		if (!in_array($r[0], $ok, true))
		{
			$this->fail("The server refused \"" . ($shown ?? $line) . "\": " . ($r[1] ?: 'no answer'));
			return null;
		}
		return $r;
	}

	private function helo_name() : string
	{
		$h = gethostname();
		return ($h && preg_match('/^[A-Za-z0-9.-]+$/', $h)) ? $h : 'localhost';
	}

	/**
	 * Sends one message. $headers are complete header lines (without To, Subject, Date,
	 * Message-ID, MIME lines, which are added here). Returns true on success.
	 */
	public function deliver(string $from, array $rcpts, string $data) : bool
	{
		$s = $this->s;
		$timeout = (int)$s['timeout'];
		$ctx = stream_context_create(['ssl' => [
			'verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => trim($s['host'], '[]'),
			'SNI_enabled' => true,
		]]);
		$scheme = ('tls' === $s['security']) ? 'ssl' : 'tcp';
		$this->note('..', "Connecting to {$s['host']} port {$s['port']} (" . ['starttls' => 'STARTTLS', 'tls' => 'TLS from the start', 'none' => 'no encryption'][$s['security']] . ')');
		$this->sock = @stream_socket_client("$scheme://{$s['host']}:{$s['port']}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
		if (!$this->sock)
			return $this->fail("Could not connect to {$s['host']} port {$s['port']}: " . ($errstr ?: "error $errno")
				. '. Check the server name and port; some hosting companies block outgoing mail ports (port 25 most often).');
		stream_set_timeout($this->sock, $timeout);

		$r = $this->read();
		if (220 !== $r[0])
			return $this->fail('The server did not greet us: ' . ($r[1] ?: 'no answer'));
		$helo = $this->helo_name();
		if (!($r = $this->cmd("EHLO $helo", [250])))
			return false;
		$ext = $r[1];

		if ('starttls' === $s['security'])
		{
			if (!preg_match('/^250[ -]STARTTLS\b/mi', $ext))
				return $this->fail('The server does not offer STARTTLS on this port. Choose "TLS from the start" (usually port 465) or check the port.');
			if (!$this->cmd('STARTTLS', [220]))
				return false;
			$method = defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')
				? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
				: STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
			if (!@stream_socket_enable_crypto($this->sock, true, $method))
				return $this->fail('The encrypted connection could not be set up (the server\'s certificate may not match its name).');
			$this->note('..', 'Encryption is on.');
			if (!($r = $this->cmd("EHLO $helo", [250])))
				return false;
			$ext = $r[1];
		}

		if ($s['auth'])
		{
			if (!preg_match('/^250[ -]AUTH[ =](.*)$/mi', $ext, $am))
				return $this->fail('The server does not offer log-in (AUTH) here. On most relays this means the connection is not encrypted: use STARTTLS or TLS.');
			$mechs = strtoupper($am[1]);
			$user = (string)$s['username'];
			$pass = (string)$s['password'];
			if (false !== strpos($mechs, 'PLAIN'))
			{
				if (!$this->cmd('AUTH PLAIN ' . base64_encode("\0$user\0$pass"), [235], 'AUTH PLAIN (user name and password hidden)'))
					return $this->fail('Log-in failed: check the user name and password. ' . $this->error);
			}
			else if (false !== strpos($mechs, 'LOGIN'))
			{
				if (!$this->cmd('AUTH LOGIN', [334]))
					return false;
				if (!$this->cmd(base64_encode($user), [334], '(user name hidden)'))
					return false;
				if (!$this->cmd(base64_encode($pass), [235], '(password hidden)'))
					return $this->fail('Log-in failed: check the user name and password. ' . $this->error);
			}
			else
				return $this->fail("The server offers only these log-in methods, which this package does not support: $mechs");
		}

		if (!$this->cmd("MAIL FROM:<$from>", [250]))
			return false;
		foreach ($rcpts as $to)
			if (!$this->cmd("RCPT TO:<$to>", [250, 251]))
				return false;
		if (!$this->cmd('DATA', [354]))
			return false;
		/* dot-stuffing: a line starting with "." gets a second "." */
		$data = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $data));
		$data = str_replace("\n", "\r\n", rtrim($data, "\n")) . "\r\n.";
		$this->note('C:', '(message: ' . strlen($data) . ' bytes)');
		@fwrite($this->sock, $data . "\r\n");
		$r = $this->read();
		if (250 !== $r[0])
			return $this->fail('The server did not accept the message: ' . ($r[1] ?: 'no answer'));
		$this->send('QUIT');
		$this->read();
		@fclose($this->sock);
		$this->sock = null;
		return true;
	}
}

/** "Name <a@b>" or "a@b" -> the bare address; '' if there is none. */
function cp_mail_bare_address(string $s) : string
{
	if (preg_match('/<([^<>@\s]+@[^<>\s]+)>/', $s, $m))
		return $m[1];
	$s = trim($s);
	return false !== filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : '';
}

/** A header value with non-ASCII characters, encoded for e-mail (RFC 2047). */
function cp_mail_encode_header(string $s) : string
{
	if (!preg_match('/[^\x20-\x7e]/', $s))
		return $s;
	return mb_encode_mimeheader($s, 'UTF-8', 'B', "\r\n");
}

function cp_mail_from_header(string $address, string $name) : string
{
	if ('' === $name)
		return $address;
	$n = preg_match('/[^\x20-\x7e]/', $name) ? cp_mail_encode_header($name) : '"' . addcslashes($name, '"\\') . '"';
	return "$n <$address>";
}

/**
 * Sends a message with the SMTP settings. $extra_headers are the header lines CQPweb passes
 * (From:, Reply-To: and others). The sender address of the E-mail page wins over CQPweb's;
 * if neither exists, the SMTP user name is used when it is an address.
 * Returns true on success; $log gets the conversation with the server (passwords hidden).
 */
function cp_mail_send(string $to, string $subject, string $body, array $extra_headers = [], ?array &$log = null) : bool
{
	$s = cp_mail_settings();
	$cfg_from = '';
	$reply_to = '';
	$other = [];
	foreach ($extra_headers as $h)
	{
		foreach (preg_split('/\r\n|\n/', (string)$h) as $line)
		{
			if ('' === trim($line))
				continue;
			if (preg_match('/^From:\s*(.*)$/i', $line, $m))
				$cfg_from = trim($m[1]);
			else if (preg_match('/^Reply-To:\s*(.*)$/i', $line, $m))
				$reply_to = trim($m[1]);
			else if (preg_match('/^(To|Subject|Date|Message-ID|MIME-Version|Content-Type|Content-Transfer-Encoding|Bcc):/i', $line))
				continue;
			else
				$other[] = $line;
		}
	}
	$from = $s['fromAddress'] ?: cp_mail_bare_address($cfg_from);
	if ('' === $from && false !== filter_var($s['username'], FILTER_VALIDATE_EMAIL))
		$from = $s['username'];
	if ('' === $from)
	{
		$log = ['!! There is no sender address: set one on the E-mail page.'];
		return false;
	}
	if ('' !== $s['replyTo'])
		$reply_to = $s['replyTo'];

	$rcpts = [];
	foreach (explode(',', $to) as $t)
		if ('' !== ($a = cp_mail_bare_address($t)))
			$rcpts[] = $a;
	if (empty($rcpts))
	{
		$log = ["!! Not a valid recipient address: $to"];
		return false;
	}

	$domain = substr(strrchr($from, '@'), 1);
	$headers = [
		'Date: ' . date('r'),
		'From: ' . cp_mail_from_header($from, $s['fromName']),
		'To: ' . implode(', ', $rcpts),
		'Subject: ' . cp_mail_encode_header(preg_replace('/[\r\n]+/', ' ', $subject)),
		'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: base64',
	];
	if ('' !== $reply_to)
		$headers[] = 'Reply-To: ' . $reply_to;
	foreach ($other as $o)
		$headers[] = $o;

	$body = str_replace(["\r\n", "\r"], "\n", $body);
	$data = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");

	$smtp = new CpSmtp($s);
	$ok = $smtp->deliver($from, $rcpts, $data);
	$log = $smtp->log;
	if (!$ok)
		error_log('cqpweb-new-ui: e-mail to ' . implode(', ', $rcpts) . ' failed: ' . $smtp->error);
	return $ok;
}


/*
 * =====================
 * CHECKS FOR THE ADMIN PAGE
 * =====================
 */

/** The DNS records that matter for the sender's domain: [label => [lines]]. */
function cp_mail_dns_report(string $from) : array
{
	$out = [];
	$domain = strtolower(substr((string)strrchr($from, '@'), 1));
	if ('' === $domain || !function_exists('dns_get_record'))
		return $out;
	$txt = @dns_get_record($domain, DNS_TXT) ?: [];
	$spf = [];
	foreach ($txt as $r)
		if (isset($r['txt']) && 0 === stripos($r['txt'], 'v=spf1'))
			$spf[] = $r['txt'];
	$out['SPF record of ' . $domain] = $spf ?: ['none'];
	$dm = [];
	foreach (@dns_get_record('_dmarc.' . $domain, DNS_TXT) ?: [] as $r)
		if (isset($r['txt']))
			$dm[] = $r['txt'];
	$out['DMARC record (_dmarc.' . $domain . ')'] = $dm ?: ['none'];
	$mx = [];
	foreach (@dns_get_record($domain, DNS_MX) ?: [] as $r)
		$mx[] = $r['pri'] . ' ' . $r['target'];
	$out['Mail servers that receive mail for ' . $domain . ' (MX)'] = $mx ?: ['none'];
	return $out;
}
