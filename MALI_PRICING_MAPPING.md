# Mali Pricing Board Mapping

**Source:** `canvas_flow-chart-for-price-calculator-260805_1904.pdf`, dated 5 August 2026.
**Blueprint version:** `2026.08.05`
**Plugin version:** `1.4.0`

This document records what was taken directly from the board and what required a conservative implementation decision. Undefined prices are never guessed.

## Customer structure

The board says customers may choose one or more of:

- Web & Accessibility
- Tech & Support
- Business Support & Marketing

The plugin therefore starts with a multi-select route screen and combines every selected route into one review. Contact details are collected only after pricing and tailored-quote outcomes are shown.

## Web & Accessibility

| Service | Board rule | Implemented outcome |
|---|---:|---|
| Base platform | £500 | One-off base on new website builds |
| Small/basic development | £450 per page | `£500 + pages × £450` |
| Bespoke design | £300 per page | Combined with bespoke development |
| Bespoke development | £600 per page | `£500 + pages × (£300 + £600)` |
| Existing-site improvements | No formula supplied | Tailored quote, with URL and requested changes collected |
| WooCommerce | £750 | One-off add-on for new builds |
| Hosting only | £30/month | Monthly |
| Hosting + plugins | £45/month | Monthly |
| Hosting + 1 hour editing | £95/month | Monthly |
| Hosting + 4 hours editing | £245/month | Monthly |
| Alt text, up to 25 images | £100 | One-off |
| Alt text, 26 to 50 images | £200 | One-off |
| Alt text, 51 to 75 images | £300 | One-off |
| Alt text, 76+ images | Manual review | Tailored quote |
| Accessibility testing | £60 | One-off |
| Accessibility setup | £500 | One-off |

The board does not define what the £60 accessibility test covers. The calculator preserves the amount but the service scope must be confirmed in Inkfire's sales terms.

## Tech & Support

| Service | Board rule | Implemented outcome |
|---|---:|---|
| Ongoing IT support | £50 per user/month | Users × £50 monthly |
| Device management | £35 per device/month | Devices × £35 monthly |
| Dark web monitoring | £15 per domain/month | Domains × £15 monthly |
| A few hours of IT consulting | £120/hour | Hours × £120 one-off |
| Small IT project | “Starts from £X” but X is absent | Tailored quote, no invented amount |
| Larger IT project | Discovery recommended | Tailored quote |
| Windows configuration | £1,800 | One-off per selected platform |
| iOS configuration | £1,800 | One-off per selected platform |
| Android configuration | £1,800 | One-off per selected platform |
| Intune management | £25 per device/month | Devices × £25 monthly |
| Cyber Essentials | Scope varies | Tailored quote |
| Accessibility-focused tech support | £120/hour | Hours × £120 one-off |

Microsoft 365, email management, cloud backup, security, remote support, and accessibility support are collected as scope indicators within ongoing IT support. The board does not assign separate prices to those checkboxes, so the plugin does not add any.

## Business Support & Marketing

### Branding

| Service | Board rule | Implemented outcome |
|---|---:|---|
| Mini branding package | £600 | One-off |
| Major branding package | £3,000 | One-off |
| Brand guidelines, social templates, business cards, presentation templates, email signatures | Prices variable | Tailored quote when selected |
| Not sure | Show both options | Both packages are explained; customer chooses or requests tailored help |

### Social media content

| Content type | Board rule | Implemented outcome |
|---|---:|---|
| Static post | £50 per post | Monthly quantity × £50 |
| Carousel post | £100 per post | Monthly quantity × £100 |
| Short-form video | £150 per post | Monthly quantity × £150 |
| Story graphics | No standalone rate | Tailored quote |
| Mixed content | No safe mix formula | Tailored quote |

Board volume guidance is implemented as:

| Customer answer | Implemented quantity |
|---|---:|
| A few posts each month | 4 posts |
| Weekly content | 8 posts |
| Multiple posts per week | 12 to 16 posts, shown as a range |
| Daily content | Tailored quote |
| Not sure yet | Tailored quote |

Graphics, captions, planning, scheduling, community management, analytics, ad management, and photography/content days are collected as scope. They do not add an automatic charge unless the board supplied a separate price elsewhere.

### Written content

| Service | Board rule | Implemented outcome |
|---|---:|---|
| Blog post | £150 per post/month | 1, 2, or 4 posts calculated monthly |
| 8+ blog posts | Discovery recommended | Tailored quote |
| Newsletter | £150 each/month | 1, 2, or 4 calculated monthly |
| Weekly newsletters | £150 each | Implemented as 4 to 5 per month, shown as a range |
| Website copy | £100/hour | 1–3, 4–8, or 8–16 hour ranges |
| Website copy, 16+ hours | Scope too broad | Tailored quote |
| Editing, accessible rewriting, product descriptions, general content | No separate formula | Customer scope is collected and routed conservatively |

The 4-to-5 interpretation for weekly newsletters reflects calendar months. Inkfire should confirm whether commercial packages should standardise on four issues.

### Executive and administrative support

| Service | Board rule | Implemented outcome |
|---|---:|---|
| Executive Assistant | £60/hour | Selected monthly hours × £60 |
| Personal Assistant | £80/hour | Selected monthly hours × £80 |
| Virtual Assistant | £50/hour | Selected monthly hours × £50 |
| Customer Service | £60/hour | Selected monthly hours × £60 |
| Access to Work funding | Contact for quote | Tailored quote, no automatic total |

The board offers 5, 10, 20, or 40+ hours per month. Forty or more is treated as tailored because the upper quantity is not defined.

### Strategy and consultancy

| Service | Board rule | Implemented outcome |
|---|---:|---|
| Strategy, mentoring, accessibility consultancy, digital transformation, systems/process improvement, growth strategy, training/workshops | £120/hour shown nearby | Planning hours × £120 |
| One-off consultancy or workshop | Delivery choice | One-off total |
| Monthly consultancy or ongoing advisory | Delivery choice | Monthly total |

The source layout places £120/hour alongside these services but does not define a fixed package. The calculator therefore asks for planning hours and labels the result as an estimate.

### Marketing and production

| Service | Board rule | Implemented outcome |
|---|---:|---|
| Graphic design | Starting at £75/hour | Planning hours × £75, clearly an estimate |
| UGC day at client site | £500/day, travel excluded | Days × £500 one-off |
| UGC day using Inkfire-provided venue | £2,500/day | Days × £2,500 one-off |
| Website hosting | £45/month | Monthly |
| Paid ads, illustration, print and packaging, or unclear marketing scope | No complete formula | Tailored quote |

The standalone £45 hosting line is retained for Business/Marketing enquiries. The Web route separately offers £30 hosting-only and £45 hosting-plus-plugins options.

## Global output

The final review shows:

- one-off estimate
- monthly estimate
- ranges where the source gives a quantity band
- tailored-quote items
- excluding-VAT note
- planning-estimate disclaimer

The final contact fields are name, business name, email, optional phone, optional website, optional notes, and privacy confirmation.

## Decisions Inkfire should confirm commercially

The code is safe without these decisions because affected items use tailored quotes or transparent ranges. Inkfire should still confirm:

1. The exact scope of the £60 accessibility test.
2. The missing starting amount for a small IT project.
3. Whether weekly newsletters are sold as four or actual-calendar frequency.
4. Whether story graphics need their own price.
5. Whether mixed social content should use a package matrix.
6. Prices for branding extras.
7. Whether graphic design should remain an hourly planning estimate or become packages.
8. Whether strategy work is always £120/hour across every listed delivery type.
9. Travel and expense rules for client-site UGC days.
10. The boundary between £45 standalone hosting and £45 Web hosting plus plugins.
