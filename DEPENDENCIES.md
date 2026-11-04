# Dependencies

The SMS-gateway SDKs are managed with **Composer** instead of the old vendored `libraries/` tree.

## Pulling the libraries

```bash
composer install
```

Creates `vendor/` (**gitignored**). Each gateway loads `vendor/autoload.php` in its
`load_library()` / `init_api()`.

- **Source of truth:** `composer.json` + `composer.lock`.
- **PHP:** 8.1+ (CI matrix targets **8.2 and 8.4**).

## What's managed here

| Library | Replaces (removed) | Notes |
|---------|--------------------|-------|
| `twilio/sdk` ^6.44 | `libraries/twilio` 6.9.2 | Same `Twilio\Rest\Client` API. |
| `alibabacloud/sdk` ^1.8 | `libraries/alibaba` | Same `AlibabaCloud\Client` API. |
| `bandwidth/sdk` ^13 | `libraries/bandwidth` 2.14 | Same `BandwidthLib` API. |

The whole vendored `libraries/` tree was removed.
