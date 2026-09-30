# TrucksBook Unofficial API & Banner Generator

A zero-database PHP script that serves as both a dynamic banner generator and an unofficial JSON API for [TrucksBook](https://trucksbook.eu/) profiles. Banners are perfect for showing off your current trucking progress on forum signatures, personal websites, or VTC pages without ever needing to update them manually. The script parses live ETS2 lifetime stats, frequent deliveries, company details, awards, and news feeds directly from the frontend.

## How it works & Why this exists
Since [TrucksBook](https://trucksbook.eu/) keeps its API completely closed to third-party developers, this script acts like a regular web browser when you request a banner or JSON data. It silently visits a public profile page, downloads the raw HTML code, and uses regular expressions to extract real-time stats. After pulling the data, it either draws those stats onto a sleek PNG image on the fly or packages them neatly into a JSON response for your own apps.

## 🖼️ Banner Usage

You don't need to host it yourself. You can just use my public generator for forums and websites. 

Copy the URL below and replace `YOUR_ID_HERE` with your actual TrucksBook profile ID:

```text
https://thurstan.p-host.in/badge.php?id=YOUR_ID_HERE
```

### Banner Example
For [profile ID `567363`](https://trucksbook.eu/profile/567363), the URL looks like this:
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
When requested, the API returns a structured JSON object. 

**JSON Response Structure:**
```json
{
  "user": {
    "id": "123456",
    "username": "DriverName",
    "country": "CountryName",
    "avatar_url": "https://trucksbook.eu/data/...",
    "flag_url": "https://trucksbook.eu/data/...",
    "company": "Company Name or Loner",
    "followers": 15,
    "following": 5,
    "is_premium": true
  },
  "lifetime_stats": {
    "distance_km": 150000,
    "deliveries": 320
  },
  "frequent_deliveries": {
    "job_type": "Standard cargo",
    "from": {
      "city": "Departure City",
      "flag_url": "https://trucksbook.eu/data/..."
    },
    "to": {
      "city": "Destination City",
      "flag_url": "https://trucksbook.eu/data/..."
    },
    "sender_company_image": "https://trucksbook.eu/data/...",
    "receiver_company_image": "https://trucksbook.eu/data/...",
    "cargo": "Cargo Name",
    "parking_problems": "Easy parking",
    "truck": {
      "model": "Truck Model",
      "brand_image": "https://trucksbook.eu/components/..."
    }
  },
  "awards": [
    {
      "title": "Award Title",
      "description": "Award description text.",
      "game_icon": "https://trucksbook.eu/data/...",
      "trophy_color": "gold"
    }
  ],
  "news_feed": [
    {
      "event": "Company joined",
      "date": "2026-01-01T12:00:00+02:00",
      "company_name": "Company Name",
      "status_change": "Dismissed / Left"
    }
  ]
}
```

### Data Dictionary

| Object / Key | Type | Description |
| :--- | :--- | :--- |
| **`user`** | `Object` | Basic profile information. |
| - `id` | `String` | The unique TrucksBook profile ID. |
| - `username` | `String` | Display name of the driver. |
| - `country` | `String` | The driver's country name. |
| - `avatar_url` | `String` | URL to the user's profile picture. |
| - `flag_url` | `String` | URL to the country flag image. |
| - `company` | `String` | Current company name, role, or "Loner". |
| - `followers` / `following` | `Integer` | Social statistics. |
| - `is_premium` | `Boolean` | Returns `true` if the user has an active premium subscription. |
| **`lifetime_stats`** | `Object` | Overall ETS2 statistics. |
| - `distance_km` | `Integer` | Total distance driven (numeric value only). |
| - `deliveries` | `Integer` | Total completed deliveries. |
| **`frequent_deliveries`**| `Object` | Data from the "Most frequent deliveries" tab. |
| - `job_type` | `String` | Type of the most frequent job (e.g., Standard cargo). |
| - `from` / `to` | `Object` | Contains city name (`String`) and country flag URL (`String`). |
| - `sender_company_image` | `String` | URL to the sender company logo. |
| - `receiver_company_image`| `String` | URL to the receiver company logo. |
| - `cargo` | `String` | Most frequently transported cargo. |
| - `parking_problems` | `String` | Parking difficulty preference. |
| - `truck` | `Object` | Contains truck model name (`String`) and brand logo URL (`String`). |
| **`awards`** | `Array` | List of earned trophies (Objects). Contains `title`, `description`, `game_icon`, and `trophy_color` (e.g., gold, silver, bronze). |
| **`news_feed`** | `Array` | Timeline of recent events (Objects). Contains the `event` type, ISO `date`, `company_name`, and `status_change`. |

**JSON Response Example:**  
<img width="649" height="877" alt="image" src="https://github.com/user-attachments/assets/13c3423d-29b4-405b-bcb0-d26545e1b271" />

---

## Self-Hosting

Want to run it on your own server? It's easy:
1. Upload `badge.php` and the `font.ttf` file to any web server that supports PHP.
2. Make sure the **cURL** and **GD** PHP extensions are enabled.
3. That's it! No database or complex config is needed.

---

<p align="center">
  <sub><em><strong>Disclaimer:</strong> This project is completely unofficial and is not affiliated with, endorsed by, or connected to TrucksBook. The script only makes basic, read-only HTTP requests to public profile pages, mimicking normal web browser behavior, and causes absolutely no harm or heavy load to TrucksBook servers.</em></sub>
</p>
