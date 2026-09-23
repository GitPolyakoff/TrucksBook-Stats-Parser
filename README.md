# TrucksBook Unofficial API & Banner Generator

A zero-database PHP script that serves as both a dynamic banner generator and an unofficial JSON API for TrucksBook profiles. It parses live ETS2/ATS lifetime stats, frequent deliveries, company details, awards, and news feeds directly from the frontend.

## Why this exists
Since TrucksBook keeps its API completely closed to third-party developers, this script directly scrapes and parses your real-time stats from the TrucksBook frontend to generate banners on the fly or return structured JSON data for your own applications.

## 🖼️ Banner Usage (Dynamic Signature)

You don't need to host it yourself. You can just use my public generator for forums and websites. 

Copy the URL below and replace `YOUR_ID_HERE` with your actual TrucksBook profile ID:

```text
https://thurstan.p-host.in/badge.php?id=YOUR_ID_HERE
```

### Banner Example
For profile ID `567363`, the URL looks like this:
`https://thurstan.p-host.in/badge.php?id=567363`

**Result:**  
![TrucksBook Banner Example](https://thurstan.p-host.in/badge.php?id=567363)

**How it looks embedded on an external website/forum:**  
<img width="779" height="393" alt="image" src="https://github.com/user-attachments/assets/1c11b244-41c1-49dd-8863-0050b46cc3d9" />

---

## 💻 Unofficial JSON API Usage

You can use this script as a REST-like API to fetch profile data for your own projects, Discord bots, or websites. Simply add the `&format=json` parameter to the URL.

**Endpoint:**
```text
https://thurstan.p-host.in/badge.php?id=YOUR_ID_HERE&format=json
```

### Available Data in JSON
When requested, the API returns a structured JSON object containing:
*   **User Info:** ID, username, country, avatar URL, premium status, followers, following, and current company role.
*   **Lifetime Stats:** Total distance (km) and deliveries.
*   **Frequent Deliveries:** Job type, departure/destination cities and flags, sender/receiver company images, cargo, parking difficulty, and truck model.
*   **Awards:** List of earned trophies, including descriptions and trophy colors (gold, silver, bronze).
*   **News Feed:** Timeline of recent profile events (company joined/left, account creation dates, etc.).

**JSON Response Example:**  
<img width="649" height="877" alt="image" src="https://github.com/user-attachments/assets/13c3423d-29b4-405b-bcb0-d26545e1b271" />

---

## Self-Hosting

Want to run it on your own server? It's easy:
1. Upload `badge.php` and the `font.ttf` file to any web server that supports PHP.
2. Make sure the **cURL** and **GD** PHP extensions are enabled.
3. That's it! No database or complex config is needed.
