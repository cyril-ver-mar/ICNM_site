# ADR 0002: Kadence theme

Date: 2026-08-30
Status: accepted

Context:

IBOCH’s public site is WordPress + Kadence + Kadence Blocks. ICNM must look like that family of institute sites without becoming a pixel clone. Alternatives: a custom theme that merely resembles IBOCH; Kadence first, then a later unique theme.

Decision:

Use Kadence (and its block constructor) as the v1 theme. Tune colours, logo, and ICNM-specific sections on top. Do not budget a second custom theme.

Consequences:

Page layouts will live in Kadence blocks; moving off Kadence later means rebuilding those layouts. Staff who talk to IBOCH colleagues get the same editor. Visual-impaired plugin and header/footer chrome should follow IBOCH’s Kadence patterns.
