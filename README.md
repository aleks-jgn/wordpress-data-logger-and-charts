# Data Logger & Charts

A lightweight, flexible WordPress plugin that records token holder data (or any custom metric) and displays it beautifully with an adaptive Chart.js line chart. Perfect for landing pages and analytics..

![WordPress Plugin Version](https://img.shields.io/badge/plugin-v1.5-blue)
![WordPress Tested](https://img.shields.io/badge/tested-6.5%2B-brightgreen)
![License](https://img.shields.io/badge/license-AGPL--3.0-green)

---

## ✨ DEMO

[Plugin Demo URL](https://alex555764-wordpress-cvqrq.tw1.ru/)

---

## ✨ Features

- **Two Data Sources** – Automatic daily API fetching or manual JSON file.
- **Fully Responsive Chart** – Width always 100% of the parent container, height adjustable via settings.
- **Shortcode Ready** – Place `[crypto_chart]` anywhere on your site.
- **Chart.js Powered** – Uses the free, open‑source [Chart.js](https://www.chartjs.org/) library for smooth, interactive graphs.
- **REST API Endpoint** – Built‑in endpoint returns your data in JSON for external use.
- **Admin Settings Page** – Intuitive UI for configuration, no coding needed.
- **Modern, Secure Code** – Follows WordPress coding standards, all data escapes and sanitizes properly.
- **Actively Maintained** – Developer continues to improve and update the plugin.

---

## 🚀 How It Works

1. **Automatic Mode**  
   The plugin schedules a daily cron job to call an external API and store the `holders_count` value into `wp-content/uploads/cdlc-data.json`.  
   The chart always displays the **last 7 data points**.

2. **Manual Mode**  
   You switch to “Manual” in the settings and provide your own `wp-content/uploads/cdlc-data-custom.json` file.  
   The plugin reads this file and renders the chart – perfect when you have existing data or want full control.

---

## 📦 Installation

1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress plugin uploader.
2. Activate it – two empty JSON files are created automatically in the uploads folder.
3. Visit **Settings → Crypto Data Logger** to choose your data source and adjust the chart height.

---

## 🛠 Usage

### Shortcode

Insert the chart anywhere in your posts, pages, or widget areas:  
```
[crypto_chart]
```

**In PHP templates:**

```php
<?php echo do_shortcode('[crypto_chart]'); ?>
```
The shortcode loads Chart.js and the custom script only when needed.  

---

## ⚙️ Settings Page

- **Data Source Mode** – Choose between *Automatic* (API) and *Manual* (custom JSON).
- **API URL** – The endpoint returning token data (used only in automatic mode).
- **Chart Height (px)** – Adjust the height; width is always 100% responsive.

---

## 📊 Data Format (Manual Mode)

If you use the Manual mode, your `cdlc-data-custom.json` must be a valid JSON array:

```json
[
  {"date":"2025-01-01","value":1024},
  {"date":"2025-01-02","value":1056},
  ...
]
```

---

## 🌐 REST API

The plugin exposes a public endpoint to retrieve the current data history:
```
GET /wp-json/token-data/v1/history
```
The response is the full array from either `cdlc-data.json` or `cdlc-data-custom.json`, depending on the selected mode.

---

## 📚 Dependencies

- [Chart.js](https://www.chartjs.org/) (loaded from CDN) – a free, open‑source JavaScript charting library.
- WordPress Cron API for automatic daily fetches.

---

## 🔧 Scalability & Customization

This plugin is intentionally built to be **scalable and easy to adapt**:

- Replace the API endpoint with any other data source.
- Change the metric from `holders_count` to any field – just a small code tweak.
- Modify the chart appearance by editing `customized-chart.js` (Chart.js configuration is fully exposed).
- Add more shortcodes or different chart types using the same data layer.

If you need a custom feature or integration, feel free to reach out or fork the project.

---

## 👨‍💻 Support & Maintenance

The plugin is **actively maintained** by [aleks-jgn](https://github.com/aleks-jgn).  
It is tested with the latest WordPress release to ensure compatibility.

Found a bug or have an idea? Open an issue on GitHub – contributions are welcome.
















