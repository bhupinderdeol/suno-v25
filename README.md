# Suno v25 — DNA Spectrum Media Player

**CS50x Final Project**
**Student:** Bhupinder Deol
**GitHub Username:** bhupinderdeol
**edX Username:** bhupinderdeol
**Location:** Mississauga, Ontario, Canada

---

## 🎥 Video Demo

https://youtu.be/wOIE6vIw8XA
---

## 📌 Overview

**Suno v25** is a self-contained, full-stack media streaming application built using a single PHP file. It supports playback of MP3 audio and MP4 video files, real-time audio visualization, dynamic file uploads, and interactive user engagement features such as reactions and comments.

The application is designed to demonstrate how a modern streaming-like experience can be implemented **without frameworks or databases**, relying instead on core web technologies: PHP, JavaScript, HTML5, and CSS.

---

## 🎯 Objectives

The primary goals of this project were:

* Build a **fully functional media player** using minimal dependencies
* Implement **real-time audio visualization** using the Web Audio API
* Create a **dynamic, file-based media system** without a database
* Provide an **interactive user interface** with modern UX features
* Maintain **portability and simplicity** for deployment

---

## 🧩 Key Features

### 🎵 Media Playback

* Supports **MP3 (audio)** and **MP4 (video)** formats
* Automatically detects media type and adjusts UI
* Video playback with **overlay spectrum visualization**
* Playback controls:

  * Play / Pause
  * Seek (±10 seconds)
  * Volume & mute
  * Playback speed (0.5× to 2×)
* Loop and shuffle functionality
* Interactive progress bar with real-time time display

---

### 🎛️ Audio Spectrum Visualizer

The system uses the **Web Audio API (`AnalyserNode`)** and HTML5 Canvas to render real-time visualizations.

Includes **13 visualization modes**:

* Classic: Bars, Waveforms, Gradient, Particles, Radial
* DNA-inspired: Pulse, Helix, Spiral, Strand, Circular

Additional features:

* Adjustable **FFT size (32–4096)**
* Smooth animation using `requestAnimationFrame`
* Dynamic color and motion effects

---

### 📂 Media Library & Upload System

* PHP automatically scans the directory for media files
* No manual configuration required
* Drag-and-drop upload support
* Live upload progress feedback
* Server-side validation:

  * File type restriction (MP3/MP4)
  * File size limit (512 MB)
  * Filename sanitization
* Newly uploaded files appear instantly in the playlist

---

### ❤️ Reactions & Comments

* Per-track engagement system using **localStorage**
* Features:

  * ❤️ Love counter
  * 👍 Like counter
  * Comment input with Enter-to-submit
* Data persists per file without requiring a backend database

---

### 📱 Responsive Design

* Desktop: two-column layout (playlist + player)
* Mobile: stacked layout for smaller screens
* Optimized for modern browsers

---

## 🏗️ Project Structure

```
suno/
├── suno_v25.php      # Complete application (PHP + HTML + CSS + JS)
├── README.md         # Project documentation
├── DESIGN.md         # Technical design explanation
├── sample.mp3        # Sample media file
├── sample.mp4        # Sample media file
```

---

## ⚙️ Installation & Usage

### Requirements

* Apache2 with PHP 8.x
* Modern browser (Chrome, Firefox, Edge)

### Setup

```bash
sudo cp suno_v25.php /var/www/html/suno/
sudo chown -R www-data:www-data /var/www/html/suno/
```

Place media files in the same directory.

Open in browser:

```
http://localhost/suno/suno_v25.php
```

---

## 🧠 Design Philosophy

This project intentionally avoids using frameworks or databases to emphasize:

* **Simplicity** — minimal setup and dependencies
* **Portability** — runs on any standard PHP server
* **Efficiency** — direct file system access instead of queries
* **Frontend-driven interactivity** — leveraging JavaScript for responsiveness

---

## ⚠️ Challenges

* Synchronizing real-time visualization with audio playback
* Handling both audio and video modes dynamically
* Maintaining performance across different FFT sizes
* Designing an intuitive UI without external libraries

---

## 🚀 Future Improvements

* User authentication and saved playlists
* Cloud-based media streaming
* AI-based playlist recommendations
* Database integration for scalability

---

## 🙏 Acknowledgements

* Harvard CS50x
* Web Audio API (MDN)
* HTML5 Canvas API
* jQuery for AJAX and DOM handling

---

## 📎 Final Notes

Suno v25 demonstrates how a complete multimedia platform can be built using core web technologies. The project combines backend scripting, frontend interactivity, and real-time processing into a single cohesive system.
