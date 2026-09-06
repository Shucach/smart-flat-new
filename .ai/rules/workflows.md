---
paths:
  - '.github/workflows/**'
---

# Workflows

## Production queue worker is a systemd unit on the Pi
Production runs QUEUE_CONNECTION=database with one worker: /etc/systemd/system/smartflat-queue.service on the Raspberry Pi (ssh pi@192.168.0.210 -p5252), which runs `docker exec -u www-data ubuntu_22_php83_apache2 … artisan queue:work` with --max-time=3600 and Restart=always.

One worker only, on purpose: a video encode takes most of that board. The deploy job ends with `queue:restart` so workers pick up the new release; systemd brings them back.

ffmpeg is installed in that shared container and pinned in /var/docker/ubuntu_php_apache2/ubuntu22_php83_apache2/Dockerfile so a rebuild keeps it.
