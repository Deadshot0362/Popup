# Project Scope Baseline

## Task 1: Finalized Name, Scope, and Release Boundaries

- Project Name: PopupPilot
- Product: WordPress Popup Builder Plugin
- Baseline Date: 2026-04-29

## Vision
PopupPilot helps WordPress site owners launch and optimize conversion-focused popups without code using a visual editor, targeting rules, campaign controls, and analytics.

## Target Users
- Ecommerce store owners
- Bloggers and publishers
- Marketers and growth teams
- Agencies managing multiple client sites

## V1 (Must-Have Release Scope)

### Core Editor
- React + TypeScript frontend editor
- Drag-and-drop canvas
- Components: text, image, button, form (multi-field), countdown, video
- Layer control with z-index
- Multi-device layouts (desktop/tablet/mobile)
- Undo/Redo (up to 50 states)
- Multi-step popup flow model and editor UI
- Template save/load
- JSON export/import with schema validation

### Trigger & Targeting
- Triggers: page load, time delay, scroll %, exit intent, inactivity, click selector, URL match, referrer match
- Targeting: device type, UTM source, logged-in state, new vs returning, page regex rules
- Frequency controls: once/session, once/X days, max impressions/user

### Campaigns
- Campaign grouping
- Status: draft, active, paused
- Schedule start/end with timezone
- Priority rules
- A/B variants with traffic split

### Analytics
- Metrics: impressions, views, clicks, conversions, CTR
- Dashboard filters: date, campaign, popup, device, source
- CSV export

### Integrations
- Webhooks (generic)
- REST API for external sync

### Security/Quality
- WordPress nonce + capability checks
- Input sanitization and output escaping
- Core unit/integration tests for validation and rule engine

## V1.1 (Post-Launch Expansion)

- AI popup generation from prompt (schema-constrained)
- Prompt-based diff editing with preview/apply
- Revenue attribution analytics
- Native integrations: Mailchimp, HubSpot, ConvertKit
- Zapier preset flow
- Advanced geo + behavior targeting refinements
- Deeper observability and performance optimizations

## Out of Scope (for V1 + V1.1)
- Full marketing automation suite replacement
- Cross-site global analytics warehouse
- Native mobile app builder

## Acceptance Criteria for Task 1
- Project naming finalized
- Clear v1 vs v1.1 boundaries documented
- Scope is sequentially actionable for implementation tasks

## Notes
- This scope intentionally places AI and native integrations in v1.1 to reduce first-release risk and speed time-to-market.
