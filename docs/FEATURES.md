# Features

A chronological list of every feature and improvement shipped during development. Newest entries go at the end.

* Foundation: Added the company and user models.
* Foundation: Added the employee model, separate from the user account that signs in.
* Emails: Sent transactional emails and recorded what was sent to whom.
* Logs: Recorded the actions users perform.
* Auth: Added the registration screen, which creates the company and its first owner.
* Auth: Added the login screen.
* Auth: Made the guest screens accessible to screen readers and keyboard users.
* App-wide: Added Turbo, Alpine Ajax and instant.page so screens update without a full reload.
* Settings/account/profile: Added the profile screen, where somebody edits their own details.
* Auth: Added a local sign in shortcut on the login screen for development.
* Settings/account/profile: Saved the profile without reloading the page.
* App-wide: Confirmed a save with a toast instead of a banner.
* Settings/account/profile: Paired the first and last name fields on one row.
* Settings/account/profile: Let somebody upload a photo of themselves.
* Settings/account/logs: Let somebody read the log of their own actions.
* Settings/account/logs: Let somebody read the emails we sent them.
* Settings: Gave the logs and the emails sent their own screen in the settings sidebar.
* Settings/account/security: Let somebody change their own password from a security screen.
* Settings/account/security: Recorded and showed when the password was last changed.
* App-wide: Laid the application out for a phone.
* App-wide: Gave the empty lists a proper blank state.
* App-wide: Added a row component for the lists inside a box.
* Settings/account/preferences: Added a preferences screen for language and time format.
* Settings/account/security: Let somebody turn two factor authentication on from their settings.
* App-wide: Laid the rows inside a box out properly on a narrow screen.
* App-wide: Gave cancel and back actions a secondary button.
* Settings/account/security: Let somebody create and revoke their own API keys.
* Settings/account/security: Created and revoked an API key without reloading the page.
* Permissions: Added roles, which grant permissions over either the whole company or somebody's own record.
* Permissions: Gave every new company an Administrator, a People administrator and a Member role.
* Permissions: Made the first user of a company an administrator.
* Permissions: Added actions to create, change, delete, hand out and take back a role.
* Permissions: Checked every action that touches an employee or the company against the roles of whoever asked.
* Permissions: Kept the private details of a colleague off the screen for anybody not allowed to see them.
* App-wide: Switched the interface to the fonts already on the reader's machine, so nothing is downloaded before the page reads.
* App-wide: Added the stacked layer, a layout for a screen that needs the whole window and names the screen it was opened from.
* Settings: Added an Administration section to the sidebar, shown only to somebody who may administer the company.
* Settings/administration/roles: Added the screen listing every role of the company.
* Settings/administration/roles: Let somebody rename a role and say afresh what it is allowed to do.
* Settings/administration/roles: Grouped the permissions into people, sensitive data and administration.
* Settings/administration/roles: Let somebody pick the scope of each permission granted.
* Settings/administration/roles: Let somebody filter the permissions and fold the groups away.
* Settings/administration/roles: Warned about a role that administers the company.
* Settings/administration/roles: Let somebody create a role, empty or copied from an existing one.
* Settings/administration/roles: Let somebody duplicate a role.
* Settings/administration/roles: Let somebody delete a role, after asking, and refused when somebody still holds it.
* Settings/administration/roles: Showed who holds a role and let somebody hand it out and take it back.
* Locations: Added offices, which a company owns as a list rather than typing an address on every employee who works there.
* Locations: Added actions to create, change and delete an office of the company.
* Settings/administration/locations: Added the screen listing every office of the company.
* Settings/administration/locations: Showed how many offices, countries and time zones the company keeps, and which office is the head office.
* Settings/administration/locations: Let somebody search the offices by name, city or country.
* Settings/administration/locations: Split the open, the archived and all the offices into three pages of their own.
* Settings/administration/locations: Let somebody order the list by office or by city.
* Settings/administration/locations: Added a panel that slides in from the right to edit an office without leaving the list.
* Settings/administration/locations: Let somebody promote an office to head office, which takes the badge off whichever office had it.
* Settings/administration/locations: Let somebody archive an office, after asking, keeping everything written about it.
* Settings/administration/locations: Let somebody reopen an archived office.
* Settings/administration/locations: Let somebody add an office from a dialog asking only for its name, city and country.
* Settings: Added the offices of the company to the sidebar, for whoever may change its settings.
* Docs: Split the product specification into modular Spec-Kit files under docs/specs, covering the core, the assets module and the backlog.
* Modules: Let a company turn a module on and off, and denied the permissions of a module it has not turned on.
* Occurrences: Wrote down everything that happens in a log playbooks will one day react to.
* Permissions: Gave every company an IT administrator role, and let a member see the equipment the company owns.
* Assets: Added the catalogue a company defines once: manufacturers, categories and models.
* Assets: Gave a company seven categories to start from when it turns the module on, so recording the first laptop is not three levels of setup.
* Assets: Gave every company the five states a piece of equipment can be in, and let it add its own.
* Assets: Recorded the equipment the company owns, each item with its own tag and the serial number the manufacturer stamped on it.
* Assets: Let somebody archive a piece of equipment, keeping everybody who has held it.
* Assets: Let somebody hand equipment to a colleague, to an office, or to another piece of equipment.
* Assets: Refused to hand out equipment somebody already has, or that is not ready to be handed out.
* Assets: Refused to leave a chain of equipment holding itself.
* Assets: Let somebody take equipment back, recording the state it came back in and where it went.
* Assets: Showed equipment somebody is holding as deployed, worked out from who has it rather than stored.
* Assets: Read the history of equipment from both ends, from the item and from whoever held it.
* Assets: Swept the fleet once a day and flagged equipment that is late coming back, once rather than every day.
