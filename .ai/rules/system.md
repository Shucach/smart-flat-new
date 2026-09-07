---
paths:
  - 'app/Modules/System/**'
---

# System

## Production cannot power the board off from inside its container
The app runs in the shared ubuntu_22_php83_apache2 container on the Pi: no sudo binary, no docker socket, and /sbin/reboot is a symlink to systemctl addressing a PID namespace that owns nothing. `shell` power driver can never work there whatever the sudoers say — the sudo rule on the host (`pi ALL=(ALL) NOPASSWD: ALL`) applies to the pi user, not to www-data inside the container.

Production therefore uses SMARTFLAT_POWER_DRIVER=request-file: RequestFilePowerManager writes storage/app/power/<action>.request, which is on the host through the /var/www/sf.vanzzo.net bind mount (rsync excludes /storage/, so it survives deploys). A host unit, smartflat-power.path, acts on the file and deletes it.

NullPowerManager throws rather than returning quietly: a power button that answers "команду надіслано" and leaves the server running is the bug this was.

df: the container reports NFS shares as `192.168.0.39:/export/media` and its root as `overlay`, neither of which starts with `/`, and Docker binds single files such as /etc/hosts that df reports as whole filesystems. DfParser filters on a pseudo-device list plus ignored mount roots for that reason — never on "device starts with a slash".

## The host half of power control drains every request file
Installed on the Pi (ssh pi@192.168.0.210 -p5252): /usr/local/bin/smartflat-power, plus smartflat-power.path and .service in /etc/systemd/system. The path unit is enabled and watches PathExistsGlob=/var/www/sf.vanzzo.net/code/storage/app/power/*.request.

The script deletes every *.request it finds, recognised action or not, and deletes before acting. Both matter: a PathExistsGlob unit does not fire again while the glob still matches, so one leftover file — an unknown action, or a reboot that was cancelled — would silence power control permanently.

Verified end to end by writing an unknown-action request as www-data from inside the container: the unit fired within four seconds, drained the directory and left uptime untouched.
