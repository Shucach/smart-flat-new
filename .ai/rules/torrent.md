---
paths:
  - 'app/Modules/Torrent/**'
---

# Torrent

## Transmission RPC quirks the client absorbs
Transmission answers the first call of a session with 409 plus an `X-Transmission-Session-Id` header; TransmissionTorrentClient caches that id and retries once. The id rotates on every daemon restart, so the retry is a normal path, not an error.

`torrent-add` answers `result: success` for a torrent that is already there, under `torrent-duplicate` instead of `torrent-added` — the client turns that into a rejection so the page does not report a fresh addition.

The daemon runs in its own container on the Pi (/var/www/transmission), with USER/PASS from the .env next to its compose file. Setting both is what turns `rpc-authentication-required` on; clearing them turns it off again on the next `docker compose up -d`.

`torrent-get` only reports the page count, files and peers are per-torrent kilobytes, and the list is polled every few seconds — hence LIST_FIELDS and DETAIL_FIELDS are separate and the detail is read only while a panel is open.

NullTorrentClient throws rather than answering quietly: an empty list would read as "no torrents" instead of "no daemon".
