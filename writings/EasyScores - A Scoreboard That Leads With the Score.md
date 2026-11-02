---
title: "EasyScores - A Scoreboard That Leads With the Score"
date: 2026-11-02
author: Dom Foley
description: I built a mobile-first NBA, NFL and MLB tracker in Laravel and React, and most of the interesting work ended up being about when not to call the data provider.
reading_time: 6
slug: easyscores-a-scoreboard-that-leads-with-the-score
canonical_url: https://domfoley.com/writings/easyscores-a-scoreboard-that-leads-with-the-score
tags:
  - laravel
  - react
  - buildinpublic
  - sideproject
category: backend
lede: "A sports tracker where the score is the first thing you see, and the hard part was being polite to someone else's API."
---
## What EasyScores Is

[EasyScores](https://easyscores.laravel.cloud/) is a sports tracker for the NBA, NFL and MLB. You pick the teams you follow, and the home screen is built around them: next game, last result, where they sit in the division, and whether they're on a streak. There's a scoreboard for every league and day, standings, team season pages, a game page with the linescore and box score, and a bet tracker tucked away under Extras for anyone who wants it.

The whole idea is that checking on your team should take one tap. Most sports apps bury the score under video, news and promos, so EasyScores goes the other way: phones get a bottom tab bar, desktop gets a sidebar, and the game page leads with the score before anything else.

## The First Version Hit a Wall in a Day

The MVP ran on a hosted sports data API whose free tier allowed **20 calls a day**. That sounds like a lot right up until you realise one person opening a scoreboard, a game and a team page can use a quarter of it in a minute. It worked as a proof of concept and was never going to survive real use.

So the second version moved to ESPN's public site API: no key, every league I wanted, and box scores. The catch is that it's unofficial. It can change shape or go away whenever ESPN feels like it. So I didn't let ESPN leak into the app. Everything goes through a `SportsProvider` interface:

```php

interface SportsProvider
{
    public function scoreboard(League $league, CarbonImmutable $day): array;
    public function teamSeason(Team $team, int $startYear): TeamSeason;
    public function game(League $league, string $gameId): ?GameDetail;
    public function standings(League $league): Standings;
    public function teamLeaders(Team $team): TeamLeaders;
}

```

Providers only fetch and normalise, turning ESPN's JSON into the app's own `Game`, `Standings` and `TeamSeason` objects. Caching and fallbacks live one layer up in a `SportsRepository`. If ESPN breaks or I find a better source, I write one new class and nothing else changes. The tests use a fake provider plus some trimmed-down ESPN fixtures, so none of them touch the network.

## Cache Lifetimes Follow the Games

The obvious approach is to cache every response for N minutes. The problem is that no single N is right. A live game wants fresh data every few seconds, a game tomorrow night doesn't change for hours, and a final from last week is never going to change again.

So the cache lifetime is decided by the games in the response:

```php

// cache lifetimes follow the games themselves
foreach ($games as $game) 
{
    if ($game->status->isFinished()) 
    {
        continue;
    }

    $start = $game->startTime();

    // live, or due to have started: refresh on the live interval
    if ($game->status->isLive() || $start === null || $start->isPast()) 
    {
        return $this->liveTtl();
    }

    $nextStart = $nextStart === null ? $start : $nextStart->min($start);
}

// nothing live: hold until the next game starts
return $this->untilStart($nextStart);

```

In practice:

- **Live:** refresh every 30 seconds.
- **Upcoming:** hold until tip-off, but no longer than 30 minutes, so pre-game odds don't go stale.
- **Finished:** cache for a week. Past days of scores are cached for a week too.

A quiet Tuesday morning costs almost nothing, and a Sunday full of live NFL games refreshes exactly as often as it needs to.

## Don't Let a Crowd Stampede the API

Smart lifetimes don't help much if fifty people load the same scoreboard the second its cache expires and all fifty go to ESPN. So refreshing a cache entry takes a lock. The first request does the fetch, and everyone else gets the previous copy in the meantime instead of waiting:

```php

$lock = Cache::lock($key.':lock', 15);

if (! $lock->get()) 
{
    // someone else is already refreshing: serve the last good copy
    $stale = Cache::get($key.':stale');

    if ($stale !== null) 
    {
        return $stale;
    }

    $lock->block(10);
}

```

Every successful response is also saved as a "last known good" copy for seven days. If ESPN errors or times out, the page shows that copy with a small "Showing the last update" note instead of an empty screen. On top of that, outbound calls are capped at 120 a minute overall, and each signed-in user can cause at most 30 of them a minute. That keeps the app a polite client, and one browser tab left on refresh can't burn the budget for everyone.

## Polling Only While Something Is Live

On the front end it's Inertia and React, and live games poll with Inertia's `usePoll`. Two details keep that cheap. It only runs while a game on screen is actually live, and it only reloads the props that change:

```ts

export function useLivePolling(isLive: boolean, only: string[], intervalSeconds: number): void {
    const { start, stop } = usePoll(intervalSeconds * 1000, { only }, { autoStart: false });

    useEffect(() => {
        if (isLive) start();
        return stop;
    }, [isLive, start, stop]);
}

```

Because the server answers from the shared cache, polling never reaches ESPN directly. A thousand people watching the same game is one upstream call every 30 seconds, not a thousand.

## Heads-Ups: a Friend Texting You the Score

Heads-Ups are the short notes at the top of the home screen: a final for one of your teams, or a game that's close late. They're built on the fly from cached data. The only thing stored is which ones you've swiped away.

The rule I gave myself for the wording was "a friend texting you the score": result first, one detail, no exclamation marks, no gloating. Each moment has a few templates so it doesn't feel canned. The template is picked by a seed from the Heads-Up's key, so a note doesn't change its wording every time the page refreshes:

> Final: Rockets take this one, 119-98. Up next: Tomorrow 7:00 PM at the Cavaliers in Cleveland.

## Keeping the Insights Honest

There are two bits of context on game cards, and both are deliberately boring:

- **Key games** are flagged for simple, checkable reasons: playoff games, division races, and two top teams meeting. They never say a team "must win".
- **Best bets** on the game page are plain hit rates from recent games, like "25+ points: 4 of 6". Those are past results, not predictions, and the card says so.

I'd rather show you a true number and let you decide than dress up a guess as analysis.

## The Stack

- Laravel 13 with Inertia v3, React 19 and TypeScript
- Tailwind CSS v4 on the front end
- Fortify for auth, plus passkeys
- PHPStan (Larastan) and Pint keeping things honest
- Around 170 tests
- Hosted on Laravel Cloud

Adding a league is mostly one new case on the `League` enum (its season window, typical game length and what counts as a close margin) plus a team sync.

The full project page is [on my work page](https://domfoley.com/work/easyscores), with screenshots and a demo, or you can just [go check a score](https://easyscores.laravel.cloud/).
