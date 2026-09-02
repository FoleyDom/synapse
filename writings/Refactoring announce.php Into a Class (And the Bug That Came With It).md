---
title: Refactoring announce.php Into a Class (And the Bug That Came With It)
date: 2026-08-18
author: Dom Foley
description: I wrapped my cross-posting script in a class for tidier grouping. Then broke every function call in it by forgetting one keyword.
reading_time: 2
slug: refactoring-announce-php-into-a-class
canonical_url: https://domfoley.com/writings/refactoring-announce-php-into-a-class
tags:
  - php
  - dev
category: backend
lede: "I wrapped a working script in a class and broke every function call in it with one missing keyword."
---
## Same Logic, New Wrapper

Took the [procedural version](/writings/announce-php-v1-just-functions-no-class) and wrapped it in a single class, `BlogPostAnnouncer`, mostly to get everything under one namespace instead of leaving the functions floating in the global namespace next to whatever else PHP has lying around. `main()` became `BlogPostAnnouncer::main($argv)`, called once at the bottom of the file. Every other function moved inside the class body more or less copy-pasted, unchanged.

## The Bug I Didn't Notice Until I Actually Ran It

That "more or less copy-pasted" is doing a lot of work in that sentence. Inside a class, an unqualified call like `parse_frontmatter($raw)` doesn't resolve to a sibling method the way it resolved to a global function before — PHP goes looking for a global function named `parse_frontmatter`, doesn't find one, and throws a fatal error instead. `main()` is the only method I remembered to mark `static`, and none of the calls inside it use `self::` or `static::` to reach the others. Every method in the class calls its siblings the same bare way that used to be correct in the procedural version and is now wrong.

I didn't catch this reading the diff. I caught it by actually running the script and watching it die on the very first function call it made.

## The Fix Is One Keyword, Repeated Everywhere

The fix is mechanical: `self::parse_frontmatter(…)` instead of `parse_frontmatter(…)`, for every call, in every method. I know exactly what's wrong and haven't pushed the fix yet, because the procedural version still runs everything for real right now — nothing's actually blocked. That's the upside of not deleting v1 the moment v2 exists: the working thing keeps working while the new thing gets to sit there being wrong for a while without costing me anything.

## What the Refactor Was Actually For

Honestly, mostly readability. "These functions all belong to this one tool" is a small thing to formalize on a script this size — I could've prefixed every function `announcer_parse_frontmatter` and gotten the same grouping for free without any of this. The real reason was I wanted the practice: reaching for a class deliberately, on something small enough that getting it wrong costs me an afternoon of debugging and not a production incident.
