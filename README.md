# Synapse

Source of truth for everything I write — all version-controlled in one place. Gets built into my portfolio site with a CLI that preps cross-posting copy.

## How the *synapse* Works

This repo (*like the name*) is where all the thoughts and fleeting moments of genius are collected. Finished posts fetch straight from `writings/` at build time — one source of truth, no risk of two repos drifting apart.

## Structure Overview

```plaintext
synapse/
├── writings/                # finished, published posts
│   └── YYYY-MM-DD-some-post.md
├── drafts/                 # work in progress, version-controlled but not built
│   └── YYYY-MM-DD-2am-half-thoughts.md
├── scripts/
│   └── announce.php        # generates cross-posting copy
└── .github/workflows/
    └── deploy.yml          # pings the portfolio site's Vercel deploy hook on push
```

## Writing a Post

Every post needs frontmatter:

```yaml
---
author: "Dom Foley"
canonical_url: https://domfoley.com/writings/some-post
date: YYYY-MM-DD
description: "One-liner used for previews, RSS, and social copy"
reading_time: 4
slug: some-post
tags: [laravel, devops]
title: "Post title"
---
```

> **Note:** `canonical_url` matters the moment this post shows up anywhere else (LinkedIn, X, etc.) — it tells search engines this site is the original source, so a cross-posted copy elsewhere doesn't outrank it.

## Drafts Abyss

Work in progress goes in `drafts/`. Once it's ready, move it into `writings/` and push — that's the trigger for everything downstream.

## Publishing

Pushing to `main` fires `.github/workflows/deploy.yml`, which hits the portfolio site's Vercel deploy hook:

```yaml
- name: Trigger Vercel rebuild
  run: curl -X POST "${{ secrets.VERCEL_DEPLOY_HOOK }}"
```

The portfolio site pulls fresh content from `writings/` on every build — no PAT, no cross-repo write access, nothing to keep in sync manually.

> **Note:** Repo secret required: `VERCEL_DEPLOY_HOOK` — generate one in your Vercel project's settings under Git → Deploy Hooks.

## Cross-Posting

- **Hashnode** — same idea as the deploy pipeline: it pulls, you don't push.

- **LinkedIn / X** — no auto-posting. Both require more API/app-review overhead than it's worth for personal-scale posting, so instead:

```bash
php ./scripts/announce.php writings/YYYY-MM-DD-some-post.md
# prints ready-to-paste LinkedIn and X copy (title, description, link, hashtags pulled from `tags`), with a current character count for X.
```
