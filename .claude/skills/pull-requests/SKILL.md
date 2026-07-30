---
name: pull-requests
description: Conventions for writing pull requests, including Conventional Commits titles and structured descriptions. Use when the user wants to create or open a pull request, or write a PR title or description. Trigger whenever a pull request, PR, or merge request is mentioned.
---

# Create a pull request

- The pull request title MUST follow the Conventional Commits naming convention, e.g. `feat: add new locale fr_FR`.
- You SHOULD avoid uppercase in the title, except for proper nouns and acronyms.
- The pull request description MUST contain:
    - if it's a new feature:
        - a summary of the changes made in the PR, in indicative mood (present tense), using a list if necessary.
    - if it's a change or a fix:
        - an explanation of what the situation was.
        - a summary of the changes made in the PR, in indicative mood (present tense), using a list if necessary.
- You SHOULD add notes about important details when necessary.
- You MUST indicate whether the PR closes an issue, and reference the issue number.
- You MUST NOT mention Claude Code anywhere.
