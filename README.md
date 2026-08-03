# TrucksBook Badge Generator

A simple, zero-database PHP badge generator for TrucksBook profiles. It creates an auto-updating PNG signature with your live ETS2 lifetime stats (distance, deliveries, country, and username) that you can easily use on forums and websites.

## Why this exists
Since TrucksBook keeps its API completely closed to third-party developers, this script directly scrapes and parses your real-time stats from the TrucksBook frontend to generate the badge on the fly.

## Usage

You don't need to host it yourself. You can just use my public generator. 

Copy the URL below and replace `YOUR_ID_HERE` with your actual TrucksBook profile ID:

```text
https://thurstan.p-host.in/badge.php?id=YOUR_ID_HERE
```

### Example
For profile ID `567363`, the URL looks like this:
`https://thurstan.p-host.in/badge.php?id=567363`

**Result:**  
![TrucksBook Badge Example](https://thurstan.p-host.in/badge.php?id=567363)

## Self-Hosting

Want to run it on your own server? It's easy:
1. Upload `badge.php` and a `font.ttf` file to any web server that supports PHP.
2. Make sure the **cURL** and **GD** PHP extensions are enabled.
3. That's it! No database or complex config is needed.