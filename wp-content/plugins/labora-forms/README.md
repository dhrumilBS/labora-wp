# Labora Forms

Contact Form 7 forms for the Labora site, with stricter validation than CF7's own.
Requires Contact Form 7 (6.x) and PHP 8.3. Leads are stored by **Database for Contact Form 7** (Contact > Database).

## Forms

Defined in code (`includes/class-forms.php`), so they are versioned and identical on every environment.

```bash
wp labora-forms setup                       # create or update the forms in CF7 (keeps Additional Settings edits)
wp labora-forms setup --overwrite-settings  # also reset Additional Settings to the defaults
```

| Key | CF7 title | Used on | Fields |
|---|---|---|---|
| `demo` | Book a demo | Homepage (`labora-child/template-parts/demo-form.php`) | full_name*, email*, company*, lab_type*, centers*, message |
| `contact` | Contact | Contact page (when it is built) | full_name*, email*, company*, phone, topic*, centers*, message, plan (hidden), modules (hidden) |

Markup matches the HTML site (`.form-grid`, `.field`, labels, ids), so the theme styles it; CF7-specific pieces
(error tips, spinner, response box) are styled in `labora-child/assets/css/child.css`.

In a template: `<?php echo labora_forms_render( 'demo' ); ?>` (CF7's CSS and JS load only on pages that render a form).

## No email is sent

Each form's **Additional Settings** contain `skip_mail: on`: the submission is validated and saved as a lead, and CF7
reports success, but no email goes out. To start sending, remove that line in Contact > Contact Forms > (form) >
Additional Settings. The Mail tab is already filled in (to the site admin email, Reply-To the visitor).

Other Additional Settings lines read by this plugin:

| Line | Effect |
|---|---|
| `labora_event: demo_request_submitted` | Event pushed to `dataLayer` after a successful send |
| `labora_thanks: /thank-you/` | After a successful send, wait 1.5 s, then open this page (empty: stay on the form) |
| `labora_required_<field>: Message` | Message when that required field is empty, e.g. `labora_required_full_name: Enter your name.` |

## Validation (all CF7 forms on the site)

Nothing is saved and no email is sent unless every check passes.

**Phone (`[tel]`)**, normalized to E.164 and saved that way:

| Typed | Saved |
|---|---|
| `(512) 555-0123`, `512.555.0123`, `1 512 555 0123` | `+15125550123` |
| `(512) 555-0123 x45` | `+15125550123 ext. 45` |
| `+44 20 7946 0958` | `+442079460958` |

Rejected, with a specific message: letters (`1-800-FLOWERS`), too few or too many digits, more than 10 digits without
a `+` country code, and invalid US area codes or exchanges (starting with 0 or 1, or N11 codes such as 911).
CF7's own looser phone rule is removed from these forms' schema (server and browser) so these messages show.

**Email (`[email]`)**: format (WordPress `is_email`), temporary inboxes rejected (mailinator.com, guerrillamail.com ...),
and the domain must be able to receive mail (MX, or A/AAAA). The domain is lowercased. DNS results are cached for a
day; if DNS itself is unreachable, the address is accepted rather than blocking real visitors.

**One submission per 24 hours**: a submission is stopped when its email *or* phone matches a successful submission
of the same form in the last 24 hours, with the message "We already received a request from you in the last 24 hours.
Our team will reply by email, so there is no need to send it again." Only keyed hashes (HMAC-SHA256 with the site's
salt) of the email and phone are kept, in `{prefix}labora_form_log`, and rows older than 48 hours are deleted daily.

**Spam**: a hidden `website` field (honeypot). If it is filled, CF7 marks the submission as spam.

## Filters

| Filter | Default | Use |
|---|---|---|
| `labora_forms_phone_valid` | `true` | Final say on a normalized number, e.g. allow only `+1` |
| `labora_forms_email_dns_check` | `true` | Turn the mail-domain check off |
| `labora_forms_disposable_domains` | built-in list | Add or remove temporary-inbox domains |
| `labora_forms_duplicate_window` | `DAY_IN_SECONDS` | Length of the repeat window |
| `labora_forms_duplicate_match` | `'any'` | `'both'`: only block when email and phone both match |
| `labora_forms_duplicate_scope` | `'form'` | `'all'`: one submission across all forms |
