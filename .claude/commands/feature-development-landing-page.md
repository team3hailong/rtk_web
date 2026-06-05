---
name: feature-development-landing-page
description: Workflow command scaffold for feature-development-landing-page in rtk_web.
allowed_tools: ["Bash", "Read", "Write", "Grep", "Glob"]
---

# /feature-development-landing-page

Use this workflow when working on **feature-development-landing-page** in `rtk_web`.

## Goal

Implements or updates features on the landing page, including popups, avatars, and styling.

## Common Files

- `public/assets/css/landing.css`
- `public/assets/js/landing.js`
- `public/assets/img/landingpage/`
- `public/landing.php`

## Suggested Sequence

1. Understand the current state and failure mode before editing.
2. Make the smallest coherent change that satisfies the workflow goal.
3. Run the most relevant verification for touched files.
4. Summarize what changed and what still needs review.

## Typical Commit Signals

- Edit or add images in public/assets/img/landingpage/
- Update CSS in public/assets/css/landing.css
- Update or add JS in public/assets/js/landing.js
- Modify PHP logic or markup in public/landing.php

## Notes

- Treat this as a scaffold, not a hard-coded script.
- Update the command if the workflow evolves materially.