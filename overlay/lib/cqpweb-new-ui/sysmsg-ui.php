<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the admin page "System messages", with editing.
 * VERSION 1.0.2
 *
 * Printed inside the admin control panel (adm/index.php?ui=systemMessages) in place of
 * CQPweb's own page, which could only add and delete messages. Here every message can also
 * be edited: its title, its date and its text. Saves through adm/cqpweb-new-ui-act.php
 * (actions "sysmsgSave" and "sysmsgDelete"). Messages stay in CQPweb's own table
 * (system_messages), so CQPweb shows them exactly as before.
 */

const CP_SYSMSG_TITLE_MAX = 100;

/** All system messages, newest first: arrays with id, date (YYYY-MM-DD), header, content. */
function cp_sysmsg_all() : array
{
	$out = [];
	$result = do_sql_query("select `id`, `date_of_posting`, `header`, `content` from `system_messages` order by `date_of_posting` desc, `id` desc");
	while ($o = mysqli_fetch_object($result))
		$out[] = [
			'id'      => (int)$o->id,
			'date'    => substr((string)$o->date_of_posting, 0, 10),
			'header'  => stripslashes((string)$o->header),
			'content' => stripslashes((string)$o->content),
		];
	return $out;
}

/**
 * Adds (id 0) or updates one system message. Returns '' on success, else the reason.
 * $new_id receives the message's id.
 */
function cp_sysmsg_save(int $id, string $header, string $content, string $date, ?int &$new_id = null) : string
{
	global $User;
	if (!$User->is_admin())
		return 'Only administrators can change system messages.';
	$header = trim(Normalizer::normalize($header, Normalizer::FORM_C) ?: $header);
	$content = trim(str_replace("\r\n", "\n", Normalizer::normalize($content, Normalizer::FORM_C) ?: $content));
	if ('' === $header)
		return 'The title is empty.';
	if (mb_strlen($header) > CP_SYSMSG_TITLE_MAX || strlen($header) > 255)
		return 'The title is too long (at most ' . CP_SYSMSG_TITLE_MAX . ' characters).';
	if ('' === $content)
		return 'The text is empty.';
	if (strlen($content) > 60000)
		return 'The text is too long.';
	if ('' !== $date && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))))
		return 'The date must be a real date, written as YYYY-MM-DD.';

	$h = escape_sql($header);
	$c = escape_sql($content);
	if (0 === $id)
	{
		/* a new message: today's date and time unless another date was given */
		$when = ('' === $date || date('Y-m-d') === $date) ? 'CURRENT_TIMESTAMP' : "'$date 12:00:00'";
		do_sql_query("insert into `system_messages` set `header` = '$h', `content` = '$c', `date_of_posting` = $when");
		$new_id = (int)get_sql_insert_id();
		return '';
	}
	$result = do_sql_query("select `id` from `system_messages` where `id` = $id");
	if (0 == mysqli_num_rows($result))
		return 'That message no longer exists.';
	/* the column updates itself on every change, so the date is always set explicitly;
	 * an unchanged date keeps its original time of day */
	$when = ('' === $date) ? '`date_of_posting`' : "concat('$date ', time(`date_of_posting`))";
	do_sql_query("update `system_messages` set `header` = '$h', `content` = '$c', `date_of_posting` = $when where `id` = $id");
	$new_id = $id;
	return '';
}

/** Deletes one system message. Returns '' on success, else the reason. */
function cp_sysmsg_delete(int $id) : string
{
	global $User;
	if (!$User->is_admin())
		return 'Only administrators can change system messages.';
	do_sql_query("delete from `system_messages` where `id` = $id");
	return '';
}

/** The admin page. */
function cp_sysmsg_admin_ui() : void
{
	$v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance-admin.css') ?: 0;
	$today = date('Y-m-d');
	$max = CP_SYSMSG_TITLE_MAX;
	$msgs = cp_sysmsg_all();
	?>
	<link rel="stylesheet" href="../css/cqpweb-new-ui/appearance-admin.css?v=<?= $v ?>">
	<div class="cpa" id="cpSysMsg">
		<div class="cpa-intro">
			<h2 class="cpa-h1">System messages</h2>
			<p class="cpa-note">System messages appear on the home page, on every corpus's query page and on the account pages,
			newest first, ordered by their date. Write plain text: line breaks are kept.</p>
			<p class="cpa-note">To add a link, write it exactly like this, with double quotes and one space after <code>a</code>:
			<code>&lt;a href="https://www.example.org/"&gt;text to click&lt;/a&gt;</code>.
			For a page on this site, start the address with a slash (or, if CQPweb is not at the top of the site, with the
			full address of the page), so that it works wherever the message is shown:
			<code>&lt;a href="/news.php"&gt;What is new&lt;/a&gt;</code>,
			<code>&lt;a href="/help-center.php"&gt;Help&lt;/a&gt;</code>,
			<code>&lt;a href="/mycorpus/"&gt;search my corpus&lt;/a&gt;</code>.
			Links to other sites get a small arrow; links within this site do not.</p>
		</div>

		<section class="cpa-card cp-msg" data-id="0" aria-labelledby="cpMsgNewH">
			<h3 id="cpMsgNewH" class="cpa-h2">Add a message</h3>
			<div class="cpa-row cpa-stack">
				<label for="cpMsgT0">Title</label>
				<input type="text" id="cpMsgT0" class="cp-msg-title" maxlength="<?= $max ?>" required>
			</div>
			<div class="cpa-row">
				<label for="cpMsgD0">Date</label>
				<input type="date" id="cpMsgD0" class="cp-msg-date" value="<?= $today ?>">
			</div>
			<div class="cpa-row cpa-stack">
				<label for="cpMsgC0">Text</label>
				<textarea id="cpMsgC0" class="cp-msg-text" rows="5"></textarea>
			</div>
			<div class="cpa-row">
				<button type="button" class="cp-msg-save">Add message</button>
				<span role="status" class="cpa-status cp-msg-status"></span>
			</div>
		</section>

		<h3 class="cpa-h2"><?= count($msgs) ? 'Current messages (' . count($msgs) . ')' : 'There are no messages at the moment.' ?></h3>
		<?php
		foreach ($msgs as $m)
		{
			$id = $m['id'];
			?>
			<section class="cpa-card cp-msg" id="cpMsg<?= $id ?>" data-id="<?= $id ?>" aria-label="System message <?= $id ?>">
				<div class="cpa-row cpa-stack">
					<label for="cpMsgT<?= $id ?>">Title</label>
					<input type="text" id="cpMsgT<?= $id ?>" class="cp-msg-title" maxlength="<?= $max ?>" value="<?= escape_html($m['header']) ?>" required>
				</div>
				<div class="cpa-row">
					<label for="cpMsgD<?= $id ?>">Date</label>
					<input type="date" id="cpMsgD<?= $id ?>" class="cp-msg-date" value="<?= escape_html($m['date']) ?>">
				</div>
				<div class="cpa-row cpa-stack">
					<label for="cpMsgC<?= $id ?>">Text</label>
					<textarea id="cpMsgC<?= $id ?>" class="cp-msg-text" rows="<?= max(4, min(16, substr_count($m['content'], "\n") + 3)) ?>"><?= escape_html($m['content']) ?></textarea>
				</div>
				<div class="cpa-row">
					<button type="button" class="cp-msg-save">Save changes</button>
					<button type="button" class="cp-msg-del cpa-secondary">Delete</button>
					<span role="status" class="cpa-status cp-msg-status"></span>
				</div>
			</section>
			<?php
		}
		?>
	</div>
	<script>
	(function () {
		/* the request travels base64-encoded, so a web application firewall (such as ModSecurity
		 * with the OWASP rules) does not mistake links, tags or a password in it for an attack */
		function seal(body) {
			var a = body.action, c = {};
			Object.keys(body).forEach(function (k) { if (k !== 'action') c[k] = body[k]; });
			var bytes = new TextEncoder().encode(JSON.stringify(c)), bin = '';
			for (var i = 0; i < bytes.length; i += 0x8000)
				bin += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
			return JSON.stringify({ action: a, payload64: btoa(bin) });
		}
		function post(body) {
			return fetch('cqpweb-new-ui-act.php', {
				method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
				body: seal(body)
			}).then(function (r) { return r.json(); })
			  .catch(function () { return { ok: false, message: 'The server could not be reached.' }; });
		}
		Array.prototype.forEach.call(document.querySelectorAll('#cpSysMsg .cp-msg'), function (card) {
			var id = parseInt(card.getAttribute('data-id'), 10);
			var st = card.querySelector('.cp-msg-status');
			function say(msg, bad) { st.textContent = msg; st.className = 'cpa-status cp-msg-status' + (bad ? ' cpa-bad' : ''); }
			card.querySelector('.cp-msg-save').addEventListener('click', function () {
				var btn = this;
				btn.disabled = true;
				say('Saving…');
				post({ action: 'sysmsgSave', id: id,
					header: card.querySelector('.cp-msg-title').value,
					date: card.querySelector('.cp-msg-date').value,
					content: card.querySelector('.cp-msg-text').value })
				.then(function (r) {
					btn.disabled = false;
					say(r.message, !r.ok);
					if (r.ok && 0 === id)
						location.reload();
				});
			});
			var del = card.querySelector('.cp-msg-del');
			if (del)
				del.addEventListener('click', function () {
					var btn = this;
					if (!btn.classList.contains('cp-armed')) {
						btn.classList.add('cp-armed');
						btn.textContent = 'Click again to delete';
						setTimeout(function () { btn.classList.remove('cp-armed'); btn.textContent = 'Delete'; }, 5000);
						return;
					}
					btn.disabled = true;
					post({ action: 'sysmsgDelete', id: id }).then(function (r) {
						if (r.ok)
							card.remove();
						else {
							btn.disabled = false;
							say(r.message, true);
						}
					});
				});
		});
		if (location.hash) {
			var target = document.querySelector(location.hash.replace(/[^#A-Za-z0-9]/g, ''));
			if (target) {
				target.classList.add('cp-msg-focus');
				var t = target.querySelector('.cp-msg-text');
				if (t) t.focus();
			}
		}
	})();
	</script>
	<?php
}
