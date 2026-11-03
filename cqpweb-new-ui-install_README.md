# cqpweb-new-ui-install_README.md

Documents `cqpweb-new-ui-install.py` 1.0.4.

## Purpose

Installs cqpweb-new-ui over a CQPweb 3.3.19 server, upgrades it, or removes it. It:

1. checks that the CQPweb folder holds CQPweb 3.3.19 and that the 14 files it replaces are CQPweb's originals (checksums in `core-originals.sha256`) or the files of this version or of an earlier version of this package (checksums in `previous-versions.sha256`);
2. backs up every file it replaces into one archive in the backup folder;
3. copies the package's files into the CQPweb folder, with the owner and group of CQPweb's own files;
4. creates the data folder and its subfolders, owned by the web server's user (folders that already exist keep their owner and permissions), and copies in the fonts and the two CQP syntax tutorials, never over files already there;
5. writes `lib/cqpweb-new-ui/local-config.php` when folders other than the defaults are chosen;
6. builds the font list (`cp-fonts.css`) as the web server's user and checks the syntax of every installed PHP file, and of the site's own `lib/cqpweb-new-ui/local-hooks.php` if there is one.

It never writes, replaces or removes `lib/cqpweb-new-ui/local-hooks.php` (a site's own code; see the main README). It never touches CQPweb's database or configuration file, the web server's settings, the mail system, or any firewall or intrusion-prevention software.

## Usage

Run as root, from the unpacked package. It acts by default; `--dry-run` only shows what it would do.

```bash
sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb --dry-run
sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb
sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb --uninstall
```

| Option | Meaning |
|---|---|
| `--cqpweb-root DIR` | the CQPweb folder (default `/var/www/html/cqpweb`) |
| `--data-dir DIR` | settings, uploads and history (default `/usr/local/share/cqpweb/cqpweb-new-ui`) |
| `--font-dir DIR` | the font folder (default `fonts/` inside the data folder) |
| `--font-url URL` | the address the web server already serves the font folder at, for example `/fonts` (default: the package serves it) |
| `--pages-dir DIR` | the user pages folder (default `pages/` inside the data folder) |
| `--pages-url URL` | the address the web server already serves the user pages folder at (default: the package serves it) |
| `--news-file NAME` | the news page's file name in the user pages folder (default `news.html`) |
| `--web-user USER` | the web server's user (default: the first of www-data, apache, http, nginx, wwwrun that exists) |
| `--backup-dir DIR` | where backups go (default `/var/backups/cqpweb-new-ui`) |
| `--accept-modified` | replace files that differ from CQPweb's originals (they are backed up first) |
| `--uninstall` | put CQPweb's original files back and remove the package's files |
| `--backup FILE` | with `--uninstall`: the backup to restore from (default: the oldest in the backup folder, which holds CQPweb's originals) |
| `--dry-run` | show what would be done; change nothing |

## Output

One line per file or folder, the tag first:

| Tag | Meaning |
|---|---|
| `NEW` | a file the package adds |
| `REPLACE` | a CQPweb file replaced by the package's version |
| `SAME` | already the package's version; left as it is |
| `UPDATE` | a file of an earlier version of this package, replaced by this version's (the earlier version is shown) |
| `MODIFIED` | differs from CQPweb's original and from the files of every version of this package; the installer stops unless `--accept-modified` is given |
| `FOLDER`, `FONT`, `PAGE` | created in the data folder |
| `KEPT` | already there; not overwritten, owner and permissions unchanged (also shown for the site's own `local-hooks.php`) |
| `CONFIG` | `local-config.php` written |
| `BACKUP` | the backup archive |
| `RESTORE`, `REMOVE`, `LEFT` | while uninstalling: a file put back, a package file removed, a file left in place (a package file changed after installation, or the site's own `local-hooks.php`) |

## Backups

Each run writes `cqpweb-new-ui-backup-YYYYMMDD-HHMMSS.tar.gz` (permissions 600) with every file it replaces and a `manifest.json` listing the files it added. `--uninstall` uses the oldest by default, since that one holds CQPweb's originals. The data folder is never removed.

## Exit codes

0 done; 1 or a message on the terminal: refused, with nothing changed.

## Platform

Linux, Python 3, standard library only. It must run on the server where CQPweb is installed.

## Changelog

- 1.0.4: recognizes the files of earlier versions (`previous-versions.sha256`), so an upgrade no longer needs `--accept-modified`; shows them as `UPDATE`; `--uninstall` also removes files added by later versions.
- 1.0.3: shows the site's own `local-hooks.php` as kept, checks its syntax, and never removes it.
- 1.0.2: version number only; the usage commands run the installer with `python3`, since a download from GitHub does not keep its permission to run.
- 1.0.1: version number only (the package's admin pages changed; the installer did not).
- 1.0.0: first version.
