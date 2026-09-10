---
Description: Setting up commit signing turned into a longer detour than expected — GPG vs SSH, which key to reuse, and an unresolved quantum question I wasn't trying to open.
Title: "Signing My Commits: SSH, GPG, and a Question I Can't Actually Answer Yet"
Date: 2026-09-05
Author: Dom Foley
Read_Time: "4"
Slug: github-commit-signing-ssh-gpg-quantum
Canonical_url: https://domfoley.com/writings/github-commit-signing-ssh-gpg-quantum
Tags:
  - git
  - security
  - dev
Category: security
Lede: The green Verified badge on GitHub is doing a lot less work than I assumed.
---
## Why Bother, For a Solo Repo

Nobody's spoofing commits on my personal projects. That's not really the point. Signing commits is one of those habits that's cheap to build now and annoying to retrofit later. If I'm going to care about supply-chain hygiene anywhere, “did this commit actually come from me” seemed like the least I could ask of my own repos before asking it of anyone else's.

## GPG Is the Establishment Choice, and It Shows

GPG has been the default answer for years, and using it felt like inheriting someone else's tool chain — key generation, a keyring to manage, `gpg-agent` doing its own thing in the background, revocation certificates I'd need to remember exist if a key is ever compromised. All of that is genuinely useful. None of it felt proportional to “sign my commits, so the badge shows up.”

## SSH Signing Is Just … Reusing What I Already Have

Git added SSH-based signing a few versions back, and GitHub verifies it the same as GPG. No keyring, no separate agent — if you already generate SSH keys for anything else, you already understand the entire mental model. That's the whole pitch. It's not that GPG is wrong, it's that SSH signing is the amount of ceremony this actually warranted.

## Where I Landed, With One Deliberate Choice

I didn't reuse my existing access key for signing, and I'd push back gently on anyone who does. One key doing double duty for “prove I pushed this” and “let me into this server” means a single leak costs you two different guarantees at once. A dedicated `ed25519` key that only ever signs commits keeps the blast radius smaller if something ever goes wrong with it.

## The Part I Didn't Mean to Get Into

Somewhere in the reading I ended up down a much less practical hole: none of this is quantum-safe, and it's not close. RSA and ed25519 both fall to a sufficiently capable quantum attacker, and there isn't a mainstream, git-native post-quantum signing scheme I could've adopted today even if I wanted to. That's not a today problem — nobody's git history is under quantum threat in 2026 — but it's a strange thing to sit with, knowing the signature I just set up has a theoretical expiration date I can't currently do anything about. For now, I'm signing with what exists and treating “revisit this when the tooling catches up” as an actual line item, not just a thing I'm telling myself.
