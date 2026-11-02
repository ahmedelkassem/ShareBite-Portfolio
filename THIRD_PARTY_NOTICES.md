# Third-party components and attribution

ShareBite uses the following independently developed libraries and map services. This list is based on the **current checked-in source**, not a substitute for every upstream license or a legal review.

| Component | How used | License / attribution | Official source |
| --- | --- | --- | --- |
| PHPMailer 7.1.1 | Bundled PHP classes under `PHPMailer/` for SMTP mail | LGPL 2.1, with the upstream license text copied verbatim to [`PHPMailer/LICENSE`](PHPMailer/LICENSE) | [PHPMailer v7.1.1](https://github.com/PHPMailer/PHPMailer/releases/tag/v7.1.1) and [license](https://github.com/PHPMailer/PHPMailer/blob/v7.1.1/LICENSE) |
| Bootstrap 5.3.3 | Styles and bundled JavaScript loaded via jsDelivr | MIT | [Bootstrap](https://github.com/twbs/bootstrap/blob/v5.3.3/LICENSE) |
| Leaflet 1.9.4 | Interactive maps loaded via unpkg | BSD 2-Clause | [Leaflet](https://github.com/Leaflet/Leaflet/blob/v1.9.4/LICENSE) |
| Chart.js | Dashboard charts loaded through an **unversioned** jsDelivr URL | MIT | [Chart.js](https://github.com/chartjs/Chart.js/blob/master/LICENSE.md) |
| Font Awesome Free 6.5.2 | Icon fonts and CSS via cdnjs | Free icons: CC BY 4.0, fonts: SIL OFL 1.1, code: MIT; preserve required attribution | [Font Awesome Free license](https://fontawesome.com/license/free) |
| OpenStreetMap | Map tiles and geographic data | Attribution and tile usage requirements vary by use; keep map attribution and review tile service policy before public hosting | [OSM copyright](https://www.openstreetmap.org/copyright) and [tile policy](https://operations.osmfoundation.org/policies/tiles/) |

## Distribution notes

- The included `PHPMailer/` directory contains third-party source, not code written for ShareBite. Preserve its copyright notices, license and upstream attribution.
- Other dependencies are linked via CDN rather than committed as local source files. Their license and attribution requirements still apply.
- For later live hosting, pin the Chart.js version, consider integrity/cross-origin protections for CDN assets, and verify third-party advisories and acceptable-use rules.
- This document is for portfolio transparency. It does **not** grant a license to ShareBite's original application code, which is a separate ownership decision.
