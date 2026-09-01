---
title: Building My Own Blog Pipeline So I'd Actually Publish
date: 2026-08-17
author: Dom Foley
description: How this repo went from a folder of markdown files to a push-to-main publishing pipeline, and why removing friction mattered more than any feature.
reading_time: 3
slug: building-my-own-blog-pipeline-so-id-actually-publish
canonical_url: https://domfoley.com/writings/building-my-own-blog-pipeline-so-id-actually-publish
tags:
  - devops
  - career
  - github-actions
  - buildinpublic
  - blog
category: devops
lede: This post is the first real thing to go through the pipeline it's describing.
---
## The Actual Problem Wasn't “I Don't Write”

I write plenty — half-finished drafts, notes to myself, the occasional real post. The problem was always what happens after a draft feels done: copy it somewhere, format it for whatever platform, write separate versions for LinkedIn and X, remember to add the canonical link so a cross-posted copy doesn't outrank the original. By the time all of that was done, whatever made me want to publish in the first place had usually evaporated. So I stopped trying to fix my writing habits and started fixing the pipeline instead.

### One Repo, Two Folders

This repo, Synapse, is the whole source of truth now — `writings/` for finished posts, `drafts/` for everything still in progress. The portfolio site pulls straight from `writings/` at build time. Sounds obvious written down like that, but it took me longer than it should have to actually commit to it. My first instinct was to keep copying finished posts into the site's own repo too, treating `writings/` here as more of a staging area than the real source — which just gives you two places a post can quietly drift out of sync, exactly the kind of manual step this pipeline was supposed to get rid of.

### Push to Main, Not "Remember to Deploy"

The workflow that made this actually stick is embarrassingly simple: push to `main`, and a GitHub Action hits my portfolio site's Vercel deploy hook. No manual deploy step, no “I'll push the site update later.” If a finished post is sitting in `writings/`, it's live within a minute of the push finishing. More recently I extended that same action to build the published post through the portfolio site's own API rather than just triggering a generic rebuild — a small change on paper, but it's the difference between “the site rebuilds and hopefully picks up the new post correctly” and actually knowing it did.

### Testing in Public, Sort Of

Some of the recent commits in this repo are literally just me testing whether a tag or category change in frontmatter parses and publishes the way I expect. Not a glamorous commit message, but an honest one. I'd rather iterate on the pipeline in small, visible commits than get it perfect before using it once — and I've already broken it at least once fixing it, proof the thing is real, not a diagram I drew once and never touched.

### Cross-Posting Without an API I Don't Control

LinkedIn and X don't have auto-posting worth the API review overhead for something this small, so that part stays manual on purpose — I run [a script](https://domfoley.com/writings/refactoring-announce-php-into-a-class) that generates ready-to-paste copy for both, with a character count, so I'm not guessing whether the X post fits.

### The Real Win

None of this made me a better writer. What it did was remove every excuse between “this draft feels done” and “this is live”, which turns out to have been most of what was stopping me. [The retrospective post](https://domfoley/writings/six-month-retrospective-the-human-element-of-engineering) I just finished writing is the first real test of whether that's true — it sat unfinished for over a month before this pipeline existed to actually push it out the door.
