# Cookie Consent Banner - WordPress Plugin

Simple WordPress plugin that adds a cookie consent banner with Google Consent Mode v2 support.

## Installation

1. Copy the `cookie-consent-banner` folder to the `/wp-content/plugins/` directory of your WordPress site
2. Activate the plugin through the "Plugins" menu in WordPress admin panel
3. Go to "Settings" → "Cookie Consent" to configure banner texts

## Features

- ✅ Beautiful cookie consent banner
- ✅ Google Consent Mode v2 support
- ✅ Customize all texts through WordPress admin panel
- ✅ Responsive design for mobile devices
- ✅ Ability to select individual cookie types
- ✅ Automatic Google Analytics integration (optional)
- ✅ Automatic updates from GitHub repository

## Configuration

After activating the plugin, go to **Settings → Cookie Consent** to configure:

- Banner title
- Banner text
- All button texts
- Cookie type labels
- Google Analytics ID (optional)

## Plugin Structure

```
cookie-consent-banner/
├── cookie-consent-banner.php  # Main plugin file
├── assets/
│   ├── css/
│   │   └── consent-banner.css  # Banner styles
│   └── js/
│       ├── consent.js          # Main logic
│       └── jquery.cookie.js    # jQuery Cookie plugin
└── README.md                   # This file
```

## Automatic Updates

The plugin supports automatic updates from the GitHub repository. When a new version is released on GitHub, WordPress will automatically notify you in the admin panel. You can update the plugin directly from the WordPress admin panel under **Plugins → Installed Plugins**.

### How it works:

1. The plugin checks GitHub releases API every 12 hours
2. If a new version is found, you'll see a notification in the WordPress admin
3. Click "Update now" to install the latest version
4. The update process is handled automatically by WordPress

**Note:** Make sure your GitHub repository has releases created with version tags (e.g., v1.0.1, v1.1.0).

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- jQuery (automatically included by WordPress)

## License

GPL v2 or later
