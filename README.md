# Cloudflare Image Transforms Plugin for Craft

This plugin integrates Cloudflare's image transformation capabilities into Craft CMS, allowing you to offload the heavy lifting of creating and managing image transformations to Cloudflare. By leveraging Cloudflare's efficient image processing, you can significantly reduce server load and improve performance compared to handling transformations with PHP.

## Prerequisites

Before using this plugin, ensure you meet the following prerequisites:

- Have a Cloudflare account with image transformations enabled. You can enable this feature through the Cloudflare dashboard. Refer to the [Cloudflare documentation](https://developers.cloudflare.com/images/transform-images/) for more information.
- Images must be hosted on a domain with Cloudflare acting as a CDN. This can be achieved through various setups, such as using an S3 volume with a custom public Cloudflare R2 domain or having Cloudflare in front of your entire domain.

## Installation

You can install the plugin via Composer and Craft CLI:

```bash
composer require lenvanessen/cloudflare-image-transforms
php craft plugin/install cloudflare-image-transforms
```

## Configuration
After installation, follow these steps to configure the plugin:

1. Enable Cloudflare Transformations: Ensure that Cloudflare image transformations are enabled for your Cloudflare account. Refer to the Cloudflare documentation for guidance on enabling transformations.
2. Enable Transformations for Your Domain: In your Cloudflare dashboard, navigate to Images > Transformations and enable transformations for your specific domain.
3. Create an API Key: Create a new API key in your Cloudflare account that has the necessary permissions to purge your Cloudflare cache.
4. Configure Settings: In the Craft CMS control panel, go to Settings > Plugins > Cloudflare Image Transforms. Enter your Cloudflare Zone ID and API key in the provided fields.

## Credits
This plugin was inspired by and includes code from Pixel & Tonic's implementation for Craft Cloud. Many thanks to them for their contribution and inspiration.
