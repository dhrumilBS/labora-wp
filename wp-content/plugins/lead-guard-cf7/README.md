# Lead Guard for Contact Form 7

Reusable on any WordPress site that uses Contact Form 7 (6.x). It checks every CF7 form by field **type**, so it
works with any field names and any theme. When a check fails, the submission stops before anything is sent or
stored (also with "Database for Contact Form 7", Flamingo, or any plugin that saves on `wpcf7_mail_sent`).

Requires: WordPress 6.4+, PHP 8.1+, Contact Form 7. Settings: **Contact > Lead Guard**.

## What it does

| Check | Where the message appears |
|---|---|
| **Phone** (`[tel]`): valid number, normalized to E.164 (`(512) 555-0123 x45` is saved as `+15125550123 ext. 45`). Without `+`, numbers are read as US/Canada (or rejected, if set). Letters, wrong length, and invalid US area codes or exchanges are rejected. | Under the field |
| **Email** (`[email]`): domain lowercased; temporary inboxes rejected; free providers optionally rejected (work email only); the domain must be able to receive mail (MX or A/AAAA, cached a day; accepted if DNS itself is down). | Under the field |
| **Repeat submissions**: the same email or phone (or both, if set) within the window (24 h by default), per form or across forms. Only keyed hashes are stored, and old rows are deleted daily. | The form's message box, at the bottom |
| **Honeypot**: a hidden field added to every form automatically; a submission that fills it is marked as spam. | The form's message box |

CF7's own phone rule (looser, one generic message) is removed from the form schema when phone checks are on, on the
server and in the browser, so the checks above apply.

Also on every form:

- **Clean address**: CF7 points each form at `#wpcf7-f123-o1`, so that part ends up in the address bar (and the page
  jumps to it) after a submission without JavaScript. Lead Guard removes it.
- **Confirmation hook for themes**: put `data-lead-guard-wrap` on an element around the form and a hidden element
  with `data-lead-guard-status` inside it. After a successful send the wrapper gets the class `is-sent` and the
  status element is shown and focused (without scrolling). Style `.is-sent` to hide the form if you want.

Messages: the ones above are on the Lead Guard settings page. CF7's own messages (sent, spam, validation error,
invalid email or phone, required field ...) are on each form's **Messages** tab; Lead Guard does not duplicate them.

## Per-form settings (Additional Settings tab of a form)

Each form's Additional Settings tab shows this list under the box, with that form's required field names.

```
lead_guard: off                                    # turn every check off for this form
lead_guard_duplicates: off                         # allow repeat submissions on this form
lead_guard_required_your-name: Enter your name.    # message when that required field is empty
lead_guard_redirect: /thank-you/                   # open this page after a successful send (path or full URL)
lead_guard_delay: 1500                             # ms before the redirect, so the confirmation is seen (0 = at once)
lead_guard_event: demo_request_submitted           # push {event, form, topic} to window.dataLayer after a send
skip_mail: on                                      # CF7's own: store the submission but send no email
```

Before a redirect, the plugin writes `sessionStorage.lead_guard_sent` = `{form, id, name, topic, ts}` (first name
only, from the first `*name*` field), so a thank-you page can greet the visitor.

## For developers

| Hook | Use |
|---|---|
| `lead_guard_cf7_phone_valid` (filter: bool, $e164, $input) | Final say on a number, e.g. allow only `+1` |
| `lead_guard_cf7_message` (filter: $text, $key) | Change any message in code (keys: phone, phone_country, email, email_disposable, email_domain, email_free, duplicate) |

Data: option `lead_guard_cf7`, table `{prefix}lead_guard_log`, cron `lead_guard_cf7_prune`. Uninstalling the plugin
removes all three.
