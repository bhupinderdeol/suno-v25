# Design Document — Suno v25

## 📌 Overview

Suno v25 is a lightweight, self-contained media streaming application built using PHP, JavaScript, HTML, and CSS. The system dynamically loads and plays media files (MP3 and MP4) from the server directory while providing real-time audio visualization and interactive user features.

The primary design goal was to create a **fully functional streaming-like platform without using frameworks or databases**, emphasizing simplicity, portability, and performance.

---

## 🏗️ System Architecture

The application follows a **hybrid architecture** combining server-side rendering with client-side interactivity:

### 1. Backend (PHP)

* Handles file scanning using `glob()` to detect media files
* Processes file uploads via `$_FILES`
* Performs validation:

  * File type (MP3, MP4)
  * File size limit (512 MB)
  * Filename sanitization
* Outputs media list as JSON to the frontend

### 2. Frontend (JavaScript)

* Controls playback logic using the HTML5 `<video>` element
* Implements real-time visualization via Web Audio API
* Manages UI state, playlist navigation, and interactions
* Handles asynchronous uploads using AJAX (jQuery)

### 3. UI Layer (HTML/CSS)

* Responsive layout using CSS Grid
* Custom-styled controls instead of default browser UI
* Neon-themed visual design for enhanced user experience

---

## 🔁 Data Flow

1. PHP scans directory → generates media list
2. Media list is passed to JavaScript as JSON
3. User selects a file → media is loaded into `<video>` element
4. Audio stream is routed into `AnalyserNode`
5. Frequency/time-domain data is rendered to Canvas
6. User interactions (play, seek, upload) update UI dynamically

---

## 🎛️ Core Components

### Media Loader

* Uses `glob()` to retrieve all `.mp3` and `.mp4` files
* Sorted alphabetically for predictable playback order
* Eliminates need for database indexing

### Media Player

* Single `<video>` element handles both audio and video
* Simplifies architecture by avoiding separate players
* Dynamically toggles video visibility for audio-only files

### Audio Processing

* Implemented using Web Audio API:

  * `AudioContext`
  * `AnalyserNode`
* Configurable FFT size (32–4096)
* Generates frequency and waveform data in real time

### Visualization Engine

* Canvas-based rendering at ~60 FPS
* 13 visualization modes:

  * Classic (bars, waveform, particles, etc.)
  * DNA-inspired animations
* Modular design using switch-case for easy extension

### Upload System

* AJAX-based upload with progress tracking
* Immediate UI update without page reload
* Server-side validation ensures security and stability

### Reactions & Comments

* Stored in `localStorage` per media file
* Avoids backend complexity while maintaining persistence
* Enables lightweight user interaction

---

## 🎨 Design Decisions

### 1. No Database

**Reason:**

* Simplifies deployment
* Eliminates configuration overhead

**Trade-off:**

* No multi-user or cross-device data persistence

---

### 2. Single File Architecture (`suno_v25.php`)

**Reason:**

* Maximum portability
* Easy to deploy and distribute

**Trade-off:**

* Reduced modularity compared to multi-file systems

---

### 3. Web Audio API for Visualization

**Reason:**

* Native browser support
* Real-time processing capability

**Trade-off:**

* Performance varies across devices

---

### 4. LocalStorage for User Data

**Reason:**

* No backend required
* Instant read/write performance

**Trade-off:**

* Data is browser-specific and not shared

---

### 5. HTML5 Video Element for All Media

**Reason:**

* Unified playback system
* Simplifies synchronization with audio analysis

**Trade-off:**

* Limited customization compared to custom media engines

---

## ⚙️ Performance Considerations

* FFT size directly impacts CPU usage
* Canvas rendering optimized using `requestAnimationFrame`
* Minimal DOM manipulation for smoother UI updates
* Media files loaded on demand (not preloaded)

---

## ⚠️ Challenges Encountered

### Real-Time Synchronization

Ensuring that audio visualization matches playback timing required precise integration between the media element and the `AnalyserNode`.

### Cross-Device Compatibility

Different browsers handle audio contexts and autoplay restrictions differently, requiring conditional handling.

### UI Responsiveness

Balancing visual complexity (animations) with performance was critical, especially on lower-end devices.

---

## 🚀 Future Improvements

* User authentication system with persistent accounts
* Cloud-based media streaming
* Database integration (MySQL or NoSQL)
* AI-based playlist generation
* Advanced analytics (play counts, trends)

---

## 🧠 Conclusion

Suno v25 demonstrates that a modern, interactive media streaming experience can be achieved using core web technologies without relying on external frameworks or databases. The design prioritizes simplicity, efficiency, and real-time interactivity while maintaining flexibility for future expansion.
