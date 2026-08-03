# 21. Pillar: Communicate

| | |
| --- | --- |
| **Identifier** | `backlog/21-pillar-communicate` |
| **Status** | Placeholder |
| **Source** | Sections 3.3 and 6 of the monolithic specification |
| **Depends on** | `03-employees`, `06-teams`, `11-domain-events`, `13-module-system` |

## Why this is a placeholder and not a spec

Two bullet points in the source document and no data model. See
`20-pillar-operate` for the reasoning; it applies identically here.

## What the pillar is for

This is the deliberately light pillar. No wiki and no work logs, both covered by
Notion, GitHub and Linear. What is left is what makes the product feel people
first rather than administrative.

## What it contains

### Team and company news

Announcements and internal news.

### Get to know your colleagues

The part of a directory that is not a directory: what people are working on, what
they are interested in, how to reach them.

## The constraint that shapes this pillar

Section 6 of the source document contains the most important thing written about
this area, and it is a warning rather than a feature.

Meta shut Workplace, the former Facebook for Work, in 2024 and 2025. It had
millions of users at its peak and still failed to beat Slack and Teams on their
own ground.

The conclusion drawn: **the differentiator is not a standalone social feed.** It
is a layer connected to data already structured elsewhere in the core.

- Announcements generated from arrival events rather than typed by somebody who
  remembered.
- Groups populated automatically from teams that already exist.
- Profiles built from the employee record rather than filled in twice.

Anything in this pillar that could exist as a standalone product is the wrong
thing to build. Anything that only works because the core already knows who
joined, who reports to whom and which team somebody is on, is the right thing.

That test is sharper than the general constitution test and should be applied
first.

## What already exists to build on

- `11-domain-events` publishes `employee.arrived`, which is what an automatic
  arrival announcement is generated from. That is the single clearest example of
  the pattern above.
- `06-teams` is what groups would be populated from.
- `03-employees` is what a profile is built from.

## Open questions

- Where does an announcement appear? A feed, an email, both? A feed nobody visits
  is worse than nothing.
- Comments and reactions. Section 6 lists them under a Community module, which
  implies yes, but they are also the fastest way to build the isolated social
  network the Workplace lesson warns against.
- The relationship with Slack. Most target companies live there. An announcement
  that does not reach Slack may not reach anybody, and pushing to Slack is an
  integration, which founding principle 6 says comes after the human version.

## Next step

The arrival announcement generated from `employee.arrived` is the smallest thing
in this pillar that demonstrates the whole thesis. It is worth specifying on its
own, before anything resembling a feed exists.
