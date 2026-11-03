# cqpweb-new-ui

VERSION 1.0.2

A modern interface, confirmation e-mail workaround, and a set of fixes and additions for [CQPweb](https://cwb.sourceforge.io/cqpweb.php) 3.3.19, the web interface of the IMS Open Corpus Workbench. It installs over an existing CQPweb with one command, keeps a backup of everything it replaces, and can be removed the same way, meaning you can try it risk-free.

Author: Scott Sadowsky.

A Spanish version of this file is in [README.es.md](README.es.md).

## What it adds

### Appearance

- A new skin for every CQPweb page: a top bar with "Homepage" and "Back to *corpus*" buttons, cleaner tables and forms, and layouts that work on phones.
- Day and night mode, switched by each visitor with the button at the top right.
- Colors chosen on a new admin page, **Appearance**: twelve color schemes, separately for the home page, the user pages, the admin pages and each corpus, with every color adjustable and a live contrast check.
- Fonts served from your own server: seven families come with the package, and about 1,600 Google Fonts families can be added from the Appearance page. Visitors' browsers never contact Google.
- A new home page with one card per corpus, colored with that corpus's scheme, showing its size and linking to its search page and documentation.
- Concordances: every second word can be set off with a background shade, a text color, or both (word and tag together when tags are shown below the words). The default is set per corpus, and each visitor can change it above the concordance.
- A clearer concordance summary line: the query in code type, the corpus size, and the frequency per million words.

### Personalization (new admin page, under "Frontend interface")

- Site name, your name and web address, and a contact address.
- Your own logo and browser icon, uploaded from the page. The icons phones use when a visitor saves the site to the home screen are made from the browser icon.
- The home-page welcome, a text at the bottom of every page, the notice on the "Create an account" page, and the title of the news page.
- The query form's defaults: Simple query or CQP syntax, and case-sensitive or not.
- Public documentation pages for each corpus, readable without logging in.

Anything not set falls back to what CQPweb's own configuration file already says (its home-page logo and welcome message, its administrator address). Only when that is empty too do the placeholders appear: a "Your Logo Here!" image and "Welcome to [YOUR SITE NAME HERE]! Created by [YOUR NAME HERE]".

### E-mail that arrives (new admin page, first under "Backend system")

Out of the box, CQPweb's account-confirmation and password-reset messages usually never reach anyone: CQPweb hands them to the server's local mail system, hosting companies block the port it needs, and receiving providers discard mail from servers that are not recognized senders. With this package CQPweb logs in to a real outgoing mail server (an SMTP relay: a commercial service, or your institution's own server) and sends through it. Nothing else has to be installed on the server.

The **E-mail** page has:

- the server, port, encryption, user name and password (kept in a file only the web server can read, and never shown again);
- the sender's address and name, and a reply-to address;
- a "Send a test message" button that shows the whole conversation with the mail server, with the password hidden;
- your domain's SPF, DMARC and MX records as the server sees them;
- a step-by-step guide to the whole chain: choosing the sender address, getting a relay account, verifying the domain (DKIM and SPF), adding DMARC, testing, and what each error means.

While SMTP sending is off, CQPweb sends mail exactly as it does without the package.

### Help page

A public **Help** page, linked from the bottom of every page, with:

1. the CQP Interface and Query Language Manual, by the authors of CQP;
2. a CQP syntax tutorial in Spanish;
3. a CQP syntax tutorial in English;
4. the CWB project's video tutorials;
5. the CQPweb System Administrator's Manual;
6. the CWB download and installation page;
7. the CWB mailing list archive.

On the Appearance page each entry can be shown or hidden, its title, description and address changed, and new entries (title, description, address) added.

The two tutorials also appear in every corpus menu under "Tutorials", and a box pointing to them appears above the query box whenever "CQP syntax" is selected.

### Searching

- A "Case-sensitive" box on every query form, in Simple query and in CQP syntax. Its starting state is the site's default; each browser remembers the visitor's own choice. In CQP syntax, `%C` after one value keeps just that value case-sensitive.
- The two simple query modes are merged into one "Simple query" entry, first in the list; the box decides the case.
- Words typed with "CQP syntax" selected but without CQP's brackets (`house`, `my house`), which CQP would reject, are run as `[word="house"]` tokens, with a clear note saying so.
- "Match strategy" is grayed out, with an explanation, while "CQP syntax" is selected (CQPweb ignores it there).
- When every database slot is busy, a waiting page retries by itself every five seconds, instead of CQPweb's "Too many database processes!" error.

### Accounts

- A password rule: at least 12 characters (or CQPweb's own minimum, if higher), with a lowercase letter, an uppercase letter, a digit and a special character. It is checked on the server, and the forms show a live checklist.
- **Maintenance mode** (new admin page): only superusers can use the site while it is on; everyone else sees your message.

### Pages and messages

- **System messages** can be edited (title, date, text), not only added and deleted; an "[edit]" link appears beside each message for administrators, and "[x]" deletes in place.
- **News page**: a public page of what has changed on your site, written on an admin page in a simple text format.
- **Edit user pages**: the HTML pages in the user pages folder (tutorials, documentation) can be created and edited from the admin control panel, with the previous versions kept.
- Embedded pages are shown inside the site design. CQPweb 3.3.19 printed only the file's path.

### Fixes to CQPweb 3.3.19

- Browsers kept running old copies of CQPweb's scripts after an update (the "please wait" spinner asked for `undefinedcss/img/throbber.gif` and the query page stopped with "grab is not defined"). Every script and stylesheet is now asked for with a version tag.
- The page framer (`UiPageFramer`) asked for `jquery.js` and `always.js` without their `jsc/` folder, and never printed its stylesheets.
- When all database slots were busy, the database record just made was left behind, so the retry failed too.
- Embedded pages showed their file path instead of their contents.
- The "location" label on the sign-up form was printed twice.
- CQPweb's own password check on the sign-up form (`jsc/useracct-create.js`) used an undefined variable, so it stopped with an error and let every password through. It now works, and the server checks the password rule as well.
- The query form's CQP syntax help link points to the current CQP Interface and Query Language Manual (PDF).
- Every link to another site uses https (the CWB mailing list archive keeps the http address it is published at).
- American spelling throughout the interface ("Analyze corpus", "color scheme", "Categorized queries").

## Requirements

- CQPweb 3.3.19 (Subversion revision 1918). The installer checks it.
- PHP 8 with the `intl` and `mbstring` extensions. The `gd` extension is optional: without it, the phone home-screen icons are not made from the uploaded browser icon.
- Python 3 to run the installer.
- Linux (CQPweb runs only on Unix-like systems).
- To add fonts from the Appearance page, the server must be able to reach fonts.googleapis.com and fonts.gstatic.com.

CQPweb 3.3.19 can be had with Subversion:

```bash
svn checkout -r 1918 https://svn.code.sf.net/p/cwb/code/gui/cqpweb/trunk cqpweb
```

## Installation

1. Download this repository (on GitHub: Code > Download ZIP), unpack it on the server, and open a terminal in the unpacked folder (`CQPweb-New-UI-main` when the ZIP comes from GitHub).
2. See what the installer would do. Nothing is changed:

   ```bash
   sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb --dry-run
   ```

3. Install:

   ```bash
   sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb
   ```

4. Log in to CQPweb as an administrator and open the Admin control panel:
   - **Frontend interface > Personalization**: your site's name, logo, icon and texts;
   - **Backend system > E-mail**: follow the guide on the page;
   - **Frontend interface > Appearance**: colors, fonts, and the Help page's links.

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
- It never changes the web server's settings or the server's mail system.
- It does nothing with firewalls, fail2ban or any other protective software. Setting those up is left to each site's administrators.

## Upgrading

Run the new version's installer the same way. Files that are still CQPweb's originals, or this version's own copies, are handled by themselves; files that differ from both are listed, and the installer stops until it is run again with `--accept-modified`. Every run makes a new backup.

## Removing

```bash
sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb --uninstall
```

This puts back CQPweb's original files from the first backup the installer made, and removes the files the package added. The data folder is kept; delete it by hand if it is no longer wanted.

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
