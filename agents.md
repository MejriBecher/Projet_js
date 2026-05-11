\# PROJECT RULES — READ BEFORE DOING ANYTHING

## Hotel Identity

- **Name:** Azur Cove Hotel
- **Tagline:** *Where the Sea Meets Serenity*
- **Theme:** Coastal & fresh — navy, sky blue, sand, linen white



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

## Design System

### CSS Variables — defined in `assets/css/style.css`

- `--navy` (#0C3B5E), `--ocean` (#1A6A9A), `--sky` (#7EC8E3)
- `--mist` (#E8F4FD), `--sand` (#D4A96A), `--linen` (#FAF6F0), `--charcoal` (#2C2C2A)
- `--font-heading`: 'Playfair Display', serif / `--font-body`: 'Inter', sans-serif

### Typography & Color Rules

- Headings (h1–h3, room titles, hotel name): `var(--font-heading)`, color `--navy`
- Body, labels, forms, buttons: `var(--font-body)`
- Page background: `--linen`
- Primary button: bg `--navy`, text white, hover bg `--ocean`
- Accent / price badges: bg `--sand`, text `--charcoal`
- Nav bar: bg `--navy`, links white, active link color `--sky`
- Max content width: `1100px`, centered with `margin: 0 auto`
- Room cards: white bg, `border-radius: var(--radius-lg)`, `box-shadow: 0 2px 12px rgba(12,59,94,0.08)`

### Seed Image URLs

Rooms:
- Deluxe Ocean View → `https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80`
- Standard Double → `https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80`
- Family Suite → `https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80`
- Premium Penthouse → `https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80`

Services:
- Breakfast → `https://images.unsplash.com/photo-1533777857889-4be7c70b33f7?w=800&q=80`
- Transfer → `https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=800&q=80`
- Spa → `https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80`

Hero: `https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1400&q=85`
Exterior: `https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=85`

### Admin

- `admin@azurcove.com` / `admin123`
- Name: Azur Admin

