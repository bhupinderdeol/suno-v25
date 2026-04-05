<?php
// ── Upload Handler (AJAX) ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['mediaUpload'])) {
    header('Content-Type: application/json');
    $uploadDir = __DIR__ . '/';
    $allowedExts = ['mp3', 'mp4'];
    $maxSize = 512 * 1024 * 1024; // 512 MB
    $file = $_FILES['mediaUpload'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
        exit;
    }
    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['success' => false, 'message' => 'Only MP3 and MP4 files are allowed.']);
        exit;
    }
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File exceeds 512 MB limit.']);
        exit;
    }

    $safeName = preg_replace('/[^A-Za-z0-9._\- ]/', '_', basename($file['name']));
    $destPath = $uploadDir . $safeName;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success' => true, 'message' => 'Uploaded: ' . $safeName, 'filename' => $safeName]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Server could not save file. Check directory permissions on /var/www/html/suno/']);
    }
    exit;
}

// ── Scan Media Files ─────────────────────────────────────────────────────────
$mediaFiles = array_merge(
    glob("*.mp3") ?: [],
    glob("*.MP3") ?: [],
    glob("*.mp4") ?: [],
    glob("*.MP4") ?: []
);
sort($mediaFiles);
$mediaList = json_encode($mediaFiles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNA Spectrum Player v26 — Audio & Video</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #16213e 50%, #1a1a2e 100%);
            color: #00ff88;
            overflow-x: hidden;
        }

        .container {
            display: grid;
            grid-template-columns: 320px 1fr;
            height: 100vh;
            gap: 0;
        }

        /* ── Playlist Column ─────────────────────────────────────────── */
        .playlist-column {
            background: linear-gradient(180deg, rgba(0,255,136,0.05) 0%, rgba(0,0,0,0.3) 100%);
            border-right: 3px solid #00ff88;
            overflow-y: auto;
            padding: 10px;
            box-shadow: inset -5px 0 15px rgba(0,255,136,0.2);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .playlist-header {
            background: linear-gradient(90deg, #9C27B0 0%, #7B1FA2 100%);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 0 15px rgba(156,39,176,0.5);
            flex-shrink: 0;
        }

        .playlist-title {
            font-size: 1.2em;
            font-weight: bold;
            color: white;
            text-shadow: 0 0 10px rgba(255,255,255,0.8);
        }

        .track-count {
            font-size: 0.85em;
            color: #00ffff;
            margin-top: 3px;
        }

        /* ── Upload Panel ────────────────────────────────────────────── */
        .upload-panel {
            flex-shrink: 0;
            background: rgba(0,255,136,0.04);
            border: 2px dashed rgba(0,255,136,0.4);
            border-radius: 10px;
            padding: 10px;
            transition: border-color 0.3s, background 0.3s;
        }

        .upload-panel.drag-over {
            border-color: #00ffff;
            background: rgba(0,255,255,0.08);
            box-shadow: 0 0 20px rgba(0,255,255,0.3);
        }

        .upload-title {
            text-align: center;
            font-size: 0.78em;
            font-weight: bold;
            color: #00ffff;
            text-shadow: 0 0 6px #00ffff;
            letter-spacing: 1px;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .upload-drop-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .upload-drop-icon {
            font-size: 28px;
            line-height: 1;
            filter: drop-shadow(0 0 6px #00ff88);
        }

        .upload-drop-hint {
            font-size: 0.72em;
            color: rgba(0,255,136,0.6);
            text-align: center;
            line-height: 1.4;
        }

        /* Hidden file input */
        #fileInput { display: none; }

        /* ── Neon Upload Button ──────────────────────────────────────── */
        .btn-upload {
            width: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            border: 2px solid #00ff88;
            background: linear-gradient(135deg, rgba(0,255,136,0.12) 0%, rgba(0,255,136,0.04) 100%);
            color: #00ff88;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            letter-spacing: 0.5px;
            transition: all 0.25s ease;
            text-shadow: 0 0 6px rgba(0,255,136,0.8);
            box-shadow: 0 0 12px rgba(0,255,136,0.25), inset 0 0 6px rgba(0,255,136,0.05);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .btn-upload:hover {
            background: linear-gradient(135deg, rgba(0,255,136,0.28) 0%, rgba(0,255,136,0.12) 100%);
            border-color: #00ffff;
            color: #00ffff;
            box-shadow: 0 0 22px rgba(0,255,255,0.5), inset 0 0 10px rgba(0,255,255,0.08);
            text-shadow: 0 0 10px rgba(0,255,255,1);
            transform: translateY(-1px);
        }

        .btn-upload:active { transform: scale(0.97) translateY(0); }

        .upload-progress-wrap {
            display: none;
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }

        .upload-progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #00ff88, #00ffff);
            border-radius: 4px;
            transition: width 0.2s;
            box-shadow: 0 0 8px #00ff88;
        }

        .upload-status {
            font-size: 0.7em;
            text-align: center;
            min-height: 14px;
            transition: color 0.3s;
        }

        .upload-status.ok  { color: #00ff88; text-shadow: 0 0 5px #00ff88; }
        .upload-status.err { color: #ff4466; text-shadow: 0 0 5px #ff4466; }

        /* ── Playlist Items ──────────────────────────────────────────── */
        #playlistItems { flex: 1; }

        .playlist-item {
            padding: 10px 12px;
            margin-bottom: 6px;
            background: rgba(0,255,136,0.05);
            border: 2px solid rgba(0,255,136,0.3);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9em;
            word-wrap: break-word;
            line-height: 1.4;
        }

        .playlist-item:hover {
            background: rgba(0,255,255,0.1);
            border-color: #00ffff;
            transform: translateX(5px);
            box-shadow: 0 0 15px rgba(0,255,255,0.4);
        }

        .playlist-item.active {
            background: linear-gradient(90deg, rgba(255,0,255,0.3) 0%, rgba(0,255,255,0.3) 100%);
            border-color: #ff00ff;
            box-shadow: 0 0 20px rgba(255,0,255,0.6);
            font-weight: bold;
            color: #ff00ff;
        }

        .playlist-item.video-file::before { content: "🎬 "; }
        .playlist-item.audio-file::before { content: "🎵 "; }

        /* Scrollbar */
        .playlist-column::-webkit-scrollbar { width: 10px; }
        .playlist-column::-webkit-scrollbar-track { background: rgba(0,0,0,0.3); border-radius: 10px; }
        .playlist-column::-webkit-scrollbar-thumb { background: #00ff88; border-radius: 10px; box-shadow: 0 0 10px #00ff88; }

        /* ── Player Column ───────────────────────────────────────────── */
        .player-column {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow-y: auto;
            padding: 10px;
            position: relative;
        }

        .now-playing {
            background: linear-gradient(90deg, #00ff88 0%, #00ffff 100%);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            color: #000;
            font-weight: bold;
            font-size: 1.1em;
            box-shadow: 0 0 20px rgba(0,255,136,0.5);
            text-align: center;
            word-wrap: break-word;
        }

        /* ── Video Container ─────────────────────────────────────────── */
        #video-container {
            width: 100%;
            margin: 5px auto;
            border: 3px solid #ff00ff;
            border-radius: 8px;
            background: #000;
            box-shadow: 0 0 30px rgba(255,0,255,0.4);
            display: none;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        #video-container.visible { display: block; }

        #mediaElement {
            width: 100%;
            max-height: 700px;
            display: block;
            border-radius: 6px;
            position: relative;
            z-index: 2;
        }

        /* ── Controls Row ────────────────────────────────────────────── */
        .controls-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: center;
            margin: 8px 0;
            padding: 10px;
            background: rgba(0,255,136,0.05);
            border-radius: 6px;
            border: 1px solid rgba(0,255,136,0.2);
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .control-group label {
            font-weight: bold;
            color: #00ffff;
            text-shadow: 0 0 5px #00ffff;
            font-size: 0.9em;
            white-space: nowrap;
        }

        /* ── Select Dropdowns ────────────────────────────────────────── */
        select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 2px solid rgba(0,255,136,0.5);
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #00ff88;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            outline: none;
            min-width: 120px;
            box-shadow: 0 0 10px rgba(0,255,136,0.2);
            transition: all 0.3s;
        }

        select:hover  { border-color: #00ffff; box-shadow: 0 0 15px rgba(0,255,255,0.4); }
        select:focus  { border-color: #ff00ff; box-shadow: 0 0 15px rgba(255,0,255,0.4); }
        select option { background: #1a1a2e; color: #00ff88; padding: 8px; }

        /* ── Play/Pause Neon Button ───────────────────────────────────
           Improved v26: full neon glow, pulsing border, responsive
        ──────────────────────────────────────────────────────────────── */
        .toggle-btn {
            padding: 9px 18px;
            border-radius: 8px;
            border: 2px solid #00ff88;
            background: linear-gradient(135deg, rgba(0,255,136,0.15) 0%, rgba(0,200,100,0.05) 100%);
            color: #00ff88;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            outline: none;
            min-width: 120px;
            text-shadow: 0 0 8px rgba(0,255,136,0.9);
            box-shadow:
                0 0 12px rgba(0,255,136,0.35),
                inset 0 0 8px rgba(0,255,136,0.06);
            transition: all 0.25s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        /* Animated shimmer line */
        .toggle-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,255,136,0.15), transparent);
            transition: left 0.4s ease;
        }

        .toggle-btn:hover {
            border-color: #00ffff;
            color: #00ffff;
            background: linear-gradient(135deg, rgba(0,255,255,0.2) 0%, rgba(0,200,255,0.08) 100%);
            box-shadow:
                0 0 20px rgba(0,255,255,0.55),
                0 0 40px rgba(0,255,255,0.2),
                inset 0 0 12px rgba(0,255,255,0.08);
            text-shadow: 0 0 12px rgba(0,255,255,1);
            transform: translateY(-1px);
        }

        .toggle-btn:hover::before { left: 150%; }

        .toggle-btn:active {
            transform: scale(0.97) translateY(0);
            box-shadow: 0 0 10px rgba(0,255,136,0.4);
        }

        /* Playing state — magenta glow */
        .toggle-btn.playing {
            border-color: #ff00ff;
            color: #ff00ff;
            background: linear-gradient(135deg, rgba(255,0,255,0.15) 0%, rgba(180,0,180,0.05) 100%);
            box-shadow:
                0 0 16px rgba(255,0,255,0.5),
                0 0 35px rgba(255,0,255,0.15),
                inset 0 0 10px rgba(255,0,255,0.07);
            text-shadow: 0 0 10px rgba(255,0,255,1);
            animation: playingPulse 2s ease-in-out infinite;
        }

        @keyframes playingPulse {
            0%, 100% { box-shadow: 0 0 16px rgba(255,0,255,0.5), 0 0 35px rgba(255,0,255,0.15), inset 0 0 10px rgba(255,0,255,0.07); }
            50%       { box-shadow: 0 0 24px rgba(255,0,255,0.75), 0 0 55px rgba(255,0,255,0.25), inset 0 0 16px rgba(255,0,255,0.1); }
        }

        /* ── Spectrum Container ───────────────────────────────────────── */
        #spectrum-container {
            margin: 5px auto;
            border: 3px solid #00ff88;
            border-radius: 8px;
            width: 100%;
            height: 300px;
            background: radial-gradient(circle, #0a0a0a 0%, #000000 100%);
            box-shadow: inset 0 0 50px rgba(0,255,136,0.2), 0 0 30px rgba(0,255,136,0.3);
            position: relative;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        #spectrum-container.video-overlay {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            margin: 0;
            border-radius: 6px;
            background: transparent !important;
            box-shadow: inset 0 0 100px rgba(0,255,136,0.1);
            border: 2px solid rgba(0,255,136,0.3);
            z-index: 1;
            pointer-events: none;
        }

        #spectrum-container.video-overlay #reactions-overlay { pointer-events: auto; }
        #spectrum-container.video-overlay canvas { background: transparent; }

        /* ── Reactions Overlay ───────────────────────────────────────── */
        #reactions-overlay {
            position: absolute;
            top: 8px; right: 8px;
            width: 220px;
            background: rgba(0,0,0,0.55);
            border: 1px solid rgba(0,255,136,0.35);
            border-radius: 10px;
            padding: 10px 12px;
            z-index: 3;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 284px;
            box-sizing: border-box;
            pointer-events: auto;
        }

        .react-row { display: flex; gap: 10px; justify-content: center; }

        .react-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            user-select: none;
            font-size: 28px;
            line-height: 1;
            transition: transform 0.1s;
            background: none;
            border: none;
            padding: 0; margin: 0;
            box-shadow: none;
        }

        .react-btn:hover  { transform: scale(1.2); background: none; box-shadow: none; }
        .react-btn:active { transform: scale(1.35); }

        .react-count {
            font-size: 13px;
            color: #00ffff;
            text-shadow: 0 0 6px #00ffff;
            font-weight: bold;
            margin-top: 2px;
        }

        #overlay-divider { border: none; border-top: 1px solid rgba(0,255,136,0.25); margin: 0; }

        #overlay-comments-list {
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
            max-height: 200px;
        }

        #overlay-comments-list::-webkit-scrollbar { width: 5px; }
        #overlay-comments-list::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); border-radius: 5px; }
        #overlay-comments-list::-webkit-scrollbar-thumb { background: rgba(0,255,136,0.4); border-radius: 5px; }

        .overlay-comment {
            background: rgba(255,255,255,0.07);
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 12px;
            color: #e0e0e0;
            word-break: break-word;
            line-height: 1.4;
        }

        .overlay-no-comments {
            color: rgba(255,255,255,0.3);
            font-size: 11px;
            text-align: center;
            font-style: italic;
        }

        #overlay-comment-input-row { display: flex; gap: 5px; }

        #overlay-comment-input {
            flex: 1;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(0,255,136,0.3);
            border-radius: 5px;
            color: #fff;
            font-size: 12px;
            padding: 5px 7px;
            outline: none;
        }

        #overlay-comment-input::placeholder { color: rgba(255,255,255,0.35); }
        #overlay-comment-input:focus { border-color: rgba(0,255,136,0.7); }

        #overlay-comment-submit {
            background: linear-gradient(135deg, #00ff88, #00cc6a);
            border: none;
            border-radius: 5px;
            color: #000;
            font-size: 13px;
            font-weight: bold;
            padding: 4px 9px;
            cursor: pointer;
            box-shadow: none;
            margin: 0;
        }

        #overlay-comment-submit:hover {
            background: linear-gradient(135deg, #00cc6a, #00aa55);
            box-shadow: none;
            transform: none;
        }

        /* ── Progress Bar ────────────────────────────────────────────── */
        canvas { width: 100%; height: 100%; display: block; }

        .progress-container {
            position: relative;
            width: 100%;
            height: 18px;
            background-color: rgba(255,255,255,0.1);
            border-radius: 10px;
            margin: 10px 0;
            cursor: pointer;
            border: 1px solid rgba(0,255,136,0.3);
        }

        #progressBar {
            position: absolute;
            left: 0; top: 0;
            height: 100%;
            background: linear-gradient(90deg, #00ff88 0%, #00ffff 100%);
            border-radius: 10px;
            width: 0%;
            box-shadow: 0 0 10px rgba(0,255,136,0.5);
        }

        #progressHandle {
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 14px; height: 14px;
            background-color: #fff;
            border: 2px solid #00ff88;
            border-radius: 50%;
            left: 0%;
            cursor: pointer;
            box-shadow: 0 0 10px rgba(0,255,136,0.8);
        }

        #volumeControl { width: 120px; }

        #timeDisplay {
            color: #00ff88;
            text-shadow: 0 0 5px #00ff88;
            font-family: monospace;
            font-size: 1em;
            white-space: nowrap;
        }

        /* ── Mobile: reaction sizes ──────────────────────────────────── */
        @media (max-width: 768px) {
            #reactions-overlay { width: 170px; font-size: 12px; }
            .react-btn { font-size: 22px; }
        }

        /* ── Responsive Layout ───────────────────────────────────────── */
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
                height: auto;
                min-height: 100vh;
            }

            .playlist-column {
                border-right: none;
                border-bottom: 3px solid #00ff88;
                max-height: 340px;
                overflow-y: auto;
            }

            #spectrum-container { height: 250px; }
            #mediaElement { max-height: 500px; }
            .controls-row { flex-direction: column; gap: 10px; }
            .player-column { height: auto; }
        }

        @media (max-width: 768px) {
            #spectrum-container { height: 200px; }
            #mediaElement { max-height: 400px; }
            select, .toggle-btn { min-width: 100px; font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- ── Playlist Column ──────────────────────────────────────────── -->
    <div class="playlist-column">

        <div class="playlist-header">
            <div class="playlist-title">🎬 MEDIA LIBRARY 🎵</div>
            <div class="track-count" id="trackCount">0 files</div>
        </div>

        <!-- Upload Panel -->
        <div class="upload-panel" id="uploadPanel">
            <div class="upload-title">⬆ Upload Media</div>
            <div class="upload-drop-zone" id="dropZone">
                <div class="upload-drop-icon">📂</div>
                <div class="upload-drop-hint">Drag &amp; drop MP3 / MP4 here<br>or click the button below</div>
            </div>
            <input type="file" id="fileInput" accept=".mp3,.mp4,audio/mpeg,video/mp4" multiple>
            <button class="btn-upload" id="browseBtn">📁 Browse Files</button>
            <div class="upload-progress-wrap" id="uploadProgressWrap">
                <div class="upload-progress-bar" id="uploadProgressBar"></div>
            </div>
            <div class="upload-status" id="uploadStatus"></div>
        </div>

        <!-- Playlist Items -->
        <div id="playlistItems"></div>
    </div>

    <!-- ── Player Column ────────────────────────────────────────────── -->
    <div class="player-column" id="playerColumn">

        <div class="now-playing" id="nowPlaying">No media loaded</div>

        <!-- Video Display (shown for MP4) -->
        <div id="video-container">
            <video id="mediaElement" controls></video>
        </div>

        <!-- Controls Row 1: Playback / Seek / Audio / Volume / Speed -->
        <div class="controls-row">
            <div class="control-group">
                <label for="playbackBtn">Playback:</label>
                <button class="toggle-btn" id="playbackBtn">▶️ Play</button>
            </div>
            <div class="control-group">
                <label for="seekSelect">Seek:</label>
                <select id="seekSelect">
                    <option value="none">-- Select --</option>
                    <option value="rewind10">⏪ -10s</option>
                    <option value="forward10">+10s ⏩</option>
                </select>
            </div>
            <div class="control-group">
                <label for="audioSelect">Audio:</label>
                <select id="audioSelect">
                    <option value="unmuted" selected>🔊 Unmuted</option>
                    <option value="muted">🔇 Muted</option>
                </select>
            </div>
            <div class="control-group">
                <label for="volumeControl">Vol:</label>
                <input type="range" id="volumeControl" min="0" max="1" step="0.1" value="1">
            </div>
            <div class="control-group">
                <label for="speedControl">Speed:</label>
                <select id="speedControl">
                    <option value="0.5">0.5x</option>
                    <option value="1" selected>1x</option>
                    <option value="1.5">1.5x</option>
                    <option value="2">2x</option>
                </select>
            </div>
        </div>

        <!-- Controls Row 2: Track / Loop / Shuffle / FFT / Style -->
        <div class="controls-row">
            <div class="control-group">
                <label for="playlistSelect">Track:</label>
                <select id="playlistSelect">
                    <option value="none">-- Select --</option>
                    <option value="prev">⏮️ Previous</option>
                    <option value="next">Next ⏭️</option>
                </select>
            </div>
            <div class="control-group">
                <label for="loopSelect">Loop:</label>
                <select id="loopSelect">
                    <option value="off" selected>🔁 Off</option>
                    <option value="on">🔁 On</option>
                </select>
            </div>
            <div class="control-group">
                <label for="shuffleSelect">Shuffle:</label>
                <select id="shuffleSelect">
                    <option value="off" selected>🔀 Off</option>
                    <option value="on">🔀 On</option>
                </select>
            </div>
            <div class="control-group">
                <label for="fftSelect">FFT:</label>
                <select id="fftSelect">
                    <option value="32">32</option>
                    <option value="64">64</option>
                    <option value="128">128</option>
                    <option value="256" selected>256</option>
                    <option value="512">512</option>
                    <option value="1024">1024</option>
                    <option value="2048">2048</option>
                    <option value="4096">4096</option>
                </select>
            </div>
            <div class="control-group">
                <label for="styleSelect">Style:</label>
                <select id="styleSelect">
                    <optgroup label="Classic Styles">
                        <option value="randomBars">Random Bars</option>
                        <option value="rainbowBars">Rainbow Bars</option>
                        <option value="circularBars">Circular Bars</option>
                        <option value="waveform">Waveform</option>
                        <option value="mirrored">Mirrored</option>
                        <option value="gradient">Gradient</option>
                        <option value="particles">Particles</option>
                        <option value="radial">Radial Bloom</option>
                    </optgroup>
                    <optgroup label="🧬 DNA Styles">
                        <option value="dnaPulse" selected>DNA Pulse</option>
                        <option value="dnaHelix">DNA Helix</option>
                        <option value="dnaSpiral">DNA Spiral</option>
                        <option value="dnaStrand">DNA Strand</option>
                        <option value="dnaCircular">DNA Circular</option>
                    </optgroup>
                </select>
            </div>
        </div>

        <!-- Spectrum Canvas -->
        <div id="spectrum-container">
            <canvas id="spectrum"></canvas>
            <!-- Reactions & Comments Overlay -->
            <div id="reactions-overlay">
                <div class="react-row">
                    <button class="react-btn" id="loveBtn" title="Love">❤️<span class="react-count" id="loveCount">0</span></button>
                    <button class="react-btn" id="likeBtn" title="Like">👍<span class="react-count" id="likeCount">0</span></button>
                </div>
                <hr id="overlay-divider">
                <div id="overlay-comments-list">
                    <span class="overlay-no-comments">No comments yet</span>
                </div>
                <div id="overlay-comment-input-row">
                    <input type="text" id="overlay-comment-input" maxlength="120" placeholder="Comment…">
                    <button id="overlay-comment-submit">➤</button>
                </div>
            </div>
        </div>

        <!-- Time Display -->
        <div class="controls-row" id="timeDisplayRow" style="justify-content:center;">
            <span id="timeDisplay">0:00 / 0:00</span>
        </div>

        <!-- Progress Bar -->
        <div class="progress-container" id="progressBarContainer">
            <div id="progressBar"></div>
            <div id="progressHandle"></div>
        </div>

    </div><!-- /.player-column -->
</div><!-- /.container -->

<script>
    // ── Playlist data from PHP ───────────────────────────────────────────────
    const playlist = <?php echo $mediaList; ?>;
    let currentTrackIndex = 0;
    let isLooping   = false;
    let isShuffling = false;

    // ── Canvas setup ─────────────────────────────────────────────────────────
    const canvas = document.getElementById("spectrum");
    const ctx    = canvas.getContext("2d");
    function resizeCanvas() { canvas.width = canvas.offsetWidth; canvas.height = canvas.offsetHeight; }
    resizeCanvas();

    // ── Audio setup ───────────────────────────────────────────────────────────
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const analyser     = audioContext.createAnalyser();
    analyser.fftSize   = 256;
    let bufferLength   = analyser.frequencyBinCount;
    let dataArray      = new Uint8Array(bufferLength);

    let currentStyle = 'dnaPulse';
    let rotation     = 0;

    // ── DOM ───────────────────────────────────────────────────────────────────
    const mediaElement           = document.getElementById('mediaElement');
    const videoContainer         = document.getElementById('video-container');
    const playerColumn           = document.getElementById('playerColumn');
    const spectrumContainer      = document.getElementById('spectrum-container');
    const timeDisplayRow         = document.getElementById('timeDisplayRow');
    const progressBarContainer   = document.getElementById('progressBarContainer');

    const source = audioContext.createMediaElementSource(mediaElement);
    source.connect(analyser);
    analyser.connect(audioContext.destination);

    const playbackBtn   = document.getElementById('playbackBtn');
    const seekSelect    = document.getElementById('seekSelect');
    const audioSelect   = document.getElementById('audioSelect');
    const volumeControl = document.getElementById('volumeControl');
    const speedControl  = document.getElementById('speedControl');
    const playlistSelect= document.getElementById('playlistSelect');
    const loopSelect    = document.getElementById('loopSelect');
    const shuffleSelect = document.getElementById('shuffleSelect');
    const fftSelect     = document.getElementById('fftSelect');
    const styleSelect   = document.getElementById('styleSelect');

    const timeDisplay   = document.getElementById('timeDisplay');
    const progressBar   = document.getElementById('progressBar');
    const progressHandle= document.getElementById('progressHandle');
    const nowPlaying    = document.getElementById('nowPlaying');
    const trackCount    = document.getElementById('trackCount');
    const playlistItems = document.getElementById('playlistItems');

    // ── Helpers ───────────────────────────────────────────────────────────────
    function isVideoFile(filename) {
        return filename.split('.').pop().toLowerCase() === 'mp4';
    }

    // ── Playlist init ─────────────────────────────────────────────────────────
    function initPlaylist() {
        trackCount.textContent = `${playlist.length} files`;
        playlist.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'playlist-item ' + (isVideoFile(file) ? 'video-file' : 'audio-file');
            item.textContent = `${index + 1}. ${file}`;
            item.addEventListener('click', () => loadTrack(index));
            playlistItems.appendChild(item);
        });
        if (playlist.length > 0) loadTrack(0);
    }

    function loadTrack(index) {
        if (index < 0 || index >= playlist.length) return;
        currentTrackIndex = index;
        const file = playlist[index];
        mediaElement.src = file;

        if (isVideoFile(file)) {
            videoContainer.classList.add('visible');
            nowPlaying.textContent = `🎬 ${file}`;
            videoContainer.appendChild(spectrumContainer);
            spectrumContainer.classList.add('video-overlay');
        } else {
            videoContainer.classList.remove('visible');
            nowPlaying.textContent = `🎵 ${file}`;
            playerColumn.insertBefore(spectrumContainer, timeDisplayRow);
            spectrumContainer.classList.remove('video-overlay');
        }

        document.querySelectorAll('.playlist-item').forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });

        playMedia();
    }

    async function playMedia() {
        if (audioContext.state === 'suspended') await audioContext.resume();
        await mediaElement.play();
        if (!animationId) drawSpectrum();
        updatePlaybackBtnText();
    }

    function pauseMedia() {
        mediaElement.pause();
        updatePlaybackBtnText();
    }

    function updatePlaybackBtnText() {
        if (mediaElement.paused) {
            playbackBtn.textContent = '▶️ Play';
            playbackBtn.classList.remove('playing');
        } else {
            playbackBtn.textContent = '⏸️ Pause';
            playbackBtn.classList.add('playing');
        }
    }

    playbackBtn.addEventListener('click', async () => {
        if (audioContext.state === 'suspended') await audioContext.resume();
        if (mediaElement.paused) {
            await mediaElement.play();
            if (!animationId) drawSpectrum();
        } else {
            mediaElement.pause();
        }
        updatePlaybackBtnText();
    });

    mediaElement.addEventListener('play',  updatePlaybackBtnText);
    mediaElement.addEventListener('pause', updatePlaybackBtnText);

    seekSelect.addEventListener('change', () => {
        const action = seekSelect.value;
        if (action === 'rewind10')  mediaElement.currentTime = Math.max(mediaElement.currentTime - 10, 0);
        if (action === 'forward10' && mediaElement.duration)
            mediaElement.currentTime = Math.min(mediaElement.currentTime + 10, mediaElement.duration);
        seekSelect.value = 'none';
    });

    audioSelect.addEventListener('change', () => {
        mediaElement.muted = audioSelect.value === 'muted';
    });

    volumeControl.addEventListener('input', e => {
        mediaElement.volume = e.target.value;
        audioSelect.value   = mediaElement.volume == 0 ? 'muted' : 'unmuted';
    });

    speedControl.addEventListener('change', e => {
        mediaElement.playbackRate = parseFloat(e.target.value);
    });

    playlistSelect.addEventListener('change', () => {
        const action = playlistSelect.value;
        if (action === 'prev') {
            let ni = currentTrackIndex - 1;
            if (ni < 0) ni = isLooping ? playlist.length - 1 : 0;
            loadTrack(ni);
        } else if (action === 'next') {
            playNextTrack();
        }
        playlistSelect.value = 'none';
    });

    loopSelect.addEventListener('change',    () => { isLooping   = loopSelect.value   === 'on'; });
    shuffleSelect.addEventListener('change', () => { isShuffling = shuffleSelect.value === 'on'; });

    function playNextTrack() {
        let newIndex;
        if (isShuffling) {
            newIndex = Math.floor(Math.random() * playlist.length);
        } else {
            newIndex = currentTrackIndex + 1;
            if (newIndex >= playlist.length) newIndex = isLooping ? 0 : playlist.length - 1;
        }
        loadTrack(newIndex);
    }

    mediaElement.addEventListener('ended', () => {
        if (currentTrackIndex < playlist.length - 1 || isLooping) playNextTrack();
        else updatePlaybackBtnText();
    });

    fftSelect.addEventListener('change', () => {
        analyser.fftSize = parseInt(fftSelect.value);
        bufferLength     = analyser.frequencyBinCount;
        dataArray        = new Uint8Array(bufferLength);
    });

    styleSelect.addEventListener('change', () => { currentStyle = styleSelect.value; });

    // ── Classic Visualization Functions (all intact from v24) ────────────────
    function drawRandomBars() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const barWidth = canvas.width / bufferLength;
        for (let i = 0; i < bufferLength; i++) {
            const barHeight = dataArray[i] * 1.8;
            const x = i * barWidth * 1.25;
            const randomColor = `rgb(${Math.floor(Math.random()*256)},${Math.floor(Math.random()*256)},${Math.floor(Math.random()*256)})`;
            ctx.fillStyle = randomColor;
            ctx.fillRect(x, canvas.height - barHeight, barWidth * 1.5, barHeight);
        }
    }

    function drawRainbowBars() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const barWidth = (canvas.width / bufferLength) * 2;
        let x = 0;
        for (let i = 0; i < bufferLength; i++) {
            const barHeight = (dataArray[i] / 255) * canvas.height;
            const hue = (i / bufferLength) * 360;
            ctx.fillStyle = `hsl(${hue},100%,50%)`;
            ctx.fillRect(x, canvas.height - barHeight, barWidth - 1, barHeight);
            x += barWidth;
        }
    }

    function drawCircularBars() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius  = Math.min(centerX, centerY) - 50;
        for (let i = 0; i < bufferLength; i++) {
            const barHeight = dataArray[i] * 0.5;
            const angle = (i / bufferLength) * Math.PI * 2;
            const x1 = centerX + Math.cos(angle) * radius;
            const y1 = centerY + Math.sin(angle) * radius;
            const x2 = centerX + Math.cos(angle) * (radius + barHeight);
            const y2 = centerY + Math.sin(angle) * (radius + barHeight);
            const hue = (i / bufferLength) * 360;
            ctx.strokeStyle = `hsl(${hue},100%,50%)`;
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
        }
    }

    function drawWaveform() {
        analyser.getByteTimeDomainData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#00ff00';
        ctx.beginPath();
        const sliceWidth = canvas.width / bufferLength;
        let x = 0;
        for (let i = 0; i < bufferLength; i++) {
            const v = dataArray[i] / 128.0;
            const y = v * canvas.height / 2;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            x += sliceWidth;
        }
        ctx.lineTo(canvas.width, canvas.height / 2);
        ctx.stroke();
    }

    function drawMirroredBars() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const barWidth = (canvas.width / bufferLength) * 2;
        let x = 0;
        for (let i = 0; i < bufferLength; i++) {
            const barHeight = (dataArray[i] / 255) * (canvas.height / 2);
            const hue = (i / bufferLength) * 360;
            ctx.fillStyle = `hsl(${hue},100%,50%)`;
            ctx.fillRect(x, canvas.height/2 - barHeight, barWidth-1, barHeight);
            ctx.fillRect(x, canvas.height/2,              barWidth-1, barHeight);
            x += barWidth;
        }
    }

    function drawGradientBars() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const barWidth = (canvas.width / bufferLength) * 2;
        let x = 0;
        for (let i = 0; i < bufferLength; i++) {
            const barHeight = (dataArray[i] / 255) * canvas.height;
            const gradient  = ctx.createLinearGradient(0, canvas.height - barHeight, 0, canvas.height);
            gradient.addColorStop(0,   '#ff0000');
            gradient.addColorStop(0.5, '#ffff00');
            gradient.addColorStop(1,   '#00ff00');
            ctx.fillStyle = gradient;
            ctx.fillRect(x, canvas.height - barHeight, barWidth-1, barHeight);
            x += barWidth;
        }
    }

    function drawParticles() {
        analyser.getByteFrequencyData(dataArray);
        ctx.fillStyle = 'rgba(0,0,0,0.1)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < bufferLength; i++) {
            const intensity = dataArray[i] / 255;
            const x    = (i / bufferLength) * canvas.width;
            const y    = canvas.height / 2;
            const size = intensity * 20;
            const hue  = (i / bufferLength) * 360;
            ctx.fillStyle = `hsla(${hue},100%,50%,${intensity})`;
            ctx.beginPath();
            ctx.arc(x, y + (Math.random()-0.5)*100*intensity, size, 0, Math.PI*2);
            ctx.fill();
        }
    }

    function drawRadialBloom() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const centerX = canvas.width  / 2;
        const centerY = canvas.height / 2;
        for (let i = 0; i < bufferLength; i++) {
            const intensity = dataArray[i] / 255;
            const angle     = (i / bufferLength) * Math.PI * 2;
            const distance  = intensity * 200;
            const x   = centerX + Math.cos(angle) * distance;
            const y   = centerY + Math.sin(angle) * distance;
            const hue = (i / bufferLength) * 360;
            const gradient = ctx.createRadialGradient(x, y, 0, x, y, 30);
            gradient.addColorStop(0, `hsla(${hue},100%,50%,${intensity})`);
            gradient.addColorStop(1, 'transparent');
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(x, y, 30, 0, Math.PI*2);
            ctx.fill();
        }
    }

    // ── DNA Visualization Functions (all intact from v24) ────────────────────
    function drawDNAHelix() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        rotation += 0.02;
        const centerX   = canvas.width / 2;
        const helixWidth= 80;
        const segments  = 50;
        for (let i = 0; i < segments; i++) {
            const y     = (i / segments) * canvas.height;
            const angle = (i / segments) * Math.PI * 4 + rotation;
            const dataIndex = Math.floor((i / segments) * bufferLength);
            const amplitude = (dataArray[dataIndex] / 255) * helixWidth;
            const x1 = centerX + Math.cos(angle) * amplitude;
            ctx.fillStyle  = '#00ffff';
            ctx.shadowBlur = 15;
            ctx.shadowColor= '#00ffff';
            ctx.beginPath(); ctx.arc(x1, y, 4, 0, Math.PI*2); ctx.fill();
            const x2 = centerX + Math.cos(angle + Math.PI) * amplitude;
            ctx.fillStyle  = '#ff00ff';
            ctx.shadowColor= '#ff00ff';
            ctx.beginPath(); ctx.arc(x2, y, 4, 0, Math.PI*2); ctx.fill();
            if (i % 3 === 0) {
                ctx.strokeStyle = `rgba(0,255,136,${amplitude/helixWidth})`;
                ctx.shadowBlur  = 10;
                ctx.shadowColor = '#00ff88';
                ctx.lineWidth   = 2;
                ctx.beginPath(); ctx.moveTo(x1, y); ctx.lineTo(x2, y); ctx.stroke();
            }
        }
        ctx.shadowBlur = 0;
    }

    function drawDNASpiral() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        rotation += 0.01;
        const centerX   = canvas.width / 2;
        const baseRadius= 50;
        for (let i = 0; i < bufferLength; i++) {
            const y         = (i / bufferLength) * canvas.height;
            const intensity = dataArray[i] / 255;
            const angle     = (i / bufferLength) * Math.PI * 6 + rotation;
            const radius    = baseRadius + intensity * 100;
            const x   = centerX + Math.cos(angle) * radius;
            const hue = (i / bufferLength) * 360;
            ctx.fillStyle  = `hsl(${hue},100%,50%)`;
            ctx.shadowBlur = 20;
            ctx.shadowColor= `hsl(${hue},100%,50%)`;
            ctx.beginPath(); ctx.arc(x, y, 3 + intensity*5, 0, Math.PI*2); ctx.fill();
        }
        ctx.shadowBlur = 0;
    }

    function drawDNAStrand() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const segments = Math.min(bufferLength, 100);
        const spacing  = canvas.width / segments;
        for (let i = 0; i < segments; i++) {
            const x         = i * spacing;
            const dataIndex = Math.floor((i / segments) * bufferLength);
            const intensity = dataArray[dataIndex] / 255;
            const y1 = canvas.height*0.3 + Math.sin(i*0.2 + rotation)*50*intensity;
            const y2 = canvas.height*0.7 + Math.sin(i*0.2 + rotation + Math.PI)*50*intensity;
            ctx.fillStyle  = '#00ffff'; ctx.shadowBlur = 15; ctx.shadowColor = '#00ffff';
            ctx.beginPath(); ctx.arc(x, y1, 3+intensity*4, 0, Math.PI*2); ctx.fill();
            ctx.fillStyle  = '#ff00ff'; ctx.shadowColor = '#ff00ff';
            ctx.beginPath(); ctx.arc(x, y2, 3+intensity*4, 0, Math.PI*2); ctx.fill();
            if (i % 2 === 0) {
                ctx.strokeStyle = `rgba(0,255,136,${intensity})`;
                ctx.shadowBlur  = 10; ctx.shadowColor = '#00ff88'; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(x, y1); ctx.lineTo(x, y2); ctx.stroke();
            }
        }
        rotation += 0.05;
        ctx.shadowBlur = 0;
    }

    function drawDNAPulse() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const centerX      = canvas.width  / 2;
        const centerY      = canvas.height / 2;
        const avgIntensity = dataArray.reduce((a,b) => a+b, 0) / bufferLength / 255;
        rotation += 0.03;
        for (let ring = 0; ring < 5; ring++) {
            const baseRadius = 50 + ring * 60;
            const points     = 12;
            for (let i = 0; i < points; i++) {
                const angle     = (i/points)*Math.PI*2 + rotation + ring*0.5;
                const dataIndex = Math.floor((i/points)*bufferLength);
                const intensity = dataArray[dataIndex] / 255;
                const radius    = baseRadius + intensity*40 + avgIntensity*30;
                const x   = centerX + Math.cos(angle)*radius;
                const y   = centerY + Math.sin(angle)*radius;
                const hue = (ring*72 + i*30) % 360;
                ctx.fillStyle  = `hsl(${hue},100%,50%)`;
                ctx.shadowBlur = 20;
                ctx.shadowColor= `hsl(${hue},100%,50%)`;
                ctx.beginPath(); ctx.arc(x, y, 4+intensity*6, 0, Math.PI*2); ctx.fill();
            }
        }
        ctx.shadowBlur = 0;
    }

    function drawDNACircular() {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const centerX  = canvas.width  / 2;
        const centerY  = canvas.height / 2;
        const baseRadius = Math.min(centerX, centerY) - 100;
        rotation += 0.01;
        for (let i = 0; i < bufferLength; i++) {
            const angle     = (i / bufferLength) * Math.PI * 2;
            const intensity = dataArray[i] / 255;
            const r1  = baseRadius - 30;
            const x1  = centerX + Math.cos(angle + rotation) * r1;
            const y1  = centerY + Math.sin(angle + rotation) * r1;
            const r2  = baseRadius + 30 + intensity * 50;
            const x2  = centerX + Math.cos(angle + rotation) * r2;
            const y2  = centerY + Math.sin(angle + rotation) * r2;
            const hue = (i / bufferLength) * 360;
            ctx.fillStyle  = `hsl(${hue},100%,50%)`;
            ctx.shadowBlur = 15;
            ctx.shadowColor= `hsl(${hue},100%,50%)`;
            ctx.beginPath(); ctx.arc(x1, y1, 3, 0, Math.PI*2); ctx.fill();
            ctx.fillStyle  = `hsl(${hue},100%,70%)`;
            ctx.shadowColor= `hsl(${hue},100%,70%)`;
            ctx.beginPath(); ctx.arc(x2, y2, 3+intensity*3, 0, Math.PI*2); ctx.fill();
            if (i % 4 === 0) {
                ctx.strokeStyle = `hsla(${hue},100%,60%,${intensity})`;
                ctx.shadowBlur  = 10; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(x1,y1); ctx.lineTo(x2,y2); ctx.stroke();
            }
        }
        ctx.shadowBlur = 0;
    }

    // ── Main draw ─────────────────────────────────────────────────────────────
    let animationId = null;
    function drawSpectrum() {
        animationId = requestAnimationFrame(drawSpectrum);
        switch(currentStyle) {
            case 'randomBars':   drawRandomBars();   break;
            case 'rainbowBars':  drawRainbowBars();  break;
            case 'circularBars': drawCircularBars(); break;
            case 'waveform':     drawWaveform();     break;
            case 'mirrored':     drawMirroredBars(); break;
            case 'gradient':     drawGradientBars(); break;
            case 'particles':    drawParticles();    break;
            case 'radial':       drawRadialBloom();  break;
            case 'dnaHelix':     drawDNAHelix();     break;
            case 'dnaSpiral':    drawDNASpiral();    break;
            case 'dnaStrand':    drawDNAStrand();    break;
            case 'dnaPulse':     drawDNAPulse();     break;
            case 'dnaCircular':  drawDNACircular();  break;
        }
    }

    // ── Progress bar ─────────────────────────────────────────────────────────
    function updateProgressBar() {
        if (mediaElement.duration) {
            const progress = (mediaElement.currentTime / mediaElement.duration) * 100;
            progressBar.style.width    = `${progress}%`;
            progressHandle.style.left  = `${progress}%`;
        }
    }

    progressBarContainer.addEventListener('click', e => {
        if (!mediaElement.duration) return;
        const rect = progressBarContainer.getBoundingClientRect();
        mediaElement.currentTime = ((e.clientX - rect.left) / rect.width) * mediaElement.duration;
    });

    let isDragging = false;
    progressHandle.addEventListener('mousedown', () => isDragging = true);
    document.addEventListener('mousemove', e => {
        if (isDragging && mediaElement.duration) {
            const rect = progressBarContainer.getBoundingClientRect();
            const x    = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
            mediaElement.currentTime = (x / rect.width) * mediaElement.duration;
        }
    });
    document.addEventListener('mouseup', () => isDragging = false);

    function formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${s.toString().padStart(2,'0')}`;
    }

    mediaElement.addEventListener('timeupdate', () => {
        updateProgressBar();
        timeDisplay.textContent = `${formatTime(mediaElement.currentTime)} / ${formatTime(mediaElement.duration)}`;
    });

    mediaElement.addEventListener('error', error => {
        console.error("Media playback error:", error);
        updatePlaybackBtnText();
    });

    window.addEventListener('resize', resizeCanvas);

    // ── Reactions & Comments (jQuery) ─────────────────────────────────────────
    const STORAGE_KEY_PREFIX = 'suno_reactions_';

    function storageKey(filename)  { return STORAGE_KEY_PREFIX + filename; }

    function loadReactionsForTrack(filename) {
        try {
            const raw = localStorage.getItem(storageKey(filename));
            if (raw) {
                const d = JSON.parse(raw);
                return {
                    love    : typeof d.love === 'number' ? d.love : 0,
                    like    : typeof d.like === 'number' ? d.like : 0,
                    comments: Array.isArray(d.comments)  ? d.comments : []
                };
            }
        } catch(e) {}
        return { love: 0, like: 0, comments: [] };
    }

    function saveReactionsForTrack(filename, data) {
        try { localStorage.setItem(storageKey(filename), JSON.stringify(data)); } catch(e) {}
    }

    function renderOverlay(filename) {
        const data = loadReactionsForTrack(filename);
        $('#loveCount').text(data.love);
        $('#likeCount').text(data.like);
        renderComments(data.comments);
    }

    function renderComments(comments) {
        const $list = $('#overlay-comments-list');
        $list.empty();
        if (comments.length === 0) {
            $list.append('<span class="overlay-no-comments">No comments yet</span>');
        } else {
            comments.forEach(c => $list.append($('<div class="overlay-comment">').text(c)));
            $list.scrollTop($list[0].scrollHeight);
        }
    }

    function currentFilename() {
        if (playlist.length === 0) return null;
        return playlist[currentTrackIndex] || null;
    }

    $('#loveBtn').on('click', function() {
        const f = currentFilename(); if (!f) return;
        const data = loadReactionsForTrack(f);
        data.love++;
        saveReactionsForTrack(f, data);
        $('#loveCount').text(data.love);
        $(this).css('transform','scale(1.4)');
        setTimeout(() => $(this).css('transform',''), 150);
    });

    $('#likeBtn').on('click', function() {
        const f = currentFilename(); if (!f) return;
        const data = loadReactionsForTrack(f);
        data.like++;
        saveReactionsForTrack(f, data);
        $('#likeCount').text(data.like);
        $(this).css('transform','scale(1.4)');
        setTimeout(() => $(this).css('transform',''), 150);
    });

    function submitOverlayComment() {
        const f    = currentFilename(); if (!f) return;
        const text = $('#overlay-comment-input').val().trim(); if (!text) return;
        const data = loadReactionsForTrack(f);
        data.comments.push(text);
        saveReactionsForTrack(f, data);
        $('#overlay-comment-input').val('');
        renderComments(data.comments);
    }

    $('#overlay-comment-submit').on('click', submitOverlayComment);
    $('#overlay-comment-input').on('keyup', e => { if (e.key === 'Enter') submitOverlayComment(); });

    // Wrap loadTrack to also refresh reactions overlay
    const _origLoadTrack = loadTrack;
    loadTrack = function(index) {
        _origLoadTrack(index);
        const f = playlist[index];
        if (f) renderOverlay(f);
    };

    if (playlist.length > 0) renderOverlay(playlist[0]);

    // ── Upload Logic (jQuery AJAX) ────────────────────────────────────────────
    const $uploadPanel   = $('#uploadPanel');
    const $dropZone      = $('#dropZone');
    const $fileInput     = $('#fileInput');
    const $browseBtn     = $('#browseBtn');
    const $uploadStatus  = $('#uploadStatus');
    const $progressWrap  = $('#uploadProgressWrap');
    const $progressBar2  = $('#uploadProgressBar');

    // Open file browser
    $browseBtn.on('click', () => $fileInput.trigger('click'));
    $dropZone.on('click',  () => $fileInput.trigger('click'));

    // Drag-and-drop events
    $uploadPanel.on('dragover dragenter', function(e) {
        e.preventDefault(); e.stopPropagation();
        $(this).addClass('drag-over');
    });
    $uploadPanel.on('dragleave drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        $(this).removeClass('drag-over');
        if (e.type === 'drop') {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length) handleFiles(files);
        }
    });

    // File input change
    $fileInput.on('change', function() {
        if (this.files.length) handleFiles(this.files);
        this.value = ''; // allow re-selecting same file
    });

    function handleFiles(files) {
        Array.from(files).forEach(uploadFile);
    }

    function uploadFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['mp3','mp4'].includes(ext)) {
            showUploadStatus('⚠ Only MP3 / MP4 allowed: ' + file.name, 'err');
            return;
        }

        const formData = new FormData();
        formData.append('mediaUpload', file);

        $progressWrap.show();
        $progressBar2.css('width', '0%');
        showUploadStatus('Uploading ' + file.name + '…', 'ok');

        $.ajax({
            url         : window.location.href,
            type        : 'POST',
            data        : formData,
            processData : false,
            contentType : false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        $progressBar2.css('width', pct + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(resp) {
                $progressWrap.hide();
                if (resp.success) {
                    showUploadStatus('✔ ' + resp.message, 'ok');
                    addToPlaylist(resp.filename);
                } else {
                    showUploadStatus('✘ ' + resp.message, 'err');
                }
            },
            error: function() {
                $progressWrap.hide();
                showUploadStatus('✘ Upload failed — server error', 'err');
            }
        });
    }

    function showUploadStatus(msg, type) {
        $uploadStatus.text(msg).removeClass('ok err').addClass(type);
        if (type === 'ok') setTimeout(() => $uploadStatus.text(''), 5000);
    }

    // Dynamically add newly-uploaded file to playlist (no page reload needed)
    function addToPlaylist(filename) {
        // Avoid duplicates
        if (playlist.includes(filename)) {
            showUploadStatus('ℹ Already in playlist: ' + filename, 'ok');
            return;
        }
        playlist.push(filename);
        playlist.sort();

        // Rebuild playlist UI
        $(playlistItems).empty();
        playlist.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'playlist-item ' + (isVideoFile(file) ? 'video-file' : 'audio-file');
            item.textContent = `${index + 1}. ${file}`;
            item.addEventListener('click', () => loadTrack(index));
            playlistItems.appendChild(item);
        });

        trackCount.textContent = `${playlist.length} files`;
        // Highlight current track
        document.querySelectorAll('.playlist-item').forEach((item, i) => {
            item.classList.toggle('active', i === currentTrackIndex);
        });
        showUploadStatus('✔ Added to playlist: ' + filename, 'ok');
    }

    // ── Initialize ────────────────────────────────────────────────────────────
    initPlaylist();
</script>
</body>
</html>
