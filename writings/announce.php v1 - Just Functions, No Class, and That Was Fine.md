---
title: announce.php v1 - Just Functions, No Class, and That Was Fine
date: 2026-07-16
author: Dom Foley
description: The first version of my cross-posting script was one flat file of functions. Here's why I didn't reach for a class right away.
reading_time: 2
slug: announce-php-v1-just-functions-no-class
canonical_url: https://domfoley.com/writings/announce-php-v1-just-functions-no-class
tags:
  - php
  - dev
  - sideproject
  - script
category: backend
lede: The whole script fit in my head, so it didn't need a class yet.
---
## The Whole Script Fit in My Head

When I first wrote `announce.php`, it was one file, top to bottom, no class: `parse_frontmatter`, `to_hash_tag`, `build_linked_in_post`, `build_x_post`, `main`. Read it once, in order, and you understood the whole tool. No jumping between files to find where something actually happens, no inheritance chain to trace. Just a script that reads a markdown file and prints some strings.

## I Didn't Reach for a Class Because I Didn't Need One

I've read enough “wrap everything in a class” advice to know the instinct to reach for OOP is sometimes more about looking like professional code than solving an actual problem. A class implies state worth managing across calls. This script runs once, does its thing, exits. `build_linked_in_post` and `build_x_post` both take the same frontmatter array as a parameter, but that's not shared state — that's just a shared argument. There was nothing here that needed an object.

## Small Tests, No Framework

The file ends with `test_parse_frontmatter`, `test_to_hash_tag`, and a couple more, each just a function full of `assert()` calls. No PHPUnit, no test runner config. For a script this size, pulling in a testing framework felt like more ceremony than the issue warranted — a handful of asserts catches “did I just break the regex” just as well, and I wrote them mainly so future me wouldn't have to guess whether a change silently broke something before pushing to a pipeline I actually rely on.

## Where It Started to Strain

Even so, by the time I had two post builders, a parser, a hashtag formatter, a debug helper, and a handful of test functions all sharing the same file, I could feel it wanting to be something else — not because the functions were badly written, but because I wanted grouping, and PHP doesn't give you namespacing-by-file the way some languages do without extra ceremony. That's what pushed me toward a class-based rewrite next — which, it turns out, brought a problem of its own.
