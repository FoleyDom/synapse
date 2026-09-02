---
author: Dom Foley
date: 2026-07-03
description: A look back over the last six months, the problems faced, solutions uncovered, and knowledge gained.
reading_time: 4
slug: six-month-retrospective-the-human-element-of-engineering
canonical_url: https://domfoley.com/writings/six-month-retrospective-the-human-element-of-engineering
tags:
  - php
  - dev
  - career
category: career
title: Six-Month Retrospective - The Human Element of Engineering
---
## Time Flies — How We Use It Is Up To Us

Three years in. I don't have a CS degree, I didn't take the traditional path into this field, and there were plenty of stretches early on where I was fairly convinced I'd picked the wrong one. I've since learned that feeling doesn't really go away — you just get more practiced at not confusing it with the truth.

Furthermore, I keep telling myself I'll document things as I go. Write the quick note, come back to it later, turn it into something. Then three months disappear and last week is already fuzzy, let alone whatever I meant to write about in June. Not a unique problem. I wanted to actually fix it instead of just noting that it's a concern again — more on that at the end.

## Things I've Learned, Used, or Found Helpful

### Xdebug — and the Types That Make It Necessary

PHP being loosely typed cuts both ways. Writing it is fast and forgiving. Debugging it is a different story — an error five function calls from where the real issue lives doesn't hand you a straight line back to the cause. I'd known Xdebug existed for years and just never set it up. No one on my team had either, until a new hire swore by it and got stuck configuring it. I looked into why: the IDE couldn't see the Xdebug port because it was broadcasting from inside a VM. One fix later, and stepping through legacy code the senior members of the team hadn't looked at in years felt like turning the lights on in a room I'd been guessing my way through.

It's part of why I push for strict types on anything new now. Xdebug is what you reach for after the bug already happened. Types are the version of that fix that runs before you ship — catching the five-steps-away error at the one step where it actually occurred.

### Efficient Code Isn't Clever, It's Necessary

For the first stretch of this job, “it works” was the bar. Loop over a dataset, hit the database once per related record, ship it, move on. I wasn't thinking about what happens when a few thousand people hit that same code at once — until a discount email went out, and I watched page load times climb in real time. The fix wasn't clever: stop hitting the database more than necessary, cache what doesn't change every request. No new library, no rewrite. Just actually reading the docs for the tools I already had instead of reaching for a new one. That's been the real theme of this stretch — less “learn a new framework”, more “understand the one you're using”.

### Asking for Help Without the Ego Hit

The technical stuff was never the hard part. The hard part was standup — sitting next to people who'd been writing PHP since before I finished my first tutorial, and saying out loud that I didn't understand legacy code they'd written years earlier. Self-taught, no degree, and a very specific flavor of imposter syndrome that shows up every time someone says “oh yeah, that's simple” about something I've been stuck on for an hour.

What actually helped was noticing the senior devs asked for help constantly too — just about different things. Nobody knew Xdebug was misconfigured until I looked into it, because I was new enough to still be bothered by a broken setup everyone else had learned to route around. Not having the context turned out to be an advantage as often as a liability — I asked the questions that assumed nothing, and it was usually the one that got us to the actual bug. Three years in, “I don't know” isn't a confession anymore. It's the fastest way to get a real answer instead of a guessed one.

## Six Months From Now

I don't know what the next stretch looks like technically, and that's kind of the point of writing this down instead of just living through it and forgetting. What I do know is I built the pipeline this post is running through specifically so “I should write more” stops being a thing I tell myself and starts being a thing that happens when I push to `main`. Ask me again in six months whether it worked.
