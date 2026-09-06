---
paths:
  - 'app/Modules/Frame/**'
---

# Frame

## Clips are transcoded here, never on the frame
The frame is a BCM2835 board: it decodes H.264 only (no HEVC block at all), refuses anything that is not 600x1024 at profile ≤ High / level ≤ 4.2 / yuv420p, and cannot re-encode. So a phone clip is converted by FfmpegVideoTranscoder on the queue before UploadFrameVideo sends it, and the frame's rejection reason is passed back verbatim.

FrameException::$permanent tells a refusal (the frame read the file and said no) from an outage: only the latter is retried. UploadFrameVideo keeps the encode in frame-uploads/encoded/{id}.mp4 so a retry costs only the transfer.

Progress lives in frame_uploads rows because an encode takes minutes; the page polls `uploads` and shows it. Requires ffmpeg/ffprobe on the app host — set SMARTFLAT_FRAME_VIDEO_DRIVER=null where they are missing.
