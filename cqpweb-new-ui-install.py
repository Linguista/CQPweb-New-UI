#!/usr/bin/env python3
# VERSION 1.0.4
"""
cqpweb-new-ui-install.py: installs, upgrades or removes cqpweb-new-ui on a CQPweb 3.3.19 server.

What it does, in order (each step prints one line per file or folder):
  1. Checks that the CQPweb folder holds CQPweb 3.3.19 and that the files the package
     replaces are CQPweb's own originals, or the files of this or an earlier version of this
     package (previous-versions.sha256), for an upgrade.
  2. Backs up every file it will replace into one archive in the backup folder.
  3. Copies the package's files into the CQPweb folder, with the owner and group of the
     CQPweb files already there.
  4. Creates the data folder (settings, fonts, user pages) owned by the web server's user,
     copies in the fonts and the two CQP syntax tutorials (never over existing files),
     and writes lib/cqpweb-new-ui/local-config.php when folders other than the defaults
     are chosen.
  5. Builds the font list (cp-fonts.css) and checks the syntax of every installed PHP file,
     and of the site's own lib/cqpweb-new-ui/local-hooks.php if there is one. That file is
     never written, replaced or removed.

It never touches the database, CQPweb's configuration file, the web server's settings,
the mail system, or any firewall or intrusion-prevention software.

Run as root. It acts by default; --dry-run only shows what it would do.
Linux only (CQPweb runs only on Unix-like systems).
"""

import argparse
import datetime
import grp
import hashlib
import io
import json
import os
import pwd
import re
import shutil
import subprocess
import sys
import tarfile

VERSION = '1.0.4'
CQPWEB_VERSION = '3.3.19'
PACKAGE = 'cqpweb-new-ui'
DEFAULT_ROOT = '/var/www/html/cqpweb'
DEFAULT_DATA = '/usr/local/share/cqpweb/cqpweb-new-ui'
DEFAULT_BACKUPS = '/var/backups/cqpweb-new-ui'
HERE = os.path.dirname(os.path.abspath(__file__))


def say(tag, text):
    print(f'{tag:<9}{text}', flush=True)


def sha256(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(1 << 20), b''):
            h.update(chunk)
    return h.hexdigest()


def read_sums(path):
    out = {}
    with open(path, encoding='utf-8') as f:
        for line in f:
            line = line.rstrip('\n')
            if line:
                digest, name = line.split(None, 1)
                out[name.lstrip('*')] = digest
    return out


def read_previous():
    """Checksums of the files of earlier versions: {path: {sha256: version}}."""
    out = {}
    path = os.path.join(HERE, 'previous-versions.sha256')
    if not os.path.exists(path):
        return out
    with open(path, encoding='utf-8') as f:
        for line in f:
            line = line.rstrip('\n')
            if not line or line.startswith('#'):
                continue
            digest, name, version = line.split('  ')
            out.setdefault(name, {})[digest] = version
    return out


def package_files():
    """Every file of overlay/, as paths relative to the CQPweb root."""
    base = os.path.join(HERE, 'overlay')
    out = []
    for d, dirs, files in os.walk(base):
        dirs.sort()
        for f in sorted(files):
            out.append(os.path.relpath(os.path.join(d, f), base))
    return out


def detect_web_user(wanted):
    if wanted:
        pwd.getpwnam(wanted)
        return wanted
    for name in ('www-data', 'apache', 'http', 'nginx', 'wwwrun'):
        try:
            pwd.getpwnam(name)
            return name
        except KeyError:
            pass
    sys.exit('Could not find the web server\'s user (tried www-data, apache, http, nginx, wwwrun). Give it with --web-user.')


def cqpweb_version(root):
    env = os.path.join(root, 'lib', 'environment.php')
    if not os.path.isfile(env):
        return None
    with open(env, encoding='utf-8', errors='replace') as f:
        m = re.search(r"CQPWEB_VERSION\s*=\s*'([^']+)'", f.read())
    return m.group(1) if m else None


def run_as(user, cmd):
    if shutil.which('runuser'):
        return subprocess.run(['runuser', '-u', user, '--'] + cmd, capture_output=True, text=True)
    return subprocess.run(['su', '-s', '/bin/sh', user, '-c', ' '.join(cmd)], capture_output=True, text=True)


def php_quote(s):
    return "'" + s.replace('\\', '\\\\').replace("'", "\\'") + "'"


def install(a):
    root = os.path.realpath(a.cqpweb_root)
    dry = a.dry_run
    if not dry and os.geteuid() != 0:
        sys.exit('Run this as root (sudo), or add --dry-run to only see what it would do.')

    ver = cqpweb_version(root)
    if ver is None:
        sys.exit(f'{root} does not look like a CQPweb folder (no lib/environment.php with CQPWEB_VERSION).')
    say('CQPWEB', f'{ver} in {root}')
    if ver != CQPWEB_VERSION and not a.accept_modified:
        sys.exit(f'This package is made for CQPweb {CQPWEB_VERSION}; this server has {ver}. Nothing was changed.')

    originals = read_sums(os.path.join(HERE, 'core-originals.sha256'))
    files = package_files()
    ours = {rel: sha256(os.path.join(HERE, 'overlay', rel)) for rel in files}
    previous = read_previous()

    # 1. what is there now
    replace, create, same, modified = [], [], [], []
    earlier = {}
    for rel in files:
        target = os.path.join(root, rel)
        if not os.path.exists(target):
            create.append(rel)
            continue
        now = sha256(target)
        if now == ours[rel]:
            same.append(rel)
        elif rel in originals and now == originals[rel]:
            replace.append(rel)
        elif now in previous.get(rel, {}):
            earlier[rel] = previous[rel][now]
            replace.append(rel)
        else:
            modified.append(rel)
            replace.append(rel)
    for rel in modified:
        say('MODIFIED', rel)
    if modified and not a.accept_modified:
        sys.exit(f'{len(modified)} file(s) differ both from CQPweb {CQPWEB_VERSION} and from this package (listed above). '
                 'They may hold changes of your own. Nothing was changed. To replace them anyway (they are backed up first), '
                 'run again with --accept-modified.')
    for rel in missing_originals(root, originals):
        sys.exit(f'{rel} is missing from the CQPweb folder; this does not look like a complete CQPweb {CQPWEB_VERSION}.')

    # 2. backup
    stamp = datetime.datetime.now().strftime('%Y%m%d-%H%M%S')
    backup = os.path.join(a.backup_dir, f'{PACKAGE}-backup-{stamp}.tar.gz')
    manifest = {'package': PACKAGE, 'version': VERSION, 'cqpweb_root': root, 'date': stamp,
                'replaced': replace + same, 'created': create}
    say('BACKUP', backup)
    if not dry:
        os.makedirs(a.backup_dir, mode=0o700, exist_ok=True)
        with tarfile.open(backup, 'w:gz') as tar:
            for rel in replace + same:
                tar.add(os.path.join(root, rel), arcname=os.path.join('files', rel))
            data = json.dumps(manifest, indent=1).encode('utf-8')
            info = tarfile.TarInfo('manifest.json')
            info.size = len(data)
            info.mtime = int(datetime.datetime.now().timestamp())
            tar.addfile(info, io.BytesIO(data))
        os.chmod(backup, 0o600)

    # 3. copy, with the owner and group of CQPweb's own files
    ref = os.stat(os.path.join(root, 'lib', 'environment.php'))
    for rel in files:
        target = os.path.join(root, rel)
        tag = 'SAME' if rel in same else ('UPDATE' if rel in earlier else ('REPLACE' if rel in replace else 'NEW'))
        say(tag, f'{rel} (from {earlier[rel]})' if rel in earlier else rel)
        if dry or rel in same:
            continue
        d = os.path.dirname(target)
        if not os.path.isdir(d):
            os.makedirs(d, mode=0o755, exist_ok=True)
            os.chown(d, ref.st_uid, ref.st_gid)
        shutil.copyfile(os.path.join(HERE, 'overlay', rel), target)
        os.chmod(target, 0o644)
        os.chown(target, ref.st_uid, ref.st_gid)

    # 4. data folder
    web_user = detect_web_user(a.web_user)
    pw = pwd.getpwnam(web_user)
    data = a.data_dir
    fonts = a.font_dir or os.path.join(data, 'fonts')
    pages = a.pages_dir or os.path.join(data, 'pages')
    for d in (data, os.path.join(data, 'brand'), fonts, os.path.join(fonts, 'google'), pages):
        if os.path.isdir(d):
            # an existing folder keeps its owner and permissions
            say('KEPT', d)
            continue
        say('FOLDER', d)
        if not dry:
            os.makedirs(d, exist_ok=True)
            os.chown(d, pw.pw_uid, pw.pw_gid)
            os.chmod(d, 0o2775)
    src_google = os.path.join(HERE, 'fonts', 'google')
    for fam in sorted(os.listdir(src_google)):
        dest = os.path.join(fonts, 'google', fam)
        if os.path.exists(dest):
            say('KEPT', dest)
            continue
        say('FONT', dest)
        if not dry:
            shutil.copytree(os.path.join(src_google, fam), dest)
            for d, dirs, fs in os.walk(dest):
                os.chown(d, pw.pw_uid, pw.pw_gid)
                for f in fs:
                    os.chown(os.path.join(d, f), pw.pw_uid, pw.pw_gid)
                    os.chmod(os.path.join(d, f), 0o664)
    for page in sorted(os.listdir(os.path.join(HERE, 'userpages'))):
        dest = os.path.join(pages, page)
        if os.path.exists(dest):
            say('KEPT', dest)
            continue
        say('PAGE', dest)
        if not dry:
            shutil.copyfile(os.path.join(HERE, 'userpages', page), dest)
            os.chown(dest, pw.pw_uid, pw.pw_gid)
            os.chmod(dest, 0o664)

    # local-config.php: only when something differs from the defaults
    lc = os.path.join(root, 'lib', PACKAGE, 'local-config.php')
    settings = []
    if os.path.realpath(data) != DEFAULT_DATA:
        settings.append(('CP_DATA_DIR', data))
    if a.font_dir:
        settings.append(('CP_FONT_ROOT', fonts))
    if a.font_url:
        settings.append(('CP_FONT_URL', a.font_url))
    if a.pages_dir:
        settings.append(('CP_TUTORIAL_DIR', pages))
    if a.pages_url:
        settings.append(('CP_PAGES_URL', a.pages_url))
    if a.news_file:
        settings.append(('CP_NEWS_FILE', a.news_file))
    if settings:
        say('CONFIG', lc)
        body = ('<?php\n/* cqpweb-new-ui: folders chosen at installation (written by cqpweb-new-ui-install.py). */\n'
                + ''.join(f"define('{k}', {php_quote(v)});\n" for k, v in settings))
        if not dry:
            with open(lc, 'w', encoding='utf-8') as f:
                f.write(body)
            os.chmod(lc, 0o644)
            os.chown(lc, ref.st_uid, ref.st_gid)
    elif os.path.exists(lc):
        say('KEPT', lc)

    hooks = os.path.join(root, 'lib', PACKAGE, 'local-hooks.php')
    if os.path.exists(hooks):
        say('KEPT', f'{hooks} (the site\'s own code)')

    # 5. font list and syntax check
    if dry:
        say('DRY RUN', 'nothing was changed')
        return
    tool = os.path.join(root, 'lib', PACKAGE, 'tools-rebuild-fonts.php')
    if shutil.which('php'):
        r = run_as(web_user, ['php', tool])
        say('FONTS', (r.stdout or r.stderr).strip())
        bad = 0
        for rel in files:
            if rel.endswith('.php'):
                r = subprocess.run(['php', '-l', os.path.join(root, rel)], capture_output=True, text=True)
                if r.returncode != 0:
                    bad += 1
                    say('PHP ERR', f'{rel}: {r.stdout.strip()} {r.stderr.strip()}')
        say('PHP', f'{len([f for f in files if f.endswith(".php")]) - bad} files checked, {bad} with errors')
        if os.path.exists(hooks):
            r = subprocess.run(['php', '-l', hooks], capture_output=True, text=True)
            say('PHP' if r.returncode == 0 else 'PHP ERR', f'{hooks}: ' + (r.stdout.strip() or r.stderr.strip()))
    else:
        say('NOTE', f'php was not found on the PATH: run as the web server\'s user: php {tool}')
    say('DONE', f'{len(create)} new, {len(replace)} replaced, {len(same)} already installed. Backup: {backup}')
    say('NEXT', 'Log in to CQPweb as an administrator: Admin control panel > Personalization, then Backend system > E-mail.')


def missing_originals(root, originals):
    return [rel for rel in originals if not os.path.exists(os.path.join(root, rel))]


def uninstall(a):
    root = os.path.realpath(a.cqpweb_root)
    dry = a.dry_run
    if not dry and os.geteuid() != 0:
        sys.exit('Run this as root (sudo), or add --dry-run to only see what it would do.')
    backup = a.backup
    if not backup:
        cands = sorted(f for f in os.listdir(a.backup_dir) if f.startswith(f'{PACKAGE}-backup-') and f.endswith('.tar.gz')) \
            if os.path.isdir(a.backup_dir) else []
        if not cands:
            sys.exit(f'No backup found in {a.backup_dir}. Give one with --backup.')
        # the oldest backup holds CQPweb's originals; later ones may hold this package's own files
        backup = os.path.join(a.backup_dir, cands[0])
    say('BACKUP', backup)
    with tarfile.open(backup, 'r:gz') as tar:
        manifest = json.load(tar.extractfile('manifest.json'))
        if os.path.realpath(manifest['cqpweb_root']) != root:
            sys.exit(f'That backup was made for {manifest["cqpweb_root"]}, not {root}.')
        ours = {rel: sha256(os.path.join(HERE, 'overlay', rel)) for rel in package_files()}
        previous = read_previous()
        restored = set()
        for member in tar.getmembers():
            if not member.name.startswith('files/') or not member.isfile():
                continue
            rel = member.name[len('files/'):]
            restored.add(rel)
            target = os.path.join(root, rel)
            say('RESTORE', rel)
            if not dry:
                data = tar.extractfile(member).read()
                st = os.stat(target) if os.path.exists(target) else os.stat(os.path.join(root, 'lib', 'environment.php'))
                with open(target, 'wb') as f:
                    f.write(data)
                os.chown(target, st.st_uid, st.st_gid)
                os.chmod(target, 0o644)
        # the files the first installation added, and those later versions added
        added = list(manifest['created']) + [rel for rel in ours if rel not in restored and rel not in manifest['created']]
        for rel in added:
            target = os.path.join(root, rel)
            if not os.path.exists(target):
                continue
            now = sha256(target)
            if rel in ours and now != ours[rel] and now not in previous.get(rel, {}):
                say('LEFT', f'{rel} (changed since installation)')
                continue
            say('REMOVE', rel)
            if not dry:
                os.unlink(target)
    hooks = os.path.join(root, 'lib', PACKAGE, 'local-hooks.php')
    if os.path.exists(hooks):
        say('LEFT', f'{os.path.relpath(hooks, root)} (the site\'s own code; never removed)')
    lc = os.path.join(root, 'lib', PACKAGE, 'local-config.php')
    if os.path.exists(lc):
        say('REMOVE', os.path.relpath(lc, root))
        if not dry:
            os.unlink(lc)
    for d in (os.path.join(root, 'lib', PACKAGE), os.path.join(root, 'css', PACKAGE)):
        if os.path.isdir(d) and not os.listdir(d):
            say('REMOVE', os.path.relpath(d, root) + '/')
            if not dry:
                os.rmdir(d)
    say('KEPT', f'the data folder (settings, fonts, pages, uploaded images): {a.data_dir}')
    say('DONE' if not dry else 'DRY RUN', 'CQPweb\'s original files are back.' if not dry else 'nothing was changed')


def main():
    p = argparse.ArgumentParser(description='Install, upgrade or remove cqpweb-new-ui on a CQPweb 3.3.19 server.')
    p.add_argument('--version', action='version', version=f'%(prog)s {VERSION}')
    p.add_argument('--cqpweb-root', default=DEFAULT_ROOT, help=f'the CQPweb folder (default {DEFAULT_ROOT})')
    p.add_argument('--data-dir', default=DEFAULT_DATA, help=f'settings, uploads and history (default {DEFAULT_DATA})')
    p.add_argument('--font-dir', help='the font folder (default: fonts/ inside the data folder)')
    p.add_argument('--font-url', help='the address the web server already serves the font folder at (default: the package serves it)')
    p.add_argument('--pages-dir', help='the user pages folder (default: pages/ inside the data folder)')
    p.add_argument('--pages-url', help='the address the web server already serves the user pages folder at (default: the package serves it)')
    p.add_argument('--news-file', help='the file name of the news page in the user pages folder (default news.html)')
    p.add_argument('--web-user', help='the web server\'s user (default: found automatically)')
    p.add_argument('--backup-dir', default=DEFAULT_BACKUPS, help=f'where backups go (default {DEFAULT_BACKUPS})')
    p.add_argument('--accept-modified', action='store_true',
                   help='replace files that differ from CQPweb\'s originals (they are backed up first)')
    p.add_argument('--uninstall', action='store_true', help='put CQPweb\'s original files back from the first backup')
    p.add_argument('--backup', help='with --uninstall: the backup to restore from (default: the oldest in the backup folder)')
    p.add_argument('--dry-run', action='store_true', help='show what would be done, change nothing')
    a = p.parse_args()
    if sys.platform != 'linux':
        sys.exit('This installer runs on the Linux server where CQPweb is installed.')
    a.data_dir = os.path.abspath(a.data_dir)
    if a.uninstall:
        uninstall(a)
    else:
        install(a)


if __name__ == '__main__':
    main()
