# cqpweb-new-ui

VERSION 1.0.2

A modern interface, confirmation e-mail workaround, and a set of fixes and additions for [CQPweb](https://cwb.sourceforge.io/cqpweb.php) 3.3.19, the web interface of the IMS Open Corpus Workbench. It installs over an existing CQPweb with one command, keeps a backup of everything it replaces, and can be removed the same way, meaning you can try it risk-free.

Author: Scott Sadowsky.

## What it adds

### Appearance

- A new skin for every CQPweb page: a top bar with "Homepage" and "Back to *corpus*" buttons, cleaner tables and forms, and layouts that work on phones.
- Day and night mode, switched by each visitor with the button at the top right.
- Colors chosen on a new admin page, **Appearance**: twelve color schemes, separately for the home page, the user pages, the admin pages and each corpus, with every color adjustable and a live contrast check.
- Fonts served from your own server: seven families come with the package, and about 1,600 Google Fonts families can be added from the Appearance page. Visitors' browsers never contact Google.
- A new home page with one card per corpus, colored with that corpus's scheme, showing its size and linking to its search page and documentation.
- Concordances: every second word can be highlighted with a background shade, a text color, or both (words and tags are highlighted together in a single box when tags are shown below the words). The default is set per corpus, and it can be changed with the control above the concordance.
- A clearer concordance summary line: the query and frequency per million words are shown in bold, monospace type.

### Personalization (new admin page, under "Frontend interface")

- Site name, your name and web address, and a contact address.
- Your own logo and browser icon, uploaded from the page. The icons phones use when a visitor saves the site to the home screen are made from the browser icon.
- The home-page welcome, a text at the bottom of every page, the notice on the "Create an account" page, and the title of the news page.
- The query form's defaults: Simple query or CQP syntax, and case-sensitive or not.
- Public documentation pages for each corpus, readable without logging in.

Anything not set falls back to what CQPweb's own configuration file already says (its home-page logo and welcome message, its administrator address). Only when that is empty too do the placeholders appear: a "Your Logo Here!" image and "Welcome to [YOUR SITE NAME HERE]! Created by [YOUR NAME HERE]".

### E-mail workaround (new admin page, first under "Backend system")

Out of the box, CQPweb's account-confirmation and password-reset messages usually never reach anyone: hosting companies often block the port it needs, and receiving providers discard mail from servers that are not recognized senders. With this package, CQPweb logs in to a real outgoing mail server (an SMTP relay: a free or commercial service, or your institution's own server) and sends mail through it. Nothing else has to be installed on the server.

The **E-mail** page has:

- the server, port, encryption, user name and password (kept in a file only the web server can read, and never shown again);
- the sender's address and name, and a reply-to address;
- a "Send a test message" button that shows the whole conversation with the mail server, with the password hidden;
- your domain's SPF, DMARC and MX records as the server sees them;
- a step-by-step guide to the whole chain: choosing the sender address, getting a relay account, verifying the domain (DKIM and SPF), adding DMARC, testing, and what each error means.

While this new SMTP sending setup is turned off, CQPweb sends mail exactly as it does without the package.

### Help page

A public **Help** page, linked from the bottom of every page, with:

1. the CQP Interface and Query Language Manual, by the authors of CQP;
2. a CQP syntax tutorial in Spanish;
3. a CQP syntax tutorial in English;
4. the CWB project's video tutorials;
5. the CQPweb System Administrator's Manual;
6. the CWB download and installation page;
7. the CWB mailing list archive.

On the Appearance page, each entry can be shown or hidden, its title, description and address can be changed, and new entries (in the form of `title`, `description`, `address`) can be added.

The two tutorials also appear in every corpus menu under "Tutorials", and a box pointing to them appears above the query box whenever "CQP syntax" is selected.

### Searching

- A "Case-sensitive" box on every query form, in Simple query and in CQP syntax. Its starting state is the site's default; in addition, each browser remembers the visitor's own choice. In CQP syntax mode, a new operator, `%C` (with a capital "C"), placed after one value keeps just that value case-sensitive in case-insensitive mode.
- The two simple query modes are merged into one "Simple query" entry, first in the list; the box decides the case.
- Words typed with "CQP syntax" selected but without CQP's bracketed syntax (`house`, `my house`), which CQP would normally reject with an error message, are automqatically converted into CQP syntax queries (e.g. `[word="house"]`), and the user is advised about what happened and why.
- "Match strategy" is grayed out, with an explanation, while "CQP syntax" is selected, as CQPweb ignores it there.
- When every database slot is busy, a waiting page retries by itself every five seconds, instead of CQPweb's "Too many database processes!" error.

### Accounts

- A password rule: at least 12 characters (or CQPweb's own minimum, if higher), with a lowercase letter, an uppercase letter, a digit and a special character. It is checked on the server, and the forms show a live checklist. This doesn't affect existing passwords.
- **Maintenance mode** (new admin page): only superusers can use the site while it is on; everyone else sees your message. 

### Pages and messages

- **System messages** can now be edited (title, date, text), not just added and deleted; an "[edit]" link appears beside each message for administrators, and "[x]" deletes in place.
- **News page**: a public page of what has changed on your site, written on an admin page in a simple text format.
- **Edit user pages**: the HTML pages in the user pages folder (tutorials, documentation, etc.) can be created and edited from the admin control panel, with the previous versions kept.
- Embedded pages are shown inside the site design.

### Apparent fixes to CQPweb 3.3.19
_I say "apparent" because I'm not 100% sure that all of these issues were presrent in the CQPweb codebase; notwithstanding that, everything now works as described below._

- Browsers kept running old copies of CQPweb's scripts after an update (the "please wait" spinner asked for `undefinedcss/img/throbber.gif` and the query page stopped with "grab is not defined"). Every script and stylesheet is now asked for with a version tag.
- The page framer (`UiPageFramer`) asked for `jquery.js` and `always.js` without their `jsc/` folder, and never printed its stylesheets.
- When all database slots were busy, the database record just made was left behind, so the retry failed too.
- The "location" label on the sign-up form was printed twice.
- The query form's CQP syntax help link points to the current CQP Interface and Query Language Manual (PDF).
- Every link to another site uses `https` (except the CWB mailing list archive, which keeps the `http` address it is published at).

## Requirements

- CQPweb 3.3.19 (Subversion revision 1918). The installer checks for it.
- PHP 8 with the `intl` and `mbstring` extensions. The `gd` extension is optional: without it, the phone home-screen icons are not made from the uploaded browser icon.
- Python 3 to run the installer.
- Linux (CQPweb runs only on Unix-like systems).
- To add fonts from the Appearance page, the server must be able to reach fonts.googleapis.com and fonts.gstatic.com.

CQPweb 3.3.19 can be gotten from Subversion:

```bash
svn checkout -r 1918 https://svn.code.sf.net/p/cwb/code/gui/cqpweb/trunk cqpweb
```

## Installation of CQPweb-New-UI

Run these commands on the server, as a user who can use `sudo`.

1. Download and unpack the package:

   ```bash
   cd ~
   curl -L https://github.com/Linguista/CQPweb-New-UI/archive/refs/heads/main.tar.gz | tar -xz
   cd ~/CQPweb-New-UI-main
   ```

2. See what the installer would do. Nothing is changed:

   ```bash
   sudo ./cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb --dry-run
   ```

   `--cqpweb-root` tells the installer where CQPweb is installed. The commands here use `/var/www/html/cqpweb`, the most common location.

   On an unmodified CQPweb 3.3.19, the dry run lists 14 `REPLACE` lines, 36 `NEW` lines and one `BACKUP`, and ends with `DRY RUN nothing was changed`. If the dry run worked, skip directly to 3. Otherwise, read the following:

   - If it says the folder "does not look like a CQPweb folder", CQPweb is installed somewhere else. This command lists the folders the web server publishes:

     ```bash
     sudo grep -RhE '^\s*(DocumentRoot|Alias)\s' /etc/apache2/sites-enabled/ /etc/httpd/conf.d/ 2>/dev/null
     ```

     CQPweb's folder is one of them, or a `cqpweb` folder inside one of them: it is the one that contains `lib/environment.php`. To check a folder, for example `/srv/www/corpus/cqpweb`:

     ```bash
     ls /srv/www/corpus/cqpweb/lib/environment.php
     ```

     If the file is listed, that is CQPweb's folder; if `ls` says "No such file or directory", it is not. Put CQPweb's folder after `--cqpweb-root` in every command, instead of `/var/www/html/cqpweb`. For example:

     ```bash
     sudo ./cqpweb-new-ui-install.py --cqpweb-root /srv/www/corpus/cqpweb --dry-run
     ```

   - If it stops at files marked `MODIFIED`, those files were changed after CQPweb was installed. Check them first: adding `--accept-modified` replaces them (they are backed up before that).

3. Install, with the same `--cqpweb-root` as in the dry run:

   ```bash
   sudo ./cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb
   ```

4. Log in to CQPweb as an administrator and open the Admin control panel:
   - **Frontend interface > Personalization**: the site's name, owner, logo, icons, texts and query defaults;
   - **Backend system > E-mail**: the SMTP server's settings, following the guide on the page; then send the test message from that page;
   - **Frontend interface > Appearance**: colors, fonts, and the Help page's links.

Without `curl`, the package can also be downloaded from the Github page (**Code > Download ZIP**) and unpacked with `unzip`. A ZIP file does not keep the installer's permission to run, so the commands will have to start with `sudo python3 cqpweb-new-ui-install.py` instead of `sudo ./cqpweb-new-ui-install.py`. But if you have CQPweb on a headless server, you really want to use curl.

The installer's options and output are described in [cqpweb-new-ui-install_README.md](cqpweb-new-ui-install_README.md).

No web server settings need to change: the package serves its own fonts, images and pages. It also works behind a web application firewall such as ModSecurity with the OWASP Core Rule Set: the admin pages send what is typed in them base64-encoded, so the firewall does not block a save. Without that, the Core Rule Set blocks, for example, a mail password that contains quotation marks the way an SQL command does, a text that mentions a command such as `cat /etc/passwd`, and pasted HTML with a script or an event attribute; plain links and formatting pass either way.

## Folders and permissions

Everything the package writes is kept outside the CQPweb folder, in one data folder owned by the web server's user (by default `/usr/local/share/cqpweb/cqpweb-new-ui`):

| Path in the data folder | What it holds |
|---|---|
| `appearance.json` | colors and fonts (Appearance page) |
| `brand.json` | Personalization settings |
| `help.json` | the Help page's links |
| `site.json` | maintenance mode |
| `mail.json` | e-mail settings, with the password; permissions 600 |
| `brand/` | the uploaded logo and icons |
| `fonts/` | the fonts and the generated `cp-fonts.css` |
| `pages/` | the user pages: tutorials, documentation pages, the news page |
| `news-source.txt`, `news-history/`, `page-history/` | the news page's text and earlier versions of the pages |

The installer's `--data-dir`, `--font-dir`, `--pages-dir`, `--font-url` and `--pages-url` options put these elsewhere, for example where an existing site already keeps its fonts and pages; it then writes them to `lib/cqpweb-new-ui/local-config.php`.

Pictures and PDFs used by the user pages are copied into the pages folder by hand and referred to by file name alone.

## What it does not do

- It never changes CQPweb's database or its configuration file.
- It never changes the web server's settings or the server's mail system. The mail system CQPweb-New-UI incorporates runs instead of the built-in one, but does not touch it, so you can always go back to it at the flick of a checkbox.
- It does nothing with firewalls, fail2ban or any other server protection software. Setting those up is left to each site's administrator.

## Upgrading

Run the new version's installer the same way. Files that are still CQPweb's originals, or this version's own copies, are handled by themselves; files that differ from both are listed, and the installer stops until it is run again with `--accept-modified`. Every run makes a new backup.

## Removing

CQPweb-New-UI can be easily and completely uninstalled, leaving no trace, so it's harmless to try it out. To uninstall it:

```bash
cd ~/CQPweb-New-UI-main
sudo ./cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb --uninstall
```

This puts back CQPweb's original files from the first backup the installer made (kept in `/var/backups/cqpweb-new-ui/`), and removes the files the package added. The data folder is kept; delete it by hand if you no longer want it.

## Files changed in CQPweb

14 CQPweb files are replaced; each change is marked `cqpweb-new-ui` in the code, and the differences are in `patches/`. `core-originals.sha256` holds the checksums of the originals.

| File | Change |
|---|---|
| `lib/html-lib.php` | Skin, fonts and icons in every page's head; top bar; version tags on scripts and stylesheets; page framer fixes; site footer; news link in the menus; editing and deleting system messages |
| `lib/main-home-ui.php` | The new home page |
| `lib/admin-home-ui.php` | The new admin pages and menu entries |
| `lib/general-lib.php` | Sending e-mail through the SMTP server, when switched on |
| `lib/environment.php` | Maintenance mode check; American spelling in two labels |
| `lib/exiterror-lib.php` | The waiting page instead of "Too many database processes!" |
| `lib/db-lib.php` | Removes the stale database record when all slots are busy |
| `lib/concordance-ui.php` | The Case-sensitive box and the bare-word conversion |
| `lib/query-forms.php` | Default query type; tutorial box; CQP manual link |
| `lib/query-home-ui.php` | "Tutorials" in the corpus menu |
| `lib/useracct-act.php` | The password rule |
| `lib/useracct-forms.php` | Sign-up notice, password rule text, doubled label |
| `lib/info-forms.php` | Embedded pages shown in full; https links |
| `jsc/useracct-create.js` | The sign-up form's password check works (it used an undefined variable) |

The other files are new: `lib/cqpweb-new-ui/`, `css/cqpweb-new-ui/`, `adm/cqpweb-new-ui-act.php`, and the public pages `help-center.php`, `news.php`, `page.php`, `documentation.php` and `cqpweb-new-ui-asset.php`.

## Licenses

- cqpweb-new-ui is free software under the GNU General Public License, version 2 ([LICENSE](LICENSE)), the license of CQPweb itself. The CQPweb files it includes are CQPweb's, by Andrew Hardie and the CWB development team, with the changes listed above.
- The fonts in `fonts/google/` are under the SIL Open Font License 1.1; each family's folder has its `OFL.txt` with its copyright notice.
- `css/cqpweb-new-ui/google-fonts.json` lists the Google Fonts family names and categories, taken from Google Fonts' public metadata.

## Changelog

- 1.0.2: tested on a CQPweb 3.3.19 server with MySQL 8.0, Apache 2.4 and ModSecurity with the OWASP Core Rule Set. Fixed: saving the Help page's links with an entry missing from the request hid that entry; CQPweb's sign-up password check script (`jsc/useracct-create.js`) is now patched, so it no longer stops with an error; the links at the bottom of every page are centered (CQPweb gave them the width of its side menu); web-address fields are styled like the other fields; the test-message box no longer suggests the stand-in address CQPweb's setup script gives superusers; the installer is run with `python3`, since a download from GitHub does not keep its permission to run.
- 1.0.1: the Personalization, E-mail, Maintenance mode and System messages pages, and the Help page section of the Appearance page, send their fields base64-encoded, so that saving them is not blocked by a web application firewall (ModSecurity with the OWASP rules reads links, tags and passwords in a request as an attack).
- 1.0.0: first public release.
