# Documentation Portal Roadmap

A roadmap for the OfficeLife product documentation portal.

This document describes what the portal should contain, section by section and page by page. It is an implementation plan, not the documentation itself. A writing agent should be able to build every page from this roadmap without having to rethink the structure.

---

## How to use this roadmap

Read this section first. It sets the scope, the audiences, and the conventions used everywhere below.

### What the product is

OfficeLife is an open source HR application. The interface calls itself "Open source HR" on the guest screens, and the public site lives at `officelife.io`.

The mental model the whole product rests on, and the one the portal must teach first, comes straight from the schema:

- A **company** is the workspace. Everything belongs to one.
- An **employee** is somebody who works for that company. The employee record exists on its own, before they arrive and after they leave, and does not need a login.
- A **user** is a login. It belongs to a company, and it usually points at the employee it gives access to. It does not have to.
- The first person to sign up creates the company, becomes its **owner**, and gets both a user and an employee record in one move.

That separation between "a person who works here" and "an account that can sign in" is the single most important idea in the product, and several sections below exist only to make it land.

### The state of the product, and what that means for the portal

**Read this before writing a single page.** OfficeLife is early. As of this roadmap, the user facing surfaces that exist are the guest and authentication screens, plus one screen inside the application: the profile, where somebody edits their own employee record and their emergency contact. There is no dashboard, no employee directory, no billing screen, and no API. `routes/web.php` still sends `/` to a landing page, with a comment saying it stands in "until there is a dashboard to send people to."

This has two consequences the writer must respect:

1. **Every numbered section below is writable today.** They cover accounts, signing in, your profile, security, language, the emails the product sends, and running your own instance. Every claim in them is backed by code that exists and by tests that pass.
2. **"Not yet documentable", at the end, is not writable.** It is an inventory of things the database or the enums anticipate but no screen or route delivers. It exists so nobody writes those pages by accident, and so they can be written the moment the feature lands.

Never present a planned capability as finished. When a page has to mention something that is half built, say plainly what works and what does not.

### Who the readers are

Three audiences, needing different pages:

1. **Employees.** The people who sign in and use the product. Not developers. Most of the portal serves them.
2. **Company owners and administrators.** The person who signed up, plus anyone they later put in charge. Today the owner is the only distinguished role in the code: `companies.owner_user_id`, checked directly in `DestroyUser`, `DestroyCompany`, and `UpdateUserInformation`. There is no roles table and no permission system beyond that single check. Do not write about "roles" or "permission levels" in the plural.
3. **Operators.** Whoever installs, configures, and runs an instance. Developers and sysadmins.

Every page below names its audience.

### Where the portal lives

There is no documentation route today. `/docs` is free, and is the natural home. The portal is written as Markdown under `docs/portal/{locale}/`, mirroring the four locales the application already ships: `en`, `fr_FR`, `de_DE`, `es_ES` (see `config/officelife.php`). Write `en` first and completely; the others are translations of it.

### Conventions for every page entry

Each proposed page lists:

- **Purpose.** What the page accomplishes for the reader.
- **Audience.** Which reader group it serves.
- **Summary.** What it should cover, grounded in behavior that exists.
- **Prerequisites.** What the reader should have read or done first.
- **Complexity.** Low, medium or high, as a hint at how much work the page is.
- **Related pages.** Where to go next or sideways.

The `id` given for each page is its permanent frontmatter identifier, in `domain.action` form. Ids must not change once assigned, and `@doc(...)` links must always reference them rather than filenames. Folders and files carry an `N-` ordering prefix on disk that never appears in frontmatter. The first file of every folder is `1-introduction.md`. All of this follows the writer skill's rules, which take precedence over anything here if the two ever disagree.

### Ordering

Sections are ordered by how somebody actually meets the product: find out what it is, create an account, understand the vocabulary, learn to get back in, fill in your profile, secure the account, adjust the interface, understand the mail it sends, and (for operators) run it. Nobody should need a later section to finish an earlier one.

---

## Section 1: Introduction

**Why this section exists.** The portal needs a front door: one page that says what OfficeLife is, who it is for, and where to go next. It is also the only honest place to set expectations about how young the product is.

**Who it is for.** Everybody.

**On disk.** `1-introduction.md` at the portal root. Section value `portal`, slug `portal`.

### Welcome to OfficeLife

- **id.** `portal.introduction`
- **Purpose.** Orient a first time reader in under a minute and route them to the right section.
- **Audience.** All three.
- **Summary.** What OfficeLife is: open source HR software a company runs for its own people. The one line model, stated early and plainly: a company holds employees, and some of those employees have an account they sign in with. Who the product suits. That it is open source and can be run by you. A short map of the portal: start with Getting started; owners should read Your company and your people; operators should jump to Running your own instance. Then a candid paragraph, in plain language, that the product is in early development and this portal currently documents accounts, signing in, and security, with the rest arriving as it is built. No roadmap promises and no dates.
- **Prerequisites.** None. This is the true entry point.
- **Complexity.** Low.
- **Related pages.** `getting.what-is-officelife`, `getting.create-account`, `hosting.introduction`.

---

## Section 2: Getting started

**Why this section exists.** The shortest path from "I found this" to "I have an account and I am inside." Everything advanced is deferred.

**Who it is for.** Anyone creating a company, and anyone evaluating whether to.

**On disk.** `2-getting-started/`. Section value `getting-started`.

### Getting started

- **id.** `getting.introduction`
- **Purpose.** Section index. Say what the reader will have achieved by the end of it.
- **Audience.** New users.
- **Summary.** Three outcomes: you understand what OfficeLife is, you have a company and an account, and you have confirmed your email address. List the pages in order.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** Every page in this section.

### What is OfficeLife

- **id.** `getting.what-is-officelife`
- **Purpose.** Tell a newcomer what the product does and whether it fits them, before they invest any time.
- **Audience.** Employees and prospective owners.
- **Summary.** The problem it addresses: companies keeping people information in spreadsheets, inboxes and memory. The model in one paragraph: one company, its employees, and accounts for the ones who need to sign in. That it is open source, so you can read every line and run it yourself. Be explicit and unembarrassed about scope: today the product handles accounts, sign in and company creation, and more is being built. A reader who discovers that limitation on their own after signing up will not come back.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `getting.cloud-or-self-hosted`, `concepts.model`.

### Using officelife.io or running it yourself

- **id.** `getting.cloud-or-self-hosted`
- **Purpose.** Help the reader choose how they will run OfficeLife before they create anything.
- **Audience.** Prospective owners and operators.
- **Summary.** Two ways to run it: use the hosted service, or install it on your own server. The application knows which it is (`companies.is_self_hosted`) but the software is the same either way. State clearly what the portal can and cannot promise about the hosted service: the codebase has a `plan` field on every company and a thirty day `trial_ends_at` set at signup, but **no billing, payment or subscription code exists**, nothing reads either field, and no screen shows a plan. So: do not write pricing, do not describe upgrade paths, do not mention tiers. Say that self hosting is free and point at the self hosting section.
- **Prerequisites.** `getting.what-is-officelife`.
- **Complexity.** Medium. The temptation to invent a pricing story here is the main risk.
- **Related pages.** `hosting.introduction`, `concepts.company`.

### Create your company and your account

- **id.** `getting.create-account`
- **Purpose.** Walk somebody through the sign up form and explain what it creates.
- **Audience.** The person starting a company in OfficeLife.
- **Summary.** A `::::steps` walkthrough of `/register`: first name, last name, company name, email address, password, and the terms checkbox. What each field does and what it becomes. Explain, because the screen says so and users ask, that you can rename the company later and that you become its first administrator. Then the part nobody expects: submitting this form creates three things at once, a company, your user account, and your own employee record, and it makes you the owner. Cover the rules the form enforces, in the reader's words rather than the validator's: a password of at least eight characters, typed twice; an email address that no other account already uses; disposable or throwaway addresses are refused (the `disposable_email` rule); agreeing to the terms of use and the privacy policy is required, and links to both come from configuration so a self hosted instance can point at its own. Mention the human check that may appear (Cloudflare Turnstile), which is off by default and switched on per instance. Finish by saying what happens next: you are signed in and sent straight to the email confirmation screen.
- **Prerequisites.** `getting.what-is-officelife`.
- **Complexity.** Medium.
- **Related pages.** `getting.confirm-email`, `concepts.owner`, `concepts.employee-vs-user`.

:::note for the writer
The screen also carries a line telling people joining an existing company to ask an administrator to invite them rather than signing up again. Repeat that advice on this page, but do **not** describe an invitation flow: no invitation code, route, mail or screen exists yet. Say only that a second signup creates a second, separate company, which is almost never what they want, and that they should ask whoever runs their company for an account.
:::

### Confirm your email address

- **id.** `getting.confirm-email`
- **Purpose.** Get a brand new account over its last hurdle.
- **Audience.** Anyone who just signed up.
- **Summary.** Why confirmation exists. What the screen shows, and where the email goes. That the link is signed and expires after an hour, and what to do when it has: use **Send the email again** from the same screen. That resending is rate limited to six attempts a minute, so hammering it does not help. The reassurance the product itself offers: it can take a minute and it sometimes lands in spam. What you see when it works. Note that confirming later, rather than now, does not currently lock you out of anything.
- **Prerequisites.** `getting.create-account`.
- **Complexity.** Low.
- **Related pages.** `email.verify`, `signin.password`, `troubleshoot.no-email`.

### What you can do today

- **id.** `getting.what-works-today`
- **Purpose.** Set honest expectations for somebody who just got in and is looking at a bare page.
- **Audience.** New owners.
- **Summary.** A short, factual page. What exists: your company, your account, your employee record, the profile screen where you edit it, the list of emails the product has sent you, and everything in the account and security sections of this portal. What does not exist yet: any screen for browsing employees, editing your company, or managing accounts. Where the project is going, only insofar as the code shows it, with no dates. Where to follow along or contribute, since it is open source.
- **Prerequisites.** `getting.create-account`.
- **Complexity.** Low, but must be revisited every time a feature ships. Flag it in the page as the portal's "what is built" page.
- **Related pages.** `portal.introduction`, `concepts.introduction`.

---

## Section 3: Core concepts

**Why this section exists.** OfficeLife makes one distinction that no amount of clicking will teach you: employees and accounts are different things. Every later page leans on it. Concepts stay here, tasks stay elsewhere.

**Who it is for.** Everybody, but especially owners.

**On disk.** `3-core-concepts/`. Section value `core-concepts`.

### Core concepts

- **id.** `concepts.introduction`
- **Purpose.** Section index and the shortest possible statement of the model.
- **Audience.** All.
- **Summary.** Company, employee, user, owner, in one paragraph each, each linking to its page. Say why the reader should care: it explains why somebody can leave the company without their record disappearing, and why an account can exist for somebody who does not work here.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** All pages in this section.

### How OfficeLife is organised

- **id.** `concepts.model`
- **Purpose.** Give the reader the mental picture the whole product assumes.
- **Audience.** All.
- **Summary.** One company at the top. Employees belong to it. Accounts belong to it too, and each account may point at one employee. Draw it in prose (and mark a diagram placeholder). Explain the boundary that follows from it: everything you can see is inside your company, and there is no way to reach another company's data. State the current limit plainly: an account belongs to exactly one company, so somebody who genuinely works for two needs two accounts with two addresses.
- **Prerequisites.** None.
- **Complexity.** Medium.
- **Related pages.** `concepts.company`, `concepts.employee-vs-user`.

### Your company

- **id.** `concepts.company`
- **Purpose.** Explain what a company is in OfficeLife and what it records.
- **Audience.** Owners.
- **Summary.** The company is the workspace and the boundary. What it holds: a name, a URL friendly identifier derived from that name, a legal name, a logo, a website, an industry, a size range, a founding date, a timezone, a language, a currency, and how the company works (fully remote, hybrid, or office based). Explain the two that are not self evident: the size range is a declared band rather than a headcount the product counts, and the timezone and language act as the default for everyone who has not chosen their own. Be explicit that **there is no screen to edit any of this yet**: the fields are set when you sign up, and the ability to change them is coming. Do not describe a settings page.
- **Prerequisites.** `concepts.model`.
- **Complexity.** Medium. The honesty about the missing screen is the whole difficulty.
- **Related pages.** `concepts.owner`, `language.company-default`.

### Employees and accounts are not the same thing

- **id.** `concepts.employee-vs-user`
- **Purpose.** Teach the distinction that everything else depends on.
- **Audience.** All, above all owners.
- **Summary.** Lead with a scenario rather than a definition. Pam works in the warehouse and has no computer: she is an employee with no account. Michael runs the branch: he is an employee with an account. The bookkeeper who visits on Fridays has an account but is not on the payroll: an account with no employee. Then the definitions. An employee record holds who somebody is at work: their name, the name they go by if it differs, a work email, a job title, the country and timezone they work from, when they were hired, when they left, and the private details they fill in themselves (personal email and phone, date of birth, home address, emergency contact). An account holds only what is needed to sign in. Explain the two consequences that matter: deleting an account does not erase the person's employment record, and somebody who has left keeps their record. Explain "still employed" as the product defines it, which surprises people: somebody serving a notice period has a departure date in the future and still works here until that date arrives.
- **Prerequisites.** `concepts.model`.
- **Complexity.** High. This is the most important page in the portal.
- **Related pages.** `concepts.employee-record`, `concepts.owner`.

### What an employee record holds

- **id.** `concepts.employee-record`
- **Purpose.** Say exactly what the product knows about a person, which is a question people ask about HR software before they ask anything else.
- **Audience.** Employees and owners.
- **Summary.** A plain inventory, grouped by who it is for. Work facts: employee number as used by payroll, legal first and last name, the name they go by, photo, work email, job title, country, timezone, hire date, departure date. Personal facts: personal email and phone, date of birth, home address, and an emergency contact with their relationship to the employee. Explain the naming rule the product applies, because it shows up everywhere: the product calls you by the name you go by when you have given one, and by your legal name otherwise. Be clear about what is not stored: no salary, no compensation, no performance record, none of it exists in the product today. Be equally clear about which of it you can reach: the profile screen edits four of these fields on your own record, no screen creates an employee except the one the product makes for you when you sign up, and nothing lets you see or edit anybody else's.
- **Prerequisites.** `concepts.employee-vs-user`.
- **Complexity.** Medium.
- **Related pages.** `concepts.employee-vs-user`, `security.privacy`.

### The company owner

- **id.** `concepts.owner`
- **Purpose.** Explain the one privileged position in the product.
- **Audience.** Owners.
- **Summary.** Who the owner is: the person who created the company. What being owner means today, stated as the code actually enforces it: only the owner can delete another account in the company, only the owner can delete the company, and the owner can edit another member's account details where an ordinary member can only edit their own. The two safeguards: the owner cannot delete their own account, because a company always needs one, and nobody can act on an account outside their own company. Then the honest caveat: there is no roles or permissions system beyond this. There is no administrator role, no groups, no per feature permission. Ownership cannot currently be transferred through any screen.
- **Prerequisites.** `concepts.model`.
- **Complexity.** Medium.
- **Related pages.** `concepts.company`, `account.delete`.

### The activity log

- **id.** `concepts.activity-log`
- **Purpose.** Explain that the product keeps a record of what people do, and what it means for the reader.
- **Audience.** Owners, and privacy conscious employees.
- **Summary.** What is recorded, listed exactly and no more: a company created, a company updated, account details updated, a password changed, an account deleted, an employee created, an email address confirmed, a sign in, and a sign in link requested. Each entry keeps who did it, when, and the email address they had at the time, so the record still says who acted after their account is gone. Why that design choice was made. That the log belongs to the company. Then the limit: **there is no screen that displays this log yet**. Write the page as "what the product records about you", not as "how to read the audit trail".
- **Prerequisites.** `concepts.model`.
- **Complexity.** Low.
- **Related pages.** `security.privacy`, `email.record`.

---

## Section 4: Signing in

**Why this section exists.** Signing in is, today, the largest genuinely finished part of the product, and it offers more ways in than most applications. Each deserves its own page, because a reader arrives at exactly one of them with exactly one problem.

**Who it is for.** Everybody with an account.

**On disk.** `4-signing-in/`. Section value `signing-in`.

### Signing in

- **id.** `signin.introduction`
- **Purpose.** Section index, and a decision aid.
- **Audience.** All.
- **Summary.** The three ways in, and when each is right: your password, a link emailed to you, or a new password when you have forgotten the old one. A sentence on the two factor code, for those who have set it up. Link each.
- **Prerequisites.** An account.
- **Complexity.** Low.
- **Related pages.** All pages in this section.

### Sign in with your password

- **id.** `signin.password`
- **Purpose.** Get somebody in, and explain the choices the screen offers.
- **Audience.** All.
- **Summary.** The form, and the two things on it worth explaining. **Remember me**, which keeps you signed in for thirty days by default on that browser (the screen names the number, and a self hosted instance can change it), and what that means on a shared machine. What happens after a correct password when two factor authentication is on: you are not in yet, you get the code screen. Then the two behaviours that generate support requests, explained rather than apologised for. First, every refusal says the same thing, "these credentials do not match our records", whether the password was wrong, the account is suspended, or no account exists, because saying which would let a stranger find out who has an account here. Second, after five failed attempts from the same address and place you are locked out for a few minutes and told how many seconds remain. Add that a failed attempt sends an email to the address's owner, and link to it.
- **Prerequisites.** An account.
- **Complexity.** Medium.
- **Related pages.** `signin.magic-link`, `signin.forgot-password`, `security.two-factor`, `email.login-failed`.

### Sign in with a link instead of a password

- **id.** `signin.magic-link`
- **Purpose.** Explain passwordless sign in, which most readers will not have met before.
- **Audience.** All.
- **Summary.** What it is: you give your address, the product emails you a link, you click it, you are in. Why you would: you cannot remember your password, or you would rather not type one. The three rules that matter, all stated up front because each one catches people out: the link works **once**, it expires after **five minutes** by default, and it works from any browser on any device, so you can request it on your laptop and open it on your phone. What to do when it expires: ask for another. Why the confirmation screen says "if that address has an account", rather than confirming it does. That requesting links is rate limited to six a minute. That, unlike a password sign in, getting in this way sends you an email saying so, and why that is deliberate.
- **Prerequisites.** An account.
- **Complexity.** Medium.
- **Related pages.** `email.magic-link`, `email.new-login`, `signin.password`.

### Forgotten your password

- **id.** `signin.forgot-password`
- **Purpose.** Get somebody who is locked out back in.
- **Audience.** All.
- **Summary.** A `::::steps` walkthrough: request the link, open the email, choose a new password, sign in with it. The rules: the reset link works once and is thrown away as soon as it is used, and the new password must be at least eight characters and typed twice. The message you get either way, "if that address has an account, a link is on its way", and why it is worded like that. What "that link no longer works, ask for another one" means and what to do about it. That requests are rate limited. Offer the alternative for somebody who only needs to get in this once: a sign in link is faster and changes nothing.
- **Prerequisites.** An account.
- **Complexity.** Low.
- **Related pages.** `signin.magic-link`, `security.password`, `troubleshoot.no-email`.

### Signing out

- **id.** `signin.sign-out`
- **Purpose.** Cover the small thing nobody documents and everybody eventually needs.
- **Audience.** All.
- **Summary.** How to sign out, and what it does: your session ends on that browser, and remember me is forgotten with it. That signing out on one device does not sign you out on the others, and that there is currently no screen to end your other sessions. What to do if you think somebody else is signed in as you: change your password, and link there.
- **Prerequisites.** Being signed in.
- **Complexity.** Low.
- **Related pages.** `security.password`, `security.suspicious-activity`.

---

## Section 5: Your profile

**Why this section exists.** The profile screen is the first and, so far, the only place in the signed in application where somebody changes something about themselves. It is what a new user reaches for once they are in, and it is where the abstract distinction between an employee and an account finally becomes something you can click. It also holds the one piece of genuinely private information the product asks for, which deserves its own page rather than a paragraph, and the one place where somebody can read what the product did on their behalf: the emails it sent them.

**Who it is for.** Every employee with an account.

**On disk.** `5-your-profile/`. Section value `your-profile`.

### Your profile

- **id.** `profile.introduction`
- **Purpose.** Section index, and the reader's first tour of a screen inside the application.
- **Audience.** All.
- **Summary.** Where the screen is and how to reach it: **Settings**, then **Profile**, at `/settings/account/profile`. What it is made of, in the order it appears: your avatar, the details your colleagues can see, your emergency contact, which they cannot, and the emails the product has sent you. Make the connection to the concepts section explicit, because this is where it lands: this screen edits your **employee record**, not your account, which is why your sign in address is nowhere on it. Note what the sidebar shows and what it does not: **Preferences** is listed but cannot be opened, because that screen does not exist yet.
- **Prerequisites.** `concepts.employee-vs-user`.
- **Complexity.** Low.
- **Related pages.** Both pages below, `concepts.employee-record`, `security.introduction`.

### Edit your details

- **id.** `profile.details`
- **Purpose.** Walk somebody through the only editing form the product has, and explain the one field whose behavior is not obvious.
- **Audience.** All.
- **Summary.** The four fields, and which are required: **first name** and **last name** are, **display name** and **work email** are not. Explain the display name properly, since it is the field people ask about: it is the name you go by, and when you give one the product calls you by it everywhere, including the sidebar and your initials; leave it empty and your legal name is used instead. Say plainly who sees all of this: everyone in your company. Describe what saving looks like, because it is not what a reader expects from a web form: the page does not reload, a short confirmation slides into the bottom right corner and leaves on its own after a few seconds (it can be dismissed sooner with its close button), and the **Last saved** line under the fields updates in place. Note that saving is recorded in the company's activity log. Cover the avatar box in a sentence or two, honestly: there is no picture to upload today, the product draws the first letters of your name in a circle instead, and the screen says as much.
- **Prerequisites.** `profile.introduction`.
- **Complexity.** Low.
- **Related pages.** `concepts.employee-record`, `concepts.activity-log`, `profile.emergency-contact`.

### Add an emergency contact

- **id.** `profile.emergency-contact`
- **Purpose.** Explain the one form in the product that asks for somebody else's personal details, and be exact about who can read them.
- **Audience.** All.
- **Summary.** What it is for, in the product's own framing: who to call if something happens to you at work. The three fields, all optional: **name**, **phone number**, **relationship**. Be precise about privacy, because this is the page where a reader will be looking for it. What the code actually does: the details are stored on your employee record, they are never written into the activity log, which records only that they changed, and no screen in the product shows them to anybody but you. Note that the screen tells you your company administrators can see this, and that no such screen exists yet, so today the honest answer is that only you can read them in the application. Tell the reader they can empty the fields and save to remove the contact, and that saving here behaves exactly as it does on the details form: no reload, and a confirmation in the bottom right corner that goes away by itself.
- **Prerequisites.** `profile.introduction`.
- **Complexity.** Medium, because the privacy claim has to be worded carefully.
- **Related pages.** `security.privacy`, `concepts.employee-record`, `concepts.activity-log`.

### Read the emails we sent you

- **id.** `profile.emails`
- **Purpose.** Show somebody where to check what the product sent them, and teach them to read the delivery state, which is the reason most people open this box in the first place.
- **Audience.** All, and in practice anybody who was expecting an email that never arrived.
- **Summary.** Where it is: the **Emails sent** box at the bottom of the profile, the six most recent first, with **Browse all emails** leading to the whole list at `/settings/account/profile/emails`, ten at a time behind a **Load more** link. What a row shows: who it went to, the subject, and how long ago it left. What the coloured dot means, and this is the part worth being exact about: amber means the email left but the mail service has not confirmed it arrived, green means it was delivered, red means it bounced. Say plainly that on most instances the dot stays amber for good, because delivery and bounce reporting only arrives from a provider that reports it back, and that amber therefore is not a sign of a problem. What happens when a row is opened: the copy of the email as it was sent, minus its links. Explain that omission honestly rather than as a footnote, since a reader who wanted to click the link will be looking for it: sign in links are stored nowhere, because a stored link is a stored way into the account, and any link in an old email has almost certainly expired anyway. Close with the diagnostic use: no entry at all means the email was never sent, which is a different problem from one that was sent and never arrived, and point at the troubleshooting page for both.
- **Prerequisites.** `profile.introduction`.
- **Complexity.** Medium, because the delivery states have to be explained without alarming anybody.
- **Related pages.** `email.record`, `email.introduction`, `troubleshoot.no-email`, `security.privacy`.

---

## Section 6: Your account and its security

**Why this section exists.** Account and security questions are the ones users ask under pressure, when something looks wrong. They need direct, calm answers in one place. This section also has to be scrupulous about which protections are on by default, which are optional, and which are half built.

**Who it is for.** Everybody, plus owners for the parts about other people's accounts.

**On disk.** `6-account-and-security/`. Section value `account-and-security`.

### Your account and its security

- **id.** `security.introduction`
- **Purpose.** Section index, and a short account of how OfficeLife protects you without being asked.
- **Audience.** All.
- **Summary.** What is on by default for everyone: passwords are stored hashed and never recoverable, sign in attempts are rate limited, failed attempts and sign ins from a new place email you, sign in links are single use and short lived, and only the fingerprint of a sign in link is ever stored so the link in your inbox cannot be rebuilt from the database. Then what you can add: a stronger password, and a code from your phone. Link onwards.
- **Prerequisites.** An account.
- **Complexity.** Medium.
- **Related pages.** All pages in this section.

### Change your password

- **id.** `security.password`
- **Purpose.** Explain what the product requires of a password and what changing one does.
- **Audience.** All.
- **Summary.** The rule, which is short: at least eight characters, confirmed by typing it twice. Advice on choosing one. That the change is recorded in the company's activity log. That an account which signs in through an identity provider has no password to change, and what the message about that means. Then be straight with the reader: **the only way to change your password today is the forgotten password flow**, because the settings screens carry your employee record and nothing about your account yet. Walk them through it in one line and link there. Do not describe a password form that does not exist.
- **Prerequisites.** An account.
- **Complexity.** Medium.
- **Related pages.** `signin.forgot-password`, `concepts.activity-log`.

### Two factor authentication

- **id.** `security.two-factor`
- **Purpose.** Explain the code screen honestly, to the only two audiences that can currently meet it.
- **Audience.** Employees whose account already has it, and operators.
- **Summary.** What two factor authentication is and why it helps. How the challenge works when your account has it: after your password is accepted you are not signed in, you are asked for the six digit code your authenticator app is showing, and only then are you in. That a recovery code works in place of the app's code, that each recovery code works once and is spent the moment it is used, and that this is what you use when you have lost your phone. What "that code is not right" and "that took too long, please sign in again" mean, the second being a session that expired between the two screens. That attempts at the challenge are rate limited.
- **Prerequisites.** `signin.password`.
- **Complexity.** High, because of the caveat below.
- **Related pages.** `signin.password`, `security.suspicious-activity`.

:::warning for the writer
There is no screen, route or command to **turn two factor authentication on**. The challenge, the code verification, the recovery codes and the storage all exist, but nothing enrols a user. Only an account whose two factor enrolment was set directly in the database will ever see the challenge. The page must say so in the first paragraph, in the reader's language: setting this up from inside OfficeLife is not possible yet, and this page explains the challenge for accounts that already have it. Do not write enrolment steps, do not describe a QR code screen, do not tell readers to save their recovery codes at a moment that does not exist.
:::

### When something looks wrong

- **id.** `security.suspicious-activity`
- **Purpose.** Give a worried reader one page that tells them what happened and what to do.
- **Audience.** All.
- **Summary.** Organised by what landed in their inbox. "A failed sign in attempt": somebody typed your address and the wrong password; if it was not you, change your password, because somebody knows your address and is guessing. "A sign in from a new place": you signed in from an address the product has not seen before; travelling, a VPN, a new phone or a browser update all cause this, so it is often nothing, but if you do not recognise it, change your password. "You signed in without a password": somebody used a sign in link for your account; the link only works once and is already spent, but whoever used it can read your email, so change your password. For each, the concrete steps, in order: change your password, sign out, tell whoever runs your company. Explain what the product does and does not check, so nobody over trusts it: it compares your sign in address with the previous one and only notices a change, it does not geolocate, score risk, or block anything.
- **Prerequisites.** `security.introduction`.
- **Complexity.** Medium.
- **Related pages.** `email.login-failed`, `email.new-login`, `email.ip-changed`, `security.password`.

### Suspended and deleted accounts

- **id.** `account.delete`
- **Purpose.** Explain what happens to an account and its data when somebody leaves, which is an HR product's most predictable question.
- **Audience.** Owners, and employees asking about themselves.
- **Summary.** Two different things, kept apart. **Suspending**: an account can be marked inactive, which stops it signing in entirely, including by sign in link, without deleting anything. Note that this is a state the product supports but no screen sets today. **Deleting**: only the owner can delete an account in their company, and the owner cannot delete their own. What deletion leaves behind, which is the part people need to understand: the employee record survives, because the person still worked here, and the activity log keeps the email address of anybody who acted, so the history still says who did what. Deleting the whole company, which only the owner can do, removes its people, its accounts, its logs and its record of emails with it. Note that neither deletion has a screen yet.
- **Prerequisites.** `concepts.owner`, `concepts.employee-vs-user`.
- **Complexity.** High. Get the consequences exactly right and put them in a `:::warning`.
- **Related pages.** `concepts.employee-vs-user`, `concepts.activity-log`, `security.privacy`.

### What OfficeLife knows about you

- **id.** `security.privacy`
- **Purpose.** Answer the privacy question directly, for a product that by its nature holds personal data.
- **Audience.** Employees.
- **Summary.** What is stored about a person, pointing at the employee record page for the full list rather than repeating it. What is stored about an account: your address, when and from where you last signed in, your language. What is stored about your activity, pointing at the log page. What is stored about the emails sent to you: every one, with its subject and body, so your company can see what it sent, with the links stripped out. What is protected: passwords are hashed, two factor secrets and recovery codes are encrypted, and only the fingerprint of a sign in link is stored. Who can see what, as the code actually allows: everything is scoped to your company, and the owner has the extra powers listed on the owner page. Where the privacy policy and terms live, and that a self hosted instance points at its own.
- **Prerequisites.** `concepts.employee-record`.
- **Complexity.** Medium.
- **Related pages.** `concepts.activity-log`, `email.record`, `account.delete`.

---

## Section 7: Language and appearance

**Why this section exists.** Two things every reader can change immediately, on any screen, signed in or not. Small, satisfying, and genuinely finished.

**Who it is for.** Everybody.

**On disk.** `7-language-and-appearance/`. Section value `language-and-appearance`.

### Language and appearance

- **id.** `language.introduction`
- **Purpose.** Section index.
- **Audience.** All.
- **Summary.** Two short pages: pick your language, pick light or dark.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** Both pages below.

### Change the language of the interface

- **id.** `language.change`
- **Purpose.** Show the picker and explain which setting wins.
- **Audience.** All.
- **Summary.** Where the picker is and how to use it, including that it works before you sign in. The four languages currently shipped: English, French, German and Spanish. Then the precedence rule, in the reader's terms and in order: what you picked in this session wins, because it is the most recent thing you asked for; failing that, the language on your account; failing that, your company's language; failing that, the application's own default. The practical consequence: a choice made from the picker lasts as long as your session, so if the interface reverts, that is why. That translations are community maintained and how to help. And, because it follows from the precedence rule, note that setting a permanent language on your account is not possible from any screen yet.
- **Prerequisites.** None.
- **Complexity.** Medium. The precedence chain is the whole page.
- **Related pages.** `language.company-default`, `hosting.locales`.

### Your company's default language

- **id.** `language.company-default`
- **Purpose.** Explain the company wide setting for the person who chooses it.
- **Audience.** Owners.
- **Summary.** What the company language is for: the language anybody sees who has not chosen one. That it is set to English when the company is created, alongside a timezone of UTC. That changing it has no screen yet. Keep this page short and link back to the precedence rule rather than restating it.
- **Prerequisites.** `language.change`, `concepts.company`.
- **Complexity.** Low.
- **Related pages.** `concepts.company`, `hosting.locales`.

### Light and dark

- **id.** `appearance.theme`
- **Purpose.** Cover the theme toggle.
- **Audience.** All.
- **Summary.** Where the toggle is, what it does, that it applies to your browser, and that it works before you sign in.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `language.change`.

---

## Section 8: Emails from OfficeLife

**Why this section exists.** Five emails exist, three of them are security warnings, and a reader who receives one arrives at the portal with that exact subject line in their hand. A page per email, findable by its subject, is the fastest possible answer. This section is also where the "we keep a copy of what we sent you" fact belongs.

**Who it is for.** Employees who received something, and owners who want to know what the product sends.

**On disk.** `8-emails/`. Section value `emails`.

### Emails from OfficeLife

- **id.** `email.introduction`
- **Purpose.** Section index and a complete inventory.
- **Audience.** All.
- **Summary.** The five emails the product sends, each with its subject line and a link to its page: confirm your email address, your sign in link, a failed sign in attempt, you signed in without a password, and a sign in from a new place. State the promise the product makes on its own screens and repeat here: it never sends marketing email. Explain that every one of them is triggered by something happening on your account, so an unexpected one is worth reading.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** Every page in this section.

### Confirm your email address

- **id.** `email.verify`
- **Purpose.** Explain the email a new account receives.
- **Audience.** New users.
- **Summary.** When it is sent, what the button does, that the link expires after an hour, and how to get another. That you can safely ignore it if you did not create an account.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `getting.confirm-email`.

### Your sign in link

- **id.** `email.magic-link`
- **Purpose.** Explain the passwordless sign in email.
- **Audience.** All.
- **Summary.** When it is sent, that the link works once and only for the next few minutes, and that the number of minutes is named in the email itself because an instance can change it. What to do when you did not ask for it: ignore it, because somebody typed your address by mistake or is checking whether you have an account, and nothing has happened to your account.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `signin.magic-link`.

### A failed sign in attempt

- **id.** `email.login-failed`
- **Purpose.** Explain a warning email that alarms people.
- **Audience.** All.
- **Summary.** What it means: somebody tried to sign in to your account and gave the wrong password. That it is sent on every failed attempt against a known address. If it was you, nothing is wrong: try again, or use a sign in link. If it was not, change your password now, because somebody knows your address and is guessing.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `security.suspicious-activity`, `security.password`.

### You signed in without a password

- **id.** `email.new-login`
- **Purpose.** Explain the confirmation sent after a sign in link is used.
- **Audience.** All.
- **Summary.** What it means, and the address it was used from. Why it is sent for link sign ins but not password sign ins: getting in without a password is worth telling you about every time. If it was not you, change your password now, and note the specific reasoning the email gives: the link is already spent and cannot be used again, but whoever used it can read your email, which is the real problem.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `signin.magic-link`, `security.suspicious-activity`.

### A sign in from a new place

- **id.** `email.ip-changed`
- **Purpose.** Explain the most frequently misread of the five.
- **Audience.** All.
- **Summary.** What triggers it: you signed in from an address the product had not seen since your last sign in. Why it is usually nothing: travelling, a VPN, a new phone, a browser update. That your very first sign in never triggers it, because there was nothing to compare against. What to do if you do not recognise it. Be precise about what the check is and is not, so nobody reads more into it than it says: it compares one address with the previous one, nothing more.
- **Prerequisites.** None.
- **Complexity.** Medium.
- **Related pages.** `security.suspicious-activity`.

### The record of what was sent

- **id.** `email.record`
- **Purpose.** Explain that the product keeps a copy of every email it sends.
- **Audience.** Owners and privacy conscious employees.
- **Summary.** What is kept: the type of email, the address it went to, the subject, the body, and when it was sent, delivered, or bounced. Why: so a company can see exactly what was sent on its behalf, and to whom. The one deliberate omission: links are stripped out of the stored copy, because a stored sign in link would be a stored way into somebody's account. That delivery and bounce information is only filled in on instances using a mail provider that reports it back. Where to read it, in one line, with the walkthrough left to the profile section: the **Emails sent** box on your profile.
- **Prerequisites.** `security.privacy`.
- **Complexity.** Medium.
- **Related pages.** `profile.emails`, `security.privacy`, `hosting.email`.

---

## Section 9: Running your own instance

**Why this section exists.** OfficeLife is open source, and self hosting is a first class way to use it. This audience is entirely different from every other section: they read commands, not walkthroughs. Everything here is grounded in the composer scripts, the environment file, and the configuration.

**Who it is for.** Operators and developers only. Say so at the top of every page.

**On disk.** `9-self-hosting/`. Section value `self-hosting`.

### Running your own instance

- **id.** `hosting.introduction`
- **Purpose.** Section index, and an honest statement of what self hosting involves today.
- **Audience.** Operators.
- **Summary.** What you need: PHP 8.4 or later, Composer, Bun, and a database. That SQLite, MySQL and PostgreSQL are all exercised by the project's own test suite, so all three work. That there is no Docker image or compose file in the repository today, so installation is a manual PHP deployment. What the section covers, in order.
- **Prerequisites.** Comfort with a command line.
- **Complexity.** Medium.
- **Related pages.** Every page in this section.

### Install OfficeLife

- **id.** `hosting.install`
- **Purpose.** Take an operator from a clone to a running instance.
- **Audience.** Operators.
- **Summary.** A `::::steps` walkthrough built on the `composer setup` script the repository already defines, which installs dependencies, creates the environment file, generates the application key, runs the migrations, and builds the front end assets. Then what setup does not do and you must: choose and configure a database, configure mail, serve the application, and run a queue worker. Cover the local development path separately and briefly, since `composer dev` runs the server, the queue worker, the log tail and the asset watcher together. Point out that Laravel Herd serves the site automatically for anyone developing on macOS.
- **Prerequisites.** `hosting.introduction`.
- **Complexity.** Medium.
- **Related pages.** `hosting.configuration`, `hosting.queue`, `hosting.email`.

### Configure your instance

- **id.** `hosting.configuration`
- **Purpose.** A reference for every setting an operator can change, in one scannable place.
- **Audience.** Operators.
- **Summary.** A reference page, tables rather than prose. Application basics: name, environment, key, URL, debug, default and fallback locale. Database. Session driver and lifetime. Queue connection. Cache. Then the settings specific to OfficeLife, each with its default and, crucially, what the user sees change: how many days "remember me" lasts (thirty, and the sign in screen names this number, so changing it changes what you promise your users), how many minutes a sign in link stays valid (five, deliberately short, and the email names it), and the URLs of the terms of use and the privacy policy, which point at officelife.io by default and which any instance running its own documents must repoint. Mail settings and the Cloudflare Turnstile keys get their own pages below.
- **Prerequisites.** `hosting.install`.
- **Complexity.** High. Completeness and accuracy matter more than prose here.
- **Related pages.** `hosting.email`, `hosting.turnstile`, `hosting.locales`.

### Send email from your instance

- **id.** `hosting.email`
- **Purpose.** Get outbound mail working, without which nobody can confirm an address or reset a password.
- **Audience.** Operators.
- **Summary.** Why this matters first: five of the product's flows depend on email. The two paths. Ordinary Laravel mail, configured with the standard mail settings and working with any SMTP service, which is the default. Or Resend, switched on with a single flag and an API key, which additionally captures the provider's own identifier for each message so delivery and bounce information can be recorded. State plainly that the delivery and bounce columns stay empty on the ordinary path. The from address and name, which appear on every email your people receive. How to check it works in development, where mail is written to the log by default.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Medium.
- **Related pages.** `email.record`, `hosting.queue`.

### Run the queue

- **id.** `hosting.queue`
- **Purpose.** Explain the piece of the deployment that silently breaks the product when it is missing.
- **Audience.** Operators.
- **Summary.** What runs in the background and therefore does not happen without a worker: every email the product sends, every entry in the activity log, and the check that notices a sign in from a new address. The symptom an operator will actually see if they skip this, which is that nobody ever receives a confirmation email and the product looks broken. That the default connection is the database, so no extra service is needed to start. That work is dispatched to a `high` queue for anything a person is waiting on, and a `low` queue for record keeping, so a worker should be told to drain `high` first. How to run one, and what to use in production to keep it running.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Medium.
- **Related pages.** `hosting.email`, `concepts.activity-log`.

### Protect your sign up and sign in screens

- **id.** `hosting.turnstile`
- **Purpose.** Explain the optional human check.
- **Audience.** Operators.
- **Summary.** What Cloudflare Turnstile is and what it protects: the registration and sign in forms. That it is off by default and needs a site key and a secret key from Cloudflare. What your users see when it is on, and what they see when it fails. The deliberate strictness worth knowing before you enable it: if the check cannot be verified for any reason, including Cloudflare being unreachable from your server, the form is refused rather than let through. What else already protects those forms without it: rate limiting on sign in, on link requests and on password resets, and the rejection of disposable email addresses at sign up.
- **Prerequisites.** `hosting.configuration`.
- **Complexity.** Medium.
- **Related pages.** `getting.create-account`, `signin.password`.

### Languages on your instance

- **id.** `hosting.locales`
- **Purpose.** Explain how languages are configured and how to add one.
- **Audience.** Operators and translators.
- **Summary.** Where the shipped languages are declared, and that each is a locale key, a label, a region and a flag drawn in CSS rather than shipped as an image. Where the translated strings live: one JSON file per locale, keyed by the English sentence. How the application default relates to the per company and per user settings, linking to the precedence page rather than repeating it. How to add a language: register the locale, add its file, translate it, contribute it back. That translations are community maintained.
- **Prerequisites.** `hosting.configuration`.
- **Complexity.** Medium.
- **Related pages.** `language.change`, `hosting.contributing`.

### Upgrade your instance

- **id.** `hosting.upgrade`
- **Purpose.** Tell an operator how to move to a new version safely.
- **Audience.** Operators.
- **Summary.** The order that matters: back up the database first, pull, install dependencies, run migrations, rebuild assets, restart the queue worker so it picks up the new code. Why the last step is the one people forget. Keep it short and mechanical, and state clearly that the project publishes no formal release process yet, so this is the general procedure rather than a versioned upgrade guide.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Medium.
- **Related pages.** `hosting.backups`, `hosting.queue`.

### Back up your data

- **id.** `hosting.backups`
- **Purpose.** Cover the thing an HR system's operator cannot get wrong.
- **Audience.** Operators.
- **Summary.** What matters and why: the database holds everything the product knows, including personal details, emergency contacts, and the record of every email sent. That the encrypted columns, two factor secrets and recovery codes, are readable only with the application key, so a database backup without that key is not a complete backup and will not restore. How to back up each supported database. What else to keep: the environment file and the application key. Testing that a restore actually works. No backup tooling ships with the product, so this is guidance rather than a feature.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Medium.
- **Related pages.** `hosting.upgrade`, `security.privacy`.

### Contribute to OfficeLife

- **id.** `hosting.contributing`
- **Purpose.** Turn interested readers into contributors, which for an open source product is a documentation job.
- **Audience.** Developers and translators.
- **Summary.** Where the code is, how to get a development environment running, and how to run the test suite. The conventions a contributor meets immediately, described rather than reproduced: business logic lives in one class per user action, controllers stay thin, code style is enforced by a formatter, and static analysis runs in continuous integration. That translations are the easiest first contribution. Link to the repository's own contributing guidance rather than duplicating it, and keep this page thin so it does not drift.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Low.
- **Related pages.** `hosting.locales`.

---

## Section 10: Troubleshooting

**Why this section exists.** A reader with a problem does not know which section their problem lives in. This one is organised by symptom, in their words, and does nothing but route them or answer them.

**Who it is for.** Everybody.

**On disk.** `10-troubleshooting/`. Section value `troubleshooting`.

### Troubleshooting

- **id.** `troubleshoot.introduction`
- **Purpose.** Section index, organised by symptom.
- **Audience.** All.
- **Summary.** A short list of "if this is happening to you" links covering the pages below plus the frequent ones that live elsewhere: I got a warning email, I forgot my password, my language keeps resetting.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** All pages in this section.

### I cannot sign in

- **id.** `troubleshoot.cannot-sign-in`
- **Purpose.** Work through every reason a sign in fails, in the order they are likely.
- **Audience.** All.
- **Summary.** Symptom by symptom. "These credentials do not match our records": your password is wrong, or your account has been suspended, or that address has no account here; the message is the same for all three on purpose, and here is how to tell them apart in practice, by asking for a sign in link and seeing whether one arrives, or by asking whoever runs your company. "Too many sign in attempts": wait the number of seconds you were told. "That link no longer works": the link was used already or has expired, ask for another. "That code is not right" and "that took too long": the two factor cases. Somebody who has genuinely been suspended cannot get in by any route, including a link, and only the person running their company can fix it.
- **Prerequisites.** `signin.introduction`.
- **Complexity.** Medium.
- **Related pages.** `signin.password`, `signin.magic-link`, `security.two-factor`.

### I am not receiving emails from OfficeLife

- **id.** `troubleshoot.no-email`
- **Purpose.** Solve the single most common support request for any product that emails links.
- **Audience.** Employees, with a clearly separated part for operators.
- **Summary.** For the reader: it can take a minute, check spam, check you typed the address you actually signed up with, and remember that the confirmation screen says the same thing whether or not that address has an account. Then, plainly, if none of that helps, the problem is on the instance rather than with you, and here is what to tell whoever runs it. For the operator, a short checklist under its own heading: is a queue worker running, is mail configured, are messages going to the log instead of out, and is the from address one your provider accepts.
- **Prerequisites.** None.
- **Complexity.** Medium.
- **Related pages.** `hosting.queue`, `hosting.email`, `getting.confirm-email`.

### The interface is in the wrong language

- **id.** `troubleshoot.wrong-language`
- **Purpose.** Answer a small, common and genuinely confusing problem.
- **Audience.** All.
- **Summary.** Why the interface may not be in the language you expect, and why a choice you made earlier may have reverted: the picker's choice lasts for your session, and there is no per account language setting reachable from any screen yet. What to do about it. Link to the precedence rule rather than restating it.
- **Prerequisites.** `language.change`.
- **Complexity.** Low.
- **Related pages.** `language.change`, `language.company-default`.

---

## Section 11: Reference

**Why this section exists.** A few facts get asked repeatedly and belong somewhere scannable, not buried in prose. Reference pages are factual and short.

**Who it is for.** All, depending on the page.

**On disk.** `11-reference/`. Section value `reference`.

### Reference

- **id.** `reference.introduction`
- **Purpose.** Section index.
- **Audience.** All.
- **Summary.** What is here and what is not.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** All pages in this section.

### Words OfficeLife uses

- **id.** `reference.glossary`
- **Purpose.** A glossary, so no page has to stop and define its terms twice.
- **Audience.** All.
- **Summary.** Company, employee, user, account, owner, display name, legal name, employee number, work mode, size range, sign in link, recovery code, activity log. One or two sentences each, each linking to the page that explains it properly. Include the terms that mean something specific here and something looser elsewhere, above all employee and user.
- **Prerequisites.** None.
- **Complexity.** Medium.
- **Related pages.** `concepts.introduction`.

### Limits and defaults

- **id.** `reference.limits`
- **Purpose.** Put every number the product enforces in one table.
- **Audience.** All, and operators in particular.
- **Summary.** A single table: minimum password length, how long "remember me" lasts, how long a sign in link lasts, how long an email confirmation link lasts, how many sign in attempts before a lockout, the rate limits on link requests, password resets, resent confirmations and two factor attempts, and the session lifetime. Mark which are configurable per instance and which are fixed in code, and link to the configuration page for the former. Give defaults, not the values from any one instance.
- **Prerequisites.** None.
- **Complexity.** Medium. Verify every number against the code before publishing, and re-verify whenever it changes.
- **Related pages.** `hosting.configuration`, `signin.introduction`.

---

## Not yet documentable

Everything below is anticipated by the database, an enum, or a model method, but has **no screen, route, command or working implementation**. None of it may be written about as a feature. It is listed here so the gaps are known, so nobody invents a page for them, and so the page can be written the day the feature ships.

Each entry names the evidence, what is missing, and the page it should become.

- **Plans, trials and billing.** Every company carries a plan (free, starter, business, enterprise), a billing email address, and a trial end date set to thirty days at signup, and the model can answer whether it is still on trial. Nothing reads any of it. There is no billing code, no payment provider, no subscription library, and no screen. **Do not write pricing, plans, trials, invoices, or upgrade documentation.** When billing exists it becomes a section of its own, and `getting.cloud-or-self-hosted` will need rewriting.

- **Single sign on.** Accounts carry an identity provider field, the model can answer whether an account uses one, sign in refuses such accounts a password path, and password changes are refused for them with a specific message. No provider integration, configuration or route exists, so no account can be created this way. `security.password` may mention the message a user could see; nothing may describe setting SSO up.

- **Turning on two factor authentication.** Covered in the warning under `security.two-factor`. The challenge is finished; enrolment does not exist.

- **Suspending an account.** Accounts have an active flag that is honoured everywhere, including by sign in links. Nothing sets it to false. `account.delete` explains the state; no page may describe how to suspend somebody.

- **Inviting people, and adding employees.** The registration screen tells people joining an existing company to ask an administrator to invite them, but no invitation exists: no route, no mail, no token, no screen. Creating an employee works as business logic and is exercised by tests, but the only employee ever created is the founder's own, during signup. **No page may describe inviting a colleague or adding an employee.** When it ships it is the most important tutorial in the portal.

- **Editing your company, your account, or somebody else's employee record.** The business logic for all three exists and is tested. Only your own employee record has a screen, covered by Section 5; nothing reaches the other three. Several pages above are shaped around this absence and say so; when the screens land, those caveats come out and the pages become how-tos.

- **Viewing the activity log.** It is written and stored, and nothing displays it. `concepts.activity-log` describes what is recorded, not how to read it. The record of emails sent used to sit here too; it now has a screen, and `profile.emails` is its page.

- **Ending your other sessions.** Sessions are stored per device, but nothing lists or revokes them. Mentioned as a limitation in `signin.sign-out` only.

- **Delivery and bounce reporting.** The record of sent emails has columns for delivery and bounce times, and the identifier a provider returns is captured when Resend is enabled, but no webhook endpoint exists to fill them in. `hosting.email` must say the columns stay empty, and `profile.emails` must say what that looks like on the screen: every email keeps the amber "on its way" dot, and neither the green nor the red one appears on an instance nothing reports back to.

- **Any API.** There is no API route file, no API controller, no resource, no token authentication. The application's own conventions describe an `Api` controller namespace and versioning, but nothing is built. **The portal must not contain an API section.**

- **The application itself, past the profile screen.** There is no dashboard, no employee directory, no home screen, and the only navigation is the settings sidebar, whose **Preferences** entry opens nothing. `/` renders a landing page rather than anything signed in. `getting.what-works-today` is the page that tells readers this, and it is the page to revisit first as the product grows.

---

## Writing order

Build the portal in this order. Each block is independently publishable, so the portal is useful before it is complete.

1. **First pass, the essentials.** `portal.introduction`, `getting.introduction`, `getting.what-is-officelife`, `getting.create-account`, `getting.confirm-email`, `signin.introduction`, `signin.password`, `signin.magic-link`, `signin.forgot-password`. This alone answers most of what a real user will ask today.
2. **Second pass, the model.** All of Section 3, plus `getting.what-works-today`. This is where the product becomes comprehensible rather than merely usable.
3. **Third pass, the one screen there is.** All of Section 5. It is short, it is the only part of the portal a reader can follow with the product open in front of them, and it makes Section 3 concrete.
4. **Fourth pass, security and email.** Sections 6 and 8. These are the pages people arrive at from an inbox, under stress, so they benefit from being written together and in one voice.
5. **Fifth pass, operators.** Section 9. A different audience and a different register; writing it in one block keeps it consistent.
6. **Sixth pass, the connective tissue.** Sections 7, 10 and 11. Language, troubleshooting and reference are best written last, because they mostly link to pages that must already exist.
7. **Then translate.** `fr_FR`, `de_DE`, `es_ES`. Only the `id` stays identical across locales; titles, slugs and sections are translated with the rest.
