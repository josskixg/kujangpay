---
version: alpha
name: KujangPay Linear Dark
description: Precision fintech & self-hosted QRIS gateway design system modeled on Linear dark-mode luminance stepping.
colors:
  primary: "#F7F8F8"
  secondary: "#D0D6E0"
  tertiary: "#5E6AD2"
  neutral: "#08090A"
  surface: "#0F1011"
  elevated: "#191A1B"
  emerald: "#10B981"
  muted: "#8A8F98"
typography:
  h1:
    fontFamily: Inter
    fontSize: 2rem
    fontWeight: 510
    lineHeight: 1.13
    letterSpacing: "-0.022em"
    fontFeature: "'cv01', 'ss03'"
  h2:
    fontFamily: Inter
    fontSize: 1.25rem
    fontWeight: 590
    lineHeight: 1.33
    letterSpacing: "-0.012em"
    fontFeature: "'cv01', 'ss03'"
  body-md:
    fontFamily: Inter
    fontSize: 0.9375rem
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "-0.011em"
    fontFeature: "'cv01', 'ss03'"
  mono-sm:
    fontFamily: JetBrains Mono
    fontSize: 0.8125rem
    fontWeight: 400
    lineHeight: 1.5
rounded:
  sm: 4px
  md: 6px
  lg: 8px
  xl: 12px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
components:
  button-primary:
    backgroundColor: "{colors.tertiary}"
    textColor: "{colors.primary}"
    rounded: "{rounded.md}"
    padding: 10px
    typography: "{typography.body-md}"
  button-primary-hover:
    backgroundColor: "{colors.elevated}"
    textColor: "{colors.primary}"
  card-surface:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.secondary}"
    rounded: "{rounded.lg}"
    padding: 16px
  badge-emerald:
    backgroundColor: "{colors.neutral}"
    textColor: "{colors.emerald}"
    rounded: "{rounded.sm}"
    padding: 4px
  caption-muted:
    backgroundColor: "{colors.neutral}"
    textColor: "{colors.muted}"
    typography: "{typography.mono-sm}"
---

## Overview

KujangPay uses a **Monitor & Operate** dual-surface architecture inspired by Linear's dark-mode engineering. Rather than relying on drop shadows or heavy color blocks, hierarchy is established via subtle background luminance increments (`#08090A` canvas → `#0F1011` sidebar/panels → `#191A1B` interactive modals) and `1px solid rgba(255,255,255,0.08)` borders.

## Colors

- **Neutral (`#08090A`):** Deep obsidian canvas.
- **Surface (`#0F1011`):** Primary container and ledger table background.
- **Elevated (`#191A1B`):** Active rows, command inspector, and QRIS payload builder.
- **Primary (`#F7F8F8`):** Crisp off-white text that prevents halation on dark screens.
- **Secondary (`#D0D6E0`):** High-legibility silver-gray for table cells and merchant names.
- **Muted (`#8A8F98`):** Metadata, CRC16 checksum tags, and timestamps.
- **Tertiary (`#5E6AD2`):** Brand indigo reserved strictly for primary actions and active states.
- **Emerald (`#10B981`):** Verified settlement status and valid CRC16-CCITT indicator.

## Typography

All UI copy uses `Inter` with OpenType features `'cv01', 'ss03'` enabled globally, paired with `JetBrains Mono` for EMVCo TLV strings, CRC16 hex outputs, HMAC-SHA256 signatures, and currency figures (`font-variant-numeric: tabular-nums`).

## Layout

Built on an 8px spatial grid. Primary workspace uses a 240px fixed left navigation rail + fluid multi-pane **Monitor & Operate** console (top telemetry strip, left dynamic QRIS EMVCo mutator & HMAC webhook simulator, right live transaction ledger & Laravel 11 code exporter).

## Elevation & Depth

Zero drop-shadows on cards. Surfaces separate strictly via `rgba(255, 255, 255, 0.07)` 1px borders and luminance stepping.

## Shapes

Buttons and inputs use `6px` radius; structural panels use `8px` to `12px` radius.

## Components

- `button-primary`: High-contrast `#5E6AD2` interactive trigger.
- `card-surface`: Translucent dark panel with `1px solid rgba(255,255,255,0.08)` border.
- `badge-emerald`: Settlement confirmation pill.

## Do's and Don'ts

- **Do** enable `tabular-nums` on every Rupiah amount, fee calculation, and CRC16 checksum.
- **Don't** use glossy gradients, centered marketing heroes inside the operator console, or uncalibrated pure white (`#FFFFFF`) text.
