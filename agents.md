\# PROJECT RULES — READ BEFORE DOING ANYTHING



\## Git Rules — Non-Negotiable



You NEVER commit directly to main or dev.

You NEVER push directly to main.

Every piece of work happens on its own branch.



\### Branch naming

\- New feature or milestone  → feat/describe-it

\- Bug fix or surgical edit  → fix/describe-it

\- Experiment                → exp/describe-it



\### Before you write any code

1\. Check which branch you are on: `git branch --show-current`

2\. If you are on main or dev, stop and create the right branch first:

&#x20;  `git checkout -b feat/what-you-are-building`



\### Commit rules

\- One commit per logical unit of work — not one giant commit at the end.

\- Use conventional commits:

&#x20; - feat: added X

&#x20; - fix: corrected Y

&#x20; - chore: updated Z

&#x20; - test: added tests for W

\- Never use vague messages like "update", "changes", or "wip".



\### After a milestone or fix is complete

1\. Commit all changes with a clear message.

2\. Push the branch: `git push origin branch-name`

3\. Tell the user — do not open a PR yourself.

&#x20;  The user reviews and merges via: `gh pr create --base main`



\### What you must NEVER do

\- `git push origin main` — forbidden

\- `git commit -m "update"` — forbidden

\- Committing node\_modules, .env, or any file in .gitignore — forbidden

\- Squashing or rebasing history without being explicitly asked — forbidden



\## State Files — Always Read First



At the start of every session, before writing any code:

1\. Read `.planning/STATE.md` — this tells you where the project is right now.

2\. Read `PROJECT\_MAP.md` — this tells you the architecture and what is pending.



Do not assume. Do not guess. Read the files.



\## Phase Awareness



The user will paste a specific prompt at the start of each phase.

That prompt defines exactly how you must behave for that phase.

Read it carefully and follow it strictly.



There are 3 phases:

\- Planning   → you only plan, no code, you wait for human approval before anything else

\- Execution  → you build the approved milestone, no TODOs, no placeholders, no stopping

\- Surgical   → you make one targeted change, you touch nothing outside of it



If the user has not pasted a phase prompt yet, ask them which phase you are in before doing anything.

