# Performance & Accessibility Summary (2025-10-27)

## TTFB Measurements (curl)
- `/` cold 0.047s · warm 0.005s
- `/kittens` cold 0.019s · warm 0.004s
- `/guides` cold 0.033s · warm 0.004s
- `/achievements` cold 0.020s · warm 0.004s
- `/contact` cold 0.018s · warm 0.003s

## Synthetic Mobile Paint Metrics (Puppeteer)
- `/` FCP 0.130s · LCP 0.130s
- `/kittens/` FCP 0.144s · LCP 0.144s
- `/guides/` FCP 0.109s · LCP 0.109s
- `/achievements/` FCP 0.109s · LCP 0.109s
- `/contact/` FCP 0.133s · LCP 0.133s

> Note: Direct Lighthouse CLI execution was attempted via `npx lighthouse` but blocked by the environment's lack of external npm registry access (ENOTFOUND registry.npmjs.org). The metrics above were gathered with headless Chromium to validate paint timings; all values are comfortably below the requested thresholds. A rerun of Lighthouse should be performed once network egress is available.
